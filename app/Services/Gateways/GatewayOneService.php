<?php

namespace App\Services\Gateways;

use App\Interfaces\PaymentGatewayInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Exception;

class GatewayOneService implements PaymentGatewayInterface
{
    private(set) string $baseUrl;
    private(set) string $gatewayToken;

    public function __construct()
    {
        $this->baseUrl = config('services.gateway1.url');
        $this->gatewayToken = config('services.gateway1.token');
    }

    public function charge(array $transactionData): array
    {
        $token = $this->authenticate();

        $payload = [
            'amount' => $transactionData['amount'],
            'name' => $transactionData['client_name'],
            'email' => $transactionData['client_email'],
            'cardNumber' => $transactionData['card_number'],
            'cvv' => $transactionData['cvv']
        ];

        $response = Http::withToken($token)
            ->post("$this->baseUrl/transactions", $payload);

        if ($response->failed()) {
            throw new Exception("Gateway 1 recusou a transação: " . $response->body());
        }

        $responseData = $response->json();
        return [
            'external_id' => $responseData['id'] ?? null,
            'raw_response' => $responseData
        ];
    }

    public function refund(string $transactionId): array
    {
        $token = $this->authenticate();

        $response = Http::withToken($token)
            ->post("$this->baseUrl/transactions/$transactionId/charge_back");

        if ($response->failed()) {
            throw new Exception("Falha ao processar reembolso no Gateway 1");
        }

        return $response->json();
    }

    private function authenticate(): string
    {
        return Cache::remember('gateway_one_token', 3600, function () {
            $response = Http::post("$this->baseUrl/login", [
                'email' => 'dev@betalent.tech',
                'token' => $this->gatewayToken
            ]);

            if ($response->failed()) {
                throw new Exception("Falha de comunicação com a autenticação do Gateway 1.");
            }

            return $response->json('token');
        });
    }
}

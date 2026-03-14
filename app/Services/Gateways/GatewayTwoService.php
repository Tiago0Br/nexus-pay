<?php

namespace App\Services\Gateways;

use App\Interfaces\PaymentGatewayInterface;
use Exception;
use Illuminate\Support\Facades\Http;

class GatewayTwoService implements PaymentGatewayInterface
{
    public private(set) string $baseUrl;

    public private(set) string $gatewayToken;

    public private(set) string $gatewaySecret;

    public function __construct()
    {
        $this->baseUrl = config('services.gateway2.url');
        $this->gatewayToken = config('services.gateway2.token');
        $this->gatewaySecret = config('services.gateway2.secret');
    }

    public function charge(array $transactionData): array
    {
        $payload = [
            'valor' => $transactionData['amount'],
            'nome' => $transactionData['client_name'],
            'email' => $transactionData['client_email'],
            'numeroCartao' => $transactionData['card_number'],
            'cvv' => $transactionData['cvv'],
        ];

        $response = Http::withHeaders([
            'Gateway-Auth-Token' => $this->gatewayToken,
            'Gateway-Auth-Secret' => $this->gatewaySecret,
        ])->post("$this->baseUrl/transacoes", $payload);

        if ($response->failed()) {
            throw new Exception('Gateway 2 recusou a transação: '.$response->body());
        }

        $responseData = $response->json();

        return [
            'external_id' => $responseData['id'] ?? null,
            'raw_response' => $responseData,
        ];
    }

    public function refund(string $transactionId): array
    {
        $response = Http::withHeaders([
            'Gateway-Auth-Token' => $this->gatewayToken,
            'Gateway-Auth-Secret' => $this->gatewaySecret,
        ])->post("$this->baseUrl/transacoes/reembolso", [
            'id' => $transactionId,
        ]);

        if ($response->failed()) {
            throw new Exception('Falha ao processar reembolso no Gateway 2');
        }

        return $response->json();
    }
}

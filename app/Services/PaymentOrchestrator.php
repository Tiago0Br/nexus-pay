<?php

namespace App\Services;

use App\Interfaces\PaymentGatewayInterface;
use App\Models\Gateway;
use App\Services\Gateways\GatewayOneService;
use App\Services\Gateways\GatewayTwoService;
use Exception;
use Illuminate\Support\Facades\Log;

class PaymentOrchestrator
{
    public function processPayment(array $data): array
    {
        $gateways = Gateway::query()->where('is_active', true)
            ->orderBy('priority')
            ->get();

        if ($gateways->isEmpty()) {
            throw new Exception('Nenhum gateway de pagamento ativo no momento.');
        }

        foreach ($gateways as $gatewayModel) {
            try {
                $gatewayService = $this->resolveGatewayService($gatewayModel->name);
                $response = $gatewayService->charge($data);

                return [
                    'status' => 'SUCCESS',
                    'gateway_id' => $gatewayModel->id,
                    'external_id' => $response['external_id'] ?? null,
                    'response' => $response
                ];
            } catch (Exception $e) {
                Log::warning("Falha no $gatewayModel->name: " . $e->getMessage());
                continue;
            }
        }

        throw new Exception('O pagamento foi recusado em todos os gateways disponíveis.');
    }

    private function resolveGatewayService(string $gatewayName): PaymentGatewayInterface
    {
        return match ($gatewayName) {
            'Gateway 1' => app(GatewayOneService::class),
            'Gateway 2' => app(GatewayTwoService::class),
            default => throw new Exception("Serviço não implementado para o gateway: $gatewayName"),
        };
    }
}

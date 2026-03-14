<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateGatewayPriorityRequest;
use App\Models\Gateway;
use Illuminate\Http\JsonResponse;

class GatewayController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(Gateway::query()->orderBy('priority')->get());
    }

    public function toggleActive(Gateway $gateway): JsonResponse
    {
        $gateway->is_active = ! $gateway->is_active;
        $gateway->save();

        $status = $gateway->is_active ? 'ativado' : 'desativado';

        return response()->json([
            'message' => "Gateway $status com sucesso.",
            'gateway' => $gateway,
        ]);
    }

    public function updatePriority(UpdateGatewayPriorityRequest $request, Gateway $gateway): JsonResponse
    {
        $gateway->priority = $request->priority;
        $gateway->save();

        return response()->json([
            'message' => 'Prioridade atualizada com sucesso.',
            'gateway' => $gateway,
        ]);
    }
}

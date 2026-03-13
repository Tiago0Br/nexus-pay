<?php

namespace App\Http\Controllers;

use App\Models\Gateway;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GatewayController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(Gateway::query()->orderBy('priority')->get());
    }

    public function toggleActive(Gateway $gateway): JsonResponse
    {
        $gateway->is_active = !$gateway->is_active;
        $gateway->save();

        $status = $gateway->is_active ? 'ativado' : 'desativado';

        return response()->json([
            'message' => "Gateway $status com sucesso.",
            'gateway' => $gateway
        ]);
    }

    public function updatePriority(Request $request, Gateway $gateway): JsonResponse
    {
        $request->validate([
            'priority' => ['required', 'integer', 'min:1']
        ]);

        $gateway->priority = $request->priority;
        $gateway->save();

        return response()->json([
            'message' => 'Prioridade atualizada com sucesso.',
            'gateway' => $gateway
        ]);
    }
}

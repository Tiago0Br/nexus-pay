<?php

namespace App\Http\Controllers;

use App\Models\Client;
use Illuminate\Http\JsonResponse;

class ClientController extends Controller
{
    public function index(): JsonResponse
    {
        return response()
            ->json(Client::all());
    }

    public function show(int $id): JsonResponse
    {
        return response()
            ->json(Client::with('transactions')->findOrFail($id));
    }
}

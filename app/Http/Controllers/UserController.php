<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(User::all());
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email', 'unique:users,email'],
            'password' => ['required', 'min:6'],
            'role' => ['required', 'string']
        ]);

        $validated['password'] = Hash::make($validated['password']);

        $user = User::query()->create($validated);

        return response()
            ->json(data: [
                'message' => 'Usuário criado',
                'user' => $user
            ], status: 201);
    }

    public function show(User $user): JsonResponse
    {
        return response()->json($user);
    }

    public function update(Request $request, User $user): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['sometimes', 'email', "unique:users,email,$user->id"],
            'password' => ['sometimes', 'min:6'],
            'role' => ['sometimes', 'string']
        ]);

        if (isset($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        }

        $user->update($validated);

        return response()
            ->json(data: [
                'message' => 'Usuário atualizado',
                'user' => $user
            ], status: 201);
    }

    public function destroy(User $user): JsonResponse
    {
        $user->delete();

        return response()->json(['message' => 'Usuário removido']);
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(Product::all());
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'min:1'],
        ]);

        $product = Product::query()->create($validated);

        return response()->json(data: [
            'message' => 'Produto criado com sucesso',
            'product' => $product
        ], status: 201);
    }

    public function show(Product $product): JsonResponse
    {
        return response()->json($product);
    }

    public function update(Request $request, Product $product): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'min:1'],
        ]);

        $product->update($validated);

        return response()->json([
            'message' => 'Produto atualizado com sucesso',
            'product' => $product
        ]);
    }

    public function destroy(Product $product): JsonResponse
    {
        $product->delete();

        return response()->json([
            'message' => 'Produto removido com sucesso',
        ]);
    }
}

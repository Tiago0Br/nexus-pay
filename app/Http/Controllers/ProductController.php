<?php

namespace App\Http\Controllers;

use App\Http\Requests\SaveProductRequest;
use App\Models\Product;
use Illuminate\Http\JsonResponse;

class ProductController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(Product::all());
    }

    public function store(SaveProductRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $product = Product::query()->create($validated);

        return response()->json(data: [
            'message' => 'Produto criado com sucesso',
            'product' => $product,
        ], status: 201);
    }

    public function show(Product $product): JsonResponse
    {
        return response()->json($product);
    }

    public function update(SaveProductRequest $request, Product $product): JsonResponse
    {
        $validated = $request->validated();

        $product->update($validated);

        return response()->json([
            'message' => 'Produto atualizado com sucesso',
            'product' => $product,
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

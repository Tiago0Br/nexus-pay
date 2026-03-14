<?php

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

describe('ProductFeature', function () {
    beforeEach(function () {
        $this->user = User::query()->create([
            'name' => 'John Doe',
            'email' => 'admin@betalent.tech',
            'password' => bcrypt('password123'),
            'role' => 'ADMIN',
        ]);

        Sanctum::actingAs($this->user);
    });

    it('should list all registered products', function () {
        Product::query()->create(['name' => 'Monitor 27', 'amount' => 150000]);
        Product::query()->create(['name' => 'Mouse Gamer', 'amount' => 25000]);

        $response = $this->getJson('/products');

        $response->assertStatus(200);

        $productsArray = $response->json();
        $this->assertCount(expectedCount: 2, haystack: $productsArray);
        $this->assertEquals(expected: 'Monitor 27', actual: $productsArray[0]['name']);
        $this->assertEquals(expected: 'Mouse Gamer', actual: $productsArray[1]['name']);
    });

    it('should register a new product successfully', function () {
        $response = $this->postJson('/products', [
            'name' => 'Cadeira Ergonomica',
            'amount' => 85000,
        ]);

        $response->assertStatus(201)
            ->assertJsonFragment(['name' => 'Cadeira Ergonomica']);

        $this->assertDatabaseHas('products', [
            'name' => 'Cadeira Ergonomica',
            'amount' => 85000,
        ]);
    });

    it('should reject product registration with invalid data (missing amount)', function () {
        $response = $this->postJson('/products', [
            'name' => 'Produto sem preco',
        ]);

        $response->assertStatus(400)
            ->assertJsonFragment(['message' => 'Os dados fornecidos são inválidos.'])
            ->assertJsonFragment(['details' => ['amount' => ['O campo amount é obrigatório.']]]);

        $this->assertDatabaseMissing('products', [
            'name' => 'Produto sem preco',
        ]);
    });
});

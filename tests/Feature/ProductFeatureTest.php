<?php

use App\Models\User;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

describe('Teste das rotas de produtos', function () {
    beforeEach(function () {
        $this->user = User::query()->create([
            'name' => 'John Doe',
            'email' => 'admin@betalent.tech',
            'password' => bcrypt('password123'),
            'role' => 'ADMIN'
        ]);

        Sanctum::actingAs($this->user);
    });

    it('deve listar todos os produtos cadastrados', function () {
        Product::query()->create(['name' => 'Monitor 27', 'amount' => 150000]);
        Product::query()->create(['name' => 'Mouse Gamer', 'amount' => 25000]);

        $response = $this->getJson('/products');

        $response->assertStatus(200);

        $data = $response->json();
        $this->assertCount(expectedCount: 2, haystack: $data['products']);
        $this->assertEquals(expected: 'Monitor 27', actual: $data['products'][0]['name']);
        $this->assertEquals(expected: 'Mouse Gamer', actual: $data['products'][1]['name']);
    });

    it('deve cadastrar um novo produto com sucesso', function () {
        $response = $this->postJson('/products', [
            'name' => 'Cadeira Ergonomica',
            'amount' => 85000
        ]);

        $response->assertStatus(201)
            ->assertJsonFragment(['name' => 'Cadeira Ergonomica']);

        $this->assertDatabaseHas('products', [
            'name' => 'Cadeira Ergonomica',
            'amount' => 85000
        ]);
    });

    it('deve barrar o cadastro de um produto com dados inválidos (sem valor)', function () {
        $response = $this->postJson('/products', [
            'name' => 'Produto sem preco'
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['amount']);

        $this->assertDatabaseMissing('products', [
            'name' => 'Produto sem preco'
        ]);
    });
});

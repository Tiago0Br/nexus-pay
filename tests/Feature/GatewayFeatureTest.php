<?php

use App\Models\Gateway;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

describe('Testes das rotas de Gateways', function () {
    beforeEach(function () {
        $this->user = User::query()->create([
            'name' => 'John Doe',
            'email' => 'admin_gateways@betalent.tech',
            'password' => bcrypt('password123'),
            'role' => 'ADMIN',
        ]);
        Sanctum::actingAs($this->user);

        $this->gateway = Gateway::query()->create([
            'name' => 'Gateway Master',
            'is_active' => true,
            'priority' => 2,
        ]);
    });

    it('deve listar os gateways ordenados por prioridade', function () {
        Gateway::query()->create(['name' => 'Gateway Top', 'is_active' => true, 'priority' => 1]);

        $response = $this->getJson('/gateways');

        $response->assertStatus(200)
            ->assertJsonCount(2);

        expect($response->json()[0]['name'])->toBe('Gateway Top');
    });

    it('deve ativar/desativar um gateway', function () {
        $response = $this->patchJson("/gateways/{$this->gateway->id}/toggle-active");

        $response->assertStatus(200)
            ->assertJsonFragment(['is_active' => false]);

        $this->assertDatabaseHas('gateways', [
            'id' => $this->gateway->id,
            'is_active' => false,
        ]);
    });

    it('deve alterar a prioridade de um gateway', function () {
        $response = $this->patchJson("/gateways/{$this->gateway->id}/priority", [
            'priority' => 5,
        ]);

        $response->assertStatus(200)
            ->assertJsonFragment(['priority' => 5]);
    });
});

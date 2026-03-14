<?php

use App\Models\Client;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

describe('ClientFeature', function () {
    beforeEach(function () {
        Sanctum::actingAs(User::query()->create([
            'name' => 'John Doe',
            'email' => 'admin_clients@betalent.tech',
            'password' => bcrypt('password123'),
            'role' => 'ADMIN',
        ]));

        $this->client = Client::query()->create(['name' => 'Cliente VIP', 'email' => 'vip@email.com']);

        Transaction::query()->create([
            'client_id' => $this->client->id,
            'status' => 'SUCCESS',
            'amount' => 10000,
            'card_last_numbers' => '1234',
        ]);
    });

    it('should list clients', function () {
        $response = $this->getJson('/clients');

        $response->assertStatus(200)
            ->assertJsonFragment(['name' => 'Cliente VIP']);
    });

    it('should display client details and purchase history', function () {
        $response = $this->getJson("/clients/{$this->client->id}");

        $response->assertStatus(200)
            ->assertJsonFragment(['name' => 'Cliente VIP'])
            ->assertJsonStructure(['id', 'name', 'email', 'transactions' => [['id', 'status', 'amount']]]);
    });
});

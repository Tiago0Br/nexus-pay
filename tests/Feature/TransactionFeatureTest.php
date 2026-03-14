<?php

use App\Models\Client;
use App\Models\Gateway;
use App\Models\Product;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

describe('Testes das rotas de transações', function () {
    beforeEach(function () {
        $this->user = User::query()->create([
            'name' => 'John Doe',
            'email' => 'test@betalent.tech',
            'password' => bcrypt('password123'),
            'role' => 'ADMIN',
        ]);

        Sanctum::actingAs($this->user);

        $this->product = Product::query()->create([
            'name' => 'Teclado Mecânico',
            'amount' => 45000,
        ]);

        $this->gateway1 = Gateway::query()->create(['name' => 'Gateway 1', 'priority' => 1, 'is_active' => true]);
        $this->gateway2 = Gateway::query()->create(['name' => 'Gateway 2', 'priority' => 2, 'is_active' => true]);
    });

    it('deve processar a compra com sucesso no Gateway 1', function () {
        Http::fake([
            '*localhost:3001/login*' => Http::response(['token' => 'fake_token_g1']),
            '*localhost:3001/transactions*' => Http::response(['id' => 'ext_gateway_1_id']),
        ]);

        $response = $this->postJson('/transactions', [
            'client_name' => 'Cliente Teste',
            'client_email' => 'cliente@email.com',
            'product_id' => $this->product->id,
            'quantity' => 1,
            'cardNumber' => '5569000000006063',
            'cvv' => '010',
        ]);

        $response->assertStatus(201)
            ->assertJsonFragment(['status' => 'SUCCESS']);

        $this->assertDatabaseHas(table: 'transactions', data: [
            'amount' => 45000,
            'status' => 'SUCCESS',
            'external_id' => 'ext_gateway_1_id',
        ]);
    });

    it('deve falhar no Gateway 1 e processar a compra com sucesso no Gateway 2 (Fallback)', function () {
        Http::fake([
            '*localhost:3001/login*' => Http::response(['token' => 'fake_token_g1']),
            '*localhost:3001/transactions*' => Http::response(body: ['error' => 'Cartão recusado'], status: 400),
            '*localhost:3002/transacoes*' => Http::response(['id' => 'ext_gateway_2_id']),
        ]);

        $response = $this->postJson('/transactions', [
            'client_name' => 'Cliente Teste',
            'client_email' => 'cliente@email.com',
            'product_id' => $this->product->id,
            'quantity' => 1,
            'cardNumber' => '5569000000006063',
            'cvv' => '100',
        ]);

        $response->assertStatus(201)
            ->assertJsonFragment(['status' => 'SUCCESS']);

        $this->assertDatabaseHas('transactions', [
            'amount' => 45000,
            'status' => 'SUCCESS',
            'external_id' => 'ext_gateway_2_id',
            'gateway_id' => $this->gateway2->id,
        ]);
    });

    it('deve retornar erro e fazer rollback se todos os gateways falharem', function () {
        Http::fake([
            '*localhost:3001/login*' => Http::response(['token' => 'fake_token_g1']),
            '*localhost:3001/transactions*' => Http::response(body: ['error' => 'Erro G1'], status: 400),
            '*localhost:3002/transacoes*' => Http::response(body: ['error' => 'Erro G2'], status: 400),
        ]);

        $response = $this->postJson('/transactions', [
            'client_name' => 'Cliente Azarado',
            'client_email' => 'azarado@email.com',
            'product_id' => $this->product->id,
            'quantity' => 1,
            'cardNumber' => '5569000000006063',
            'cvv' => '999',
        ]);

        $response->assertStatus(402)
            ->assertJsonFragment(['message' => 'Pagamento recusado em todos os gateways disponíveis.']);

        $this->assertDatabaseMissing('transactions', [
            'amount' => 45000,
        ]);
    });

    it('deve realizar o reembolso de uma transação com sucesso', function () {
        $client = Client::query()->create([
            'name' => 'Cliente Reembolso',
            'email' => 'reembolso@email.com',
        ]);

        $transaction = Transaction::query()->create([
            'client_id' => $client->id,
            'gateway_id' => $this->gateway1->id,
            'external_id' => 'ext_123_abc',
            'status' => 'SUCCESS',
            'amount' => 45000,
            'card_last_numbers' => '6063',
        ]);

        Http::fake([
            '*localhost:3001/login*' => Http::response(['token' => 'fake_token']),
            '*localhost:3001/transactions/*/charge_back*' => Http::response(['status' => 'refunded']),
        ]);

        $response = $this->patchJson("/transactions/$transaction->id/charge_back");

        $response->assertStatus(200)
            ->assertJsonFragment(['status' => 'CHARGED_BACK']);

        $this->assertDatabaseHas('transactions', [
            'id' => $transaction->id,
            'status' => 'CHARGED_BACK',
        ]);
    });

    it('deve bloquear o reembolso de uma transação já estornada', function () {
        $client = Client::query()->create([
            'name' => 'Cliente Espertinho',
            'email' => 'espertinho@email.com',
        ]);

        $transaction = Transaction::query()->create([
            'client_id' => $client->id,
            'gateway_id' => $this->gateway1->id,
            'external_id' => 'ext_999_xyz',
            'status' => 'CHARGED_BACK',
            'amount' => 45000,
            'card_last_numbers' => '6063',
        ]);

        $response = $this->patchJson("/transactions/$transaction->id/charge_back");

        $response->assertStatus(400)
            ->assertJsonFragment(['message' => 'Esta transação já foi reembolsada anteriormente.']);
    });

    it('deve listar todas as transacoes com os relacionamentos de cliente e produtos', function () {
        $client = Client::query()->create(['name' => 'Comprador', 'email' => 'compra@email.com']);

        Transaction::query()->create([
            'client_id' => $client->id,
            'status' => 'SUCCESS',
            'amount' => 5000,
            'card_last_numbers' => '9999',
        ]);

        $response = $this->getJson('/transactions');

        $response->assertStatus(200)
            ->assertJsonFragment(['status' => 'SUCCESS'])
            ->assertJsonStructure([
                '*' => ['id', 'amount', 'client_id', 'gateway_id'],
            ]);
    });

    it('deve listar detalhes de uma transacao especifica', function () {
        $client = Client::query()->create(['name' => 'Comprador Unico', 'email' => 'unico@email.com']);

        $transaction = Transaction::query()->create([
            'client_id' => $client->id,
            'status' => 'SUCCESS',
            'amount' => 7500,
            'card_last_numbers' => '0000',
        ]);

        $response = $this->getJson("/transactions/$transaction->id");

        $response->assertStatus(200)
            ->assertJsonFragment(['amount' => 7500])
            ->assertJsonFragment(['name' => 'Comprador Unico']);
    });
});

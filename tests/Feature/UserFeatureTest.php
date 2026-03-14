<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

describe('Testes das rotas de usuários', function () {
    beforeEach(function () {
        $this->admin = User::query()->create([
            'name' => 'John Doe',
            'email' => 'admin_users@betalent.tech',
            'password' => bcrypt('password123'),
            'role' => 'ADMIN',
        ]);
        Sanctum::actingAs($this->admin);
    });

    it('deve cadastrar um novo usuário', function () {
        $response = $this->postJson('/users', [
            'name' => 'John Doe',
            'email' => 'novo@betalent.tech',
            'password' => 'secret123',
            'role' => 'MANAGER',
        ]);

        $response->assertStatus(201)
            ->assertJsonFragment(['email' => 'novo@betalent.tech', 'role' => 'MANAGER']);

        $this->assertDatabaseHas('users', ['email' => 'novo@betalent.tech']);
    });

    it('nao deve cadastrar usuario com email duplicado', function () {
        $this->postJson('/users', [
            'name' => 'John Doe',
            'email' => 'duplicado@betalent.tech',
            'password' => 'secret123',
            'role' => 'MANAGER',
        ]);

        $response = $this->postJson('/users', [
            'name' => 'John Doe 2',
            'email' => 'duplicado@betalent.tech',
            'password' => 'outrasenha',
            'role' => 'USER',
        ]);

        $response->assertStatus(400)
            ->assertJsonFragment(['message' => 'Os dados fornecidos são inválidos.'])
            ->assertJsonFragment(['details' => ['email' => ['O campo email já está sendo utilizado.']]]);
    });

    it('deve deletar um usuário', function () {
        $userToDelete = User::query()->create([
            'name' => 'John Doe',
            'email' => 'delete_me@betalent.tech',
            'password' => bcrypt('123'),
            'role' => 'USER',
        ]);

        $response = $this->deleteJson("/users/$userToDelete->id");
        $response->assertStatus(200);

        $this->assertDatabaseMissing('users', ['id' => $userToDelete->id]);
    });
});

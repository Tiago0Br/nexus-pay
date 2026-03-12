<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $now = Carbon::now();

        DB::table('users')->insert([
            'name' => 'Developer',
            'email' => 'dev@betalent.tech',
            'password' => Hash::make('password123'),
            'role' => 'ADMIN',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('gateways')->insert([
            [
                'name' => 'Gateway 1',
                'is_active' => true,
                'priority' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'Gateway 2',
                'is_active' => true,
                'priority' => 2,
                'created_at' => $now,
                'updated_at' => $now,
            ]
        ]);

        DB::table('products')->insert([
            'name' => 'Teclado Mecânico Keychron',
            'amount' => 45000,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('clients')->insert([
            'name' => 'Cliente Teste',
            'email' => 'tester@email.com',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }
}

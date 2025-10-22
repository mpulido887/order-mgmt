<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Client;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 2 clientes demo con 1 usuario cada uno
        $clientA = Client::factory()->create(['name' => 'Client A', 'email' => 'clienta@example.com']);
        $clientB = Client::factory()->create(['name' => 'Client B', 'email' => 'clientb@example.com']);

        $userA = User::factory()->create([
            'client_id' => $clientA->id,
            'email'     => 'usera@example.com',
            'password'  => bcrypt('password'),
            'name'      => 'User A',
        ]);

        $userB = User::factory()->create([
            'client_id' => $clientB->id,
            'email'     => 'userb@example.com',
            'password'  => bcrypt('password'),
            'name'      => 'User B',
        ]);

        // Tokens personales (Sanctum)
        $tokenA = $userA->createToken('demo-token-client-a')->plainTextToken;
        $tokenB = $userB->createToken('demo-token-client-b')->plainTextToken;

        // Mostrar en consola al ejecutar db:seed
        $this->command->info('== DEMO TOKENS ==');
        $this->command->info("Client A / usera@example.com / pass: password");
        $this->command->info("Bearer: {$tokenA}");
        $this->command->info("Client B / userb@example.com / pass: password");
        $this->command->info("Bearer: {$tokenB}");
    }
}

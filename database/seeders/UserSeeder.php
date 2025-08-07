<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run()
    {
        // Criar usuário admin
        $admin = User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => Hash::make('admin123'),
        ]);
        
        // Criar perfil para o admin
        \App\Models\Profile::factory()->create([
            'user_id' => $admin->id,
            'bio' => 'Administrador do sistema',
        ]);
        
        // Criar 50 usuários regulares com perfis
        User::factory()
            ->count(50)
            ->withProfile()
            ->create();
    }
}
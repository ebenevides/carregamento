<?php

namespace Database\Seeders;

use App\Domain\Carregamento\Enums\PerfilUsuario;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $users = [
            ['name' => 'Admin',     'email' => 'admin@carregamento.local',     'perfil' => PerfilUsuario::ADMIN],
            ['name' => 'Expedição', 'email' => 'expedicao@carregamento.local', 'perfil' => PerfilUsuario::EXPEDICAO],
            ['name' => 'Operador',  'email' => 'operador@carregamento.local',  'perfil' => PerfilUsuario::OPERADOR],
            ['name' => 'Motorista', 'email' => 'motorista@carregamento.local', 'perfil' => PerfilUsuario::MOTORISTA],
        ];

        foreach ($users as $data) {
            User::updateOrCreate(
                ['email' => $data['email']],
                [...$data, 'password' => Hash::make('password'), 'email_verified_at' => now()]
            );
        }
    }
}

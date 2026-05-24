<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    /**
     * Inserta las cuentas de administrador del sistema.
     */
    public function run(): void
    {
        $admins = [
            [
                'nombre'   => 'Victor Admin',
                'email'    => 'admin@zerowaste.com',
                'password' => '123456',
                'is_admin' => true,
            ]
        ];

        foreach ($admins as $admin) {
            User::updateOrCreate(
                ['email' => $admin['email']],
                $admin
            );
        }
    }
}

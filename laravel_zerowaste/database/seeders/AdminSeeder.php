<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    /**
     * Inserta las 5 cuentas de administrador exclusivas.
     */
    public function run(): void
    {
        $admins = [
            [
                'nombre'   => 'Admin Principal',
                'email'    => 'admin@zerowaste.com',
                'password' => Hash::make('ZeroWaste2026!'),
                'is_admin' => true,
            ],
            [
                'nombre'   => 'Admin Operaciones',
                'email'    => 'operaciones@zerowaste.com',
                'password' => Hash::make('ZeroWaste2026!'),
                'is_admin' => true,
            ],
            [
                'nombre'   => 'Admin Contenido',
                'email'    => 'contenido@zerowaste.com',
                'password' => Hash::make('ZeroWaste2026!'),
                'is_admin' => true,
            ],
            [
                'nombre'   => 'Admin Soporte',
                'email'    => 'soporte@zerowaste.com',
                'password' => Hash::make('ZeroWaste2026!'),
                'is_admin' => true,
            ],
            [
                'nombre'   => 'Admin Desarrollo',
                'email'    => 'dev@zerowaste.com',
                'password' => Hash::make('ZeroWaste2026!'),
                'is_admin' => true,
            ],
        ];

        foreach ($admins as $admin) {
            User::updateOrCreate(
                ['email' => $admin['email']],
                $admin
            );
        }
    }
}

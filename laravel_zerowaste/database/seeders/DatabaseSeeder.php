<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Ejecuta los seeders de la base de datos.
     */
    public function run(): void
    {
        $this->call([
            AdminSeeder::class,
            LocationSeeder::class,
            CampaignSeeder::class,
        ]);
    }
}

<?php

namespace Database\Seeders;

use App\Models\Location;
use Illuminate\Database\Seeder;

class LocationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $locations = [
            [
                'nombre' => 'Acopio Querétaro PetStar',
                'direccion' => 'Calle Cascada 4 B, Parque Industrial La Noria, Qro.',
                'latitud' => 20.566195172754586,
                'longitud' => -100.29811885089731,
                'tipo' => 'Centro Principal',
                'materiales' => 'PET, Plásticos, Tapitas',
                'imagen' => 'petstar.jpg', // La imagen se agregará manualmente
            ],
            [
                'nombre' => 'Alcamare Qro',
                'direccion' => 'Av. 5 de Febrero 1410, Santiago de Querétaro, Qro.',
                'latitud' => 20.609742163190546,
                'longitud' => -100.41774955960878,
                'tipo' => 'Centro Principal',
                'materiales' => 'Cartón, Papel, Archivo muerto, Plástico, Metales',
                'imagen' => 'alcamare.jpg',
            ],
            [
                'nombre' => 'Centro De Acopio Raices Y Semillas Jurica',
                'direccion' => 'Paseo Jurica No. 1, Jurica, Qro.',
                'latitud' => 20.64698626631188,
                'longitud' => -100.43392338157149,
                'tipo' => 'Organización Ambiental',
                'materiales' => 'Semillas, Raíces, Reforestación, Vidrio, PET',
                'imagen' => 'jurica.jpg',
            ],
            [
                'nombre' => 'El Faro Centro de Acopio',
                'direccion' => 'Calle Pino Suárez esq. Churubusco, Centro, Qro.',
                'latitud' => 20.586852722572445,
                'longitud' => -100.4048242074262,
                'tipo' => 'Centro Principal',
                'materiales' => 'PET, Cartón, Papel, Electrónicos',
                'imagen' => 'elfaro.jpg',
            ],
        ];

        foreach ($locations as $location) {
            Location::updateOrCreate(
                ['nombre' => $location['nombre']],
                $location
            );
        }
    }
}

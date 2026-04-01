<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Campaign;

class CampaignSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Campaign::create([
            'nombre' => 'Talleres Ambientales en La Queretana',
            'lugar' => 'Parque Intraurbano La Queretana, Querétaro',
            'fecha_inicio' => '2026-04-01 00:00:00',
            'fecha_fin' => '2026-04-01 23:59:59',
            'descripcion' => 'El municipio ha anunciado una serie de eventos en el Parque Intraurbano "La Queretana", destacando talleres de polinizadores programados...',
            'tipo_etiqueta' => 'TALLER',
            'imagen_url' => 'event1.png',
            'link_evento' => 'https://rotativo.com.mx/calendario-eventos-ambientales-la-queretana-queretaro-2026?share_id=9284828&socialux=facebook&utm_campaign=RebelMouse&utm_content=Rotativo%20de%20Queretaro&utm_medium=social&utm_source=facebook',
            'recompensa_puntos' => 0,
            'activa' => true,
        ]);

        Campaign::create([
            'nombre' => 'Lidera y Recicla en tu Escuela 2026',
            'lugar' => 'Instituciones educativas de Querétaro',
            'fecha_inicio' => '2026-04-01 00:00:00',
            'fecha_fin' => '2026-04-01 23:59:59',
            'descripcion' => 'Esta iniciativa de la Secretaría de Educación del Estado (SEDEQ) estará activa durante todo el mes. Se enfoca en la recolección de botellas PET y tapas de...',
            'tipo_etiqueta' => 'EDUCACIÓN',
            'imagen_url' => 'event2.jpg',
            'link_evento' => 'https://linktr.ee/EDUCACIONQRO',
            'recompensa_puntos' => 0,
            'activa' => true,
        ]);

        Campaign::create([
            'nombre' => 'Campaña de Tapitas',
            'lugar' => 'Querétaro, Qro.',
            'fecha_inicio' => '2026-04-01 00:00:00',
            'fecha_fin' => '2026-04-01 23:59:59',
            'descripcion' => 'Campaña enfocada en la recolección solidaria de tapitas.',
            'tipo_etiqueta' => 'ACOPIO',
            'imagen_url' => 'event3.jpg',
            'link_evento' => 'https://amancqueretaro.org/reciclando/',
            'recompensa_puntos' => 0,
            'activa' => true,
        ]);
    }
}

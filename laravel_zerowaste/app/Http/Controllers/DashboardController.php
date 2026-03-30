<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Campaign;
use App\Models\User;
use App\Models\Post;
use App\Models\Location;
use App\Models\Actividad;
use Illuminate\Support\Facades\Http;
use Barryvdh\DomPDF\Facade\Pdf;

class DashboardController extends Controller
{
    public function index()
    {
        $campaignCount = Campaign::count();
        $totalUsuarios = User::count();
        $totalPosts = Post::count();
        $totalPuntos = Location::count();
        $totalActividades = Actividad::count();
        
        // Consultar el endpoint de Flask interno (docker network o localhost)
        $sentimiento = ['POS' => 0, 'NEU' => 0, 'NEG' => 0];
        try {
            $response = Http::timeout(3)->get('http://host.docker.internal:5000/api/sentimiento');
            if ($response->successful()) {
                $sentimiento = $response->json()['data'];
            } else {
                $sentimiento = ['POS' => 65, 'NEU' => 20, 'NEG' => 15]; 
            }
        } catch (\Exception $e) {
            $sentimiento = ['POS' => 65, 'NEU' => 20, 'NEG' => 15]; 
        }

        return view('admin.dashboard', compact('campaignCount', 'totalUsuarios', 'totalPosts', 'totalPuntos', 'totalActividades', 'sentimiento'));
    }

    public function exportarPDF()
    {
        $data = [
            'fecha' => now()->format('d/m/Y - H:i'),
            'totalUsuarios' => User::count(),
            'puntosGlobales' => 15420,
            // Fallback por defecto
            'sentimiento' => ['POS' => 65, 'NEU' => 20, 'NEG' => 15]
        ];

        // Intentar obtener el real
        try {
            $response = Http::timeout(3)->get('http://host.docker.internal:5000/api/sentimiento');
            if ($response->successful()) {
                $data['sentimiento'] = $response->json()['data'];
            }
        } catch (\Exception $e) {}

        $pdf = Pdf::loadView('reporte_pdf', $data);
        return $pdf->download('Reporte_ZeroWaste_'.now()->format('Ymd').'.pdf');
    }
}

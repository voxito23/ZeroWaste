<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Campaign;
use App\Models\User;
use App\Models\Location;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Carbon;

class ReportController extends Controller
{
    public function index()
    {
        return view('admin.reportes.index');
    }

    public function exportar(Request $request)
    {
        $request->validate([
            'tipo' => 'required|in:usuarios,campanas,mapa',
            'fecha_inicio' => 'required|date|after_or_equal:2026-03-30',
            'fecha_fin' => 'required|date|after_or_equal:fecha_inicio',
        ], [
            'tipo.required' => 'El tipo de reporte es obligatorio.',
            'tipo.in' => 'El tipo de reporte es inválido.',
            'fecha_inicio.after_or_equal' => 'La fecha de inicio debe ser a partir del 30 de marzo de 2026.',
            'fecha_fin.after_or_equal' => 'La fecha final debe ser posterior o igual a la de inicio.',
        ]);

        $tipo = $request->input('tipo');
        $fechaInicio = $request->input('fecha_inicio') . ' 00:00:00';
        $fechaFin = $request->input('fecha_fin') . ' 23:59:59';
        
        $data = [
            'tipo' => $tipo,
            'fecha_generada' => Carbon::now()->format('d/m/Y - H:i'),
            'rango_inicio' => Carbon::parse($request->input('fecha_inicio'))->format('d M Y'),
            'rango_fin' => Carbon::parse($request->input('fecha_fin'))->format('d M Y'),
        ];

        // Recolectar datos según el tipo
        if ($tipo === 'usuarios') {
            $data['registros'] = User::query()
                                     ->whereBetween('created_at', [$fechaInicio, $fechaFin])
                                     ->orderByDesc('created_at')
                                     ->get();
            $data['titulo'] = "Reporte de Usuarios Registrados";
            $data['total'] = count($data['registros']);
        } elseif ($tipo === 'campanas') {
            $data['registros'] = Campaign::query()
                                         ->whereBetween('created_at', [$fechaInicio, $fechaFin])
                                         ->orderByDesc('created_at')
                                         ->get();
            $data['titulo'] = "Reporte de Campañas Realizadas";
            $data['total'] = count($data['registros']);
        } elseif ($tipo === 'mapa') {
            $data['registros'] = Location::query()
                                         ->whereBetween('created_at', [$fechaInicio, $fechaFin])
                                         ->orderByDesc('created_at')
                                         ->get();
            $data['titulo'] = "Reporte de Puntos de Reciclaje (Mapa)";
            $data['total'] = count($data['registros']);
        }

        /** @var \Barryvdh\DomPDF\PDF $pdf */
        $pdf = Pdf::loadView('reporte_pdf', $data);

        // Opcional: Guardado temporal
        $filename = 'Reporte_' . ucfirst($tipo) . '_' . Carbon::now()->format('Ymd_His') . '.pdf';
        
        return $pdf->download($filename);
    }
}

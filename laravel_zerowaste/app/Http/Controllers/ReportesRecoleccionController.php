<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;

class ReportesRecoleccionController extends Controller
{
    public function generarPDF()
    {
        $solicitudes = DB::table('solicitudes_recoleccion')
            ->join('usuarios as ciudadano', 'solicitudes_recoleccion.usuario_id', '=', 'ciudadano.id')
            ->leftJoin('usuarios as recolector', 'solicitudes_recoleccion.recolector_id', '=', 'recolector.id')
            ->select(
                'solicitudes_recoleccion.*', 
                'ciudadano.nombre as ciudadano_nombre', 
                'recolector.nombre as recolector_nombre'
            )
            ->orderBy('created_at', 'desc')
            ->get();

        $totalRecolecciones = $solicitudes->count();
        $completadas = $solicitudes->where('estado', 'completada')->count();
        $pendientes = $solicitudes->where('estado', 'pendiente')->count();

        // Calculate average rating
        $calificaciones = $solicitudes->whereNotNull('calificacion_recolector')->pluck('calificacion_recolector');
        $promedioCalificacion = $calificaciones->avg() ?? 0;

        $pdf = Pdf::loadView('admin.recolecciones.reporte_pdf', compact(
            'solicitudes', 'totalRecolecciones', 'completadas', 'pendientes', 'promedioCalificacion'
        ));

        return $pdf->download('reporte_recolecciones_zerowaste.pdf');
    }
}

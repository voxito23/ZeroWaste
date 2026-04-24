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
    public function index(Request $request)
    {
        $search = $request->input('search');
        $categoria = $request->input('categoria');
        $fechaInicio = $request->input('fecha_inicio');
        $fechaFin = $request->input('fecha_fin');
        if ($fechaInicio) $fechaInicio .= ' 00:00:00';
        if ($fechaFin) $fechaFin .= ' 23:59:59';
        $hasDates = $fechaInicio && $fechaFin;

        $hasFilters = $search || $categoria || $hasDates;

        $uQ = User::query();
        $cQ = Campaign::query();
        $pQ = Location::query();
        $eQ = \App\Models\Evento::query();

        if ($hasDates) {
            $uQ->whereBetween('created_at', [$fechaInicio, $fechaFin]);
            $cQ->whereBetween('created_at', [$fechaInicio, $fechaFin]);
            $pQ->whereBetween('created_at', [$fechaInicio, $fechaFin]);
            $eQ->whereBetween('fecha_inicio', [$fechaInicio, $fechaFin]);
        }

        if ($search) {
            $uQ->where(function($q) use ($search) { $q->where('nombre', 'ilike', "%{$search}%")->orWhere('email', 'ilike', "%{$search}%"); });
            $cQ->where(function($q) use ($search) { $q->where('nombre', 'ilike', "%{$search}%")->orWhere('descripcion', 'ilike', "%{$search}%"); });
            $pQ->where(function($q) use ($search) { $q->where('nombre', 'ilike', "%{$search}%")->orWhere('direccion', 'ilike', "%{$search}%"); });
            $eQ->where('titulo', 'ilike', "%{$search}%");
        }

        if ($categoria) {
            if ($categoria === 'admins') $uQ->where('is_admin', true);
            else if ($categoria === 'users') $uQ->where('is_admin', false);
            
            $cQ->where('tipo_etiqueta', 'ilike', "%{$categoria}%");
            $pQ->where('tipo', 'ilike', "%{$categoria}%");
        }

        $totalUsuarios = $uQ->count();
        $totalCampanas = $cQ->count();
        $totalPuntos = $pQ->count();
        $totalEventos = $eQ->count();

        if ($request->ajax()) {
            return response()->json([
                'totalUsuarios' => $totalUsuarios,
                'totalCampanas' => $totalCampanas,
                'totalPuntos' => $totalPuntos,
                'totalEventos' => $totalEventos,
            ]);
        }

        return view('admin.reportes.index', compact(
            'totalUsuarios', 'totalCampanas', 'totalPuntos', 'totalEventos'
        ));
    }

    public function exportar(Request $request)
    {
        $request->validate([
            'tipo' => 'required|in:usuarios,campanas,mapa,eventos',
            'formato' => 'required|in:pdf,xlsx,docx,preview',
            'fecha_inicio' => 'nullable|date',
            'fecha_fin' => 'nullable|date|after_or_equal:fecha_inicio',
        ], [
            'tipo.required' => 'El tipo de reporte es obligatorio.',
            'tipo.in' => 'El tipo de reporte es inválido.',
            'formato.required' => 'El formato es obligatorio.',
            'formato.in' => 'El formato debe ser pdf, xlsx, docx o preview.',
            'fecha_fin.after_or_equal' => 'La fecha final debe ser posterior o igual a la de inicio.',
        ]);

        $tipo = $request->input('tipo');
        $formato = $request->input('formato');
        $search = $request->input('search');
        $categoria = $request->input('categoria');
        
        $fechaInicio = $request->input('fecha_inicio') ? $request->input('fecha_inicio') . ' 00:00:00' : null;
        $fechaFin = $request->input('fecha_fin') ? $request->input('fecha_fin') . ' 23:59:59' : null;
        $hasDates = $fechaInicio && $fechaFin;
        
        $data = [
            'tipo' => $tipo,
            'fecha_generada' => Carbon::now('America/Mexico_City')->format('d/m/Y - h:i A'),
            'rango_inicio' => $hasDates ? Carbon::parse($request->input('fecha_inicio'))->format('d M Y') : 'Inicio',
            'rango_fin' => $hasDates ? Carbon::parse($request->input('fecha_fin'))->format('d M Y') : 'Actual',
        ];

        // Recolectar datos según el tipo
        if ($tipo === 'usuarios') {
            $query = User::query();
            if ($hasDates) $query->whereBetween('created_at', [$fechaInicio, $fechaFin]);
            if ($search) {
                $query->where(function($q) use ($search) {
                    $q->where('nombre', 'ilike', "%{$search}%")->orWhere('email', 'ilike', "%{$search}%");
                });
            }
            if ($categoria) {
                if ($categoria === 'admins') $query->where('is_admin', true);
                else if ($categoria === 'users') $query->where('is_admin', false);
            }
            $data['registros'] = $query->orderByDesc('created_at')->get();
            $data['titulo'] = "Reporte de Usuarios Registrados";
            $data['total'] = count($data['registros']);
        } elseif ($tipo === 'campanas') {
            $query = Campaign::query();
            if ($hasDates) $query->whereBetween('created_at', [$fechaInicio, $fechaFin]);
            if ($search) {
                $query->where(function($q) use ($search) {
                    $q->where('nombre', 'ilike', "%{$search}%")->orWhere('descripcion', 'ilike', "%{$search}%")->orWhere('lugar', 'ilike', "%{$search}%");
                });
            }
            if ($categoria) {
                $query->where('tipo_etiqueta', 'ilike', "%{$categoria}%");
            }
            $data['registros'] = $query->orderByDesc('created_at')->get();
            $data['titulo'] = "Reporte de Campañas Realizadas";
            $data['total'] = count($data['registros']);
        } elseif ($tipo === 'mapa') {
            $query = Location::query();
            if ($hasDates) $query->whereBetween('created_at', [$fechaInicio, $fechaFin]);
            if ($search) {
                $query->where(function($q) use ($search) {
                    $q->where('nombre', 'ilike', "%{$search}%")->orWhere('direccion', 'ilike', "%{$search}%")->orWhere('materiales', 'ilike', "%{$search}%");
                });
            }
            if ($categoria) {
                $query->where('tipo', 'ilike', "%{$categoria}%");
            }
            $data['registros'] = $query->orderByDesc('created_at')->get();
            $data['titulo'] = "Reporte de Puntos de Reciclaje (Mapa)";
            $data['total'] = count($data['registros']);
        } elseif ($tipo === 'eventos') {
            $query = \App\Models\Evento::query();
            if ($hasDates) $query->whereBetween('fecha_inicio', [$fechaInicio, $fechaFin]);
            if ($search) {
                $query->where(function($q) use ($search) {
                    $q->where('titulo', 'ilike', "%{$search}%")->orWhere('descripcion', 'ilike', "%{$search}%")->orWhere('lugar', 'ilike', "%{$search}%");
                });
            }
            if ($categoria) {
                $query->where('tipo_etiqueta', 'ilike', "%{$categoria}%");
            }
            $data['registros'] = $query->orderByDesc('fecha_inicio')->get();
            $data['titulo'] = "Reporte de Eventos Agendados";
            $data['total'] = count($data['registros']);
        }

        $filename = 'Reporte_' . ucfirst($tipo) . '_' . Carbon::now()->format('Ymd_His') . '.' . ($formato === 'preview' ? 'pdf' : $formato);

        if ($formato === 'pdf' || $formato === 'preview') {
            /** @var \Barryvdh\DomPDF\PDF $pdf */
            $pdf = Pdf::loadView('reporte_pdf', $data);
            $pdf->getDomPDF()->set_option('isRemoteEnabled', true);
            if ($formato === 'preview') {
                return $pdf->stream($filename);
            }
            return $pdf->download($filename);
        } elseif ($formato === 'xlsx') {
            $filename = str_replace('.xlsx', '.xls', $filename);
            return response(view('reporte_excel', $data)->render())
                ->header('Content-Type', 'application/vnd.ms-excel')
                ->header('Content-Disposition', 'attachment; filename="'.$filename.'"');
        } elseif ($formato === 'docx') {
            $filename = str_replace('.docx', '.doc', $filename);
            return response(view('reporte_excel', $data)->render())
                ->header('Content-Type', 'application/msword')
                ->header('Content-Disposition', 'attachment; filename="'.$filename.'"');
        }
    }
}

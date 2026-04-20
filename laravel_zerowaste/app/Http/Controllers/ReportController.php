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
        $totalUsuarios = User::query()->count();
        $totalCampanas = Campaign::query()->count();
        $totalPuntos = Location::query()->count();
        $totalEventos = \App\Models\Evento::query()->count();

        return view('admin.reportes.index', compact(
            'totalUsuarios', 'totalCampanas', 'totalPuntos', 'totalEventos'
        ));
    }

    public function exportar(Request $request)
    {
        $request->validate([
            'tipo' => 'required|in:usuarios,campanas,mapa,eventos',
            'formato' => 'required|in:pdf,xlsx,docx,preview',
            'fecha_inicio' => 'required|date',
            'fecha_fin' => 'required|date|after_or_equal:fecha_inicio',
        ], [
            'tipo.required' => 'El tipo de reporte es obligatorio.',
            'tipo.in' => 'El tipo de reporte es inválido.',
            'formato.required' => 'El formato es obligatorio.',
            'formato.in' => 'El formato debe ser pdf, xlsx, docx o preview.',
            'formato.in' => 'El formato debe ser pdf, xlsx, docx o preview.',
            'fecha_fin.after_or_equal' => 'La fecha final debe ser posterior o igual a la de inicio.',
        ]);

        $tipo = $request->input('tipo');
        $formato = $request->input('formato');
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
        } elseif ($tipo === 'eventos') {
            $data['registros'] = \App\Models\Evento::query()
                                         ->whereBetween('fecha_inicio', [$fechaInicio, $fechaFin])
                                         ->orderByDesc('fecha_inicio')
                                         ->get();
            $data['titulo'] = "Reporte de Eventos Agendados";
            $data['total'] = count($data['registros']);
        }

        $filename = 'Reporte_' . ucfirst($tipo) . '_' . Carbon::now()->format('Ymd_His') . '.' . ($formato === 'preview' ? 'pdf' : $formato);

        if ($formato === 'pdf' || $formato === 'preview') {
            /** @var \Barryvdh\DomPDF\PDF $pdf */
            $pdf = Pdf::loadView('reporte_pdf', $data);
            if ($formato === 'preview') {
                return $pdf->stream($filename);
            }
            return $pdf->download($filename);
        } elseif ($formato === 'xlsx') {
            return \Maatwebsite\Excel\Facades\Excel::download(new \App\Exports\GenericExport($data), $filename);
        } elseif ($formato === 'docx') {
            $phpWord = new \PhpOffice\PhpWord\PhpWord();
            $section = $phpWord->addSection();
            \PhpOffice\PhpWord\Shared\Html::addHtml($section, view('reporte_basico', $data)->render(), false, false);
            
            $objWriter = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'Word2007');
            $filenameTemp = tempnam(sys_get_temp_dir(), 'word');
            $objWriter->save($filenameTemp);
            
            return response()->download($filenameTemp, $filename)->deleteFileAfterSend(true);
        }
    }
}

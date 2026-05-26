<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Campaign;
use App\Models\User;
use App\Models\Location;
use App\Models\Post;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Carbon;
use App\Exports\GenericExport;
use Maatwebsite\Excel\Facades\Excel;

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
        $fQ = Post::query();

        if ($hasDates) {
            $uQ->whereBetween('created_at', [$fechaInicio, $fechaFin]);
            $cQ->whereBetween('created_at', [$fechaInicio, $fechaFin]);
            $pQ->whereBetween('created_at', [$fechaInicio, $fechaFin]);
            $eQ->whereBetween('fecha_inicio', [$fechaInicio, $fechaFin]);
            $fQ->whereBetween('created_at', [$fechaInicio, $fechaFin]);
        }

        if ($search) {
            $uQ->where(function($q) use ($search) { $q->where('nombre', 'ilike', "%{$search}%")->orWhere('email', 'ilike', "%{$search}%"); });
            $cQ->where(function($q) use ($search) { $q->where('nombre', 'ilike', "%{$search}%")->orWhere('descripcion', 'ilike', "%{$search}%"); });
            $pQ->where(function($q) use ($search) { $q->where('nombre', 'ilike', "%{$search}%")->orWhere('direccion', 'ilike', "%{$search}%"); });
            $eQ->where('titulo', 'ilike', "%{$search}%");
            $fQ->where(function($q) use ($search) { $q->where('titulo', 'ilike', "%{$search}%")->orWhere('contenido', 'ilike', "%{$search}%"); });
        }

        if ($categoria) {
            // Module: Users
            if ($categoria === 'admins') $uQ->where('is_admin', true);
            else if ($categoria === 'users') $uQ->where('is_admin', false);
            
            // Module: Map points
            if ($categoria === 'reciclaje') {
                $pQ->where(function($q) {
                    $q->where('tipo', 'ilike', '%plástico%')
                      ->orWhere('tipo', 'ilike', '%vidrio%')
                      ->orWhere('tipo', 'ilike', '%electrónicos%')
                      ->orWhere('tipo', 'ilike', '%cartón%')
                      ->orWhere('tipo', 'ilike', '%baterías%')
                      ->orWhere('tipo', 'ilike', '%metal%')
                      ->orWhere('tipo', 'ilike', '%reciclaje%');
                });
            } elseif (in_array($categoria, ['centro principal', 'contenedor público'])) {
                $pQ->where('tipo', 'ilike', "%{$categoria}%");
            }
            
            // Module: Campaigns
            if (in_array($categoria, ['Impacto Positivo', 'Educación', 'Recaudación'])) {
                $cQ->where('tipo_etiqueta', 'ilike', "%{$categoria}%");
            } elseif (!in_array($categoria, ['admins', 'users', 'reciclaje', 'centro principal', 'contenedor público', 'Limpieza', 'Taller', 'Conferencia'])) {
                $cQ->where('tipo_etiqueta', 'ilike', "%{$categoria}%");
            }
            
            // Module: Events
            if (in_array($categoria, ['Limpieza', 'Taller', 'Conferencia'])) {
                $eQ->where('tipo', 'ilike', "%{$categoria}%");
            }
        }

        $totalUsuarios = $uQ->count();
        $totalCampanas = $cQ->count();
        $totalPuntos = $pQ->count();
        $totalEventos = $eQ->count();
        $totalForo = $fQ->count();

        if ($request->ajax()) {
            return response()->json([
                'totalUsuarios' => $totalUsuarios,
                'totalCampanas' => $totalCampanas,
                'totalPuntos' => $totalPuntos,
                'totalEventos' => $totalEventos,
                'totalForo' => $totalForo,
            ]);
        }

        return view('admin.reportes.index', compact(
            'totalUsuarios', 'totalCampanas', 'totalPuntos', 'totalEventos', 'totalForo'
        ));
    }

    public function exportar(Request $request)
    {
        ini_set('memory_limit', '1024M');
        set_time_limit(300);

        $request->validate([
            'tipo' => 'required|in:usuarios,campanas,mapa,eventos,foro',
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
        } elseif ($tipo === 'foro') {
            $query = Post::with(['autor', 'categoria']);
            if ($hasDates) $query->whereBetween('created_at', [$fechaInicio, $fechaFin]);
            if ($search) {
                $query->where(function($q) use ($search) {
                    $q->where('titulo', 'ilike', "%{$search}%")->orWhere('contenido', 'ilike', "%{$search}%");
                });
            }
            if ($categoria) {
                $query->whereHas('categoria', function($q) use ($categoria) {
                    $q->where('nombre', 'ilike', "%{$categoria}%");
                });
            }
            $data['registros'] = $query->orderByDesc('created_at')->get();
            $data['titulo'] = "Reporte de Posts del Foro";
            $data['total'] = count($data['registros']);
        }

        $filename = 'Reporte_' . ucfirst($tipo) . '_' . Carbon::now()->format('Ymd_His');

        // Determine real extension
        if ($formato === 'xlsx') {
            $filename .= '.xlsx';
        } elseif ($formato === 'docx') {
            $filename .= '.docx';
        } elseif ($formato === 'pdf' || $formato === 'preview') {
            $filename .= '.pdf';
        }

        if ($formato === 'pdf' || $formato === 'preview') {
            /** @var \Barryvdh\DomPDF\PDF $pdf */
            $pdf = Pdf::loadView('reporte_pdf', $data);
            $pdf->getDomPDF()->set_option('isRemoteEnabled', true);
            if ($formato === 'preview') {
                return $pdf->stream($filename);
            }
            return $pdf->download($filename);
        } elseif ($formato === 'xlsx') {
            return Excel::download(new GenericExport($data), $filename);
        } elseif ($formato === 'docx') {
            $phpWord = new \PhpOffice\PhpWord\PhpWord();
            $phpWord->setDefaultFontName('Calibri');
            $phpWord->setDefaultFontSize(10);

            $section = $phpWord->addSection(['marginTop' => 800, 'marginBottom' => 600, 'marginLeft' => 900, 'marginRight' => 900]);

            // Find Logo
            $logoPath = null;
            $possiblePaths = [
                public_path('img/logo_texture.png'),
                base_path('../flask_zerowaste/static/img/logo_texture.png'),
                '/var/www/flask_zerowaste/static/img/logo_texture.png',
                '/opt/ZeroWaste/flask_zerowaste/static/img/logo_texture.png'
            ];
            foreach($possiblePaths as $p) {
                if(file_exists($p)) {
                    $logoPath = $p;
                    break;
                }
            }

            // Header
            $headerTable = $section->addTable(['borderSize' => 0, 'cellMargin' => 0]);
            $headerTable->addRow();
            
            $cellLogo = $headerTable->addCell(1000, ['valign' => 'center']);
            if ($logoPath) {
                $cellLogo->addImage($logoPath, ['width' => 45, 'height' => 45]);
            } else {
                $cellLogo->addText('♻', ['bold' => true, 'size' => 30, 'color' => '10B981']);
            }
            
            $cellText = $headerTable->addCell(9000, ['valign' => 'center']);
            $headerStyle = ['bold' => true, 'size' => 22, 'color' => '064E3B', 'name' => 'Calibri'];
            $cellText->addText('ZEROWASTE', $headerStyle);
            $cellText->addText($data['titulo'], ['bold' => true, 'size' => 12, 'color' => '059669']);
            $cellText->addText('Generado: ' . $data['fecha_generada'], ['size' => 9, 'color' => '6B7280', 'italic' => true]);
            
            $section->addTextBreak();

            // Metrics
            $metricsTable = $section->addTable(['borderSize' => 6, 'borderColor' => '10B981', 'cellMargin' => 80]);
            $cellStyle = ['bgColor' => 'F0FDF4', 'valign' => 'center'];
            $metricsTable->addRow();
            $metricsTable->addCell(2400, $cellStyle)->addText($data['total'] . ' Registros', ['bold' => true, 'size' => 11, 'color' => '064E3B']);
            $metricsTable->addCell(2400, $cellStyle)->addText('Desde: ' . $data['rango_inicio'], ['bold' => true, 'size' => 9, 'color' => '059669']);
            $metricsTable->addCell(2400, $cellStyle)->addText('Hasta: ' . $data['rango_fin'], ['bold' => true, 'size' => 9, 'color' => '059669']);
            $metricsTable->addCell(2400, $cellStyle)->addText('Módulo: ' . ucfirst($data['tipo']), ['bold' => true, 'size' => 9, 'color' => '064E3B']);
            $section->addTextBreak();

            // Section Title
            $section->addText('RESULTADOS DETALLADOS', ['bold' => true, 'size' => 11, 'color' => '064E3B', 'allCaps' => true]);
            $section->addTextBreak(0);

            // Data Table
            $thStyle = ['bgColor' => '064E3B', 'valign' => 'center'];
            $thFont = ['bold' => true, 'size' => 8, 'color' => 'FFFFFF'];
            $tdFont = ['size' => 9, 'color' => '374151'];
            $tdBold = ['size' => 9, 'color' => '064E3B', 'bold' => true];

            $table = $section->addTable(['borderSize' => 4, 'borderColor' => 'E5E7EB', 'cellMargin' => 60]);

            // Headers
            $table->addRow();
            $table->addCell(500, $thStyle)->addText('#', $thFont);
            if ($data['tipo'] === 'usuarios') {
                $table->addCell(2200, $thStyle)->addText('USUARIO', $thFont);
                $table->addCell(2200, $thStyle)->addText('EMAIL', $thFont);
                $table->addCell(1500, $thStyle)->addText('UBICACIÓN', $thFont);
                $table->addCell(1000, $thStyle)->addText('ROL', $thFont);
                $table->addCell(1000, $thStyle)->addText('ESTADO', $thFont);
                $table->addCell(1200, $thStyle)->addText('REGISTRO', $thFont);
            } elseif ($data['tipo'] === 'campanas') {
                $table->addCell(2500, $thStyle)->addText('CAMPAÑA', $thFont);
                $table->addCell(1500, $thStyle)->addText('TIPO', $thFont);
                $table->addCell(3000, $thStyle)->addText('DESCRIPCIÓN', $thFont);
                $table->addCell(1000, $thStyle)->addText('ESTADO', $thFont);
                $table->addCell(1200, $thStyle)->addText('CREADA', $thFont);
            } elseif ($data['tipo'] === 'mapa') {
                $table->addCell(2200, $thStyle)->addText('PUNTO', $thFont);
                $table->addCell(1500, $thStyle)->addText('TIPO', $thFont);
                $table->addCell(2500, $thStyle)->addText('DIRECCIÓN', $thFont);
                $table->addCell(1500, $thStyle)->addText('MATERIALES', $thFont);
                $table->addCell(1500, $thStyle)->addText('COORDENADAS', $thFont);
            } elseif ($data['tipo'] === 'eventos') {
                $table->addCell(2500, $thStyle)->addText('EVENTO', $thFont);
                $table->addCell(1500, $thStyle)->addText('TIPO', $thFont);
                $table->addCell(2500, $thStyle)->addText('UBICACIÓN', $thFont);
                $table->addCell(2200, $thStyle)->addText('FECHA', $thFont);
            } elseif ($data['tipo'] === 'foro') {
                $table->addCell(2500, $thStyle)->addText('TÍTULO', $thFont);
                $table->addCell(1500, $thStyle)->addText('CATEGORÍA', $thFont);
                $table->addCell(2500, $thStyle)->addText('AUTOR', $thFont);
                $table->addCell(2200, $thStyle)->addText('FECHA', $thFont);
            }

            // Data rows
            foreach ($data['registros'] as $idx => $item) {
                $rowBg = $idx % 2 === 1 ? ['bgColor' => 'F9FAFB'] : [];
                $table->addRow();
                $table->addCell(500, $rowBg)->addText($idx + 1, ['size' => 8, 'color' => '9CA3AF', 'bold' => true]);

                if ($data['tipo'] === 'usuarios') {
                    $table->addCell(2200, $rowBg)->addText($item->nombre, $tdBold);
                    $table->addCell(2200, $rowBg)->addText($item->email, $tdFont);
                    $table->addCell(1500, $rowBg)->addText($item->ubicacion ?? '—', $tdFont);
                    $table->addCell(1000, $rowBg)->addText($item->is_admin ? 'Admin' : 'Usuario', ['size' => 8, 'bold' => true, 'color' => $item->is_admin ? '6B21A8' : '065F46']);
                    $table->addCell(1000, $rowBg)->addText(($item->bloqueado ?? false) ? 'Bloqueado' : 'Activo', ['size' => 8, 'bold' => true, 'color' => ($item->bloqueado ?? false) ? '991B1B' : '065F46']);
                    $table->addCell(1200, $rowBg)->addText($item->created_at ? Carbon::parse($item->created_at)->format('d/m/Y') : '—', $tdFont);
                } elseif ($data['tipo'] === 'campanas') {
                    $table->addCell(2500, $rowBg)->addText($item->nombre, $tdBold);
                    $table->addCell(1500, $rowBg)->addText($item->tipo_etiqueta ?? 'General', ['size' => 8, 'bold' => true, 'color' => '1E40AF']);
                    $table->addCell(3000, $rowBg)->addText(mb_strimwidth($item->descripcion ?? '', 0, 60, '...'), $tdFont);
                    $table->addCell(1000, $rowBg)->addText(($item->activa ?? false) ? 'Activa' : 'Inactiva', ['size' => 8, 'bold' => true, 'color' => ($item->activa ?? false) ? '065F46' : '991B1B']);
                    $table->addCell(1200, $rowBg)->addText($item->created_at ? Carbon::parse($item->created_at)->format('d/m/Y') : '—', $tdFont);
                } elseif ($data['tipo'] === 'mapa') {
                    $table->addCell(2200, $rowBg)->addText($item->nombre, $tdBold);
                    $table->addCell(1500, $rowBg)->addText(mb_strimwidth($item->tipo, 0, 20, ''), ['size' => 8, 'bold' => true, 'color' => '92400E']);
                    $table->addCell(2500, $rowBg)->addText(mb_strimwidth($item->direccion ?? '', 0, 50, '...'), $tdFont);
                    $table->addCell(1500, $rowBg)->addText(mb_strimwidth($item->materiales ?? '—', 0, 30, '...'), $tdFont);
                    $table->addCell(1500, $rowBg)->addText(number_format($item->latitud, 4) . ', ' . number_format($item->longitud, 4), ['size' => 8, 'color' => '6B7280', 'name' => 'Courier New']);
                } elseif ($data['tipo'] === 'eventos') {
                    $table->addCell(2500, $rowBg)->addText($item->titulo ?? $item->nombre ?? 'Evento', $tdBold);
                    $table->addCell(1500, $rowBg)->addText($item->tipo ?? 'General', ['size' => 8, 'bold' => true, 'color' => '6B21A8']);
                    $table->addCell(2500, $rowBg)->addText($item->ubicacion ?? $item->lugar ?? '—', $tdFont);
                    $table->addCell(2200, $rowBg)->addText($item->fecha_inicio ? Carbon::parse($item->fecha_inicio)->format('d/m/Y H:i') : '—', $tdFont);
                } elseif ($data['tipo'] === 'foro') {
                    $table->addCell(2500, $rowBg)->addText($item->titulo ?? 'Post', $tdBold);
                    $catNombre = $item->categoria->nombre ?? 'Sin Categoría';
                    $catLower = mb_strtolower($catNombre);
                    $catColor = '059669'; // verde default
                    if (str_contains($catLower, 'reciclaje')) $catColor = 'D97706'; // Ambar
                    elseif (str_contains($catLower, 'reducci') || str_contains($catLower, 'residuos')) $catColor = '0891B2'; // Cyan
                    elseif (str_contains($catLower, 'eventos')) $catColor = '7C3AED'; // Violeta
                    elseif (str_contains($catLower, 'dudas') || str_contains($catLower, 'preguntas')) $catColor = 'E11D48'; // Rosa
                    
                    $table->addCell(1500, $rowBg)->addText($catNombre, ['size' => 8, 'bold' => true, 'color' => $catColor]);
                    $table->addCell(2500, $rowBg)->addText($item->autor->nombre ?? 'Desconocido', $tdFont);
                    $table->addCell(2200, $rowBg)->addText($item->created_at ? Carbon::parse($item->created_at)->format('d/m/Y H:i') : '—', $tdFont);
                }
            }

            // Footer
            $section->addTextBreak();
            $section->addText('Total de registros: ' . $data['total'], ['bold' => true, 'size' => 9, 'color' => '065F46']);
            $section->addText('© ' . date('Y') . ' ZeroWaste — Plataforma de Sostenibilidad', ['size' => 8, 'color' => '9CA3AF', 'italic' => true]);

            $tempFile = tempnam(sys_get_temp_dir(), 'word_');
            $writer = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'Word2007');
            $writer->save($tempFile);

            return response()->download($tempFile, $filename)->deleteFileAfterSend(true);
        }
    }
}

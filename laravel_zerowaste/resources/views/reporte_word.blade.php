<!DOCTYPE html>
<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:w="urn:schemas-microsoft-com:office:word" xmlns="http://www.w3.org/TR/REC-html40" lang="es">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <!--[if gte mso 9]>
    <xml>
        <w:WordDocument>
            <w:View>Print</w:View>
            <w:Zoom>100</w:Zoom>
        </w:WordDocument>
    </xml>
    <![endif]-->
    <style>
        @page {
            size: letter;
            margin: 2cm 2.5cm;
        }
        body {
            font-family: 'Calibri', 'Segoe UI', Arial, sans-serif;
            color: #1F2937;
            font-size: 11pt;
            line-height: 1.5;
            margin: 0;
            padding: 0;
        }

        /* Header */
        .header-table { width: 100%; border: none; border-collapse: collapse; margin-bottom: 15pt; }
        .header-table td { border: none; vertical-align: middle; padding: 0; }
        .brand-name { font-size: 22pt; font-weight: bold; color: #064E3B; letter-spacing: 1pt; }
        .report-title { font-size: 11pt; color: #059669; font-weight: bold; margin-top: 3pt; }
        .date-tag { font-size: 9pt; color: #6B7280; text-align: right; }

        /* Separator */
        .sep { border: none; border-top: 3px solid #10B981; margin: 10pt 0; }

        /* Metrics */
        .metrics-table { width: 100%; border: 2px solid #10B981; border-collapse: collapse; margin-bottom: 15pt; }
        .metrics-table td { text-align: center; padding: 8pt 12pt; border: 1px solid #D1FAE5; background-color: #F0FDF4; }
        .metric-val { font-size: 16pt; font-weight: bold; color: #064E3B; display: block; }
        .metric-lbl { font-size: 7pt; text-transform: uppercase; font-weight: bold; letter-spacing: 1pt; color: #6B7280; }

        /* Data Table */
        table.data { width: 100%; border-collapse: collapse; font-size: 9pt; margin-top: 8pt; }
        table.data th {
            padding: 8pt 10pt;
            text-align: left;
            font-size: 8pt;
            text-transform: uppercase;
            font-weight: bold;
            color: #FFFFFF;
            background-color: #064E3B;
            border: 1px solid #064E3B;
            letter-spacing: 0.5pt;
        }
        table.data td {
            padding: 7pt 10pt;
            border: 1px solid #E5E7EB;
            color: #374151;
            vertical-align: middle;
        }
        table.data tr.even { background-color: #F9FAFB; }

        /* Badges */
        .badge { padding: 2pt 6pt; font-size: 8pt; font-weight: bold; }
        .badge-green { background-color: #D1FAE5; color: #065F46; }
        .badge-blue { background-color: #DBEAFE; color: #1E40AF; }
        .badge-yellow { background-color: #FEF3C7; color: #92400E; }
        .badge-purple { background-color: #F3E8FF; color: #6B21A8; }
        .badge-red { background-color: #FEE2E2; color: #991B1B; }
        .badge-gray { background-color: #F3F4F6; color: #4B5563; }
        .active { color: #065F46; font-weight: bold; }
        .inactive { color: #991B1B; font-weight: bold; }
        .role-admin { background-color: #F3E8FF; color: #6B21A8; padding: 2pt 6pt; font-size: 8pt; font-weight: bold; }
        .role-user { background-color: #D1FAE5; color: #065F46; padding: 2pt 6pt; font-size: 8pt; font-weight: bold; }

        /* Footer */
        .footer-table { width: 100%; border: none; border-collapse: collapse; margin-top: 20pt; }
        .footer-table td { border: none; font-size: 8pt; color: #9CA3AF; padding: 5pt 0; }
    </style>
</head>
<body>
    {{-- HEADER --}}
    <table class="header-table">
        <tr>
            <td>
                <span class="brand-name">ZEROWASTE</span>
                <div class="report-title">{{ $titulo }}</div>
            </td>
            <td class="date-tag">
                Generado: {{ $fecha_generada }}
            </td>
        </tr>
    </table>

    <hr class="sep">

    {{-- METRICS BAR --}}
    <table class="metrics-table">
        <tr>
            <td>
                <span class="metric-val">{{ $total }}</span>
                <span class="metric-lbl">Registros</span>
            </td>
            <td>
                <span class="metric-val" style="color: #059669;">{{ $rango_inicio }}</span>
                <span class="metric-lbl">Desde</span>
            </td>
            <td>
                <span class="metric-val" style="color: #059669;">{{ $rango_fin }}</span>
                <span class="metric-lbl">Hasta</span>
            </td>
            <td>
                <span class="metric-val" style="font-size: 12pt;">{{ ucfirst($tipo) }}</span>
                <span class="metric-lbl">Módulo</span>
            </td>
        </tr>
    </table>

    {{-- SECTION TITLE --}}
    <p style="font-size: 10pt; font-weight: bold; color: #064E3B; text-transform: uppercase; letter-spacing: 1pt; margin: 12pt 0 6pt 0; border-bottom: 2px solid #10B981; padding-bottom: 4pt;">
        &#9851; RESULTADOS DETALLADOS
    </p>

    {{-- DATA TABLE --}}
    <table class="data">
        <thead>
            <tr>
                @if($tipo === 'usuarios')
                    <th style="width:4%">#</th><th style="width:22%">Usuario</th><th style="width:22%">Email</th><th>Ubicación</th><th>Rol</th><th>Estado</th><th style="text-align:right">Registro</th>
                @elseif($tipo === 'campanas')
                    <th style="width:4%">#</th><th style="width:22%">Campaña</th><th>Clasificación</th><th>Descripción</th><th>Estado</th><th style="text-align:right">Creada</th>
                @elseif($tipo === 'mapa')
                    <th style="width:4%">#</th><th style="width:22%">Punto de Acopio</th><th>Tipo</th><th>Dirección</th><th>Materiales</th><th style="text-align:right">Coordenadas</th>
                @elseif($tipo === 'eventos')
                    <th style="width:4%">#</th><th style="width:22%">Evento</th><th>Tipo</th><th>Ubicación</th><th style="text-align:right">Fecha</th>
                @endif
            </tr>
        </thead>
        <tbody>
            @forelse($registros as $idx => $item)
                <tr class="{{ $idx % 2 == 1 ? 'even' : '' }}">
                    @if($tipo === 'usuarios')
                        <td style="color: #9CA3AF; font-weight: bold;">{{ $idx + 1 }}</td>
                        <td>
                            <strong style="color: #064E3B;">{{ $item->nombre }}</strong><br>
                            <span style="font-size:8pt; color:#9CA3AF;">{{ $item->titulo_perfil ?? 'Ecologista' }}</span>
                        </td>
                        <td style="font-size: 9pt;">{{ $item->email }}</td>
                        <td>{{ $item->ubicacion ?? '—' }}</td>
                        <td><span class="{{ $item->is_admin ? 'role-admin' : 'role-user' }}">{{ $item->is_admin ? 'Admin' : 'Usuario' }}</span></td>
                        <td><span class="{{ ($item->bloqueado ?? false) ? 'inactive' : 'active' }}">{{ ($item->bloqueado ?? false) ? '● Bloqueado' : '● Activo' }}</span></td>
                        <td style="text-align:right;">{{ $item->created_at ? \Illuminate\Support\Carbon::parse($item->created_at)->format('d/m/Y') : '—' }}</td>
                    @elseif($tipo === 'campanas')
                        <td style="color: #9CA3AF; font-weight: bold;">{{ $idx + 1 }}</td>
                        <td><strong style="color: #064E3B;">{{ $item->nombre }}</strong><br><span style="font-size:8pt; color:#9CA3AF;">{{ mb_strimwidth($item->lugar ?? '', 0, 30, '...') }}</span></td>
                        <td><span class="badge badge-blue">{{ $item->tipo_etiqueta ?? 'General' }}</span></td>
                        <td style="font-size: 9pt;">{{ mb_strimwidth($item->descripcion ?? '', 0, 50, '...') }}</td>
                        <td><span class="{{ ($item->activa ?? false) ? 'active' : 'inactive' }}">{{ ($item->activa ?? false) ? '● Activa' : '● Inactiva' }}</span></td>
                        <td style="text-align:right;">{{ $item->created_at ? \Illuminate\Support\Carbon::parse($item->created_at)->format('d/m/Y') : '—' }}</td>
                    @elseif($tipo === 'mapa')
                        <td style="color: #9CA3AF; font-weight: bold;">{{ $idx + 1 }}</td>
                        <td><strong style="color: #064E3B;">{{ $item->nombre }}</strong></td>
                        <td><span class="badge badge-yellow">{{ mb_strimwidth($item->tipo, 0, 18, '') }}</span></td>
                        <td style="font-size: 9pt;">{{ mb_strimwidth($item->direccion ?? '', 0, 45, '...') }}</td>
                        <td style="font-size: 9pt;">{{ mb_strimwidth($item->materiales ?? '—', 0, 25, '...') }}</td>
                        <td style="text-align:right; font-family: 'Courier New', monospace; font-size:8pt; color:#6B7280;">{{ number_format($item->latitud, 4) }}, {{ number_format($item->longitud, 4) }}</td>
                    @elseif($tipo === 'eventos')
                        <td style="color: #9CA3AF; font-weight: bold;">{{ $idx + 1 }}</td>
                        <td><strong style="color: #064E3B;">{{ $item->titulo ?? $item->nombre ?? 'Evento' }}</strong><br><span style="font-size:8pt; color:#9CA3AF;">{{ mb_strimwidth($item->descripcion ?? '', 0, 40, '...') }}</span></td>
                        <td><span class="badge">{{ $item->tipo ?? 'General' }}</span></td>
                        <td>{{ $item->ubicacion ?? $item->lugar ?? '—' }}</td>
                        <td style="text-align:right;">{{ $item->fecha_inicio ? \Illuminate\Support\Carbon::parse($item->fecha_inicio)->format('d/m/Y H:i') : '—' }}</td>
                    @endif
                </tr>
            @empty
                <tr><td colspan="7" style="text-align: center; padding: 20pt; color: #9CA3AF; font-style: italic;">No se encontraron datos en el rango seleccionado.</td></tr>
            @endforelse
        </tbody>
    </table>

    {{-- FOOTER --}}
    @if($registros->count() > 0)
    <table class="footer-table">
        <tr>
            <td style="color: #065F46; font-weight: bold; font-size: 9pt;">
                &#9851; Total de registros: <strong>{{ $total }}</strong>
            </td>
            <td style="text-align: right;">
                &copy; {{ date('Y') }} ZeroWaste &mdash; Plataforma de Sostenibilidad &bull; zerowaste-qro.com
            </td>
        </tr>
    </table>
    @endif
</body>
</html>

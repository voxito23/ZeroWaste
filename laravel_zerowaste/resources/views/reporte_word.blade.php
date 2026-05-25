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
            margin: 2cm 2cm;
        }
        body {
            font-family: 'Helvetica Neue', Arial, sans-serif;
            color: #1E293B;
            font-size: 10pt;
            line-height: 1.6;
            margin: 0;
            padding: 0;
        }

        /* Header */
        .header-table { width: 100%; border: none; border-collapse: collapse; margin-bottom: 20pt; background-color: #ECFDF5; border-bottom: 2px solid #10B981;}
        .header-table td { border: none; vertical-align: middle; padding: 15pt 20pt; }
        .brand-name { font-size: 24pt; font-weight: bold; color: #064E3B; letter-spacing: 1pt; }
        .report-title { font-size: 12pt; color: #059669; font-weight: bold; margin-top: 3pt; }
        .date-tag { font-size: 9pt; color: #475569; text-align: right; background-color: #FFFFFF; padding: 4pt 8pt; border: 1px solid #10B981; border-radius: 4pt; }

        /* Metrics */
        .metrics-table { width: 100%; border: none; border-collapse: collapse; margin-bottom: 20pt; }
        .metrics-table td { text-align: center; padding: 10pt; border: 1px solid #E2E8F0; background-color: #FFFFFF; }
        .metric-val { font-size: 18pt; font-weight: bold; color: #10B981; display: block; }
        .metric-lbl { font-size: 8pt; text-transform: uppercase; font-weight: bold; letter-spacing: 1pt; color: #94A3B8; }

        /* Data Table */
        table.data { width: 100%; border-collapse: collapse; font-size: 9pt; margin-top: 10pt; border: 1px solid #E2E8F0; }
        table.data th {
            padding: 10pt;
            text-align: left;
            font-size: 8pt;
            text-transform: uppercase;
            font-weight: bold;
            color: #64748B;
            background-color: #F8FAFC;
            border-bottom: 2px solid #E2E8F0;
            letter-spacing: 0.5pt;
        }
        table.data td {
            padding: 10pt;
            border-bottom: 1px solid #F1F5F9;
            color: #334155;
            vertical-align: middle;
        }
        table.data tr.even { background-color: #F8FAFC; }

        /* Badges */
        .badge { padding: 4pt 8pt; font-size: 8pt; font-weight: bold; border-radius: 8pt; border: 1px solid #E2E8F0;}
        .badge-green { background-color: #ECFDF5; color: #059669; border-color: #A7F3D0;}
        .badge-blue { background-color: #EFF6FF; color: #2563EB; border-color: #BFDBFE;}
        .badge-yellow { background-color: #FFFBEB; color: #D97706; border-color: #FDE68A;}
        .badge-purple { background-color: #F5F3FF; color: #7C3AED; border-color: #DDD6FE;}
        .badge-red { background-color: #FEF2F2; color: #DC2626; border-color: #FECACA;}
        
        .active { color: #059669; font-weight: bold; }
        .inactive { color: #DC2626; font-weight: bold; }

        /* Footer */
        .footer-table { width: 100%; border: none; border-collapse: collapse; margin-top: 30pt; border-top: 1px solid #E2E8F0; }
        .footer-table td { border: none; font-size: 8pt; color: #94A3B8; padding: 10pt 0; }
    </style>
</head>
<body>
    {{-- HEADER --}}
    <table class="header-table">
        <tr>
            <td>
                <span class="brand-name">&#9851; ZEROWASTE</span>
                <div class="report-title">Reporte Oficial de {{ ucfirst($tipo) }}</div>
            </td>
            <td style="text-align: right;">
                <span class="date-tag">Generado: {{ $fecha_generada }}</span>
            </td>
        </tr>
    </table>

    {{-- METRICS BAR --}}
    <table class="metrics-table">
        <tr>
            <td>
                <span class="metric-val">{{ $total }}</span>
                <span class="metric-lbl">Registros</span>
            </td>
            <td>
                <span class="metric-val">{{ $rango_inicio }}</span>
                <span class="metric-lbl">Desde</span>
            </td>
            <td>
                <span class="metric-val">{{ $rango_fin }}</span>
                <span class="metric-lbl">Hasta</span>
            </td>
        </tr>
    </table>

    {{-- SECTION TITLE --}}
    <p style="font-size: 14pt; font-weight: bold; color: #0F172A; margin: 15pt 0 10pt 0;">
        <span style="color:#10B981;">&#9851;</span> Resultados Detallados
    </p>

    {{-- DATA TABLE --}}
    <table class="data">
        <thead>
            <tr>
                @if($tipo === 'usuarios')
                    <th style="width:4%">#</th><th style="width:25%">Usuario</th><th style="width:22%">Email</th><th>Ubicación</th><th>Rol</th><th>Estado</th><th style="text-align:right">Registro</th>
                @elseif($tipo === 'campanas')
                    <th style="width:4%">#</th><th style="width:25%">Campaña</th><th>Clasificación</th><th>Descripción</th><th>Estado</th><th style="text-align:right">Creada</th>
                @elseif($tipo === 'mapa')
                    <th style="width:4%">#</th><th style="width:25%">Punto de Acopio</th><th>Tipo</th><th>Dirección</th><th>Materiales</th><th style="text-align:right">Coordenadas</th>
                @elseif($tipo === 'eventos')
                    <th style="width:4%">#</th><th style="width:25%">Evento</th><th>Tipo</th><th>Ubicación</th><th style="text-align:right">Fecha</th>
                @elseif($tipo === 'foro')
                    <th style="width:4%">#</th><th style="width:30%">Post</th><th>Categoría</th><th>Autor</th><th style="text-align:right">Fecha</th>
                @endif
            </tr>
        </thead>
        <tbody>
            @forelse($registros as $idx => $item)
                <tr class="{{ $idx % 2 == 1 ? 'even' : '' }}">
                    @if($tipo === 'usuarios')
                        <td style="color: #94A3B8; font-weight: bold;">{{ $idx + 1 }}</td>
                        <td>
                            <strong style="color: #0F172A; font-size:10pt;">{{ $item->nombre }}</strong><br>
                            <span style="font-size:8pt; color:#94A3B8;">{{ $item->titulo_perfil ?? 'Ecologista' }}</span>
                        </td>
                        <td><span style="color:#475569;">{{ $item->email }}</span></td>
                        <td style="color:#64748B;">{{ $item->ubicacion ?? '—' }}</td>
                        <td><span class="badge {{ $item->is_admin ? 'badge-purple' : 'badge-green' }}">{{ $item->is_admin ? 'Admin' : 'Usuario' }}</span></td>
                        <td><span class="{{ ($item->bloqueado ?? false) ? 'inactive' : 'active' }}">{{ ($item->bloqueado ?? false) ? '● Bloqueado' : '● Activo' }}</span></td>
                        <td style="text-align:right; color:#64748B;">{{ $item->created_at ? \Illuminate\Support\Carbon::parse($item->created_at)->format('d/m/Y') : '—' }}</td>
                    @elseif($tipo === 'campanas')
                        <td style="color: #94A3B8; font-weight: bold;">{{ $idx + 1 }}</td>
                        <td>
                            <strong style="color: #0F172A; font-size:10pt;">{{ $item->nombre }}</strong><br>
                            <span style="font-size:8pt; color:#94A3B8;">{{ mb_strimwidth($item->lugar ?? '', 0, 30, '...') }}</span>
                        </td>
                        <td><span class="badge badge-blue">{{ $item->tipo_etiqueta ?? 'General' }}</span></td>
                        <td style="color:#64748B;">{{ mb_strimwidth($item->descripcion ?? '', 0, 60, '...') }}</td>
                        <td><span class="{{ ($item->activa ?? false) ? 'active' : 'inactive' }}">{{ ($item->activa ?? false) ? '● Activa' : '● Inactiva' }}</span></td>
                        <td style="text-align:right; color:#64748B;">{{ $item->created_at ? \Illuminate\Support\Carbon::parse($item->created_at)->format('d/m/Y') : '—' }}</td>
                    @elseif($tipo === 'mapa')
                        <td style="color: #94A3B8; font-weight: bold;">{{ $idx + 1 }}</td>
                        <td><strong style="color: #0F172A; font-size:10pt;">{{ $item->nombre }}</strong></td>
                        <td><span class="badge badge-yellow">{{ mb_strimwidth($item->tipo, 0, 18, '') }}</span></td>
                        <td style="color:#64748B;">{{ mb_strimwidth($item->direccion ?? '', 0, 45, '...') }}</td>
                        <td style="color:#64748B;">{{ mb_strimwidth($item->materiales ?? '—', 0, 25, '...') }}</td>
                        <td style="text-align:right; font-family: 'Courier New', monospace; font-size:8pt; color:#94A3B8;">{{ number_format($item->latitud, 4) }}, {{ number_format($item->longitud, 4) }}</td>
                    @elseif($tipo === 'eventos')
                        <td style="color: #94A3B8; font-weight: bold;">{{ $idx + 1 }}</td>
                        <td>
                            <strong style="color: #0F172A; font-size:10pt;">{{ $item->titulo ?? $item->nombre ?? 'Evento' }}</strong><br>
                            <span style="font-size:8pt; color:#94A3B8;">{{ mb_strimwidth($item->descripcion ?? '', 0, 40, '...') }}</span>
                        </td>
                        <td><span class="badge badge-purple">{{ $item->tipo ?? 'General' }}</span></td>
                        <td style="color:#64748B;">{{ $item->ubicacion ?? $item->lugar ?? '—' }}</td>
                        <td style="text-align:right; color:#64748B;">{{ $item->fecha_inicio ? \Illuminate\Support\Carbon::parse($item->fecha_inicio)->format('d/m/Y H:i') : '—' }}</td>
                    @elseif($tipo === 'foro')
                        <td style="color: #94A3B8; font-weight: bold;">{{ $idx + 1 }}</td>
                        <td>
                            <strong style="color: #0F172A; font-size:10pt;">{{ $item->titulo ?? 'Post' }}</strong><br>
                            <span style="font-size:8pt; color:#94A3B8;">{{ mb_strimwidth(strip_tags($item->contenido ?? ''), 0, 50, '...') }}</span>
                        </td>
                        <td><span class="badge badge-green">{{ mb_strimwidth($item->categoria->nombre ?? 'Sin Categoría', 0, 15, '') }}</span></td>
                        <td style="color:#475569;">{{ $item->autor->nombre ?? 'Usuario Eliminado' }}</td>
                        <td style="text-align:right; color:#64748B;">{{ $item->created_at ? \Illuminate\Support\Carbon::parse($item->created_at)->format('d/m/Y H:i') : '—' }}</td>
                    @endif
                </tr>
            @empty
                <tr><td colspan="7" style="text-align: center; padding: 20pt; color: #94A3B8; font-style: italic;">No se encontraron datos en el rango seleccionado.</td></tr>
            @endforelse
        </tbody>
    </table>

    {{-- FOOTER --}}
    @if($registros->count() > 0)
    <table class="footer-table">
        <tr>
            <td style="color: #64748B; font-weight: bold; font-size: 9pt;">
                Total de registros: <span style="color:#10B981;">{{ $total }}</span>
            </td>
            <td style="text-align: right;">
                &copy; {{ date('Y') }} ZeroWaste &mdash; Reporte generado automáticamente.
            </td>
        </tr>
    </table>
    @endif
</body>
</html>

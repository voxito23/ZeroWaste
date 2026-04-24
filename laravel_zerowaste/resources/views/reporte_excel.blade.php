<!DOCTYPE html>
<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40" lang="es">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">

    <style>
        body { font-family: 'Montserrat', 'Calibri', 'Segoe UI', Arial, sans-serif; color: #1F2937; margin: 0; padding: 0; }
        
        /* Header */
        .header-table { width: 100%; border: none; margin-bottom: 8px; }
        .header-table td { border: none; vertical-align: middle; }
        .brand-name { font-size: 22px; font-weight: 800; color: #064E3B; letter-spacing: 1px; }
        .report-title { font-size: 13px; color: #059669; font-weight: 600; margin-top: 2px; }
        .date-tag { font-size: 10px; color: #6B7280; text-align: right; }
        
        /* Metrics row */
        .metrics-table { width: 100%; border: 2px solid #10B981; border-collapse: collapse; margin-bottom: 16px; }
        .metrics-table td { text-align: center; padding: 10px 15px; border: 1px solid #D1FAE5; background: #F0FDF4; }
        .metric-val { font-size: 18px; font-weight: 800; color: #064E3B; display: block; }
        .metric-lbl { font-size: 8px; text-transform: uppercase; font-weight: 700; letter-spacing: 1px; color: #6B7280; }
        
        /* Separator */
        .sep { height: 3px; background: linear-gradient(to right, #10B981, #059669); border: none; margin: 10px 0; }
        
        /* Data Table */
        table.data { width: 100%; border-collapse: collapse; font-size: 11px; margin-top: 8px; }
        table.data thead { background: #064E3B; }
        table.data th { padding: 10px 12px; text-align: left; font-size: 10px; text-transform: uppercase; font-weight: 700; color: #FFFFFF; border: 1px solid #064E3B; letter-spacing: 0.5px; }
        table.data td { padding: 9px 12px; border: 1px solid #E5E7EB; color: #374151; vertical-align: middle; }
        table.data tr:nth-child(even) { background-color: #F9FAFB; }
        table.data tr:nth-child(odd) { background-color: #FFFFFF; }
        
        /* Status badges */
        .active { color: #065F46; font-weight: 700; }
        .inactive { color: #991B1B; font-weight: 700; }
        .role-admin { background: #F3E8FF; color: #6B21A8; padding: 3px 8px; font-size: 9px; font-weight: 700; }
        .role-user { background: #D1FAE5; color: #065F46; padding: 3px 8px; font-size: 9px; font-weight: 700; }
        .badge { background: #F3F4F6; color: #4B5563; padding: 3px 8px; font-size: 9px; font-weight: 700; }
        .badge-green { background: #D1FAE5; color: #065F46; }
        .badge-blue { background: #DBEAFE; color: #1E40AF; }
        .badge-yellow { background: #FEF3C7; color: #92400E; }
        
        /* Footer */
        .footer-table { width: 100%; border: none; margin-top: 15px; }
        .footer-table td { border: none; font-size: 9px; color: #9CA3AF; }
    </style>
</head>
<body>
    {{-- HEADER --}}
    <table class="header-table">
        <tr>
            <td style="width: 30px; text-align: center; font-size: 18px; color: #10B981; font-weight: bold;">♻</td>
            <td>
                <span class="brand-name">ZEROWASTE</span>
                <div class="report-title">{{ $titulo }}</div>
            </td>
            <td class="date-tag">
                Generado: {{ $fecha_generada }}
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
                <span class="metric-val" style="color: #059669;">{{ $rango_inicio }}</span>
                <span class="metric-lbl">Desde</span>
            </td>
            <td>
                <span class="metric-val" style="color: #059669;">{{ $rango_fin }}</span>
                <span class="metric-lbl">Hasta</span>
            </td>
            <td>
                <span class="metric-val" style="font-size: 14px;">{{ ucfirst($tipo) }}</span>
                <span class="metric-lbl">Módulo</span>
            </td>
        </tr>
    </table>

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
                <tr>
                    @if($tipo === 'usuarios')
                        <td style="color: #9CA3AF; font-weight: 600;">{{ $idx + 1 }}</td>
                        <td>
                            <strong style="color: #064E3B;">{{ $item->nombre }}</strong><br>
                            <span style="font-size:9px; color:#9CA3AF;">{{ $item->titulo_perfil ?? 'Ecologista' }}</span>
                        </td>
                        <td style="font-size: 10px;">{{ $item->email }}</td>
                        <td>{{ $item->ubicacion ?? '—' }}</td>
                        <td><span class="{{ $item->is_admin ? 'role-admin' : 'role-user' }}">{{ $item->is_admin ? 'Admin' : 'Usuario' }}</span></td>
                        <td><span class="{{ ($item->bloqueado ?? false) ? 'inactive' : 'active' }}">{{ ($item->bloqueado ?? false) ? '● Bloqueado' : '● Activo' }}</span></td>
                        <td style="text-align:right;">{{ $item->created_at ? \Illuminate\Support\Carbon::parse($item->created_at)->format('d/m/Y') : '—' }}</td>
                    @elseif($tipo === 'campanas')
                        <td style="color: #9CA3AF; font-weight: 600;">{{ $idx + 1 }}</td>
                        <td><strong style="color: #064E3B;">{{ $item->nombre }}</strong><br><span style="font-size:9px; color:#9CA3AF;">{{ mb_strimwidth($item->lugar ?? '', 0, 30, '...') }}</span></td>
                        <td><span class="badge badge-blue">{{ $item->tipo_etiqueta ?? 'General' }}</span></td>
                        <td style="font-size: 10px;">{{ mb_strimwidth($item->descripcion ?? '', 0, 50, '...') }}</td>
                        <td><span class="{{ ($item->activa ?? false) ? 'active' : 'inactive' }}">{{ ($item->activa ?? false) ? '● Activa' : '● Inactiva' }}</span></td>
                        <td style="text-align:right;">{{ $item->created_at ? \Illuminate\Support\Carbon::parse($item->created_at)->format('d/m/Y') : '—' }}</td>
                    @elseif($tipo === 'mapa')
                        <td style="color: #9CA3AF; font-weight: 600;">{{ $idx + 1 }}</td>
                        <td><strong style="color: #064E3B;">{{ $item->nombre }}</strong></td>
                        <td><span class="badge badge-yellow">{{ mb_strimwidth($item->tipo, 0, 18, '') }}</span></td>
                        <td style="font-size: 10px;">{{ mb_strimwidth($item->direccion ?? '', 0, 45, '...') }}</td>
                        <td style="font-size: 10px;">{{ mb_strimwidth($item->materiales ?? '—', 0, 25, '...') }}</td>
                        <td style="text-align:right; font-family: 'Courier New', monospace; font-size:9px; color:#6B7280; background:#F3F4F6;">{{ number_format($item->latitud, 4) }}, {{ number_format($item->longitud, 4) }}</td>
                    @elseif($tipo === 'eventos')
                        <td style="color: #9CA3AF; font-weight: 600;">{{ $idx + 1 }}</td>
                        <td><strong style="color: #064E3B;">{{ $item->titulo ?? $item->nombre ?? 'Evento' }}</strong><br><span style="font-size:9px; color:#9CA3AF;">{{ mb_strimwidth($item->descripcion ?? '', 0, 40, '...') }}</span></td>
                        <td><span class="badge">{{ $item->tipo ?? 'General' }}</span></td>
                        <td>{{ $item->ubicacion ?? $item->lugar ?? '—' }}</td>
                        <td style="text-align:right;">{{ $item->fecha_inicio ? \Illuminate\Support\Carbon::parse($item->fecha_inicio)->format('d/m/Y H:i') : '—' }}</td>
                    @endif
                </tr>
            @empty
                <tr><td colspan="7" style="text-align: center; padding: 30px; color: #9CA3AF; font-style: italic;">No se encontraron datos en el rango seleccionado.</td></tr>
            @endforelse
        </tbody>
    </table>

    {{-- FOOTER --}}
    @if($registros->count() > 0)
    <table class="footer-table">
        <tr>
            <td style="color: #065F46; font-weight: 700; font-size: 10px;">
                ♻ Total de registros: <strong>{{ $total }}</strong>
            </td>
            <td style="text-align: right;">
                © {{ date('Y') }} ZeroWaste — Plataforma de Sostenibilidad &bull; zerowaste-qro.com
            </td>
        </tr>
    </table>
    @endif
</body>
</html>

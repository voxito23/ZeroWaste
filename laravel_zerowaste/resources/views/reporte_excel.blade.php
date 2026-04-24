<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de {{ ucfirst($tipo) }}</title>
    <style>
        body { font-family: 'Montserrat', 'Calibri', 'Arial', sans-serif; color: #1F2937; font-size: 11px; margin: 0; padding: 0; }
        h1 { font-size: 20px; font-weight: 800; color: #064E3B; margin: 10px 20px; }
        .subtitle { font-size: 12px; color: #6B7280; margin: 0 20px 5px 20px; }
        .meta { font-size: 10px; color: #6B7280; margin: 5px 20px 15px 20px; }
        table { width: 100%; border-collapse: collapse; font-size: 11px; }
        th { background: #064E3B; color: #fff; padding: 10px 12px; text-align: left; font-size: 10px; text-transform: uppercase; font-weight: 700; letter-spacing: 0.5px; border: 1px solid #064E3B; }
        td { padding: 9px 12px; border: 1px solid #E5E7EB; color: #374151; vertical-align: middle; }
        tr:nth-child(even) { background-color: #F9FAFB; }
        .badge { padding: 3px 8px; border-radius: 8px; font-size: 9px; font-weight: 700; text-transform: uppercase; }
        .badge-green { background: #D1FAE5; color: #065F46; }
        .badge-purple { background: #F3E8FF; color: #6B21A8; }
        .badge-blue { background: #DBEAFE; color: #1E40AF; }
        .badge-yellow { background: #FEF3C7; color: #92400E; }
        .badge-gray { background: #F3F4F6; color: #4B5563; }
        .badge-red { background: #FEE2E2; color: #991B1B; }
        .footer { font-size: 9px; color: #9CA3AF; margin: 15px 20px; text-align: center; }
    </style>
</head>
<body>
    <h1>ZEROWASTE — {{ $titulo }}</h1>
    <p class="subtitle">Periodo: {{ $rango_inicio }} → {{ $rango_fin }} &bull; Total: {{ $total }} registros</p>
    <p class="meta">Generado: {{ $fecha_generada }}</p>

    <table>
        <thead>
            <tr>
                @if($tipo === 'usuarios')
                    <th>#</th><th>Nombre</th><th>Email</th><th>Ubicación</th><th>Rol</th><th>Estado</th><th>Registro</th>
                @elseif($tipo === 'campanas')
                    <th>#</th><th>Campaña</th><th>Clasificación</th><th>Descripción</th><th>Estado</th><th>Creada</th>
                @elseif($tipo === 'mapa')
                    <th>#</th><th>Punto de Acopio</th><th>Tipo</th><th>Dirección</th><th>Materiales</th><th>Coordenadas</th>
                @elseif($tipo === 'eventos')
                    <th>#</th><th>Evento</th><th>Tipo</th><th>Ubicación</th><th>Fecha</th>
                @endif
            </tr>
        </thead>
        <tbody>
            @forelse($registros as $idx => $item)
                <tr>
                    @if($tipo === 'usuarios')
                        <td>{{ $idx + 1 }}</td>
                        <td><strong>{{ $item->nombre }}</strong></td>
                        <td>{{ $item->email }}</td>
                        <td>{{ $item->ubicacion ?? '—' }}</td>
                        <td><span class="badge {{ $item->is_admin ? 'badge-purple' : 'badge-green' }}">{{ $item->is_admin ? 'Admin' : 'Usuario' }}</span></td>
                        <td>{{ ($item->bloqueado ?? false) ? '🔴 Bloqueado' : '🟢 Activo' }}</td>
                        <td>{{ $item->created_at ? \Illuminate\Support\Carbon::parse($item->created_at)->format('d/m/Y') : '—' }}</td>
                    @elseif($tipo === 'campanas')
                        <td>{{ $idx + 1 }}</td>
                        <td><strong>{{ $item->nombre }}</strong></td>
                        <td><span class="badge badge-blue">{{ $item->tipo_etiqueta ?? 'General' }}</span></td>
                        <td>{{ mb_strimwidth($item->descripcion ?? '', 0, 60, '...') }}</td>
                        <td>{{ ($item->activa ?? false) ? '🟢 Activa' : '🔴 Inactiva' }}</td>
                        <td>{{ $item->created_at ? \Illuminate\Support\Carbon::parse($item->created_at)->format('d/m/Y') : '—' }}</td>
                    @elseif($tipo === 'mapa')
                        <td>{{ $idx + 1 }}</td>
                        <td><strong>{{ $item->nombre }}</strong></td>
                        <td><span class="badge badge-yellow">{{ $item->tipo }}</span></td>
                        <td>{{ $item->direccion ?? '—' }}</td>
                        <td>{{ $item->materiales ?? '—' }}</td>
                        <td>{{ number_format($item->latitud, 4) }}, {{ number_format($item->longitud, 4) }}</td>
                    @elseif($tipo === 'eventos')
                        <td>{{ $idx + 1 }}</td>
                        <td><strong>{{ $item->titulo ?? $item->nombre ?? 'Evento' }}</strong></td>
                        <td><span class="badge badge-gray">{{ $item->tipo ?? 'General' }}</span></td>
                        <td>{{ $item->ubicacion ?? $item->lugar ?? '—' }}</td>
                        <td>{{ $item->fecha_inicio ? \Illuminate\Support\Carbon::parse($item->fecha_inicio)->format('d/m/Y H:i') : '—' }}</td>
                    @endif
                </tr>
            @empty
                <tr><td colspan="7" style="text-align: center; padding: 30px; color: #9CA3AF;">No se encontraron datos.</td></tr>
            @endforelse
        </tbody>
    </table>

    <p class="footer">© {{ date('Y') }} ZeroWaste — Plataforma de Sostenibilidad y Medio Ambiente &bull; zerowaste-qro.com</p>
</body>
</html>

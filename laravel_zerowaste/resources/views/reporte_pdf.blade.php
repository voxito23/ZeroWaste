<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de {{ ucfirst($tipo) }}</title>
    <style>
        @page { margin: 0cm 0cm; }
        body { 
            font-family: 'Helvetica', 'Arial', sans-serif; 
            color: #374151; 
            font-size: 11px; 
            line-height: 1.6;
            background-color: #ffffff;
            margin: 0;
            padding: 0;
        }

        .header { 
            background-color: #1A513E; 
            color: white; 
            padding: 30px 40px;
            position: relative;
            border-bottom: 6px solid #4ADE80;
        }
        
        .logo-container {
            position: absolute;
            top: 25px;
            left: 40px;
        }

        .header-content {
            margin-left: 65px;
        }

        .header h1 { 
            font-size: 26px; 
            font-weight: 800; 
            margin: 0; 
            letter-spacing: -0.5px;
            text-transform: uppercase;
        }
        
        .header p { 
            font-size: 13px; 
            color: #A7F3D0; 
            margin: 0;
            margin-top: 4px;
            font-weight: 400;
        }

        .content { 
            padding: 35px 40px; 
        }

        .summary-banner {
            background-color: #FFFFFF;
            border: 1px solid #E5E7EB;
            padding: 20px 25px;
            border-radius: 8px;
            margin-bottom: 35px;
        }

        .summary-item {
            margin-bottom: 8px;
            font-size: 12px;
            color: #4B5563;
        }
        
        .summary-item:last-child {
            margin-bottom: 0;
        }

        .summary-item svg {
            vertical-align: middle;
            margin-right: 6px;
            margin-bottom: 2px;
        }

        .summary-item b { 
            color: #111827; 
            font-weight: 700;
        }

        .section-title {
            font-size: 18px;
            font-weight: 800;
            color: #1A513E;
            margin-bottom: 15px;
            border-bottom: 1px solid #E5E7EB;
            padding-bottom: 10px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        table.data-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 11px;
            margin-top: 10px;
        }

        table.data-table thead {
            background-color: #F3F4F6;
        }

        table.data-table th {
            padding: 14px 15px;
            text-align: left;
            font-size: 9px;
            text-transform: uppercase;
            font-weight: 700;
            color: #6B7280;
            border: none;
            letter-spacing: 0.5px;
        }

        table.data-table td {
            padding: 14px 15px;
            border-bottom: 1px solid #F3F4F6;
            color: #374151;
            vertical-align: middle;
        }

        .item-title {
            color: #059669;
            font-weight: 700;
            font-size: 12px;
        }

        .item-subtitle {
            font-size: 9px;
            color: #6B7280;
            margin-top: 2px;
            display: block;
        }

        .badge {
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 9px;
            font-weight: 700;
            text-transform: uppercase;
            display: inline-block;
        }

        .badge-green { background-color: #D1FAE5; color: #065F46; }
        .badge-blue { background-color: #DBEAFE; color: #1E40AF; }
        .badge-yellow { background-color: #FEF3C7; color: #92400E; }
        .badge-gray { background-color: #F3F4F6; color: #4B5563; }
        .badge-purple { background-color: #F3E8FF; color: #6B21A8; }
        .badge-red { background-color: #FEE2E2; color: #991B1B; }

        .footer {
            position: fixed;
            bottom: 30px;
            left: 0;
            right: 0;
            text-align: center;
            font-size: 10px;
            color: #6B7280;
        }

        .td-right {
            text-align: right;
        }
        
        .th-right {
            text-align: right;
        }

    </style>
</head>
<body>
    @php
        // Cargar logotipo desde Flask directory (ZeroWaste shared project structure)
        $logoPath = app()->basePath('../flask_zerowaste/static/img/logo.png');
        $logoSrc = '';
        if (file_exists($logoPath)) {
            $logoData = base64_encode(file_get_contents($logoPath));
            $logoSrc = 'data:image/png;base64,' . $logoData;
        }

        // Helper function for badges based on types
        function getBadgeClass($text) {
            $text = strtolower($text);
            if (str_contains($text, 'acopio') || str_contains($text, 'reciclaje') || str_contains($text, 'positivo')) return 'badge-green';
            if (str_contains($text, 'educación') || str_contains($text, 'taller') || str_contains($text, 'usuario')) return 'badge-blue';
            if (str_contains($text, 'orgánico') || str_contains($text, 'mixto')) return 'badge-yellow';
            if (str_contains($text, 'admin')) return 'badge-purple';
            if (str_contains($text, 'negativo') || str_contains($text, 'peligroso')) return 'badge-red';
            return 'badge-gray';
        }
    @endphp

    <div class="header">
        @if($logoSrc)
        <div class="logo-container">
            <img src="{{ $logoSrc }}" width="50" height="50" alt="Logo">
        </div>
        @endif
        <div class="header-content">
            <h1>ZEROWASTE PLATAFORMA</h1>
            <p>{{ $titulo }}</p>
        </div>
    </div>

    <div class="content">
        <div class="summary-banner">
            <div class="summary-item">
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="#6B7280" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
                Reporte Generado: <b>{{ $fecha_generada }}</b>
            </div>
            <div class="summary-item">
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="#6B7280" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
                Rango de Datos: <b>{{ $rango_inicio }}</b> hasta <b>{{ $rango_fin }}</b>
            </div>
            <div class="summary-item">
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="#6B7280" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><line x1="3" y1="9" x2="21" y2="9"></line><line x1="9" y1="21" x2="9" y2="9"></line></svg>
                Total de Registros Encontrados: <b>{{ $total }}</b>
            </div>
        </div>

        <div class="section-title">RESULTADOS DETALLADOS</div>
        
        <table class="data-table">
            <thead>
                <tr>
                    @if($tipo === 'usuarios')
                        <th>USUARIO</th>
                        <th>EMAIL</th>
                        <th>UBICACIÓN</th>
                        <th>ROL</th>
                        <th class="th-right">FECHA DE INGRESO</th>
                    @elseif($tipo === 'campanas')
                        <th>CAMPAÑA</th>
                        <th>CLASIFICACIÓN</th>
                        <th>LUGAR / DESCRIPCIÓN</th>
                        <th class="th-right">CREADA EN</th>
                    @elseif($tipo === 'mapa')
                        <th>NOMBRE DEL PUNTO</th>
                        <th>CLASIFICACIÓN</th>
                        <th>DIRECCIÓN</th>
                        <th class="th-right">AGREGADO EN</th>
                    @endif
                </tr>
            </thead>
            <tbody>
                @forelse($registros as $item)
                    <tr>
                        @if($tipo === 'usuarios')
                            <td>
                                <span class="item-title">{{ $item->nombre }}</span>
                                <span class="item-subtitle">{{ $item->titulo_perfil ?? 'Ecologista' }}</span>
                            </td>
                            <td>{{ $item->email }}</td>
                            <td>{{ $item->ubicacion ?? 'No especificada' }}</td>
                            <td>
                                @php $rolText = $item->is_admin ? 'Admin' : 'Usuario'; @endphp
                                <span class="badge {{ getBadgeClass($rolText) }}">{{ $rolText }}</span>
                            </td>
                            <td class="td-right">{{ $item->created_at ? \Illuminate\Support\Carbon::parse($item->created_at)->format('d/m/Y') : '30/03/2026' }}</td>
                        
                        @elseif($tipo === 'campanas')
                            <td><span class="item-title">{{ $item->nombre }}</span></td>
                            <td>
                                @php $tipoCampana = $item->tipo_etiqueta ?? 'General'; @endphp
                                <span class="badge {{ getBadgeClass($tipoCampana) }}">{{ $tipoCampana }}</span>
                            </td>
                            <td>{{ mb_strimwidth($item->descripcion ?? $item->lugar ?? '', 0, 50, '...') }}</td>
                            <td class="td-right">{{ $item->created_at ? \Illuminate\Support\Carbon::parse($item->created_at)->format('d/m/Y') : 'N/A' }}</td>
                        
                        @elseif($tipo === 'mapa')
                            <td>
                                <span class="item-title">{{ $item->nombre }}</span>
                                <span class="item-subtitle">{{ $item->latitud }}, {{ $item->longitud }}</span>
                            </td>
                            <td>
                                <span class="badge {{ getBadgeClass($item->tipo) }}">{{ mb_strimwidth($item->tipo, 0, 15, '') }}</span>
                                <span class="item-subtitle">{{ mb_strimwidth($item->materiales ?? '', 0, 30, '...') }}</span>
                            </td>
                            <td>{{ mb_strimwidth($item->direccion ?? '', 0, 50, '...') }}</td>
                            <td class="td-right">{{ $item->created_at ? \Illuminate\Support\Carbon::parse($item->created_at)->format('d/m/Y') : 'N/A' }}</td>
                        @endif
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" style="text-align: center; padding: 30px; color: #9CA3AF;">
                            No se encontraron datos en el rango seleccionado.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="footer">
        Plataforma de Sostenibilidad y Medio Ambiente &bull; &copy; {{ date('Y') }} ZeroWaste Plataforma
    </div>
</body>
</html>

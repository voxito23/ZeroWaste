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

        /* === HEADER === */
        .header { 
            background-color: #064E3B;
            color: #ffffff; 
            padding: 35px 45px;
            position: relative;
            overflow: hidden;
        }
        
        .header::after {
            content: '';
            position: absolute;
            top: -50px;
            right: -30px;
            width: 200px;
            height: 200px;
            background: rgba(0, 224, 150, 0.08);
            border-radius: 50%;
        }

        .header-flex {
            display: flex;
            align-items: center;
            position: relative;
            z-index: 1;
        }

        .logo-box {
            width: 55px;
            height: 55px;
            background: rgba(255,255,255,0.15);
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 18px;
        }

        .logo-box img { 
            width: 40px; 
            height: 40px; 
        }

        .header h1 { 
            font-size: 28px; 
            font-weight: 800; 
            margin: 0; 
            letter-spacing: -0.5px;
        }
        
        .header .subtitle { 
            font-size: 13px; 
            color: #E2E8F0; 
            margin: 4px 0 0 0;
            font-weight: 400;
        }

        .header .date-tag {
            position: absolute;
            top: 35px;
            right: 45px;
            font-size: 10px;
            background-color: #047857;
            padding: 6px 14px;
            border-radius: 20px;
            color: #ffffff;
            letter-spacing: 0.3px;
        }

        /* === METRICS BAR === */
        .metrics-bar {
            background: #F8FAF9;
            border-bottom: 2px solid #E5E7EB;
            padding: 20px 45px;
        }

        .metrics-bar table {
            width: 100%;
            border-collapse: collapse;
        }

        .metrics-bar td {
            text-align: center;
            padding: 0 5px;
        }

        .metric-value {
            font-size: 24px;
            font-weight: 800;
            color: #064E3B;
            display: block;
        }

        .metric-label {
            font-size: 9px;
            text-transform: uppercase;
            font-weight: 700;
            letter-spacing: 0.8px;
            color: #6B7280;
            margin-top: 2px;
        }

        .metric-divider {
            width: 1px;
            height: 40px;
            background: #D1D5DB;
        }

        /* === CONTENT === */
        .content { 
            padding: 30px 45px 60px 45px; 
        }

        .summary-banner {
            background-color: #F0FDF4;
            border: 1px solid #BBF7D0;
            padding: 18px 22px;
            border-radius: 10px;
            margin-bottom: 30px;
        }

        .summary-item {
            margin-bottom: 6px;
            font-size: 11px;
            color: #4B5563;
        }
        
        .summary-item:last-child { margin-bottom: 0; }

        .summary-item b { 
            color: #064E3B; 
            font-weight: 700;
        }

        .section-title {
            font-size: 16px;
            font-weight: 800;
            color: #064E3B;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 2px solid #10B981;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            display: flex;
            align-items: center;
        }

        .section-title .icon-circle {
            width: 28px;
            height: 28px;
            min-width: 28px;
            border-radius: 8px;
            display: inline-block;
            text-align: center;
            line-height: 28px;
            font-size: 14px;
            margin-right: 10px;
            font-weight: 700;
            color: white;
        }

        .icon-green { background: #10B981; }
        .icon-blue { background: #3B82F6; }
        .icon-amber { background: #F59E0B; }
        .icon-purple { background: #8B5CF6; }

        /* === DATA TABLE === */
        table.data-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 10.5px;
            margin-top: 10px;
            border-radius: 8px;
            overflow: hidden;
        }

        table.data-table thead {
            background: #064E3B;
        }

        table.data-table th {
            padding: 12px 14px;
            text-align: left;
            font-size: 9px;
            text-transform: uppercase;
            font-weight: 700;
            color: #ffffff;
            border: none;
            letter-spacing: 0.5px;
        }

        table.data-table td {
            padding: 12px 14px;
            border-bottom: 1px solid #F3F4F6;
            color: #374151;
            vertical-align: middle;
        }

        table.data-table tr:nth-child(even) {
            background-color: #F9FAFB;
        }

        table.data-table tr:last-child td {
            border-bottom: none;
        }

        /* === ITEM STYLES === */
        .item-title {
            color: #064E3B;
            font-weight: 700;
            font-size: 12px;
        }

        .item-subtitle {
            font-size: 9px;
            color: #6B7280;
            margin-top: 2px;
            display: block;
        }

        .user-avatar {
            width: 28px;
            height: 28px;
            border-radius: 50%;
            display: inline-block;
            text-align: center;
            line-height: 28px;
            font-size: 12px;
            font-weight: 800;
            color: white;
            vertical-align: middle;
            margin-right: 8px;
        }

        .avatar-green { background: #10B981; }
        .avatar-purple { background: #8B5CF6; }
        .avatar-blue { background: #3B82F6; }
        .avatar-amber { background: #F59E0B; }

        /* === BADGES === */
        .badge {
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 8px;
            font-weight: 700;
            text-transform: uppercase;
            display: inline-block;
            letter-spacing: 0.3px;
        }

        .badge-green { background-color: #D1FAE5; color: #065F46; }
        .badge-blue { background-color: #DBEAFE; color: #1E40AF; }
        .badge-yellow { background-color: #FEF3C7; color: #92400E; }
        .badge-gray { background-color: #F3F4F6; color: #4B5563; }
        .badge-purple { background-color: #F3E8FF; color: #6B21A8; }
        .badge-red { background-color: #FEE2E2; color: #991B1B; }
        .badge-teal { background-color: #CCFBF1; color: #115E59; }

        .status-active {
            display: inline-block;
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: #10B981;
            margin-right: 5px;
            vertical-align: middle;
        }

        .status-inactive {
            display: inline-block;
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: #EF4444;
            margin-right: 5px;
            vertical-align: middle;
        }

        /* === FOOTER === */
        .footer {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            background: #F8FAF9;
            border-top: 1px solid #E5E7EB;
            padding: 12px 45px;
            font-size: 9px;
            color: #6B7280;
        }

        .footer-flex {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .td-right { text-align: right; }
        .th-right { text-align: right; }

        .img-point {
            width: 32px;
            height: 32px;
            border-radius: 8px;
            object-fit: cover;
            vertical-align: middle;
            margin-right: 8px;
            border: 1px solid #E5E7EB;
        }

        .coords {
            font-family: 'Courier New', monospace;
            font-size: 9px;
            color: #9CA3AF;
            background: #F3F4F6;
            padding: 2px 6px;
            border-radius: 4px;
        }
    </style>
</head>
<body>
    @php
        $logoPath = app()->basePath('../flask_zerowaste/static/img/logo.png');
        $logoSrc = '';
        if (file_exists($logoPath)) {
            $logoData = base64_encode(file_get_contents($logoPath));
            $logoSrc = 'data:image/png;base64,' . $logoData;
        }

        function getBadgeClass($text) {
            $text = strtolower($text);
            if (str_contains($text, 'acopio') || str_contains($text, 'reciclaje') || str_contains($text, 'positivo') || str_contains($text, 'principal')) return 'badge-green';
            if (str_contains($text, 'educación') || str_contains($text, 'taller') || str_contains($text, 'usuario')) return 'badge-blue';
            if (str_contains($text, 'orgánico') || str_contains($text, 'mixto') || str_contains($text, 'ambiental')) return 'badge-yellow';
            if (str_contains($text, 'admin')) return 'badge-purple';
            if (str_contains($text, 'negativo') || str_contains($text, 'peligroso')) return 'badge-red';
            if (str_contains($text, 'contenedor') || str_contains($text, 'punto')) return 'badge-teal';
            return 'badge-gray';
        }

        $avatarColors = ['avatar-green', 'avatar-purple', 'avatar-blue', 'avatar-amber'];

        $sectionIcons = [
            'usuarios' => ['icon' => '👥', 'class' => 'icon-green'],
            'campanas' => ['icon' => '🏆', 'class' => 'icon-blue'],
            'mapa' => ['icon' => '📍', 'class' => 'icon-amber'],
            'eventos' => ['icon' => '📅', 'class' => 'icon-purple'],
        ];
    @endphp

    {{-- HEADER --}}
    <div class="header">
        <div class="date-tag">{{ $fecha_generada }}</div>
        <div class="header-flex">
            <div class="logo-box">
                @if($logoSrc)
                    <img src="{{ $logoSrc }}" alt="Logo">
                @else
                    <span style="font-size: 22px; font-weight: 800; color: white;">♻</span>
                @endif
            </div>
            <div>
                <h1>ZEROWASTE</h1>
                <p class="subtitle">{{ $titulo }}</p>
            </div>
        </div>
    </div>

    {{-- METRICS BAR --}}
    <div class="metrics-bar">
        <table>
            <tr>
                <td>
                    <span class="metric-value">{{ $total }}</span>
                    <span class="metric-label">Registros</span>
                </td>
                <td><div class="metric-divider" style="display:inline-block"></div></td>
                <td>
                    <span class="metric-value" style="color: #059669;">{{ $rango_inicio }}</span>
                    <span class="metric-label">Desde</span>
                </td>
                <td><div class="metric-divider" style="display:inline-block"></div></td>
                <td>
                    <span class="metric-value" style="color: #059669;">{{ $rango_fin }}</span>
                    <span class="metric-label">Hasta</span>
                </td>
                <td><div class="metric-divider" style="display:inline-block"></div></td>
                <td>
                    <span class="metric-value" style="font-size: 16px;">{{ ucfirst($tipo) }}</span>
                    <span class="metric-label">Módulo</span>
                </td>
            </tr>
        </table>
    </div>

    {{-- CONTENT --}}
    <div class="content">

        {{-- Section Title --}}
        <div class="section-title">
            <span class="icon-circle {{ $sectionIcons[$tipo]['class'] ?? 'icon-green' }}">{{ $sectionIcons[$tipo]['icon'] ?? '📊' }}</span>
            RESULTADOS DETALLADOS
        </div>
        
        <table class="data-table">
            <thead>
                <tr>
                    @if($tipo === 'usuarios')
                        <th style="width: 5%">#</th>
                        <th style="width: 30%">USUARIO</th>
                        <th style="width: 25%">EMAIL</th>
                        <th>UBICACIÓN</th>
                        <th>ROL</th>
                        <th>ESTADO</th>
                        <th class="th-right">REGISTRO</th>
                    @elseif($tipo === 'campanas')
                        <th style="width: 5%">#</th>
                        <th style="width: 25%">CAMPAÑA</th>
                        <th>CLASIFICACIÓN</th>
                        <th>DESCRIPCIÓN</th>
                        <th>ESTADO</th>
                        <th class="th-right">CREADA</th>
                    @elseif($tipo === 'mapa')
                        <th style="width: 5%">#</th>
                        <th style="width: 25%">PUNTO DE ACOPIO</th>
                        <th>TIPO</th>
                        <th>DIRECCIÓN</th>
                        <th>MATERIALES</th>
                        <th class="th-right">COORDENADAS</th>
                    @elseif($tipo === 'eventos')
                        <th style="width: 5%">#</th>
                        <th style="width: 25%">EVENTO</th>
                        <th>TIPO</th>
                        <th>UBICACIÓN</th>
                        <th class="th-right">FECHA</th>
                    @endif
                </tr>
            </thead>
            <tbody>
                @forelse($registros as $idx => $item)
                    <tr>
                        @if($tipo === 'usuarios')
                            <td style="color: #9CA3AF; font-weight: 600;">{{ $idx + 1 }}</td>
                            <td>
                                @php
                                    $avatarColor = $item->is_admin ? 'avatar-purple' : $avatarColors[$idx % count($avatarColors)];
                                    $fotoName = $item->foto_perfil ?? null;
                                    $fotoBase64 = '';
                                    if ($fotoName && $fotoName !== 'default.png') {
                                        $fotoPath = public_path('img/perfiles/' . $fotoName);
                                        if (!file_exists($fotoPath)) {
                                            $fotoPath = app()->basePath('../flask_zerowaste/static/img/perfiles/' . $fotoName);
                                        }
                                        if (file_exists($fotoPath)) {
                                            $ext = pathinfo($fotoPath, PATHINFO_EXTENSION);
                                            $fotoBase64 = 'data:image/' . $ext . ';base64,' . base64_encode(file_get_contents($fotoPath));
                                        }
                                    }
                                @endphp
                                @if($fotoBase64)
                                    <img src="{{ $fotoBase64 }}" style="width: 28px; height: 28px; border-radius: 50%; object-fit: cover; vertical-align: middle; margin-right: 8px; border: 2px solid {{ $item->is_admin ? '#8B5CF6' : '#10B981' }};">
                                @else
                                    <span class="user-avatar {{ $avatarColor }}">{{ strtoupper(substr($item->nombre, 0, 1)) }}</span>
                                @endif
                                <span class="item-title">{{ $item->nombre }}</span>
                                <span class="item-subtitle">{{ $item->titulo_perfil ?? 'Ecologista' }}</span>
                            </td>
                            <td style="font-size: 10px;">{{ $item->email }}</td>
                            <td>{{ $item->ubicacion ?? '—' }}</td>
                            <td>
                                @php $rolText = $item->is_admin ? 'Admin' : 'Usuario'; @endphp
                                <span class="badge {{ getBadgeClass($rolText) }}">{{ $rolText }}</span>
                            </td>
                            <td>
                                @if($item->bloqueado ?? false)
                                    <span class="status-inactive"></span>
                                    <span style="font-size: 9px; font-weight: 600; color: #EF4444;">Bloqueado</span>
                                @else
                                    <span class="status-active"></span>
                                    <span style="font-size: 9px; font-weight: 600; color: #10B981;">Activo</span>
                                @endif
                            </td>
                            <td class="td-right">{{ $item->created_at ? \Illuminate\Support\Carbon::parse($item->created_at)->format('d/m/Y') : '—' }}</td>
                        
                        @elseif($tipo === 'campanas')
                            <td style="color: #9CA3AF; font-weight: 600;">{{ $idx + 1 }}</td>
                            <td>
                                <span class="item-title">{{ $item->nombre }}</span>
                                <span class="item-subtitle">{{ mb_strimwidth($item->lugar ?? '', 0, 30, '...') }}</span>
                            </td>
                            <td>
                                @php $tipoCampana = $item->tipo_etiqueta ?? 'General'; @endphp
                                <span class="badge {{ getBadgeClass($tipoCampana) }}">{{ $tipoCampana }}</span>
                            </td>
                            <td style="font-size: 10px;">{{ mb_strimwidth($item->descripcion ?? '', 0, 45, '...') }}</td>
                            <td>
                                @if($item->activa ?? false)
                                    <span class="status-active"></span>
                                    <span style="font-size: 9px; font-weight: 600; color: #10B981;">Activa</span>
                                @else
                                    <span class="status-inactive"></span>
                                    <span style="font-size: 9px; font-weight: 600; color: #EF4444;">Inactiva</span>
                                @endif
                            </td>
                            <td class="td-right">{{ $item->created_at ? \Illuminate\Support\Carbon::parse($item->created_at)->format('d/m/Y') : '—' }}</td>
                        
                        @elseif($tipo === 'mapa')
                            <td style="color: #9CA3AF; font-weight: 600;">{{ $idx + 1 }}</td>
                            <td>
                                @php
                                    $imgUrl = $item->imagen_url ?? null;
                                    $imgSrc = '';
                                    if ($imgUrl) {
                                        $imgFullPath = app()->basePath('../flask_zerowaste/static/' . ltrim($imgUrl, '/'));
                                        if (file_exists($imgFullPath)) {
                                            $ext = pathinfo($imgFullPath, PATHINFO_EXTENSION);
                                            $imgSrc = 'data:image/' . $ext . ';base64,' . base64_encode(file_get_contents($imgFullPath));
                                        }
                                    }
                                @endphp
                                @if($imgSrc)
                                    <img src="{{ $imgSrc }}" class="img-point" alt="">
                                @endif
                                <span class="item-title">{{ $item->nombre }}</span>
                            </td>
                            <td>
                                <span class="badge {{ getBadgeClass($item->tipo) }}">{{ mb_strimwidth($item->tipo, 0, 18, '') }}</span>
                            </td>
                            <td style="font-size: 10px;">{{ mb_strimwidth($item->direccion ?? '', 0, 40, '...') }}</td>
                            <td style="font-size: 10px;">{{ mb_strimwidth($item->materiales ?? '—', 0, 25, '...') }}</td>
                            <td class="td-right">
                                <span class="coords">{{ number_format($item->latitud, 4) }}, {{ number_format($item->longitud, 4) }}</span>
                            </td>
                        
                        @elseif($tipo === 'eventos')
                            <td style="color: #9CA3AF; font-weight: 600;">{{ $idx + 1 }}</td>
                            <td>
                                <span class="item-title">{{ $item->titulo ?? $item->nombre ?? 'Evento' }}</span>
                                <span class="item-subtitle">{{ mb_strimwidth($item->descripcion ?? '', 0, 40, '...') }}</span>
                            </td>
                            <td>
                                @php $tipoEvento = $item->tipo ?? 'General'; @endphp
                                <span class="badge {{ getBadgeClass($tipoEvento) }}">{{ $tipoEvento }}</span>
                            </td>
                            <td>{{ $item->ubicacion ?? $item->lugar ?? '—' }}</td>
                            <td class="td-right">
                                {{ $item->fecha_inicio ? \Illuminate\Support\Carbon::parse($item->fecha_inicio)->format('d/m/Y H:i') : '—' }}
                            </td>
                        @endif
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" style="text-align: center; padding: 40px; color: #9CA3AF; font-style: italic;">
                            No se encontraron datos en el rango seleccionado.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        {{-- Summary footer --}}
        @if($registros->count() > 0)
        <div style="margin-top: 25px; padding: 15px 20px; background: #F0FDF4; border-radius: 8px; border: 1px solid #BBF7D0;">
            <table style="width: 100%; font-size: 11px;">
                <tr>
                    <td style="color: #065F46; font-weight: 700;">
                        ♻ Total de registros en el periodo: <strong>{{ $total }}</strong>
                    </td>
                    <td style="text-align: right; color: #6B7280; font-size: 10px;">
                        Generado automáticamente por ZeroWaste Admin
                    </td>
                </tr>
            </table>
        </div>
        @endif
    </div>

    {{-- FOOTER --}}
    <div class="footer">
        <table style="width: 100%;">
            <tr>
                <td style="font-size: 9px; color: #6B7280;">
                    Plataforma de Sostenibilidad y Medio Ambiente &bull; &copy; {{ date('Y') }} ZeroWaste
                </td>
                <td style="text-align: right; font-size: 9px; color: #9CA3AF;">
                    zerowaste-qro.com &bull; Documento confidencial
                </td>
            </tr>
        </table>
    </div>
</body>
</html>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de {{ ucfirst($tipo) }}</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800;900&display=swap');
        @page { margin: 0cm 0cm; }
        body { 
            font-family: 'Outfit', 'Helvetica', 'Arial', sans-serif; 
            color: #1F2937; 
            font-size: 11px; 
            line-height: 1.6;
            background-color: #ffffff;
            margin: 0;
            padding: 0;
        }

        /* === HEADER === */
        .header { 
            background-color: #022C22; /* Premium dark green */
            color: #ffffff; 
            padding: 35px 45px;
            position: relative;
            overflow: hidden;
            border-bottom: 4px solid #00E096; /* Subtle accent */
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
            text-align: center;
            position: relative;
            z-index: 1;
        }

        .logo-box {
            width: 56px;
            height: 56px;
            display: inline-block;
            margin: 0;
            text-align: center;
        }

        .logo-box img { 
            width: 56px; 
            height: 56px; 
            border-radius: 18px; /* Semi-round borders */
            object-fit: cover;
            border: 2px solid rgba(0, 224, 150, 0.4); /* Premium emerald border */
            box-shadow: 0 8px 20px rgba(0,0,0,0.3);
        }

        .header h1 { 
            font-size: 34px; 
            font-weight: 900; 
            margin: 0; 
            letter-spacing: -0.8px;
            text-transform: uppercase;
            color: #ffffff;
        }
        
        .header .subtitle { 
            font-size: 13px; 
            color: #A7F3D0; /* Light emerald */
            margin: 4px 0 0 0;
            font-weight: 500;
            letter-spacing: 0.2px;
        }

        .header .date-tag {
            position: absolute;
            top: 35px;
            right: 45px;
            font-size: 10px;
            background-color: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
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
            border-radius: 14px; /* More rounded */
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
            width: 32px;
            height: 32px;
            min-width: 32px;
            border-radius: 10px; /* Semi-round */
            display: inline-block;
            text-align: center;
            line-height: 32px;
            font-size: 16px;
            margin-right: 12px;
            font-weight: 700;
            color: white;
            box-shadow: 0 3px 6px rgba(0,0,0,0.1);
        }

        .icon-green { background: #10B981; }
        .icon-blue { background: #3B82F6; }
        .icon-amber { background: #F59E0B; }
        .icon-purple { background: #8B5CF6; }

        /* === DATA TABLE === */
        table.data-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 10px;
            margin-top: 10px;
            border-radius: 14px; /* Semi-round borders */
            overflow: hidden;
            table-layout: fixed;
            box-shadow: 0 4px 15px rgba(0,0,0,0.03); /* Premium shadow */
        }

        table.data-table thead {
            background: #064E3B;
        }

        table.data-table th {
            padding: 10px 8px;
            text-align: left;
            font-size: 8px;
            text-transform: uppercase;
            font-weight: 700;
            color: #ffffff;
            border: none;
            letter-spacing: 0.5px;
            word-wrap: break-word;
            overflow: hidden;
        }

        table.data-table td {
            padding: 10px 8px;
            border-bottom: 1px solid #F3F4F6;
            color: #374151;
            vertical-align: middle;
            word-wrap: break-word;
            overflow: hidden;
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
        // Buscar logo
        $logoSrc = '';
        $logoPaths = [
            public_path('img/logo_texture.png'),
            public_path('static/img/logo_texture.png'),
            base_path('../flask_zerowaste/static/img/logo_texture.png'),
            public_path('img/logo.png'),
            public_path('static/img/logo.png'),
        ];
        foreach ($logoPaths as $logoPath) {
            if (file_exists($logoPath)) {
                $logoData = base64_encode(file_get_contents($logoPath));
                $ext = pathinfo($logoPath, PATHINFO_EXTENSION);
                $logoSrc = 'data:image/' . $ext . ';base64,' . $logoData;
                break;
            }
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

        function getInitials($name) {
            $words = explode(' ', trim($name));
            $initials = '';
            foreach ($words as $w) {
                if(strlen($w) > 0) { $initials .= strtoupper($w[0]); }
                if(strlen($initials) >= 2) break;
            }
            return $initials ?: 'U';
        }

        function getProfileImageBase64($url) {
            if (empty($url)) return null;
            if (filter_var($url, FILTER_VALIDATE_URL)) {
                $ctx = stream_context_create(['http' => ['timeout' => 1.5]]);
                $data = @file_get_contents($url, false, $ctx);
                if ($data) return 'data:image/jpeg;base64,' . base64_encode($data);
                return null;
            }
            $path = public_path('storage/' . $url);
            if (file_exists($path) && is_file($path)) {
                $ext = pathinfo($path, PATHINFO_EXTENSION);
                return 'data:image/' . ($ext == 'jpg' ? 'jpeg' : $ext) . ';base64,' . base64_encode(file_get_contents($path));
            }
            return null;
        }

        $avatarColors = ['#10B981', '#8B5CF6', '#3B82F6', '#F59E0B', '#059669', '#EC4899'];

        $svgIcon = function($path) {
            return '<svg viewBox="0 0 24 24" width="18" height="18" style="fill: currentColor; vertical-align: middle;"><path d="'.$path.'"></path></svg>';
        };

        $sectionIcons = [
            'usuarios' => ['icon' => $svgIcon('M16 11c1.66 0 2.99-1.34 2.99-3S17.66 5 16 5c-1.66 0-3 1.34-3 3s1.34 3 3 3zm-8 0c1.66 0 2.99-1.34 2.99-3S9.66 5 8 5C6.34 5 5 6.34 5 8s1.34 3 3 3zm0 2c-2.33 0-7 1.17-7 3.5V19h14v-2.5c0-2.33-4.67-3.5-7-3.5zm8 0c-.29 0-.62.02-.97.05 1.16.84 1.97 1.97 1.97 3.45V19h6v-2.5c0-2.33-4.67-3.5-7-3.5z'), 'class' => 'icon-green'],
            'campanas' => ['icon' => $svgIcon('M19 5h-2V3H7v2H5c-1.1 0-2 .9-2 2v1c0 2.55 1.92 4.63 4.39 4.94A5.01 5.01 0 0 0 11 15.9V19H7v2h10v-2h-4v-3.1a5.01 5.01 0 0 0 3.61-2.96C19.08 12.63 21 10.55 21 8V7c0-1.1-.9-2-2-2zM5 8V7h2v3.82C5.84 10.4 5 9.3 5 8zm14 0c0 1.3-.84 2.4-2 2.82V7h2v1z'), 'class' => 'icon-blue'],
            'mapa' =>     ['icon' => $svgIcon('M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z'), 'class' => 'icon-amber'],
            'eventos' =>  ['icon' => $svgIcon('M19 4h-1V2h-2v2H8V2H6v2H5c-1.11 0-1.99.9-1.99 2L3 20c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 16H5V10h14v10zm0-12H5V6h14v2zm-7 5h5v5h-5z'), 'class' => 'icon-purple'],
            'foro' =>     ['icon' => $svgIcon('M21 6h-2v9H6v2c0 .55.45 1 1 1h11l4 4V7c0-.55-.45-1-1-1zm-4 6V3c0-.55-.45-1-1-1H3c-.55 0-1 .45-1 1v14l4-4h10c.55 0 1-.45 1-1z'), 'class' => 'icon-blue'],
        ];
    @endphp

    {{-- HEADER --}}
    <div class="header">
        <div class="header-flex">
            <!-- Left-aligned Logo + Title inside table -->
            <table width="100%" style="border:none; border-collapse:collapse; margin: 0;">
                <tr>
                    <td align="left" style="vertical-align: middle; width: 65px;">
                        <div class="logo-box" style="margin: 0;">
                            @if($logoSrc)
                                <img src="{{ $logoSrc }}" alt="Logo">
                            @else
                                <span style="font-size: 24px; font-weight: 800; color: white;">♻</span>
                            @endif
                        </div>
                    </td>
                    <td align="left" style="vertical-align: middle; padding-left: 15px;">
                        <h1 style="margin: 0; font-family: 'Outfit', sans-serif; font-size: 28px; tracking: -0.5px;">ZEROWASTE</h1>
                        <p class="subtitle" style="margin-top: 2px; font-family: 'Outfit', sans-serif; font-size: 13px;">{{ $titulo }}</p>
                    </td>
                    <td align="right" style="vertical-align: top;">
                        <div style="background: rgba(255,255,255,0.1); display: inline-block; padding: 6px 12px; border-radius: 20px; font-size: 10px; font-weight: 700; color: #fff;">
                            {{ $fecha_generada }}
                        </div>
                    </td>
                </tr>
            </table>
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
            <span class="icon-circle {{ $sectionIcons[$tipo]['class'] ?? 'icon-green' }}">{!! $sectionIcons[$tipo]['icon'] ?? '<svg viewBox="0 0 24 24" width="18" height="18" style="fill: currentColor; vertical-align: middle;"><path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zM9 17H7v-7h2v7zm4 0h-2V7h2v10zm4 0h-2v-4h2v4z"></path></svg>' !!}</span>
            RESULTADOS DETALLADOS
        </div>
        
        <table class="data-table">
            <thead>
                <tr>
                    @if($tipo === 'usuarios')
                        <th style="width: 4%">#</th>
                        <th style="width: 20%">USUARIO</th>
                        <th style="width: 24%">EMAIL</th>
                        <th style="width: 16%">UBICACIÓN</th>
                        <th style="width: 10%">ROL</th>
                        <th style="width: 10%">ESTADO</th>
                        <th style="width: 16%" class="th-right">REGISTRO</th>
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
                    @elseif($tipo === 'foro')
                        <th style="width: 5%">#</th>
                        <th style="width: 30%">POST</th>
                        <th>CATEGORÍA</th>
                        <th>AUTOR</th>
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
                                    $color = $avatarColors[$idx % count($avatarColors)]; 
                                    $fotoBase64 = getProfileImageBase64($item->foto_perfil);
                                @endphp
                                <table style="width:100%; border:none; padding:0; margin:0;"><tr style="background:transparent;"><td style="width: 32px; border:none; padding:0;">
                                    @if($fotoBase64)
                                        <img src="{{ $fotoBase64 }}" style="width: 28px; height: 28px; border-radius: 50%; object-fit: cover; border: 1px solid #E5E7EB;">
                                    @else
                                        <div style="width: 28px; height: 28px; border-radius: 50%; background: {{ $color }}; color: white; text-align: center; line-height: 28px; font-weight: 800; font-size: 10px;">
                                            {{ getInitials($item->nombre) }}
                                        </div>
                                    @endif
                                </td><td style="border:none; padding:0; vertical-align:middle;">
                                    <span class="item-title">{{ mb_strimwidth($item->nombre, 0, 20, '...') }}</span>
                                    <span class="item-subtitle" style="color:{{$color}}">{{ $item->titulo_perfil ?? 'Ecologista' }}</span>
                                </td></tr></table>
                            </td>
                            <td style="font-size: 10px; font-weight: 500; color: #4B5563;">{{ mb_strimwidth($item->email, 0, 25, '...') }}</td>
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
                                @php $color = $avatarColors[($idx+2) % count($avatarColors)]; @endphp
                                <table style="width:100%; border:none; padding:0; margin:0;"><tr style="background:transparent;"><td style="width: 32px; border:none; padding:0;">
                                    <div style="width: 28px; height: 28px; border-radius: 8px; background: rgba(59, 130, 246, 0.1); color: #3B82F6; text-align: center; line-height: 34px;">
                                        {!! $svgIcon('M19 5h-2V3H7v2H5c-1.1 0-2 .9-2 2v1c0 2.55 1.92 4.63 4.39 4.94A5.01 5.01 0 0 0 11 15.9V19H7v2h10v-2h-4v-3.1a5.01 5.01 0 0 0 3.61-2.96C19.08 12.63 21 10.55 21 8V7c0-1.1-.9-2-2-2zM5 8V7h2v3.82C5.84 10.4 5 9.3 5 8zm14 0c0 1.3-.84 2.4-2 2.82V7h2v1z') !!}
                                    </div>
                                </td><td style="border:none; padding:0; vertical-align:middle;">
                                    <span class="item-title">{{ mb_strimwidth($item->nombre, 0, 20, '...') }}</span>
                                    <span class="item-subtitle">{{ mb_strimwidth($item->lugar ?? '', 0, 25, '...') }}</span>
                                </td></tr></table>
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
                                <span class="item-title">{{ mb_strimwidth($item->titulo ?? $item->nombre ?? 'Evento', 0, 25, '...') }}</span>
                                <span class="item-subtitle">{{ mb_strimwidth($item->descripcion ?? '', 0, 35, '...') }}</span>
                            </td>
                            <td>
                                @php $tipoEvento = $item->tipo ?? 'General'; @endphp
                                <span class="badge {{ getBadgeClass($tipoEvento) }}">{{ $tipoEvento }}</span>
                            </td>
                            <td>
                                <span style="font-weight:600; color:#4B5563;">{{ mb_strimwidth($item->ubicacion ?? $item->lugar ?? '—', 0, 20, '...') }}</span>
                            </td>
                            <td class="td-right">
                                @if($item->fecha_inicio)
                                    @php $fDate = \Illuminate\Support\Carbon::parse($item->fecha_inicio); @endphp
                                    <div style="display:inline-block; background: #F3F4F6; border: 1px solid #E5E7EB; border-radius: 8px; text-align: center; width: 42px; padding: 2px 0;">
                                        <div style="font-size: 7px; text-transform: uppercase; color: #6B7280; font-weight: 800; margin-bottom:-2px;">{{ $fDate->translatedFormat('M') }}</div>
                                        <div style="font-size: 13px; font-weight: 900; color: #064E3B;">{{ $fDate->format('d') }}</div>
                                    </div>
                                @else
                                    —
                                @endif
                            </td>
                        @elseif($tipo === 'foro')
                            <td style="color: #9CA3AF; font-weight: 600;">{{ $idx + 1 }}</td>
                            <td>
                                <span class="item-title">{{ mb_strimwidth($item->titulo ?? 'Post', 0, 30, '...') }}</span>
                                <span class="item-subtitle">{{ mb_strimwidth(strip_tags($item->contenido ?? ''), 0, 40, '...') }}</span>
                            </td>
                            <td>
                                <span class="badge badge-green">{{ mb_strimwidth($item->categoria->nombre ?? 'Sin Categoría', 0, 15, '') }}</span>
                            </td>
                            <td>
                                @php 
                                    $nAuthor = $item->autor->nombre ?? 'Usuario'; 
                                    $color = $avatarColors[$idx % count($avatarColors)]; 
                                    $fotoBase64 = getProfileImageBase64($item->autor->foto_perfil ?? null);
                                @endphp
                                <table style="width:100%; border:none; padding:0; margin:0;"><tr style="background:transparent;"><td style="width: 24px; border:none; padding:0;">
                                    @if($fotoBase64)
                                        <img src="{{ $fotoBase64 }}" style="width: 20px; height: 20px; border-radius: 50%; object-fit: cover; border: 1px solid #E5E7EB;">
                                    @else
                                        <div style="width: 20px; height: 20px; border-radius: 50%; background: {{ $color }}; color: white; text-align: center; line-height: 20px; font-weight: 800; font-size: 8px;">
                                            {{ getInitials($nAuthor) }}
                                        </div>
                                    @endif
                                </td><td style="border:none; padding:0; vertical-align:middle;">
                                    <span style="font-size: 10px; font-weight:600; color:#1F2937;">{{ mb_strimwidth($nAuthor, 0, 15, '...') }}</span>
                                </td></tr></table>
                            </td>
                            <td class="td-right">
                                @if($item->created_at)
                                    @php $fDate = \Illuminate\Support\Carbon::parse($item->created_at); @endphp
                                    <div style="display:inline-block; background: #F0FDF4; border: 1px solid #BBF7D0; border-radius: 8px; text-align: center; width: 42px; padding: 2px 0;">
                                        <div style="font-size: 7px; text-transform: uppercase; color: #059669; font-weight: 800; margin-bottom:-2px;">{{ $fDate->translatedFormat('M') }}</div>
                                        <div style="font-size: 13px; font-weight: 900; color: #064E3B;">{{ $fDate->format('d') }}</div>
                                    </div>
                                @else
                                    —
                                @endif
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

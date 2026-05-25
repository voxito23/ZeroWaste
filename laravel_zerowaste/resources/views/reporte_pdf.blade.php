<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de {{ ucfirst($tipo) }}</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap');

        @page {
            margin: 0;
        }

        body{
            font-family:'Plus Jakarta Sans', sans-serif;
            background:#F8FAFC;
            color:#1E293B;
            font-size:11px;
            line-height:1.6;
            margin:0;
            padding:0;
        }

        /* ====================================
           HEADER
        ==================================== */

        .header{
            background-color: #064E3B;
            padding:42px 50px;
            border-radius:0 0 36px 36px;
            border-bottom:3px solid #34D399;
            position: relative;
            overflow: hidden;
        }

        .header h1{
            margin:0;
            font-size:34px;
            font-weight:900;
            color:#FFFFFF;
            letter-spacing:-1px;
            position: relative;
            z-index: 2;
        }

        .subtitle{
            margin-top:4px;
            font-size:13px;
            color:#D1FAE5;
            font-weight:600;
            position: relative;
            z-index: 2;
        }

        .logo-box{
            width:72px;
            height:72px;
            background:#FFFFFF;
            border-radius:22px;
            padding:6px;
            border:2px solid rgba(255, 255, 255, 0.4);
            display: inline-block;
            vertical-align: middle;
            position: relative;
            z-index: 2;
        }

        .logo-box img{
            width:100%;
            height:100%;
            object-fit:cover;
            border-radius:18px;
        }

        .date-tag{
            background:rgba(255, 255, 255, 0.15);
            padding:8px 16px;
            border-radius:999px;
            border:1px solid rgba(255, 255, 255, 0.3);
            color:#FFFFFF;
            font-weight:800;
            font-size:10px;
            display: inline-block;
            position: relative;
            z-index: 2;
        }

        /* ====================================
           METRICS
        ==================================== */

        .metrics-bar{
            padding:24px 40px;
            background:#F8FAFC;
        }

        .metrics-bar table{
            width:100%;
            border-collapse:separate;
            border-spacing:12px;
        }

        .metrics-bar td{
            background:#FFFFFF;
            border:1px solid #E2E8F0;
            border-radius:24px;
            padding:18px;
            text-align:center;
            width: 33.33%;
        }

        .metric-divider{
            display:none;
        }

        .metric-value{
            display:block;
            font-size:28px;
            font-weight:900;
            color:#10B981;
        }

        .metric-label{
            display:block;
            margin-top:5px;
            font-size:10px;
            color:#94A3B8;
            text-transform:uppercase;
            letter-spacing:1px;
            font-weight:800;
        }

        /* ====================================
           CONTENT
        ==================================== */

        .content{
            padding:15px 40px 80px; /* Reduced top padding, increased bottom for footer */
        }

        /* ====================================
           TITLES
        ==================================== */

        .section-title{
            font-size:18px;
            font-weight:800;
            color:#0F172A;
            border:none;
            margin-bottom:20px;
            padding:0;
            display: flex;
            align-items: center;
        }

        .icon-circle{
            width:42px;
            height:42px;
            line-height:42px;
            text-align:center;
            display:inline-block;
            border-radius:14px;
            margin-right:12px;
            color:#FFFFFF;
            vertical-align: middle;
        }

        .icon-green{
            background:#6EE7B7;
        }

        .icon-blue{
            background:#93C5FD;
        }

        .icon-purple{
            background:#C4B5FD;
        }

        .icon-amber{
            background:#FCD34D;
        }

        /* ====================================
           TABLE CARD
        ==================================== */

        .table-wrapper{
            background:#FFFFFF;
            border-radius:24px;
            border:1px solid #E2E8F0;
            overflow:hidden;
            margin-bottom: 25px;
        }

        /* ====================================
           TABLE
        ==================================== */

        .data-table{
            width:100%;
            border-collapse:collapse;
            table-layout:fixed;
        }

        .data-table thead{
            background: linear-gradient(90deg, #ECFDF5 0%, #D1FAE5 100%);
        }

        .data-table th{
            color:#064E3B;
            font-size:9px;
            text-transform:uppercase;
            letter-spacing:1px;
            font-weight:900;
            padding:16px 12px;
            border-bottom:2px solid #10B981;
            text-align: left;
        }

        .data-table td{
            padding:14px 12px;
            border-bottom:1px solid #F1F5F9;
            color:#334155;
            vertical-align:middle;
        }

        .data-table tr:nth-child(even){
            background:#F4FBF7;
        }

        .data-table tr:last-child td{
            border-bottom:none;
        }

        /* ====================================
           USER CARD
        ==================================== */

        .item-title{
            font-size:12px;
            font-weight:700;
            color:#0F172A;
        }

        .item-subtitle{
            display:block;
            margin-top:2px;
            font-size:9px;
            color:#94A3B8;
        }

        .img-point{
            width:34px;
            height:34px;
            border-radius:10px;
            object-fit:cover;
            border:1px solid #E2E8F0;
        }

        .user-avatar{
            width:30px;
            height:30px;
            border-radius:50%;
            text-align:center;
            line-height:30px;
            font-size:10px;
            font-weight:800;
            color:#FFFFFF;
            display: inline-block;
            vertical-align: middle;
            background-color: #CBD5E1;
            overflow: hidden;
        }
        
        .user-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 50%;
        }

        /* ====================================
           BADGES
        ==================================== */

        .badge{
            display:inline-block;
            padding:6px 12px;
            border-radius:999px;
            font-size:8px;
            font-weight:700;
            text-transform:uppercase;
        }

        .badge-green{
            background:#ECFDF5;
            color:#059669;
            border:1px solid #A7F3D0;
        }

        .badge-blue{
            background:#EFF6FF;
            color:#2563EB;
            border:1px solid #BFDBFE;
        }

        .badge-yellow{
            background:#FFFBEB;
            color:#D97706;
            border:1px solid #FDE68A;
        }

        .badge-purple{
            background:#F5F3FF;
            color:#7C3AED;
            border:1px solid #DDD6FE;
        }

        .badge-red{
            background:#FEF2F2;
            color:#DC2626;
            border:1px solid #FECACA;
        }

        .badge-teal{
            background:#F0FDFA;
            color:#0F766E;
            border:1px solid #99F6E4;
        }

        .badge-gray{
            background:#F8FAFC;
            color:#64748B;
            border:1px solid #CBD5E1;
        }

        /* ====================================
           STATUS
        ==================================== */

        .status-active{
            display:inline-block;
            width:8px;
            height:8px;
            border-radius:50%;
            background:#10B981;
            margin-right:5px;
        }

        .status-inactive{
            display:inline-block;
            width:8px;
            height:8px;
            border-radius:50%;
            background:#EF4444;
            margin-right:5px;
        }

        /* ====================================
           COORDS
        ==================================== */

        .coords{
            display:inline-block;
            background:#F8FAFC;
            border:1px solid #E2E8F0;
            padding:4px 8px;
            border-radius:10px;
            font-size:9px;
            color:#64748B;
            font-family:monospace;
        }

        /* ====================================
           SUMMARY
        ==================================== */

        .summary-banner{
            background:#FFFFFF;
            border:1px solid #E2E8F0;
            border-radius:24px;
            padding:20px;
        }

        .summary-item{
            color:#64748B;
            margin-bottom:8px;
        }

        .summary-item b{
            color:#0F172A;
        }

        /* ====================================
           PREMIUM FOOTER
        ==================================== */

        .footer{
            position:fixed;
            bottom:0;
            left:0;
            right:0;
            background:#FFFFFF;
            border-top:1px solid #E2E8F0;
            padding:12px 40px;
            color:#94A3B8;
            font-size:9px;
        }

        /* ====================================
           SPECIAL DATE BOXES
        ==================================== */

        .date-box{
            width:46px;
            text-align:center;
            border-radius:14px;
            background:#F8FAFC;
            border:1px solid #E2E8F0;
            padding:4px;
            display: inline-block;
        }

        .date-box .month{
            font-size:8px;
            text-transform:uppercase;
            color:#64748B;
            font-weight:800;
        }

        .date-box .day{
            font-size:14px;
            font-weight:900;
            color:#10B981;
        }

        /* ====================================
           PALETTE
        ==================================== */

        .avatar-green{ background:#6EE7B7; }
        .avatar-blue{ background:#93C5FD; }
        .avatar-purple{ background:#C4B5FD; }
        .avatar-amber{ background:#FCD34D; }

        .th-right{ text-align:right; }
        .td-right{ text-align:right; }
        .text-center{ text-align:center; }
    </style>
</head>
<body>
    @php
        // Buscar logo
        $logoSrc = '';
        $logoPaths = [
            public_path('img/logo_texture.png'),
            public_path('static/img/logo_texture.png'),
            app()->basePath('../flask_zerowaste/static/img/logo_texture.png'),
            public_path('img/logo.png')
        ];
        foreach ($logoPaths as $logoPath) {
            if (file_exists($logoPath)) {
                $ext = pathinfo($logoPath, PATHINFO_EXTENSION);
                $logoSrc = 'data:image/' . $ext . ';base64,' . base64_encode(file_get_contents($logoPath));
                break;
            }
        }

        function file_exists_ci($path) {
            if (file_exists($path)) return $path;
            $dir = dirname($path);
            $base = strtolower(basename($path));
            if (!is_dir($dir)) return false;
            $files = scandir($dir);
            foreach ($files as $f) {
                if (strtolower($f) === $base) return $dir . '/' . $f;
            }
            return false;
        }

        // Resolutor maestro de imágenes para perfiles, campañas y mapas
        function getPremiumImageBase64($url) {
            if (empty($url)) return null;
            
            if (filter_var($url, FILTER_VALIDATE_URL)) {
                $ctx = stream_context_create([
                    'http' => [
                        'timeout' => 5.0,
                        'header' => "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36\r\n"
                    ],
                    'ssl' => ['verify_peer' => false, 'verify_peer_name' => false]
                ]);
                $data = @file_get_contents($url, false, $ctx);
                if ($data) {
                    $mime = 'image/jpeg';
                    $urlLower = strtolower($url);
                    if (str_contains($urlLower, '.png')) $mime = 'image/png';
                    elseif (str_contains($urlLower, '.gif')) $mime = 'image/gif';
                    elseif (str_contains($urlLower, '.svg')) $mime = 'image/svg+xml';
                    
                    return 'data:' . $mime . ';base64,' . base64_encode($data);
                }
                return null;
            }

            $filename = basename($url);
            $cleanUrl = ltrim($url, '/');
            
            $possiblePaths = [
                public_path($cleanUrl),
                public_path('storage/' . $cleanUrl),
                public_path('img/perfiles/' . $filename),
                public_path('img/campanas/' . $filename),
                public_path('img/' . $filename),
                public_path('static/img/perfiles/' . $filename),
                app()->basePath('../flask_zerowaste/static/img/perfiles/' . $filename),
                app()->basePath('../flask_zerowaste/static/img/campanas/' . $filename),
                app()->basePath('../flask_zerowaste/static/img/posts/' . $filename),
                app()->basePath('../flask_zerowaste/static/img/puntos/' . $filename),
                app()->basePath('../flask_zerowaste/static/img/eventos/' . $filename),
                app()->basePath('../flask_zerowaste/static/img/' . $filename),
                app()->basePath('../flask_zerowaste/static/' . $cleanUrl),
            ];

            foreach ($possiblePaths as $path) {
                $realPath = file_exists_ci($path);
                if ($realPath && is_file($realPath)) {
                    $ext = strtolower(pathinfo($realPath, PATHINFO_EXTENSION));
                    $mime = ($ext == 'jpg') ? 'jpeg' : ($ext == 'svg' ? 'svg+xml' : $ext);
                    return 'data:image/' . $mime . ';base64,' . base64_encode(file_get_contents($realPath));
                }
            }
            return null;
        }

        function getInitials($name) {
            $words = explode(' ', trim($name));
            $initials = '';
            foreach ($words as $w) {
                if(strlen($w) > 0) { $initials .= strtoupper($w[0]); }
                if(strlen($initials) >= 2) break;
            }
            return $initials ?: 'ZW';
        }

        function getBadgeClass($text) {
            $text = strtolower($text);
            if (str_contains($text, 'acopio') || str_contains($text, 'reciclaje') || str_contains($text, 'activo')) return 'badge-green';
            if (str_contains($text, 'educación') || str_contains($text, 'taller') || str_contains($text, 'usuario')) return 'badge-blue';
            if (str_contains($text, 'orgánico') || str_contains($text, 'mixto')) return 'badge-yellow';
            if (str_contains($text, 'admin') || str_contains($text, 'evento')) return 'badge-purple';
            if (str_contains($text, 'inactiv') || str_contains($text, 'bloquead')) return 'badge-red';
            return 'badge-gray';
        }

        $avatarColors = ['avatar-green', 'avatar-blue', 'avatar-purple', 'avatar-amber'];
        $borderColors = ['#10B981', '#3B82F6', '#8B5CF6', '#F59E0B'];
        $bgColors = ['#6EE7B7', '#93C5FD', '#C4B5FD', '#FCD34D'];
    @endphp

    {{-- HEADER --}}
    <div class="header">
        <div style="position: absolute; top: -50px; right: -30px; width: 220px; height: 220px; background: rgba(52, 211, 153, 0.15); border-radius: 50%; z-index: 1;"></div>
        <div style="position: absolute; bottom: -80px; left: 160px; width: 280px; height: 280px; background: rgba(16, 185, 129, 0.2); border-radius: 50%; z-index: 1;"></div>
        
        <table style="width: 100%; border: none; position: relative; z-index: 2;">
            <tr>
                <td style="width: 80px; vertical-align: middle;">
                    <div class="logo-box">
                        @if($logoSrc)
                            <img src="{{ $logoSrc }}" alt="Logo">
                        @else
                            <div style="width:100%; height:100%; background:#F1F5F9; border-radius:18px; text-align:center; line-height:60px; font-size:24px;">♻</div>
                        @endif
                    </div>
                </td>
                <td style="vertical-align: middle; padding-left: 10px;">
                    <h1>ZEROWASTE</h1>
                    <div class="subtitle">Reporte Oficial de {{ ucfirst($tipo) }}</div>
                </td>
                <td style="vertical-align: middle; text-align: right;">
                    <div class="date-tag">
                        {{ $fecha_generada }}
                    </div>
                </td>
            </tr>
        </table>
    </div>

    {{-- METRICS BAR --}}
    <div class="metrics-bar">
        <table>
            <tr>
                <td>
                    <span class="metric-value">{{ $total }}</span>
                    <span class="metric-label">Registros</span>
                </td>
                <td>
                    <span class="metric-value">{{ $rango_inicio }}</span>
                    <span class="metric-label">Desde</span>
                </td>
                <td>
                    <span class="metric-value">{{ $rango_fin }}</span>
                    <span class="metric-label">Hasta</span>
                </td>
            </tr>
        </table>
    </div>

    {{-- CONTENT --}}
    <div class="content">

        <div class="section-title">
            <span class="icon-circle icon-green">
                <svg viewBox="0 0 24 24" width="20" height="20" style="fill: currentColor; vertical-align: middle; margin-top: -2px;">
                    <path d="M4 6h16v2H4zm0 5h16v2H4zm0 5h16v2H4z"/>
                </svg>
            </span>
            <span style="display:inline-block; vertical-align:middle;">Resultados Detallados</span>
        </div>

        <div class="table-wrapper">
            <table class="data-table">
                <thead>
                    <tr>
                        @if($tipo === 'usuarios')
                            <th style="width: 25%">USUARIO</th>
                            <th style="width: 25%">CONTACTO / UBICACIÓN</th>
                            <th style="width: 15%" class="text-center">ROL</th>
                            <th style="width: 15%" class="text-center">ESTADO</th>
                            <th style="width: 20%" class="text-center">REGISTRO</th>
                        @elseif($tipo === 'campanas')
                            <th style="width: 30%">CAMPAÑA</th>
                            <th style="width: 20%" class="text-center">CLASIFICACIÓN</th>
                            <th style="width: 25%">DESCRIPCIÓN</th>
                            <th style="width: 10%" class="text-center">ESTADO</th>
                            <th style="width: 15%" class="text-center">FECHA</th>
                        @elseif($tipo === 'mapa')
                            <th style="width: 30%">PUNTO DE RECICLAJE</th>
                            <th style="width: 15%" class="text-center">TIPO</th>
                            <th style="width: 25%">DIRECCIÓN</th>
                            <th style="width: 15%">MATERIALES</th>
                            <th style="width: 15%" class="text-center">COORDENADAS</th>
                        @elseif($tipo === 'eventos')
                            <th style="width: 30%">EVENTO</th>
                            <th style="width: 15%" class="text-center">TIPO</th>
                            <th style="width: 25%">UBICACIÓN</th>
                            <th style="width: 15%">DETALLES</th>
                            <th style="width: 15%" class="text-center">FECHA</th>
                        @elseif($tipo === 'foro')
                            <th style="width: 35%">POST</th>
                            <th style="width: 15%" class="text-center">CATEGORÍA</th>
                            <th style="width: 25%">AUTOR</th>
                            <th style="width: 10%" class="text-center">RESP.</th>
                            <th style="width: 15%" class="text-center">FECHA</th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                    @forelse($registros as $idx => $item)
                        <tr>
                            @if($tipo === 'usuarios')
                                <td>
                                    <table style="width:100%; border:none; padding:0; margin:0;"><tr style="background:transparent;">
                                    <td style="width: 45px; border:none; padding:0;">
                                        @php 
                                            $borderColor = $borderColors[$idx % count($borderColors)];
                                            $bgColor = $bgColors[$idx % count($bgColors)];
                                            $fotoBase64 = getPremiumImageBase64($item->foto_perfil ?? null);
                                        @endphp
                                        @if($fotoBase64)
                                            <img src="{{ $fotoBase64 }}" style="width:36px; height:36px; border-radius:50%; object-fit:cover; border: 2px solid {{ $borderColor }}; padding: 2px; background: #FFF; display:inline-block; vertical-align:middle;">
                                        @else
                                            <div style="width:36px; height:36px; border-radius:50%; text-align:center; line-height:36px; font-size:13px; font-weight:800; color:#FFFFFF; background-color:{{ $bgColor }}; border: 2px solid {{ $borderColor }}; display:inline-block; vertical-align:middle;">
                                                {{ getInitials($item->nombre) }}
                                            </div>
                                        @endif
                                    </td>
                                    <td style="border:none; padding:0; vertical-align:middle; padding-left: 10px;">
                                        <span class="item-title">{{ $item->nombre }}</span><br>
                                        <span class="item-subtitle">{{ $item->titulo_perfil ?? 'Ecologista' }}</span>
                                    </td></tr></table>
                                </td>
                                <td>
                                    <span style="font-weight: 600; color: #334155; font-size:11px;">{{ $item->email }}</span><br>
                                    <span class="item-subtitle">{{ $item->ubicacion ?? 'Ubicación no especificada' }}</span>
                                </td>
                                <td class="text-center">
                                    @php $rolText = $item->is_admin ? 'Admin' : 'Usuario'; @endphp
                                    <span class="badge {{ getBadgeClass($rolText) }}">{{ $rolText }}</span>
                                </td>
                                <td class="text-center">
                                    @if($item->bloqueado ?? false)
                                        <span class="status-inactive"></span> <span style="color:#DC2626; font-weight:700; font-size:10px;">Bloqueado</span>
                                    @else
                                        <span class="status-active"></span> <span style="color:#059669; font-weight:700; font-size:10px;">Activo</span>
                                    @endif
                                </td>
                                <td class="text-center" style="font-weight: 700; color: #64748B;">
                                    {{ $item->created_at ? \Illuminate\Support\Carbon::parse($item->created_at)->format('d/m/Y') : '—' }}
                                </td>
                            
                            @elseif($tipo === 'campanas')
                                <td>
                                    <table style="width:100%; border:none; padding:0; margin:0;"><tr style="background:transparent;">
                                    <td style="width: 45px; border:none; padding:0;">
                                        @php 
                                            $fotoBase64 = getPremiumImageBase64($item->imagen_url ?? $item->foto ?? null);
                                            $colorClass = $avatarColors[($idx+2) % count($avatarColors)];
                                        @endphp
                                        <div class="img-point {{ $fotoBase64 ? '' : $colorClass }}" style="{{ !$fotoBase64 ? 'display:flex; align-items:center; justify-content:center;' : '' }}">
                                            @if($fotoBase64)
                                                <img src="{{ $fotoBase64 }}" style="width:100%; height:100%; border-radius:10px; object-fit:cover;">
                                            @else
                                                <div style="width:100%; height:100%; text-align:center; line-height:34px; color:white; font-size:16px; font-weight:bold;">
                                                    <svg viewBox="0 0 24 24" width="16" height="16" style="fill:white;"><path d="M19 5h-2V3H7v2H5c-1.1 0-2 .9-2 2v1c0 2.55 1.92 4.63 4.39 4.94A5.01 5.01 0 0 0 11 15.9V19H7v2h10v-2h-4v-3.1a5.01 5.01 0 0 0 3.61-2.96C19.08 12.63 21 10.55 21 8V7c0-1.1-.9-2-2-2zM5 8V7h2v3.82C5.84 10.4 5 9.3 5 8zm14 0c0 1.3-.84 2.4-2 2.82V7h2v1z"/></svg>
                                                </div>
                                            @endif
                                        </div>
                                    </td>
                                    <td style="border:none; padding:0; vertical-align:middle; padding-left: 8px;">
                                        <span class="item-title">{{ $item->nombre }}</span><br>
                                        <span class="item-subtitle">{{ $item->lugar ?? 'Sin ubicación' }}</span>
                                    </td></tr></table>
                                </td>
                                <td class="text-center">
                                    @php $tipoCampana = $item->tipo_etiqueta ?? 'General'; @endphp
                                    <span class="badge {{ getBadgeClass($tipoCampana) }}">{{ $tipoCampana }}</span>
                                </td>
                                <td>
                                    <span style="font-size: 10px; color: #475569;">{{ \Illuminate\Support\Str::limit($item->descripcion ?? '', 60) }}</span>
                                </td>
                                <td class="text-center">
                                    @if($item->activa ?? false)
                                        <span class="status-active"></span> <span style="color:#059669; font-weight:700; font-size:10px;">Activa</span>
                                    @else
                                        <span class="status-inactive"></span> <span style="color:#DC2626; font-weight:700; font-size:10px;">Inactiva</span>
                                    @endif
                                </td>
                                <td class="text-center" style="font-weight: 700; color: #64748B;">
                                    {{ $item->created_at ? \Illuminate\Support\Carbon::parse($item->created_at)->format('d/m/Y') : '—' }}
                                </td>
                            
                            @elseif($tipo === 'mapa')
                                <td>
                                    <table style="width:100%; border:none; padding:0; margin:0;"><tr style="background:transparent;">
                                    <td style="width: 45px; border:none; padding:0;">
                                        @php 
                                            $fotoBase64 = getPremiumImageBase64($item->imagen_url ?? $item->foto ?? null);
                                            $colorClass = $avatarColors[($idx+1) % count($avatarColors)];
                                        @endphp
                                        <div class="img-point {{ $fotoBase64 ? '' : $colorClass }}">
                                            @if($fotoBase64)
                                                <img src="{{ $fotoBase64 }}" style="width:100%; height:100%; border-radius:10px; object-fit:cover;">
                                            @else
                                                <div style="width:100%; height:100%; text-align:center; line-height:34px; color:white; font-size:16px; font-weight:bold;">
                                                    <svg viewBox="0 0 24 24" width="16" height="16" style="fill:white;"><path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z"/></svg>
                                                </div>
                                            @endif
                                        </div>
                                    </td>
                                    <td style="border:none; padding:0; vertical-align:middle; padding-left: 8px;">
                                        <span class="item-title">{{ $item->nombre }}</span>
                                    </td></tr></table>
                                </td>
                                <td class="text-center">
                                    <span class="badge {{ getBadgeClass($item->tipo) }}">{{ $item->tipo }}</span>
                                </td>
                                <td>
                                    <span style="font-size: 10px; color: #475569;">{{ $item->direccion ?? '—' }}</span>
                                </td>
                                <td style="font-size: 10px; color: #64748B; font-weight: 500;">
                                    {{ \Illuminate\Support\Str::limit($item->materiales ?? '—', 40) }}
                                </td>
                                <td class="text-center">
                                    <span class="coords">
                                        {{ number_format($item->latitud, 4) }}, {{ number_format($item->longitud, 4) }}
                                    </span>
                                </td>
                            
                            @elseif($tipo === 'eventos')
                                <td>
                                    <span class="item-title">{{ $item->titulo ?? $item->nombre ?? 'Evento' }}</span>
                                </td>
                                <td class="text-center">
                                    @php $tipoEvento = $item->tipo ?? 'General'; @endphp
                                    <span class="badge {{ getBadgeClass($tipoEvento) }}">{{ $tipoEvento }}</span>
                                </td>
                                <td style="font-weight: 600; color: #334155; font-size: 10px;">
                                    {{ $item->ubicacion ?? $item->lugar ?? '—' }}
                                </td>
                                <td>
                                    <span style="font-size: 10px; color: #64748B;">{{ \Illuminate\Support\Str::limit($item->descripcion ?? '', 45) }}</span>
                                </td>
                                <td class="text-center">
                                    @if($item->fecha_inicio)
                                        @php $fDate = \Illuminate\Support\Carbon::parse($item->fecha_inicio); @endphp
                                        <div class="date-box">
                                            <div class="month">{{ $fDate->translatedFormat('M') }}</div>
                                            <div class="day">{{ $fDate->format('d') }}</div>
                                        </div>
                                    @else
                                        —
                                    @endif
                                </td>
                                
                            @elseif($tipo === 'foro')
                                <td>
                                    <span class="item-title" style="display:block; margin-bottom:4px;">{{ \Illuminate\Support\Str::limit($item->titulo ?? 'Post', 40) }}</span>
                                    <span class="item-subtitle">{{ \Illuminate\Support\Str::limit(strip_tags($item->contenido ?? ''), 60) }}</span>
                                </td>
                                <td class="text-center">
                                    <span class="badge badge-green">{{ $item->categoria->nombre ?? 'Sin Categoría' }}</span>
                                </td>
                                <td>
                                    <table style="width:100%; border:none; padding:0; margin:0;"><tr style="background:transparent;">
                                    <td style="width: 35px; border:none; padding:0;">
                                        @php 
                                            $nAuthor = $item->autor->nombre ?? 'Usuario'; 
                                            $colorClass = $avatarColors[$idx % count($avatarColors)]; 
                                            $fotoBase64 = getPremiumImageBase64($item->autor->foto_perfil ?? null);
                                        @endphp
                                        <div class="user-avatar {{ $fotoBase64 ? '' : $colorClass }}">
                                            @if($fotoBase64)
                                                <img src="{{ $fotoBase64 }}">
                                            @else
                                                {{ getInitials($nAuthor) }}
                                            @endif
                                        </div>
                                    </td>
                                    <td style="border:none; padding:0; vertical-align:middle; padding-left: 6px;">
                                        <span style="font-size: 10px; font-weight:700; color:#1E293B;">{{ $nAuthor }}</span>
                                    </td></tr></table>
                                </td>
                                <td class="text-center" style="font-size:12px; font-weight:800; color:#2563EB;">
                                    {{ $item->respuestas_count ?? 0 }}
                                </td>
                                <td class="text-center">
                                    @if($item->created_at)
                                        @php $fDate = \Illuminate\Support\Carbon::parse($item->created_at); @endphp
                                        <div class="date-box">
                                            <div class="month">{{ $fDate->translatedFormat('M') }}</div>
                                            <div class="day">{{ $fDate->format('d') }}</div>
                                        </div>
                                    @else
                                        —
                                    @endif
                                </td>
                            @endif
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" style="text-align: center; padding: 40px; color: #94A3B8; font-weight: 600;">
                                No se encontraron datos en este reporte.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($registros->count() > 0)
        <div class="summary-banner">
            <table style="width:100%;">
                <tr>
                    <td>
                        <div class="summary-item" style="margin:0;">
                            <b>Total Documentado:</b> Se han listado y registrado correctamente <b>{{ $total }}</b> elementos en este informe oficial.
                        </div>
                    </td>
                    <td style="text-align:right; font-size:9px; color:#94A3B8; font-weight:700;">
                        Datos verificados por ZeroWaste
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
                <td>
                    <strong>ZeroWaste Platform</strong> &bull; Reporte emitido el {{ $fecha_generada }}
                </td>
                <td style="text-align: right;">
                    Documento Oficial Confidencial &bull; Página 1
                </td>
            </tr>
        </table>
    </div>

</body>
</html>

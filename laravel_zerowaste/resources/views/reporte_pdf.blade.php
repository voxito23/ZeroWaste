<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de {{ ucfirst($tipo) }}</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800;900&display=swap');
        @page { margin: 30px; }
        body { 
            font-family: 'Outfit', 'Helvetica', 'Arial', sans-serif; 
            color: #1F2937; 
            font-size: 11px; 
            line-height: 1.5;
            background-color: #ffffff;
            margin: 0;
            padding: 0;
        }

        /* === PREMIUM HEADER === */
        .premium-header {
            background-color: #022C22; /* ZeroWaste Dark Green */
            border-radius: 16px;
            padding: 25px 30px;
            color: #ffffff;
            margin-bottom: 20px;
            position: relative;
        }

        .header-top {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        .logo-img {
            width: 50px;
            height: 50px;
            border-radius: 12px;
            object-fit: cover;
            border: 2px solid rgba(255,255,255,0.1);
        }

        .title-text {
            font-size: 24px;
            font-weight: 800;
            color: #ffffff;
            margin: 0;
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }

        .subtitle-text {
            font-size: 12px;
            color: #A7F3D0;
            margin: 2px 0 0 0;
            font-weight: 500;
        }

        .date-badge {
            background-color: rgba(255,255,255,0.1);
            border: 1px solid rgba(255,255,255,0.2);
            padding: 6px 14px;
            border-radius: 20px;
            color: #ffffff;
            font-size: 10px;
            font-weight: 600;
            display: inline-block;
        }

        /* === METRICS CARDS === */
        .metrics-container {
            width: 100%;
            border-collapse: separate;
            border-spacing: 12px 0;
            margin-left: -6px; /* Offset spacing */
        }

        .metric-card {
            background-color: rgba(255,255,255,0.05);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 12px;
            padding: 12px 16px;
            width: 33%;
        }

        .metric-label {
            font-size: 8px;
            text-transform: uppercase;
            font-weight: 700;
            color: #9CA3AF;
            letter-spacing: 0.5px;
            margin-bottom: 4px;
            display: block;
        }

        .metric-value {
            font-size: 18px;
            font-weight: 800;
            color: #ffffff;
            display: block;
        }

        .metric-sub {
            font-size: 9px;
            color: #A7F3D0;
            margin-top: 2px;
            display: block;
        }

        /* === STATUS PILLS === */
        .status-section {
            width: 100%;
            border-collapse: separate;
            border-spacing: 15px 0;
            margin-bottom: 20px;
            margin-left: -7px;
        }

        .status-pill {
            border: 1px solid #E5E7EB;
            border-radius: 20px;
            padding: 10px 20px;
            width: 50%;
        }

        .status-pill-label {
            font-size: 8px;
            text-transform: uppercase;
            font-weight: 700;
            color: #6B7280;
            letter-spacing: 0.5px;
            display: block;
            margin-bottom: 2px;
        }

        .status-pill-value {
            font-size: 14px;
            font-weight: 800;
            color: #111827;
        }

        .badge-light-green {
            background-color: #D1FAE5;
            color: #065F46;
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 9px;
            font-weight: 700;
            float: right;
            margin-top: -15px;
        }

        /* === MAIN CONTENT WRAPPER === */
        .content-wrapper {
            border: 1px solid #E5E7EB;
            border-radius: 16px;
            padding: 20px;
            background-color: #ffffff;
        }

        .section-header {
            margin-bottom: 15px;
        }

        .section-icon {
            display: inline-block;
            width: 28px;
            height: 28px;
            background-color: #F3F4F6;
            border-radius: 50%;
            text-align: center;
            line-height: 28px;
            margin-right: 10px;
            vertical-align: middle;
        }

        .section-title {
            font-size: 14px;
            font-weight: 800;
            color: #111827;
            display: inline-block;
            vertical-align: middle;
        }

        .record-count {
            float: right;
            background-color: #111827;
            color: #ffffff;
            font-size: 10px;
            font-weight: 700;
            padding: 4px 10px;
            border-radius: 12px;
            margin-top: 4px;
        }

        /* === PREMIUM DATA TABLE === */
        table.premium-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 10px;
            margin-top: 10px;
            table-layout: fixed;
        }

        table.premium-table thead {
            background-color: #ffffff;
        }

        table.premium-table th {
            padding: 10px 8px;
            text-align: left;
            font-size: 8px;
            text-transform: uppercase;
            font-weight: 800;
            color: #6B7280;
            border-top: 1px solid #E5E7EB;
            border-bottom: 2px solid #E5E7EB;
            letter-spacing: 0.5px;
        }

        table.premium-table td {
            padding: 12px 8px;
            border-bottom: 1px solid #F3F4F6;
            color: #1F2937;
            vertical-align: middle;
            word-wrap: break-word;
        }

        table.premium-table tr:last-child td {
            border-bottom: none;
        }

        /* === ITEM STYLES === */
        .dot {
            display: inline-block;
            width: 6px;
            height: 6px;
            background-color: #3B82F6;
            border-radius: 50%;
            margin-right: 8px;
            vertical-align: middle;
        }

        .dot-green { background-color: #10B981; }

        .item-title {
            color: #111827;
            font-weight: 700;
            font-size: 11px;
        }

        .item-subtitle {
            font-size: 9px;
            color: #6B7280;
            margin-top: 2px;
            display: block;
        }

        /* Avatares e Imágenes */
        .img-container {
            width: 28px;
            height: 28px;
            border-radius: 50%; /* Por defecto redondo */
            overflow: hidden;
            border: 1px solid #E5E7EB;
            display: inline-block;
            vertical-align: middle;
            background-color: #F3F4F6;
        }

        .img-square {
            border-radius: 8px; /* Para campañas y mapas */
            width: 32px;
            height: 32px;
        }

        .img-container img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .avatar-initials {
            width: 100%;
            height: 100%;
            display: block; /* fix display flex issue in dompdf */
            color: white;
            font-weight: 800;
            font-size: 10px;
            text-align: center;
            line-height: 28px;
        }

        .avatar-initials-square {
            line-height: 32px;
        }

        /* === BADGES === */
        .badge {
            padding: 3px 8px;
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

        /* === FOOTER TOTALS === */
        .footer-totals {
            margin-top: 20px;
            width: 100%;
            border-collapse: separate;
            border-spacing: 10px 0;
            margin-left: -5px;
        }

        .total-box {
            background-color: #F8FAF9;
            border: 1px solid #E5E7EB;
            border-radius: 16px;
            padding: 12px 20px;
        }

        .total-box-highlight {
            background-color: #F0FDF4;
            border: 1px solid #BBF7D0;
        }

        .text-center { text-align: center; }
        .text-right { text-align: right; }
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

        // Resolutor maestro de imágenes para perfiles, campañas y mapas
        function getPremiumImageBase64($url) {
            if (empty($url)) return null;
            
            // Si es URL remota
            if (filter_var($url, FILTER_VALIDATE_URL)) {
                $ctx = stream_context_create(['http' => ['timeout' => 1.5]]);
                $data = @file_get_contents($url, false, $ctx);
                if ($data) return 'data:image/jpeg;base64,' . base64_encode($data);
                return null;
            }

            // Rutas posibles en el servidor (Laravel y Flask)
            $filename = basename($url);
            $cleanUrl = ltrim($url, '/');
            
            $possiblePaths = [
                public_path($cleanUrl),
                public_path('storage/' . $cleanUrl),
                public_path('static/img/perfiles/' . $filename),
                app()->basePath('../flask_zerowaste/static/img/perfiles/' . $filename),
                app()->basePath('../flask_zerowaste/static/img/campanas/' . $filename),
                app()->basePath('../flask_zerowaste/static/img/posts/' . $filename),
                app()->basePath('../flask_zerowaste/static/img/puntos/' . $filename),
                app()->basePath('../flask_zerowaste/static/' . $cleanUrl),
            ];

            foreach ($possiblePaths as $path) {
                if (file_exists($path) && is_file($path)) {
                    $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
                    $mime = ($ext == 'jpg') ? 'jpeg' : ($ext == 'svg' ? 'svg+xml' : $ext);
                    return 'data:image/' . $mime . ';base64,' . base64_encode(file_get_contents($path));
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

        $avatarColors = ['#10B981', '#3B82F6', '#8B5CF6', '#F59E0B', '#EC4899', '#059669'];
    @endphp

    {{-- PREMIUM HEADER --}}
    <div class="premium-header">
        <table class="header-top">
            <tr>
                <td style="width: 60px;">
                    @if($logoSrc)
                        <img src="{{ $logoSrc }}" class="logo-img" alt="Logo">
                    @else
                        <div class="logo-img" style="background: rgba(255,255,255,0.2); text-align: center; line-height: 50px; font-size: 24px;">♻</div>
                    @endif
                </td>
                <td style="vertical-align: middle;">
                    <p style="margin:0; font-size: 9px; letter-spacing: 1px; color:#A7F3D0; text-transform:uppercase;">Reporte Oficial</p>
                    <h1 class="title-text">Resumen {{ ucfirst($tipo) }}</h1>
                    <p class="subtitle-text">Plataforma ZeroWaste &bull; Datos Consolidados</p>
                </td>
                <td style="text-align: right; vertical-align: top;">
                    <div class="date-badge">
                        {{ $fecha_generada }}
                    </div>
                </td>
            </tr>
        </table>

        <table class="metrics-container">
            <tr>
                <td>
                    <div class="metric-card">
                        <span class="metric-label">Módulo Activo</span>
                        <span class="metric-value">{{ strtoupper($tipo) }}</span>
                        <span class="metric-sub">Sección consultada</span>
                    </div>
                </td>
                <td>
                    <div class="metric-card">
                        <span class="metric-label">Rango Inicial</span>
                        <span class="metric-value">{{ $rango_inicio }}</span>
                        <span class="metric-sub">Fecha de inicio</span>
                    </div>
                </td>
                <td>
                    <div class="metric-card" style="background-color: rgba(16, 185, 129, 0.15); border-color: rgba(16, 185, 129, 0.3);">
                        <span class="metric-label" style="color: #A7F3D0;">Total Registros</span>
                        <span class="metric-value">{{ $total }}</span>
                        <span class="metric-sub">Encontrados en periodo</span>
                    </div>
                </td>
            </tr>
        </table>
    </div>

    {{-- STATUS PILLS --}}
    <table class="status-section">
        <tr>
            <td>
                <div class="status-pill">
                    <span class="status-pill-label">Estado del Reporte</span>
                    <span class="status-pill-value">Completado</span>
                    <div class="badge-light-green">Exitoso</div>
                </div>
            </td>
            <td>
                <div class="status-pill">
                    <span class="status-pill-label">Filtro Aplicado</span>
                    <span class="status-pill-value">{{ $total > 0 ? 'Con Resultados' : 'Sin Resultados' }}</span>
                </div>
            </td>
        </tr>
    </table>

    {{-- MAIN CONTENT --}}
    <div class="content-wrapper">
        <div class="section-header">
            <div class="section-icon">
                <svg viewBox="0 0 24 24" width="14" height="14" style="fill: #111827; margin-top:7px;"><path d="M4 6h16v2H4zm0 5h16v2H4zm0 5h16v2H4z"/></svg>
            </div>
            <h2 class="section-title">Resultados Detallados</h2>
            <div class="record-count">{{ str_pad($total, 2, '0', STR_PAD_LEFT) }}</div>
        </div>

        <table class="premium-table">
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
                                <td style="width: 35px; border:none; padding:0;">
                                    @php 
                                        $color = $avatarColors[$idx % count($avatarColors)]; 
                                        $fotoBase64 = getPremiumImageBase64($item->foto_perfil ?? null);
                                    @endphp
                                    <div class="img-container">
                                        @if($fotoBase64)
                                            <img src="{{ $fotoBase64 }}">
                                        @else
                                            <div class="avatar-initials" style="background-color: {{ $color }};">{{ getInitials($item->nombre) }}</div>
                                        @endif
                                    </div>
                                </td>
                                <td style="border:none; padding:0; vertical-align:middle; padding-left: 8px;">
                                    <span class="item-title">{{ $item->nombre }}</span><br>
                                    <span class="item-subtitle">{{ $item->titulo_perfil ?? 'Ecologista' }}</span>
                                </td></tr></table>
                            </td>
                            <td>
                                <span style="font-weight: 600; color: #374151;">{{ $item->email }}</span><br>
                                <span class="item-subtitle">{{ $item->ubicacion ?? 'Ubicación no especificada' }}</span>
                            </td>
                            <td class="text-center">
                                @php $rolText = $item->is_admin ? 'Admin' : 'Usuario'; @endphp
                                <span class="badge {{ getBadgeClass($rolText) }}">{{ $rolText }}</span>
                            </td>
                            <td class="text-center">
                                @if($item->bloqueado ?? false)
                                    <span style="color:#EF4444; font-weight:700; font-size:10px;">&bull; Bloqueado</span>
                                @else
                                    <span style="color:#10B981; font-weight:700; font-size:10px;">&bull; Activo</span>
                                @endif
                            </td>
                            <td class="text-center" style="font-weight: 700; color: #4B5563;">
                                {{ $item->created_at ? \Illuminate\Support\Carbon::parse($item->created_at)->format('d M Y') : '—' }}
                            </td>
                        
                        @elseif($tipo === 'campanas')
                            <td>
                                <table style="width:100%; border:none; padding:0; margin:0;"><tr style="background:transparent;">
                                <td style="width: 40px; border:none; padding:0;">
                                    @php 
                                        $fotoBase64 = getPremiumImageBase64($item->imagen ?? $item->foto ?? null);
                                        $color = $avatarColors[($idx+2) % count($avatarColors)];
                                    @endphp
                                    <div class="img-container img-square">
                                        @if($fotoBase64)
                                            <img src="{{ $fotoBase64 }}">
                                        @else
                                            <div class="avatar-initials avatar-initials-square" style="background-color: {{ $color }};"><svg viewBox="0 0 24 24" width="16" height="16" style="fill:white; margin-top:8px;"><path d="M19 5h-2V3H7v2H5c-1.1 0-2 .9-2 2v1c0 2.55 1.92 4.63 4.39 4.94A5.01 5.01 0 0 0 11 15.9V19H7v2h10v-2h-4v-3.1a5.01 5.01 0 0 0 3.61-2.96C19.08 12.63 21 10.55 21 8V7c0-1.1-.9-2-2-2zM5 8V7h2v3.82C5.84 10.4 5 9.3 5 8zm14 0c0 1.3-.84 2.4-2 2.82V7h2v1z"/></svg></div>
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
                                <span class="dot dot-green"></span>
                                <span style="font-size: 10px; color: #4B5563;">{{ \Illuminate\Support\Str::limit($item->descripcion ?? '', 60) }}</span>
                            </td>
                            <td class="text-center">
                                @if($item->activa ?? false)
                                    <span style="color:#10B981; font-weight:700; font-size:10px;">Activa</span>
                                @else
                                    <span style="color:#EF4444; font-weight:700; font-size:10px;">Inactiva</span>
                                @endif
                            </td>
                            <td class="text-center" style="font-weight: 700; color: #4B5563;">
                                {{ $item->created_at ? \Illuminate\Support\Carbon::parse($item->created_at)->format('d M Y') : '—' }}
                            </td>
                        
                        @elseif($tipo === 'mapa')
                            <td>
                                <table style="width:100%; border:none; padding:0; margin:0;"><tr style="background:transparent;">
                                <td style="width: 40px; border:none; padding:0;">
                                    @php 
                                        $fotoBase64 = getPremiumImageBase64($item->imagen_url ?? $item->foto ?? null);
                                        $color = $avatarColors[($idx+1) % count($avatarColors)];
                                    @endphp
                                    <div class="img-container img-square">
                                        @if($fotoBase64)
                                            <img src="{{ $fotoBase64 }}">
                                        @else
                                            <div class="avatar-initials avatar-initials-square" style="background-color: {{ $color }};"><svg viewBox="0 0 24 24" width="16" height="16" style="fill:white; margin-top:8px;"><path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z"/></svg></div>
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
                                <span class="dot"></span>
                                <span style="font-size: 10px; color: #4B5563;">{{ $item->direccion ?? '—' }}</span>
                            </td>
                            <td style="font-size: 10px; color: #6B7280; font-weight: 500;">
                                {{ \Illuminate\Support\Str::limit($item->materiales ?? '—', 40) }}
                            </td>
                            <td class="text-center">
                                <div style="background: #F3F4F6; padding: 4px; border-radius: 6px; font-family: monospace; font-size: 8px; color: #6B7280;">
                                    {{ number_format($item->latitud, 4) }}, {{ number_format($item->longitud, 4) }}
                                </div>
                            </td>
                        
                        @elseif($tipo === 'eventos')
                            <td>
                                <span class="dot dot-green"></span>
                                <span class="item-title">{{ $item->titulo ?? $item->nombre ?? 'Evento' }}</span>
                            </td>
                            <td class="text-center">
                                @php $tipoEvento = $item->tipo ?? 'General'; @endphp
                                <span class="badge {{ getBadgeClass($tipoEvento) }}">{{ $tipoEvento }}</span>
                            </td>
                            <td style="font-weight: 600; color: #374151; font-size: 10px;">
                                {{ $item->ubicacion ?? $item->lugar ?? '—' }}
                            </td>
                            <td>
                                <span style="font-size: 10px; color: #6B7280;">{{ \Illuminate\Support\Str::limit($item->descripcion ?? '', 45) }}</span>
                            </td>
                            <td class="text-center" style="font-weight: 800; color: #064E3B; font-size: 11px;">
                                {{ $item->fecha_inicio ? \Illuminate\Support\Carbon::parse($item->fecha_inicio)->format('d M Y') : '—' }}
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
                                <td style="width: 30px; border:none; padding:0;">
                                    @php 
                                        $nAuthor = $item->autor->nombre ?? 'Usuario'; 
                                        $color = $avatarColors[$idx % count($avatarColors)]; 
                                        $fotoBase64 = getPremiumImageBase64($item->autor->foto_perfil ?? null);
                                    @endphp
                                    <div class="img-container" style="width: 24px; height: 24px;">
                                        @if($fotoBase64)
                                            <img src="{{ $fotoBase64 }}">
                                        @else
                                            <div class="avatar-initials" style="background-color: {{ $color }}; font-size:8px; line-height:24px;">{{ getInitials($nAuthor) }}</div>
                                        @endif
                                    </div>
                                </td>
                                <td style="border:none; padding:0; vertical-align:middle; padding-left: 6px;">
                                    <span style="font-size: 10px; font-weight:700; color:#1F2937;">{{ $nAuthor }}</span>
                                </td></tr></table>
                            </td>
                            <td class="text-center" style="font-size:12px; font-weight:800; color:#3B82F6;">
                                {{ $item->respuestas_count ?? 0 }}
                            </td>
                            <td class="text-center" style="font-weight: 700; color: #4B5563;">
                                {{ $item->created_at ? \Illuminate\Support\Carbon::parse($item->created_at)->format('d M Y') : '—' }}
                            </td>
                        @endif
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" style="text-align: center; padding: 40px; color: #9CA3AF; font-weight: 600;">
                            No se encontraron datos en este reporte.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

    </div>

    {{-- FOOTER TOTALS --}}
    <table class="footer-totals">
        <tr>
            <td style="width: 65%;">
                <div class="total-box">
                    <span style="font-size: 8px; color: #6B7280; font-weight: 800; text-transform: uppercase;">Detalles del Documento</span><br>
                    <span style="font-size: 11px; color: #111827; font-weight: 700;">Generado el {{ date('d \d\e m \d\e Y, h:i A') }}</span>
                </div>
            </td>
            <td style="width: 35%;">
                <div class="total-box total-box-highlight text-center">
                    <span style="font-size: 8px; color: #065F46; font-weight: 800; text-transform: uppercase;">Total Calculado</span><br>
                    <span style="font-size: 16px; color: #064E3B; font-weight: 900;">{{ $total }} Registros</span>
                </div>
            </td>
        </tr>
    </table>

</body>
</html>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de Recolecciones a Domicilio</title>
    <style>
        body { font-family: 'Helvetica', 'Arial', sans-serif; color: #333; margin: 0; padding: 20px; }
        .header { text-align: center; border-bottom: 2px solid #10B981; padding-bottom: 10px; margin-bottom: 20px; }
        .header h1 { color: #064E3B; margin: 0; }
        .header p { color: #666; margin: 5px 0 0 0; }
        .stats-box { background: #F3F4F6; border-radius: 8px; padding: 15px; margin-bottom: 20px; }
        .stats-table { w-full; border-collapse: collapse; width: 100%; }
        .stats-table td { width: 25%; text-align: center; }
        .stats-table h3 { margin: 0; font-size: 24px; color: #10B981; }
        .stats-table p { margin: 5px 0 0 0; font-size: 12px; color: #666; text-transform: uppercase; }
        .data-table { width: 100%; border-collapse: collapse; margin-top: 20px; font-size: 12px; }
        .data-table th { background: #10B981; color: white; padding: 10px; text-align: left; }
        .data-table td { border-bottom: 1px solid #E5E7EB; padding: 10px; }
        .status-completada { color: #10B981; font-weight: bold; }
        .status-pendiente { color: #F59E0B; font-weight: bold; }
        .footer { text-align: center; margin-top: 30px; font-size: 10px; color: #9CA3AF; }
    </style>
</head>
<body>

    <div class="header">
        <h1>ZeroWaste - Reporte Ambiental</h1>
        <p>Estadísticas de Recolección a Domicilio</p>
        <p><small>Generado el: {{ date('d M Y H:i') }}</small></p>
    </div>

    <div class="stats-box">
        <table class="stats-table">
            <tr>
                <td>
                    <h3>{{ $totalRecolecciones }}</h3>
                    <p>Total Solicitudes</p>
                </td>
                <td>
                    <h3>{{ $completadas }}</h3>
                    <p>Viajes Completados</p>
                </td>
                <td>
                    <h3 style="color: #F59E0B;">{{ $pendientes }}</h3>
                    <p>Viajes Pendientes</p>
                </td>
                <td>
                    <h3 style="color: #8B5CF6;">{{ number_format($promedioCalificacion, 1) }} ⭐</h3>
                    <p>Calificación Promedio</p>
                </td>
            </tr>
        </table>
    </div>

    <table class="data-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Ciudadano</th>
                <th>Dirección</th>
                <th>Materiales</th>
                <th>Estado</th>
                <th>Recolector</th>
                <th>Estrellas</th>
            </tr>
        </thead>
        <tbody>
            @forelse($solicitudes as $solicitud)
            <tr>
                <td>#{{ $solicitud->id }}</td>
                <td>{{ $solicitud->ciudadano_nombre }}</td>
                <td>{{ $solicitud->direccion }}</td>
                <td>{{ $solicitud->materiales ?? 'N/A' }}</td>
                <td class="{{ $solicitud->estado == 'completada' ? 'status-completada' : 'status-pendiente' }}">
                    {{ ucfirst($solicitud->estado) }}
                </td>
                <td>{{ $solicitud->recolector_nombre ?? 'N/A' }}</td>
                <td>{{ $solicitud->calificacion_recolector ? $solicitud->calificacion_recolector . ' ⭐' : '-' }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="7" style="text-align: center; padding: 20px;">No hay registros para mostrar.</td>
            </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        Este documento es generado automáticamente por el sistema administrativo de ZeroWaste.<br>
        Las recolecciones a domicilio son clave para evaluar la viabilidad del proyecto de economía circular.
    </div>

</body>
</html>

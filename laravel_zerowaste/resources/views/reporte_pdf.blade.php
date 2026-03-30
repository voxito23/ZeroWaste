<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte Zero Waste</title>
    <style>
        body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; color: #1f2937; margin: 0; padding: 20px; }
        .header { text-align: center; border-bottom: 4px solid #00FA9A; padding-bottom: 25px; margin-bottom: 35px; }
        .header h1 { color: #064E3B; margin: 0; font-size: 32px; text-transform: uppercase; letter-spacing: 2px; font-weight: 900; }
        .header p { color: #6b7280; font-size: 14px; margin-top: 5px; }
        
        .section-title { color: #064E3B; font-size: 18px; border-bottom: 2px solid #e5e7eb; padding-bottom: 8px; margin-bottom: 20px; margin-top: 40px; font-weight: bold; text-transform: uppercase; }
        
        .metric-box { background: #ECFDF5; border: 1px solid #A7F3D0; border-radius: 12px; padding: 20px; text-align: center; width: 44%; display: inline-block; margin-right: 2%; box-sizing: border-box; }
        .metric-value { font-size: 28px; font-weight: 900; color: #059669; }
        .metric-label { font-size: 11px; color: #065F46; text-transform: uppercase; letter-spacing: 1px; margin-top: 5px;}

        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #e5e7eb; padding: 14px; text-align: left; font-size: 14px; }
        th { background-color: #064E3B; color: #ffffff; text-transform: uppercase; font-size: 12px; letter-spacing: 1px; }
        tr:nth-child(even) { background-color: #f9fafb; }
        
        .status-pos { color: #10B981; font-weight: bold; }
        .status-neu { color: #6B7280; font-weight: bold; }
        .status-neg { color: #EF4444; font-weight: bold; }
        
        .footer { text-align: center; margin-top: 60px; font-size: 10px; color: #9ca3af; border-top: 1px solid #f3f4f6; padding-top: 20px;}
    </style>
</head>
<body>
    <div class="header">
        <h1>Zero Waste NLP 🍃</h1>
        <p>Reporte Ejecutivo de Interacciones | Generado el: {{ $fecha }}</p>
    </div>

    <div class="section-title">Resumen de Impacto</div>
    <div style="width: 100%;">
        <div class="metric-box">
            <div class="metric-value">{{ number_format($totalUsuarios) }}</div>
            <div class="metric-label">Usuarios Activos en Plataforma</div>
        </div>
        <div class="metric-box" style="margin-right: 0;">
            <div class="metric-value">{{ number_format($puntosGlobales) }}</div>
            <div class="metric-label">Puntos Globales Ecológicos (Exp)</div>
        </div>
    </div>

    <div class="section-title">Análisis de Sentimiento NLP (Comunidad)</div>
    <table>
        <thead>
            <tr>
                <th>Clasificación AI</th>
                <th>Volumen de Posturas (%)</th>
                <th>Diagnóstico del Algoritmo</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>Sentimiento Positivo</td>
                <td class="status-pos">{{ $sentimiento['POS'] }}%</td>
                <td>Excelente recepción y logros compartidos</td>
            </tr>
            <tr>
                <td>Sentimiento Neutro</td>
                <td class="status-neu">{{ $sentimiento['NEU'] }}%</td>
                <td>Informativo / Consultas rutinarias</td>
            </tr>
            <tr>
                <td>Sentimiento Negativo</td>
                <td class="status-neg">{{ $sentimiento['NEG'] }}%</td>
                <td>Puntos de fricción / Frustración con reciclaje</td>
            </tr>
        </tbody>
    </table>

    <div class="footer">
        Este documento es confidencial y generado de forma analítica en el servidor de Laravel conectando al motor de IA Predictiva en Python de Zero Waste.
    </div>
</body>
</html>

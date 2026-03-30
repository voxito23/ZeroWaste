<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte Zero Waste</title>
    <style>
        body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; background-color: #fafafa; padding: 20px;}
        .report-box { background: white; padding: 30px; border-radius: 12px; border: 1px solid #e5e7eb; box-shadow: 0 4px 6px rgba(0,0,0,0.05);}
        .header { border-bottom: 3px solid #00E096; padding-bottom: 20px; margin-bottom: 20px; text-align: center;}
        .header img { width: 80px; margin-bottom: 10px;}
        .header h1 { color: #064E3B; margin: 0; font-size: 32px; font-weight: 900; }
        .section-title { color: #064E3B; font-size: 20px; font-weight: 900; border-bottom: 2px solid #D1FAE5; padding-bottom: 5px; margin-top: 30px;}
        .metric-box { background: linear-gradient(135deg, #ECFDF5 0%, #D1FAE5 100%); border: 1px solid #10B981; border-radius: 16px; padding: 20px; text-align: center; width: 45%; display: inline-block; box-sizing: border-box; }
        .metric-value { font-size: 30px; font-weight: 900; color: #064E3B;}
        table { width: 100%; border-collapse: collapse; margin-top: 15px;}
        th, td { border-bottom: 1px solid #e5e7eb; padding: 15px; text-align: left; }
        th { background-color: #064E3B; color: #ffffff;}
    </style>
</head>
<body>
    <div class="report-box">
        <div class="header">
            <!-- Referencing Flask global asset relative to Laravel's position in this demo, or absolute public_path based on structure -->
            <img src="{{ public_path('../../flask_zerowaste/static/img/logo.png') }}" alt="ZeroWaste Logo">
            <h1>Zero Waste Analytics</h1>
            <p>Reporte Generado: {{ $fecha }}</p>
        </div>
        <div class="section-title">Resumen Operativo</div>
        <div style="width: 100%; text-align: center;">
            <div class="metric-box"><div class="metric-value">{{ number_format($totalUsuarios) }}</div><p style="font-size: 11px; font-weight: bold; color: #064E3B; text-transform:uppercase;">Comunidad Activa</p></div>
            <div class="metric-box" style="margin-left: 5%;"><div class="metric-value">{{ number_format($puntosGlobales) }}</div><p style="font-size: 11px; font-weight: bold; color: #064E3B; text-transform:uppercase;">Puntos Ecológicos</p></div>
        </div>
        <div class="section-title">Análisis de Sentimiento NLP</div>
        <table>
            <tr><th>Postura AI</th><th>Volumen</th></tr>
            <tr><td style="color:#10B981; font-weight:bold;">Recepción Positiva</td><td style="font-weight:900;">{{ $sentimiento['POS'] }}%</td></tr>
            <tr><td style="color:#6B7280; font-weight:bold;">Postura Neutra</td><td style="font-weight:900;">{{ $sentimiento['NEU'] }}%</td></tr>
            <tr><td style="color:#EF4444; font-weight:bold;">Fricción (Negativo)</td><td style="font-weight:900;">{{ $sentimiento['NEG'] }}%</td></tr>
        </table>
        <div style="font-size: 11px; color:#6b7280; text-align:center; margin-top:30px;">Laravel ⇌ FastAPI ⇌ PostgreSQL</div>
    </div>
</body>
</html>

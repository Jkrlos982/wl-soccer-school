<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reporte de Nómina</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            margin: 0;
            padding: 20px;
            color: #333;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #4472C4;
            padding-bottom: 15px;
        }
        .header h1 {
            color: #4472C4;
            margin: 0;
            font-size: 24px;
        }
        .header .subtitle {
            color: #666;
            margin-top: 5px;
        }
        .info-section {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
        }
        .info-box {
            background: #f8f9fa;
            padding: 10px;
            border-radius: 5px;
            border-left: 4px solid #4472C4;
        }
        .info-box strong {
            color: #4472C4;
        }
        .content {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            border: 1px solid #e9ecef;
            text-align: center;
            margin: 30px 0;
        }
        .content h2 {
            color: #4472C4;
            margin-bottom: 15px;
        }
        .content p {
            color: #666;
            line-height: 1.6;
        }
        .data-section {
            background: white;
            padding: 15px;
            border-radius: 5px;
            border: 1px solid #ddd;
            margin: 20px 0;
        }
        .data-title {
            font-weight: bold;
            color: #4472C4;
            margin-bottom: 10px;
            border-bottom: 1px solid #eee;
            padding-bottom: 5px;
        }
        .data-content {
            font-family: 'Courier New', monospace;
            font-size: 10px;
            background: #f8f9fa;
            padding: 10px;
            border-radius: 3px;
            white-space: pre-wrap;
            word-break: break-all;
        }
        .footer {
            margin-top: 30px;
            text-align: center;
            font-size: 10px;
            color: #666;
            border-top: 1px solid #ddd;
            padding-top: 10px;
        }
        .warning {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            color: #856404;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
        }
        .warning strong {
            color: #856404;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Reporte de Nómina</h1>
        <div class="subtitle">{{ $report->report_name ?? 'Reporte General' }}</div>
    </div>

    <div class="info-section">
        <div class="info-box">
            <strong>Tipo:</strong> {{ ucfirst(str_replace('_', ' ', $report->report_type ?? 'general')) }}
        </div>
        <div class="info-box">
            <strong>Generado:</strong> {{ $generated_at }}
        </div>
        <div class="info-box">
            <strong>Estado:</strong> {{ ucfirst($report->status ?? 'generado') }}
        </div>
    </div>

    @if(isset($report->period_start) && isset($report->period_end))
    <div class="info-section">
        <div class="info-box" style="width: 100%;">
            <strong>Período del Reporte:</strong> {{ $report->period_start }} - {{ $report->period_end }}
        </div>
    </div>
    @endif

    <div class="content">
        <h2>Reporte de Nómina Generado</h2>
        <p>Este es un reporte de nómina generado automáticamente por el sistema.</p>
        <p>El contenido específico del reporte se encuentra en los datos adjuntos.</p>
    </div>

    @if(isset($data) && !empty($data))
    <div class="data-section">
        <div class="data-title">Datos del Reporte</div>
        <div class="data-content">{{ json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</div>
    </div>
    @else
    <div class="warning">
        <strong>Atención:</strong> No se encontraron datos específicos para este reporte.
        Esto puede deberse a que el tipo de reporte no está configurado o no hay datos disponibles para el período seleccionado.
    </div>
    @endif

    @if(isset($report->notes) && !empty($report->notes))
    <div class="data-section">
        <div class="data-title">Notas Adicionales</div>
        <div style="padding: 10px; background: #f8f9fa; border-radius: 3px;">
            {{ $report->notes }}
        </div>
    </div>
    @endif

    <div class="data-section">
        <div class="data-title">Información del Sistema</div>
        <div style="font-size: 10px; color: #666;">
            <p><strong>ID del Reporte:</strong> {{ $report->id ?? 'N/A' }}</p>
            <p><strong>Versión del Sistema:</strong> 1.0.0</p>
            <p><strong>Formato:</strong> PDF</p>
            <p><strong>Generado por:</strong> Sistema de Nómina WL School</p>
        </div>
    </div>

    <div class="footer">
        <p>Reporte generado automáticamente por el Sistema de Nómina - {{ $generated_at }}</p>
        <p>Este documento es confidencial y está destinado únicamente para uso interno de la organización.</p>
        <p>Para soporte técnico o consultas sobre este reporte, contacte al administrador del sistema.</p>
    </div>
</body>
</html>
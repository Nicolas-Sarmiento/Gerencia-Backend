<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nueva Solicitud de Cotización</title>
    <style>
        body {
            font-family: Arial, Helvetica, sans-serif;
            background-color: #f4f4f7;
            margin: 0;
            padding: 20px;
            color: #333;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        .header {
            background-color: #2F569D;
            color: #ffffff;
            padding: 24px;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            font-size: 22px;
        }
        .body-content {
            padding: 24px;
        }
        .section-title {
            font-size: 16px;
            font-weight: bold;
            color: #2F569D;
            border-bottom: 2px solid #A8CF45;
            padding-bottom: 8px;
            margin-bottom: 16px;
        }
        .info-row {
            display: flex;
            margin-bottom: 8px;
        }
        .info-label {
            font-weight: bold;
            min-width: 120px;
            color: #555;
        }
        .info-value {
            color: #333;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 12px;
        }
        th {
            background-color: #2F569D;
            color: #fff;
            padding: 10px 12px;
            text-align: left;
            font-size: 13px;
        }
        td {
            padding: 10px 12px;
            border-bottom: 1px solid #e9ecef;
            font-size: 13px;
        }
        tr:last-child td {
            border-bottom: none;
        }
        .footer {
            background-color: #f8f9fa;
            text-align: center;
            padding: 16px;
            font-size: 12px;
            color: #888;
        }
        .description-box {
            background-color: #f8f9fa;
            border-left: 4px solid #A8CF45;
            padding: 12px 16px;
            margin-top: 12px;
            border-radius: 0 4px 4px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📋 Nueva Solicitud de Cotización</h1>
        </div>

        <div class="body-content">
            <p class="section-title">Datos del Cliente</p>

            <div class="info-row">
                <span class="info-label">Nombre:</span>
                <span class="info-value">{{ $quote->client->name }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Correo:</span>
                <span class="info-value">{{ $quote->client->mail }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Teléfono:</span>
                <span class="info-value">{{ $quote->client->phone }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Fecha solicitud:</span>
                <span class="info-value">{{ \Carbon\Carbon::parse($quote->requestDate)->setTimezone('America/Bogota')->format('d/m/Y h:i A') }}</span>
            </div>

            @if($quote->description)
                <p class="section-title" style="margin-top: 24px;">Mensaje del Cliente</p>
                <div class="description-box">
                    {{ $quote->description }}
                </div>
            @endif

            <p class="section-title" style="margin-top: 24px;">Productos / Servicios Solicitados</p>

            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Producto / Servicio</th>
                        <th>Cantidad</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($quote->quoted_Items as $index => $item)
                        <tr>
                            <td>{{ $index + 1 }}</td>
                            <td>{{ $item->product->name ?? 'Producto no disponible' }}</td>
                            <td>{{ $item->quantity ?? 1 }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="footer">
            Este correo fue generado automáticamente por el sistema de cotizaciones.
        </div>
    </div>
</body>
</html>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Preliquidación IDTGB Beni - {{ $nro_tramite }}</title>
    <style>
        @page { margin: 1.5cm; }
        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            font-size: 11px;
            color: #333;
            line-height: 1.4;
        }
        .header {
            text-align: center;
            border-bottom: 3px solid #007A33;
            margin-bottom: 20px;
            padding-bottom: 10px;
        }
        .header h1 { color: #007A33; margin: 0; font-size: 18px; text-transform: uppercase; }
        .header p { margin: 2px 0; font-size: 10px; font-weight: bold; }

        .tramite-box {
            float: right;
            border: 1px solid #000;
            padding: 8px;
            text-align: center;
            background-color: #f2f2f2;
        }

        .section-title {
            background-color: #007A33;
            color: white;
            padding: 5px 10px;
            font-weight: bold;
            margin: 15px 0 10px;
            border-radius: 3px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }
        table, th, td { border: 1px solid #ccc; }
        th { background-color: #f9f9f9; padding: 6px; text-align: left; width: 30%; }
        td { padding: 6px; }

        .boleta-oficial {
            margin-top: 20px;
            border: 2px solid #333;
            padding: 15px;
            background-color: #fff;
        }
        .linea-detalle {
            border-bottom: 1px dotted #999;
            margin-bottom: 8px;
            overflow: hidden;
        }
        .linea-detalle span:first-child { float: left; background: white; padding-right: 5px; }
        .linea-detalle span:last-child { float: right; background: white; padding-left: 5px; font-weight: bold; }

        .total-final {
            font-size: 16px;
            color: #007A33;
            text-align: right;
            margin-top: 15px;
            border-top: 2px solid #007A33;
            padding-top: 10px;
        }

        .footer {
            margin-top: 40px;
            font-size: 9px;
            text-align: center;
            color: #666;
            border-top: 1px solid #eee;
            padding-top: 10px;
        }
        .nota-importante {
            margin-top: 15px;
            font-size: 9px;
            background-color: #fff3cd;
            padding: 10px;
            border: 1px solid #ffeaa7;
        }
    </style>
</head>
<body>

    <div class="tramite-box">
        <strong>NRO. TRÁMITE</strong><br>
        <span style="font-size: 14px;">{{ $nro_tramite }}</span>
    </div>

    <div class="header">
        <h1>Gobierno Autónomo Departamental del Beni</h1>
        <p>DIRECCIÓN DE RECAUDACIONES Y POLÍTICA TRIBUTARIA</p>
        <p>SISTEMA DE PRELIQUIDACIÓN DE IMPUESTOS DEPARTAMENTALES</p>
    </div>

    <div class="section-title">1. Identificación del Sujeto Pasivo</div>
    <table>
        <tr>
            <th>Nombre / Razón Social:</th>
            <td>{{ strtoupper($nombre_sujeto) }}</td>
        </tr>
        <tr>
            <th>C.I. / NIT:</th>
            <td>{{ $ci_sujeto }}</td>
        </tr>
        <tr>
            <th>Parentesco / Alícuota:</th>
            <td>{{ $parentesco }} ({{ $tasa_aplicada }}%)</td>
        </tr>
    </table>

    <div class="section-title">2. Información del Bien y Transmisión</div>
    <table>
        <tr>
            <th>Tipo de Transmisión:</th>
            <td>{{ $tipo_transmision_nombre }}</td>
        </tr>
        <tr>
            <th>Fecha Hecho Generador:</th>
            <td>{{ $fecha_transmision }}</td>
        </tr>
        <tr>
            <th>Base Imponible (100%):</th>
            <td>Bs. {{ number_format($base_original, 2, ',', '.') }}</td>
        </tr>
        <tr>
            <th>% Participación:</th>
            <td>{{ $participacion }}%</td>
        </tr>
        <tr>
            <th>Base Imponible Sujeto:</th>
            <td>Bs. {{ number_format($base_imponible_calculada, 2, ',', '.') }}</td>
        </tr>
    </table>

    <div class="section-title">3. Liquidación de la Deuda Tributaria</div>
    <div class="boleta-oficial">
        <div style="margin-bottom: 10px; font-weight: bold;">
            ESTADO AL {{ now()->format('d/m/Y') }}:
            <span style="color: {{ $dias_mora > 0 ? '#d9534f' : '#5cb85c' }}">
                {{ $dias_mora > 0 ? 'CON MORA ('.$dias_mora.' DÍAS)' : 'DENTRO DE PLAZO' }}
            </span>
        </div>

        <div class="linea-detalle">
            <span>TRIBUTO OMITIDO (S900) - ACTUALIZADO POR UFV</span>
            <span>Bs. {{ number_format($tributo_actualizado ?? $idtgb_base, 2, ',', '.') }}</span>
        </div>
        <div class="linea-detalle">
            <span>INTERESES MORATORIOS (S930) - TASA {{ $tasa_mora }}%</span>
            <span>Bs. {{ number_format($interes, 2, ',', '.') }}</span>
        </div>
       <div class="linea-detalle">
            <span>
                MULTA INCUMP. DEBERES FORMALES (S900)
                <br><small style="font-size: 8px; color: #666;">
                    Sanción de {{ $tipo_contribuyente == 'Natural' ? '50' : '100' }} UFV según Resol. Normativa de Directorio
                </small>
            </span>
            <span>Bs. {{ number_format($multa_idf, 2, ',', '.') }}</span>
        </div>

        <div class="total-final">
            <strong>TOTAL DEUDA TRIBUTARIA: Bs. {{ number_format(round($final * 2) / 2, 2, ',', '.') }}</strong>
        </div>
    </div>

    <div style="font-size: 9px; margin-top: 5px;">
        <strong>Datos Técnicos:</strong> UFV Vencimiento: {{ $ufv_vencimiento }} | UFV Pago: {{ $ufv_pago }}
    </div>

    <div class="nota-importante">
        <strong>NOTA:</strong> Este documento es una <strong>Preliquidación Referencial</strong> obtenida a través del portal web institucional.
        Los montos están sujetos a revisión por parte de la autoridad tributaria departamental al momento de la presentación de los requisitos físicos.
        Este documento no constituye un comprobante de pago definitivo.
    </div>

    <div class="footer">
        "Beni, hacia la consolidación de la autonomía departamental"<br>
        Generado el {{ now()->format('d/m/Y H:i:s') }} - Usuario: Público Web
    </div>

</body>
</html>

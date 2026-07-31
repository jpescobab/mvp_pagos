<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>{{ $cdp->folio }}</title>
    <style>
        body { font-family: Arial, Helvetica, sans-serif; color: #1a1a1a; margin: 2rem; font-size: 12px; }
        table { border-collapse: collapse; width: 100%; }
        td { padding: 4px 6px; vertical-align: top; }
        .folio-box { border: 1px solid #333; padding: 6px 12px; font-weight: bold; text-align: center; background: #ffb703; }
        .titulo { text-align: center; font-size: 18px; font-weight: bold; margin: 1rem 0; }
        .checkbox { border: 1px solid #333; width: 16px; height: 16px; text-align: center; display: inline-block; }
        .campo-box { border: 1px solid #333; padding: 6px; min-height: 24px; }
        .detalle-tabla td { border: none; }
        .detalle-tabla .etiqueta { font-weight: bold; white-space: nowrap; }
        .fecha { text-align: right; font-weight: bold; margin: 0.5rem 0; }
        .firma-box { border: 1px solid #333; padding: 8px; margin-top: 1rem; width: 260px; }
        .nota { font-size: 10px; margin-top: 1.5rem; }
        .legal { font-size: 10px; margin-top: 0.75rem; text-align: justify; }
    </style>
</head>
<body>
    <table>
        <tr>
            <td style="width: 70%;"></td>
            <td style="width: 30%;">
                <table>
                    <tr><td class="etiqueta">N°</td><td class="folio-box">{{ $cdp->folio }}</td></tr>
                    <tr>
                        <td class="etiqueta">CF /ST</td>
                        <td style="border: 1px solid #333;">{{ $cdp->cfinanciero->codigo }} &nbsp;&nbsp; {{ $cdp->subtitulo }}</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <p class="titulo">Certificado de Disponibilidad Presupuestaria (CDP)</p>

    <table>
        <tr>
            <td style="width: 50%;">
                <table>
                    <tr>
                        <td style="width: 20px;"><span class="checkbox">{{ $cdp->tipo_gasto === 'GO' ? 'X' : '' }}</span></td>
                        <td>Gasto Operacional</td>
                    </tr>
                    <tr>
                        <td><span class="checkbox">{{ $cdp->tipo_gasto === 'INI' ? 'X' : '' }}</span></td>
                        <td>Iniciativa</td>
                    </tr>
                </table>
            </td>
            <td style="width: 50%;">
                <p>Nombre</p>
                <p class="campo-box">{{ $cdp->nombre }}</p>
                <p>Código Iniciativa</p>
                <p class="campo-box">{{ $cdp->codigo_iniciativa ?? '0' }}</p>
            </td>
        </tr>
    </table>

    <p class="fecha">
        {{ $cdp->firmado_en?->locale('es')->translatedFormat('l, d \d\e F \d\e Y') ?? now()->locale('es')->translatedFormat('l, d \d\e F \d\e Y') }}
    </p>

    <p>De conformidad a lo dispuesto en el artículo 3° del Decreto Supremo N° 250, reglamento de la
        Ley N° 19.886 y de acuerdo al presupuesto aprobado para esta institución por
        {{ config("presupuesto.ley_presupuestos_por_anio.{$cdp->anio_validez}", 'la Ley de Presupuestos del Sector Público vigente') }},
        &ldquo;Certifico que, a la fecha del presente documento, la Corporación Administrativa del
        Poder Judicial, cuenta con la disponibilidad presupuestaria para el financiamiento de los
        bienes, servicios y/u obras indicados en documentación adjunta&rdquo;.</p>

    <p><strong>El presupuesto disponible ha sido reservado, de acuerdo al siguiente detalle:</strong></p>

    <table class="detalle-tabla">
        <tr>
            <td class="etiqueta">Cuenta Presupuestaria</td>
            <td>: {{ $cdp->presupuesto->catalogo->codigo }}</td>
            <td class="etiqueta">Denominación</td>
            <td>: {{ $cdp->denominacion }}</td>
        </tr>
        <tr>
            <td class="etiqueta">Unidad Ejecutora</td>
            <td>: {{ $cdp->unidad_ejecutora }}</td>
            <td class="etiqueta">Monto Impto Incluido</td>
            <td>: $ {{ number_format((float) $cdp->monto, 0, ',', '.') }}</td>
        </tr>
        <tr>
            <td class="etiqueta">N° UE</td>
            <td>: {{ $cdp->n_ue }}</td>
            <td class="etiqueta">Validez</td>
            <td>: AÑO {{ $cdp->anio_validez }}</td>
        </tr>
        <tr>
            <td class="etiqueta">Carácter del Gasto</td>
            <td>: {{ strtoupper($cdp->caracter_gasto) }}</td>
            <td class="etiqueta">Requerimiento N°</td>
            <td>: {{ $cdp->requerimiento_numero ?? '—' }}</td>
        </tr>
        <tr>
            <td class="etiqueta">Moneda de Compra</td>
            <td>: {{ $cdp->moneda_compra }}</td>
            <td class="etiqueta">Total Moneda de Compra</td>
            <td>: {{ number_format((float) $cdp->total_moneda_compra, 4, ',', '.') }}</td>
        </tr>
    </table>

    <div class="firma-box">
        <strong>FIRMADIGITAL</strong>
        @if ($cdp->firmadoPor)
            <br>{{ $cdp->firmadoPor->name }}
            <br>Firmado electrónicamente el {{ $cdp->firmado_en?->locale('es')->translatedFormat('d \d\e F \d\e Y, H:i') }}
        @endif
    </div>

    <div class="nota">
        <p>Nota:</p>
        <p>1. El presente certificado es válido hasta el 31/12/{{ $cdp->anio_validez }}.</p>
        <p>2. Si hay montos en distintos subtitulo/ítems/asignación presupuestarios, se deben identificar cada uno de éstos por separado.</p>
    </div>
</body>
</html>

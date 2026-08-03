<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Solicitud de compra — {{ $proceso->codigo }}</title>
    <style>
        body { font-family: Arial, Helvetica, sans-serif; color: #1a1a1a; margin: 2rem; font-size: 12px; }
        .logo { text-align: left; margin-bottom: 0.5rem; }
        .logo img { width: 70px; }
        .titulo { text-align: center; margin-bottom: 1.5rem; }
        .titulo h1 { font-size: 15px; margin: 0 0 0.15rem; }
        .titulo h2 { font-size: 15px; margin: 0; }
        h3 { font-size: 13px; margin: 1.25rem 0 0.5rem; }
        table.datos { border-collapse: collapse; width: 100%; }
        table.datos td { border: 1px solid #999; padding: 6px 8px; vertical-align: top; }
        table.datos td.label { width: 25%; font-weight: bold; background: #eef3f7; }
        table.datos td.valor { width: 75%; }
        table.datos td.nota { font-size: 10px; }
        .vacio { color: #888; font-style: italic; }
        table.siono { border-collapse: collapse; width: 100%; }
        table.siono td { border: none; padding: 0; }
        table.siono td.siono-caja { width: 16%; text-align: center; font-weight: bold; }
        table.siono td.siono-nota { width: 68%; font-size: 10px; padding-left: 10px; }
        .aprobacion-texto { margin: 0.75rem 0; }
        .firma { margin-top: 1.5rem; padding-top: 0.75rem; border-top: 1px solid #999; }
        .firma strong { display: block; }
        .pendiente { color: #888; font-style: italic; }
    </style>
</head>
<body>
    <div class="logo">
        <img src="{{ $logoBase64 }}" alt="Corporación Administrativa Poder Judicial">
    </div>
    <div class="titulo">
        <h1>SOLICITUD PROCESO DE COMPRAS Y/O CONTRATACIÓN</h1>
        <h2>MENOR A 1.000 UTM</h2>
    </div>

    <h3>1. ANTECEDENTES GENERALES PARA GENERAR SOLICITUD</h3>
    <table class="datos">
        <tr>
            <td class="label">Fecha requerimiento</td>
            <td class="valor">{{ $proceso->fecha_inicio?->format('d-m-Y') ?? '—' }}</td>
        </tr>
        <tr>
            <td class="label">Nombre del proceso</td>
            <td class="valor">{{ $proceso->nombre ?? '—' }}</td>
        </tr>
        <tr>
            <td class="label">Unidad requirente</td>
            <td class="valor">{{ $proceso->ccosto?->nombre ?? '—' }}</td>
        </tr>
        <tr>
            <td class="label">Nombre requirente</td>
            <td class="valor">{{ $proceso->funcionarioRequirente?->nombre ?? '—' }}</td>
        </tr>
        <tr>
            <td class="label">Características del Bien y/o Servicio</td>
            <td class="valor">{{ $proceso->caracteristicas ?? '—' }}</td>
        </tr>
        <tr>
            <td class="label">Motivo de Contratación</td>
            <td class="valor">{{ $proceso->motivo_contratacion ?? '—' }}</td>
        </tr>
        <tr>
            <td class="label">¿Se encuentra en el Plan de Compras?</td>
            <td class="valor">
                <table class="siono">
                    <tr>
                        <td class="siono-caja">SI{{ $proceso->en_plan_compras ? ' X' : '' }}</td>
                        <td class="siono-caja">NO{{ $proceso->en_plan_compras ? '' : ' X' }}</td>
                        <td class="siono-nota">
                            Si la respuesta es SI, indicar el ID del PAC:
                            <strong>{{ $proceso->en_plan_compras ? ($proceso->id_pac ?? '—') : '—' }}</strong>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
        <tr>
            <td class="label">Código BIP (SUBT. 31)</td>
            <td class="valor">{{ $proceso->codigo_bip ?? '—' }}</td>
        </tr>
        <tr>
            <td class="label">¿El bien o servicio se encuentra en Convenio Marco?</td>
            @php $tieneConvenioMarco = $proceso->modalidad?->codigo === 'CONVENIO_MARCO'; @endphp
            <td class="valor">
                <table class="siono">
                    <tr>
                        <td class="siono-caja">SI{{ $tieneConvenioMarco ? ' X' : '' }}</td>
                        <td class="siono-caja">NO{{ $tieneConvenioMarco ? '' : ' X' }}</td>
                        <td class="siono-nota">
                            Si la respuesta es SI, pero el bien o servicio no satisface la necesidad, se debe adjuntar informe que justifique recurrir a otro mecanismo de contratación.
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
        <tr>
            <td class="label">Monto Estimado de la compra y/o contratación</td>
            <td class="valor">
                @if (($proceso->moneda_compra ?? 'CLP') === 'CLP')
                    {{ $proceso->monto_estimado !== null ? number_format((float) $proceso->monto_estimado, 2, ',', '.') : '—' }}
                @else
                    {{ $proceso->monto_estimado !== null ? number_format((float) $proceso->monto_estimado, 0, ',', '.') : '—' }}
                    (Equivalente a {{ $proceso->moneda_compra }}
                    {{ $proceso->monto_estimado_solicitado !== null ? number_format((float) $proceso->monto_estimado_solicitado, 2, ',', '.') : '—' }}
                    * {{ $proceso->paridad !== null ? number_format((float) $proceso->paridad, 2, '.', '') : '—' }}
                    {{ $proceso->moneda_compra }} al {{ $proceso->fecha_paridad?->format('d-m-Y') ?? '—' }})
                @endif
            </td>
        </tr>
    </table>

    <h3>2. APROBACIÓN SOLICITUD DE COMPRA</h3>
    <p class="aprobacion-texto">
        La jefatura responsable de la Unidad Requirente, <strong>APRUEBA</strong> la petición de
        compra y/o contratación contenida en esta solicitud.
    </p>

    @if ($aprobacion)
        <div class="firma">
            <strong>{{ $aprobacion->user?->name ?? 'Usuario no disponible' }}</strong>
            {{ $aprobacion->user?->getRoleNames()->first() ?? '—' }}
            <br>
            Aprobado el {{ $aprobacion->created_at?->translatedFormat('d \d\e F \d\e Y') }}
            a las {{ $aprobacion->created_at?->format('H:i') }}
        </div>
    @else
        <p class="pendiente">Pendiente de aprobación.</p>
    @endif
</body>
</html>

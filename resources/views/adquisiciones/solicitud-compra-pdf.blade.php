<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Solicitud de compra — {{ $proceso->codigo }}</title>
    <style>
        body { font-family: Arial, Helvetica, sans-serif; color: #1a1a1a; margin: 2rem; font-size: 13px; }
        h1 { font-size: 20px; margin-bottom: 0.25rem; }
        h2 { font-size: 15px; margin-top: 1.5rem; border-bottom: 1px solid #ddd; padding-bottom: 0.25rem; }
        .meta { color: #555; margin-bottom: 1.5rem; }
        table { border-collapse: collapse; width: 100%; margin: 0.5rem 0; }
        td { padding: 4px 8px; vertical-align: top; }
        td.label { width: 220px; color: #555; }
        .vacio { color: #888; font-style: italic; }
    </style>
</head>
<body>
    <h1>Solicitud Proceso de Compras y/o Contratación</h1>
    <p class="meta">
        {{ $proceso->codigo }} — Estado: {{ $proceso->proceso?->estadoActual?->nombre ?? '—' }}
        — Generado el {{ now()->format('d-m-Y H:i') }}
    </p>

    <h2>Antecedentes generales</h2>
    <table>
        <tr><td class="label">Fecha inicio compra</td><td>{{ $proceso->fecha_inicio?->format('d-m-Y') ?? '—' }}</td></tr>
        <tr><td class="label">Nombre de compra</td><td>{{ $proceso->nombre ?? '—' }}</td></tr>
        <tr><td class="label">ID requerimiento</td><td>{{ $proceso->id_requerimiento ?? '—' }}</td></tr>
        <tr><td class="label">Unidad requirente</td><td>{{ $proceso->ccosto?->nombre ?? '—' }}</td></tr>
        <tr><td class="label">Nombre requirente</td><td>{{ $proceso->funcionarioRequirente?->nombre ?? '—' }}</td></tr>
        <tr><td class="label">Características del bien/servicio</td><td>{{ $proceso->caracteristicas ?? '—' }}</td></tr>
        <tr><td class="label">Motivo de contratación</td><td>{{ $proceso->motivo_contratacion ?? '—' }}</td></tr>
        <tr><td class="label">¿En Plan Anual de Compras?</td><td>{{ $proceso->en_plan_compras ? 'Sí' : 'No' }}</td></tr>
        @if ($proceso->en_plan_compras && $proceso->id_pac)
            <tr><td class="label">ID del PAC</td><td>{{ $proceso->id_pac }}</td></tr>
        @endif
        <tr><td class="label">Código BIP (SUBT. 31)</td><td>{{ $proceso->codigo_bip ?? '—' }}</td></tr>
        <tr><td class="label">Modalidad</td><td>{{ $proceso->modalidad?->nombre ?? '—' }}</td></tr>
        <tr><td class="label">Monto solicitado</td><td>{{ $proceso->moneda_compra ?? 'CLP' }} {{ $proceso->monto_estimado_solicitado ?? '—' }}
            @if ($proceso->moneda_compra !== 'CLP' && $proceso->paridad)
                (paridad {{ $proceso->paridad }} al {{ $proceso->fecha_paridad?->format('d-m-Y') }})
            @endif
        </td></tr>
        <tr><td class="label">Monto estimado (CLP)</td><td>${{ number_format((float) $proceso->monto_estimado, 0, ',', '.') }}</td></tr>
    </table>
</body>
</html>

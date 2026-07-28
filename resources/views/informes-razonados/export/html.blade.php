@inject('graficoRenderer', \App\Services\InformesRazonados\GraficoSvgRenderer::class)
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Informe razonado — {{ $ejecucion->definicionInformeRazonado?->nombre ?? 'Ejecución #'.$ejecucion->id }}</title>
    <style>
        body { font-family: Arial, Helvetica, sans-serif; color: #1a1a1a; margin: 2rem; font-size: 13px; }
        h1 { font-size: 20px; margin-bottom: 0.25rem; }
        h2 { font-size: 16px; margin-top: 1.5rem; border-bottom: 1px solid #ddd; padding-bottom: 0.25rem; }
        h3 { font-size: 14px; margin-top: 1rem; }
        .meta { color: #555; margin-bottom: 1.5rem; }
        table { border-collapse: collapse; width: 100%; margin: 0.5rem 0; }
        th, td { border: 1px solid #ccc; padding: 4px 8px; text-align: left; }
        th { background: #f5f5f5; }
        .vacio { color: #888; font-style: italic; }
        .severidad-critico { color: #b00020; font-weight: bold; }
        .severidad-advertencia { color: #b06a00; }
        .grafico { margin: 0.5rem 0; }
        .grafico svg { max-width: 100%; height: auto; }
    </style>
</head>
<body>
    <h1>{{ $ejecucion->definicionInformeRazonado?->nombre ?? 'Informe razonado' }}</h1>
    <p class="meta">
        Ejecución #{{ $ejecucion->id }}
        @if ($ejecucion->corteReportabilidad)
            — Corte {{ $ejecucion->corteReportabilidad->fecha_corte }}
        @endif
        — Generado el {{ now()->format('d-m-Y H:i') }}
    </p>

    <h2>Secciones</h2>
    @forelse ($ejecucion->secciones as $seccion)
        <h3>{{ $seccion->orden }}. {{ $seccion->titulo }} <small>({{ $seccion->codigo }})</small></h3>
    @empty
        <p class="vacio">Sin secciones.</p>
    @endforelse

    <h2>Métricas</h2>
    @if ($ejecucion->metricas->isEmpty())
        <p class="vacio">Sin métricas.</p>
    @else
        <table>
            <thead>
                <tr><th>Código</th><th>Etiqueta</th><th>Valor</th><th>Unidad</th></tr>
            </thead>
            <tbody>
                @foreach ($ejecucion->metricas as $metrica)
                    <tr>
                        <td>{{ $metrica->codigo }}</td>
                        <td>{{ $metrica->etiqueta }}</td>
                        <td>{{ $metrica->valor ?? '—' }}</td>
                        <td>{{ $metrica->unidad ?? '—' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <h2>Gráficos</h2>
    @forelse ($ejecucion->graficos as $grafico)
        @php($datosGrafico = is_array($grafico->datos) ? $grafico->datos : [])
        <h3>{{ $grafico->titulo }} <small>({{ $grafico->tipo }})</small></h3>
        <div class="grafico">{!! $graficoRenderer->render($grafico->tipo, $datosGrafico, (string) $grafico->titulo) !!}</div>
        {{-- Tabla de datos: acompaña al gráfico y conserva los valores en formatos que no renderizan SVG inline (Word). --}}
        @if (! empty($datosGrafico['categorias']) && ! empty($datosGrafico['series']))
            <table>
                <thead>
                    <tr>
                        <th>Serie</th>
                        @foreach ($datosGrafico['categorias'] as $categoria)
                            <th>{{ $categoria }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @foreach ($datosGrafico['series'] as $serie)
                        <tr>
                            <td>{{ $serie['nombre'] ?? '—' }}</td>
                            @foreach (($serie['valores'] ?? []) as $valor)
                                <td>{{ $valor }}</td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    @empty
        <p class="vacio">Sin gráficos.</p>
    @endforelse

    <h2>Narrativas</h2>
    @forelse ($ejecucion->narrativas as $narrativa)
        <p>{!! nl2br(e($narrativa->contenido)) !!}</p>
    @empty
        <p class="vacio">Sin narrativas.</p>
    @endforelse

    <h2>Excepciones</h2>
    @if ($ejecucion->excepciones->isEmpty())
        <p class="vacio">Sin excepciones.</p>
    @else
        <table>
            <thead>
                <tr><th>Código</th><th>Severidad</th><th>Descripción</th></tr>
            </thead>
            <tbody>
                @foreach ($ejecucion->excepciones as $excepcion)
                    <tr>
                        <td>{{ $excepcion->codigo }}</td>
                        <td class="severidad-{{ $excepcion->severidad }}">{{ $excepcion->severidad }}</td>
                        <td>{{ $excepcion->descripcion }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</body>
</html>

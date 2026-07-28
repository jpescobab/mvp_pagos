<?php

use App\Services\InformesRazonados\GraficoSvgRenderer;

function datosCanonicosDePrueba(): array
{
    return [
        'categorias' => ['Ene', 'Feb', 'Mar'],
        'series' => [
            ['nombre' => 'Ingresos', 'valores' => [100, 200, 150]],
            ['nombre' => 'Egresos', 'valores' => [80, 120, 90]],
        ],
    ];
}

test('cada tipo válido produce un SVG con el título', function (string $tipo) {
    $datos = $tipo === 'torta'
        ? ['categorias' => ['A', 'B', 'C'], 'series' => [['nombre' => 'Único', 'valores' => [3, 5, 2]]]]
        : datosCanonicosDePrueba();

    $svg = (new GraficoSvgRenderer)->render($tipo, $datos, 'Mi gráfico');

    expect($svg)->toStartWith('<svg')
        ->and($svg)->toContain('<title>Mi gráfico</title>')
        ->and($svg)->toContain('role="img"');
})->with(['barra', 'linea', 'area', 'torta']);

test('datos vacíos producen el fallback "Sin datos para graficar" sin lanzar excepción', function () {
    $svg = (new GraficoSvgRenderer)->render('barra', ['categorias' => [], 'series' => []], 'X');

    expect($svg)->toContain('Sin datos para graficar')
        ->and($svg)->not->toStartWith('<svg');
});

test('datos de forma no reconocida producen el fallback "Formato de datos no reconocido"', function () {
    $svg = (new GraficoSvgRenderer)->render('linea', ['algo' => 'raro', 'otra' => [1, 2, 3]], 'X');

    expect($svg)->toContain('Formato de datos no reconocido');
});

test('una serie con valores de largo distinto a categorías cae al fallback', function () {
    $svg = (new GraficoSvgRenderer)->render('barra', [
        'categorias' => ['Ene', 'Feb', 'Mar'],
        'series' => [['nombre' => 'Total', 'valores' => [1, 2]]],
    ], 'X');

    expect($svg)->toContain('Formato de datos no reconocido');
});

test('el título se escapa para evitar inyección en el SVG', function () {
    $svg = (new GraficoSvgRenderer)->render('barra', datosCanonicosDePrueba(), '<script>alto</script>');

    expect($svg)->not->toContain('<script>')
        ->and($svg)->toContain('&lt;script&gt;');
});

test('un gráfico de barras dibuja un rect por cada valor', function () {
    $svg = (new GraficoSvgRenderer)->render('barra', datosCanonicosDePrueba(), 'Barras');

    // 2 series x 3 categorías = 6 barras.
    expect(substr_count($svg, '<rect'))->toBeGreaterThanOrEqual(6);
});

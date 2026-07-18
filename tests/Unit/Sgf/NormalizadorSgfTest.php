<?php

use App\Services\Sgf\NormalizadorSgf;

test('normaliza un monto con signo de peso y separadores chilenos', function () {
    $normalizado = (new NormalizadorSgf)->normalizar([
        'sgf_id' => '710',
        'estado' => 'PENDIENTE',
        'grupo_actual' => 'FINANZAS',
        'rut' => '9.317.442-9',
        'monto' => '$49.589',
    ]);

    expect($normalizado['monto'])->toEqual(49589.0);
});

test('normaliza un monto chileno con miles y decimales sin signo de peso', function () {
    $normalizado = (new NormalizadorSgf)->normalizar([
        'sgf_id' => '12345',
        'estado' => 'EN_TRAMITE',
        'grupo_actual' => 'FINANZAS',
        'rut' => '11.111.111-1',
        'monto' => '1.234.567,89',
    ]);

    expect($normalizado['monto'])->toEqual(1234567.89);
});

test('deja folio_egreso, numero y fecha_sii en null cuando SGF los entrega vacíos', function () {
    $normalizado = (new NormalizadorSgf)->normalizar([
        'sgf_id' => '710',
        'estado' => 'PENDIENTE',
        'grupo_actual' => 'FINANZAS',
        'rut' => '9.317.442-9',
        'monto' => '$49.589',
        'folio_egreso' => '',
        'numero' => '318',
        'fecha_sii' => '',
    ]);

    expect($normalizado['folio_egreso'])->toBeNull();
    expect($normalizado['numero'])->toBe('318');
    expect($normalizado['fecha_sii'])->toBeNull();
});

test('propaga periodo y observacion desde las claves crudas del scraper', function () {
    $normalizado = (new NormalizadorSgf)->normalizar([
        'sgf_id' => '710',
        'estado' => 'PENDIENTE',
        'grupo_actual' => 'FINANZAS',
        'rut' => '9.317.442-9',
        'monto' => '0',
        'periodo' => '6/2026',
        'observaciones' => 'RENDICION CAJA CHICA CORTE APELACIONES DE COYHAIQUE',
    ]);

    expect($normalizado['periodo'])->toBe('6/2026');
    expect($normalizado['observacion'])->toBe('RENDICION CAJA CHICA CORTE APELACIONES DE COYHAIQUE');
});

test('propaga numero_traspaso desde la clave cruda y lo deja null cuando viene vacío', function () {
    $conValor = (new NormalizadorSgf)->normalizar([
        'sgf_id' => '710',
        'estado' => 'PENDIENTE',
        'grupo_actual' => 'FINANZAS',
        'rut' => '9.317.442-9',
        'monto' => '0',
        'numero_traspaso' => 'TR-2026-0087',
    ]);

    $vacio = (new NormalizadorSgf)->normalizar([
        'sgf_id' => '711',
        'estado' => 'PENDIENTE',
        'grupo_actual' => 'FINANZAS',
        'rut' => '9.317.442-9',
        'monto' => '0',
        'numero_traspaso' => '',
    ]);

    expect($conValor['numero_traspaso'])->toBe('TR-2026-0087');
    expect($vacio['numero_traspaso'])->toBeNull();
});

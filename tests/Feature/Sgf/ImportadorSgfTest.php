<?php

use App\Models\Documento;
use App\Models\User;
use App\Services\Sgf\ImportadorSgf;

function filaSgfDePrueba(array $overrides = []): array
{
    return array_merge([
        'sgf_id' => '12345',
        'estado' => 'EN_TRAMITE',
        'grupo_actual' => 'FINANZAS',
        'observaciones' => 'Pendiente de revisión',
        'rut' => '12345678-9',
        'monto' => '1.234.567,89',
    ], $overrides);
}

test('importar una fila SGF crea un snapshot con payload crudo, normalizado y hash correctos', function () {
    $importacion = app(ImportadorSgf::class)->iniciarImportacion('manual');
    $fila = filaSgfDePrueba();

    $snapshot = app(ImportadorSgf::class)->importarFila($importacion, $fila);

    expect($snapshot->sgf_id)->toBe('12345');
    expect($snapshot->payload_crudo)->toBe($fila);
    expect($snapshot->payload_normalizado)->toBe([
        'sgf_id' => '12345',
        'estado' => 'EN_TRAMITE',
        'grupo_actual' => 'FINANZAS',
        'observaciones' => 'Pendiente de revisión',
        'rut' => '12345678-9',
        'monto' => 1234567.89,
    ]);
    expect($snapshot->hash)->toBe(hash('sha256', json_encode($fila, JSON_THROW_ON_ERROR)));
});

test('reimportar el mismo sgf_id crea un snapshot nuevo sin alterar el anterior', function () {
    $importacion = app(ImportadorSgf::class)->iniciarImportacion('manual');

    $primero = app(ImportadorSgf::class)->importarFila($importacion, filaSgfDePrueba());

    $segundaImportacion = app(ImportadorSgf::class)->iniciarImportacion('manual');
    $segundo = app(ImportadorSgf::class)->importarFila($segundaImportacion, filaSgfDePrueba(['estado' => 'PAGADA']));

    expect($primero->id)->not->toBe($segundo->id);
    expect($primero->refresh()->payload_normalizado['estado'])->toBe('EN_TRAMITE');
    expect($segundo->payload_normalizado['estado'])->toBe('PAGADA');
});

test('importar una fila con documento crea Documento/VersionDocumento y los vincula al snapshot', function () {
    $importacion = app(ImportadorSgf::class)->iniciarImportacion('manual');
    $fila = filaSgfDePrueba([
        'documentos' => [
            ['tipo_documento_codigo' => 'FACTURA', 'nombre_archivo' => 'factura.pdf', 'ruta_archivo' => 'sgf/factura.pdf'],
        ],
    ]);

    $snapshot = app(ImportadorSgf::class)->importarFila($importacion, $fila);

    expect($snapshot->documentos)->toHaveCount(1);

    $documento = Documento::find($snapshot->documentos->first()->documento_id);
    expect($documento->tipoDocumento->codigo)->toBe('FACTURA');
    expect($documento->versiones)->toHaveCount(1);
    expect($documento->versiones->first()->nombre_archivo)->toBe('factura.pdf');
});

test('iniciarImportacion y finalizarImportacion registran fuente, usuario, momentos y total de filas', function () {
    $usuario = User::factory()->create();

    $importacion = app(ImportadorSgf::class)->iniciarImportacion('playwright', $usuario);

    expect($importacion->fuente)->toBe('playwright');
    expect($importacion->iniciado_por)->toBe($usuario->id);
    expect($importacion->estado)->toBe('en_progreso');
    expect($importacion->finalizado_en)->toBeNull();

    app(ImportadorSgf::class)->importarFila($importacion, filaSgfDePrueba());
    app(ImportadorSgf::class)->importarFila($importacion, filaSgfDePrueba(['sgf_id' => '67890']));

    $finalizada = app(ImportadorSgf::class)->finalizarImportacion($importacion);

    expect($finalizada->estado)->toBe('completado');
    expect($finalizada->finalizado_en)->not->toBeNull();
    expect($finalizada->total_filas)->toBe(2);
});

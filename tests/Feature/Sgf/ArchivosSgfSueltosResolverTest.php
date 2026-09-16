<?php

use App\Models\CasoPagoProveedor;
use App\Models\Documento;
use App\Models\Proveedor;
use App\Models\SistemaExterno;
use App\Models\SnapshotDatosExterno;
use App\Models\TipoDocumento;
use App\Services\PagoProveedores\CasoPagoProveedorImporter;
use App\Services\Sgf\ArchivosSgfSueltosResolver;
use Database\Seeders\WorkflowPagoProveedoresSeeder;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->seed(WorkflowPagoProveedoresSeeder::class);
});

function crearCasoDePruebaParaArchivosSueltos(string $sgfId): CasoPagoProveedor
{
    $proveedor = Proveedor::create(['rutproveedor' => fake()->unique()->numerify('########-#'), 'nombre' => 'Proveedor de Prueba SpA']);

    $sistema = SistemaExterno::firstOrCreate(
        ['codigo' => 'SGF'],
        ['nombre' => 'SGF', 'tipo_integracion' => 'playwright', 'activo' => true],
    );

    $normalizado = [
        'sgf_id' => $sgfId,
        'estado' => 'EN_TRAMITE',
        'grupo_actual' => 'FINANZAS',
        'observaciones' => null,
        'rut' => $proveedor->rutproveedor,
        'monto' => 500000.0,
    ];

    $snapshot = SnapshotDatosExterno::create([
        'sistema_externo_id' => $sistema->id,
        'metodo_captura' => 'playwright',
        'referencia_externa' => $normalizado['sgf_id'],
        'payload_crudo' => $normalizado,
        'payload_normalizado' => $normalizado,
        'hash' => hash('sha256', json_encode($normalizado, JSON_THROW_ON_ERROR)),
        'capturado_en' => now(),
    ]);

    return app(CasoPagoProveedorImporter::class)->importarDesdeSnapshot($snapshot);
}

test('un archivo físico sin VersionDocumento aparece como disponible', function () {
    Storage::fake('local');

    $caso = crearCasoDePruebaParaArchivosSueltos('sgf-archivos-sueltos-1');
    Storage::disk('local')->put("sgf-documentos/{$caso->sgf_id}/factura.pdf", 'contenido de prueba');

    $disponibles = app(ArchivosSgfSueltosResolver::class)->disponibles($caso);

    expect($disponibles)->toHaveCount(1);
    expect($disponibles[0]['ruta_archivo'])->toBe("sgf-documentos/{$caso->sgf_id}/factura.pdf");
    expect($disponibles[0]['nombre_archivo'])->toBe('factura.pdf');
});

test('un archivo ya registrado como VersionDocumento no aparece como disponible', function () {
    Storage::fake('local');

    $caso = crearCasoDePruebaParaArchivosSueltos('sgf-archivos-sueltos-2');
    $ruta = "sgf-documentos/{$caso->sgf_id}/factura.pdf";
    Storage::disk('local')->put($ruta, 'contenido de prueba');

    $tipo = TipoDocumento::firstOrCreate(['codigo' => 'FACTURA'], ['nombre' => 'Factura']);
    $documento = Documento::create(['tipo_documento_id' => $tipo->id, 'titulo' => 'factura.pdf']);
    $documento->versiones()->create([
        'numero_version' => 1,
        'ruta_archivo' => $ruta,
        'nombre_archivo' => 'factura.pdf',
        'hash' => 'hash-de-prueba',
    ]);

    $disponibles = app(ArchivosSgfSueltosResolver::class)->disponibles($caso);

    expect($disponibles)->toBe([]);
});

test('un caso cuya carpeta no existe todavía devuelve una lista vacía sin error', function () {
    Storage::fake('local');

    $caso = crearCasoDePruebaParaArchivosSueltos('sgf-archivos-sueltos-3');

    $disponibles = app(ArchivosSgfSueltosResolver::class)->disponibles($caso);

    expect($disponibles)->toBe([]);
});

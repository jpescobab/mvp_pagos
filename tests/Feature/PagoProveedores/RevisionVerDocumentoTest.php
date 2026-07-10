<?php

use App\Models\Documento;
use App\Models\User;
use Database\Seeders\ModalidadesAdquisicionSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\TiposDocumentoSeeder;
use Database\Seeders\WorkflowPagoProveedoresSeeder;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->seed(WorkflowPagoProveedoresSeeder::class);
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->seed(TiposDocumentoSeeder::class);
    $this->seed(ModalidadesAdquisicionSeeder::class);
});

/**
 * Adjunta una versión real (archivo en el disco "local" fake) al documento
 * del escenario, tal como lo hace GestorDocumentoProceso/ConectorSgfPlaywrightService.
 */
function adjuntarArchivoReal(Documento $documento): void
{
    Storage::fake('local');

    $ruta = 'sgf-documentos/test/factura.pdf';
    Storage::disk('local')->put($ruta, '%PDF-1.4 contenido de prueba');

    $documento->versiones()->create([
        'numero_version' => 1,
        'ruta_archivo' => $ruta,
        'nombre_archivo' => 'factura.pdf',
    ]);
}

test('un revisor de Finanzas puede ver el archivo real de un documento del pago', function () {
    $e = crearEscenarioRevision();
    adjuntarArchivoReal($e['documento']);

    $finanzas = usuarioConRol('jefe_finanzas');

    $response = $this->actingAs($finanzas)->get(
        route('pago-proveedores.revision.pagos.documentos.ver', [
            'egresoCgu' => $e['egreso']->id,
            'caso' => $e['caso']->id,
            'documento' => $e['documento']->id,
        ]),
    );

    $response->assertOk();

    // A diferencia de DocumentoProcesoController::descargar (response()->download(),
    // que fuerza Content-Disposition: attachment), response()->file() no fuerza
    // disposition alguna (el header queda ausente) — así el navegador puede
    // renderizar el PDF inline en el <iframe> del panel en vez de forzar la
    // descarga.
    $disposition = $response->headers->get('content-disposition');
    expect($disposition === null || ! str_contains($disposition, 'attachment'))->toBeTrue();
});

test('un usuario sin permiso de revisión no puede ver el documento', function () {
    $e = crearEscenarioRevision();
    adjuntarArchivoReal($e['documento']);

    $sinPermiso = User::factory()->create();

    $response = $this->actingAs($sinPermiso)->get(
        route('pago-proveedores.revision.pagos.documentos.ver', [
            'egresoCgu' => $e['egreso']->id,
            'caso' => $e['caso']->id,
            'documento' => $e['documento']->id,
        ]),
    );

    $response->assertForbidden();
});

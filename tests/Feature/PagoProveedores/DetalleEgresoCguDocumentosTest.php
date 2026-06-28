<?php

use App\Models\Documento;
use App\Models\EgresoCgu;
use App\Models\SecurityAuditLog;
use App\Models\User;
use App\Models\VinculoDocumento;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\WorkflowPagoProveedoresSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;

function crearEgresoCguDePrueba(): EgresoCgu
{
    $caso = crearCasoPagoProveedorDePrueba('sgf-egreso-'.fake()->unique()->numerify('####'));

    $egreso = EgresoCgu::create([
        'numero_egreso' => 'EGR-'.fake()->unique()->numerify('####'),
        'fecha' => now(),
        'monto_total' => $caso->monto,
    ]);

    $egreso->items()->create([
        'caso_pago_proveedor_id' => $caso->id,
        'monto' => $caso->monto,
    ]);

    return $egreso;
}

test('el detalle de un egreso CGU muestra sus items y documentos vinculados', function () {
    $this->withoutVite();
    Storage::fake('local');
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->seed(WorkflowPagoProveedoresSeeder::class);

    $egreso = crearEgresoCguDePrueba();
    $tipoDocumento = crearTipoDocumentoDePrueba();

    $usuario = User::factory()->create();
    $usuario->givePermissionTo('documentos.gestionar');

    $this->actingAs($usuario)->post(
        route('egresos-cgu.documentos.store', $egreso),
        ['archivo' => UploadedFile::fake()->create('comprobante.pdf', 100, 'application/pdf'), 'tipo_documento_id' => $tipoDocumento->id],
    );

    $response = $this->actingAs($usuario)->get(route('pago-proveedores.egresos-cgu.show', $egreso));

    $response->assertInertia(fn (Assert $page) => $page
        ->component('pago-proveedores/egresos-cgu/show')
        ->where('egreso.id', $egreso->id)
        ->has('egreso.items', 1)
        ->has('egreso.documentos', 1)
    );
});

test('subir un documento a un egreso CGU crea documento, version y vinculo activo', function () {
    Storage::fake('local');
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->seed(WorkflowPagoProveedoresSeeder::class);

    $egreso = crearEgresoCguDePrueba();
    $tipoDocumento = crearTipoDocumentoDePrueba();

    $usuario = User::factory()->create();
    $usuario->givePermissionTo('documentos.gestionar');

    $response = $this->actingAs($usuario)->post(
        route('egresos-cgu.documentos.store', $egreso),
        ['archivo' => UploadedFile::fake()->create('comprobante.pdf', 100, 'application/pdf'), 'tipo_documento_id' => $tipoDocumento->id],
    );

    $response->assertSessionHasNoErrors();

    $vinculo = VinculoDocumento::where('vinculable_type', EgresoCgu::class)
        ->where('vinculable_id', $egreso->id)
        ->first();

    expect($vinculo)->not->toBeNull();
    expect($vinculo->activo)->toBeTrue();
    expect($vinculo->documento->versiones)->toHaveCount(1);
});

test('un usuario sin el permiso no puede subir un documento a un egreso CGU y queda auditado', function () {
    Storage::fake('local');
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->seed(WorkflowPagoProveedoresSeeder::class);

    $egreso = crearEgresoCguDePrueba();
    $tipoDocumento = crearTipoDocumentoDePrueba();

    $usuario = User::factory()->create();

    $response = $this->actingAs($usuario)->post(
        route('egresos-cgu.documentos.store', $egreso),
        ['archivo' => UploadedFile::fake()->create('comprobante.pdf', 100, 'application/pdf'), 'tipo_documento_id' => $tipoDocumento->id],
    );

    $response->assertForbidden();
    expect(Documento::count())->toBe(0);
    expect(SecurityAuditLog::where('event', 'acceso_denegado')->exists())->toBeTrue();
});

test('descargar y desvincular un documento de un egreso CGU', function () {
    Storage::fake('local');
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->seed(WorkflowPagoProveedoresSeeder::class);

    $egreso = crearEgresoCguDePrueba();
    $tipoDocumento = crearTipoDocumentoDePrueba();

    $usuario = User::factory()->create();
    $usuario->givePermissionTo('documentos.gestionar');

    $this->actingAs($usuario)->post(
        route('egresos-cgu.documentos.store', $egreso),
        ['archivo' => UploadedFile::fake()->create('comprobante.pdf', 100, 'application/pdf'), 'tipo_documento_id' => $tipoDocumento->id],
    );

    $vinculo = VinculoDocumento::first();
    $documento = $vinculo->documento;

    $respuestaDescarga = $this->actingAs($usuario)->get(route('egresos-cgu.documentos.descargar', [$egreso, $documento]));
    $respuestaDescarga->assertOk();

    $respuestaDesvincular = $this->actingAs($usuario)->delete(route('egresos-cgu.documentos.destroy', [$egreso, $vinculo]));

    $respuestaDesvincular->assertSessionHasNoErrors();
    expect($vinculo->refresh()->activo)->toBeFalse();
    expect(Documento::count())->toBe(1);
});

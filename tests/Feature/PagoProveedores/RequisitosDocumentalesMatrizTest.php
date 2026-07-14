<?php

use App\Models\CasoPagoProveedor;
use App\Models\ConjuntoRequisitosDocumentales;
use App\Models\DefinicionWorkflow;
use App\Models\ModalidadAdquisicion;
use App\Models\Proceso;
use App\Models\RequisitoDocumental;
use App\Models\TipoDocumento;
use App\Models\TipoProcesoPago;
use App\Models\User;
use Database\Seeders\ModalidadesAdquisicionSeeder;
use Database\Seeders\TiposDocumentoSeeder;
use Database\Seeders\TiposProcesoPagoSeeder;
use Database\Seeders\WorkflowAdquisicionesSeeder;
use Database\Seeders\WorkflowPagoProveedoresSeeder;
use Inertia\Testing\AssertableInertia as Assert;

function sembrarParaMatrizRequisitosDocumentales(): void
{
    test()->seed(WorkflowPagoProveedoresSeeder::class);
    test()->seed(TiposDocumentoSeeder::class);
    test()->seed(TiposProcesoPagoSeeder::class);
}

function crearUsuarioConPermisoMatrizRequisitos(): User
{
    $usuario = User::factory()->create();
    $usuario->givePermissionTo('pago_proveedores.administrar_requisitos_documentales');

    return $usuario;
}

test('fijar una celda como obligatorio crea el requisito documental correcto', function () {
    sembrarParaMatrizRequisitosDocumentales();
    $usuario = crearUsuarioConPermisoMatrizRequisitos();

    $tipoDocumento = TipoDocumento::where('codigo', 'FACTURA')->firstOrFail();
    $tipoProceso = TipoProcesoPago::where('codigo', 'COMPRA')->firstOrFail();

    $response = $this->actingAs($usuario)->put(
        route('pago-proveedores.requisitos-documentales.update', $tipoDocumento),
        ['tipo_proceso_pago_id' => $tipoProceso->id, 'tipo_requisito' => 'obligatorio'],
    );

    $response->assertSessionHasNoErrors();

    $conjunto = ConjuntoRequisitosDocumentales::where('codigo', 'pago_proveedores')->firstOrFail();
    $definicion = DefinicionWorkflow::where('codigo', 'pago_proveedores')->firstOrFail();

    $requisito = RequisitoDocumental::where('tipo_documento_id', $tipoDocumento->id)
        ->where('tipo_proceso_pago_id', $tipoProceso->id)
        ->first();

    expect($requisito)->not->toBeNull();
    expect($requisito->conjunto_requisitos_documentales_id)->toBe($conjunto->id);
    expect($requisito->definicion_workflow_id)->toBe($definicion->id);
    expect($requisito->tipo_requisito)->toBe('obligatorio');
    expect($requisito->modalidad_id)->toBeNull();
});

test('fijar "no aplica" sobre una combinación existente elimina la fila', function () {
    sembrarParaMatrizRequisitosDocumentales();
    $usuario = crearUsuarioConPermisoMatrizRequisitos();

    $tipoDocumento = TipoDocumento::where('codigo', 'FACTURA')->firstOrFail();
    $tipoProceso = TipoProcesoPago::where('codigo', 'COMPRA')->firstOrFail();

    $conjunto = ConjuntoRequisitosDocumentales::firstOrCreate(['codigo' => 'pago_proveedores'], ['nombre' => 'Pago de Proveedores', 'activo' => true]);
    $definicion = DefinicionWorkflow::where('codigo', 'pago_proveedores')->firstOrFail();

    RequisitoDocumental::create([
        'conjunto_requisitos_documentales_id' => $conjunto->id,
        'definicion_workflow_id' => $definicion->id,
        'tipo_documento_id' => $tipoDocumento->id,
        'tipo_proceso_pago_id' => $tipoProceso->id,
        'tipo_requisito' => 'obligatorio',
        'activo' => true,
    ]);

    $response = $this->actingAs($usuario)->put(
        route('pago-proveedores.requisitos-documentales.update', $tipoDocumento),
        ['tipo_proceso_pago_id' => $tipoProceso->id, 'tipo_requisito' => null],
    );

    $response->assertSessionHasNoErrors();

    expect(
        RequisitoDocumental::where('tipo_documento_id', $tipoDocumento->id)
            ->where('tipo_proceso_pago_id', $tipoProceso->id)
            ->exists(),
    )->toBeFalse();
});

test('fijar la columna "Todos los tipos" crea la fila con tipo_proceso_pago_id null', function () {
    sembrarParaMatrizRequisitosDocumentales();
    $usuario = crearUsuarioConPermisoMatrizRequisitos();

    $tipoDocumento = TipoDocumento::where('codigo', 'COMPROBANTE')->firstOrFail();

    $response = $this->actingAs($usuario)->put(
        route('pago-proveedores.requisitos-documentales.update', $tipoDocumento),
        ['tipo_proceso_pago_id' => null, 'tipo_requisito' => 'obligatorio'],
    );

    $response->assertSessionHasNoErrors();

    $requisito = RequisitoDocumental::where('tipo_documento_id', $tipoDocumento->id)
        ->whereNull('tipo_proceso_pago_id')
        ->first();

    expect($requisito)->not->toBeNull();
    expect($requisito->tipo_requisito)->toBe('obligatorio');
});

test('el checklist de un caso existente refleja un cambio de la matriz al recargar la página del caso', function () {
    sembrarParaMatrizRequisitosDocumentales();
    $usuario = crearUsuarioConPermisoMatrizRequisitos();

    $tipoProceso = TipoProcesoPago::create(['codigo' => 'CONSUMOS_BASICOS', 'nombre' => 'Consumos básicos', 'activo' => true]);
    $tipoDocumento = TipoDocumento::where('codigo', 'FACTURA')->firstOrFail();

    $definicion = DefinicionWorkflow::where('codigo', 'pago_proveedores')->firstOrFail();
    $caso = CasoPagoProveedor::create(['sgf_id' => 'sgf-matriz-checklist-1', 'monto' => 1000]);
    $proceso = Proceso::create([
        'definicion_workflow_id' => $definicion->id,
        'estado_actual_id' => $definicion->estados()->where('es_inicial', true)->value('id'),
        'sujeto_type' => CasoPagoProveedor::class,
        'sujeto_id' => $caso->id,
        'monto' => 1000,
        'tipo_proceso_pago_id' => $tipoProceso->id,
    ]);

    $primeraCarga = $this->actingAs($usuario)->get(route('pago-proveedores.casos.show', $caso));
    $itemsAntes = $primeraCarga->viewData('page')['props']['caso']['proceso']['checklist']['items'] ?? [];
    expect(collect($itemsAntes)->pluck('tipo_documento'))->not->toContain('Factura');

    $this->actingAs($usuario)->put(
        route('pago-proveedores.requisitos-documentales.update', $tipoDocumento),
        ['tipo_proceso_pago_id' => $tipoProceso->id, 'tipo_requisito' => 'obligatorio'],
    );

    $segundaCarga = $this->actingAs($usuario)->get(route('pago-proveedores.casos.show', $caso));
    $itemsDespues = $segundaCarga->viewData('page')['props']['caso']['proceso']['checklist']['items'];
    $itemFactura = collect($itemsDespues)->firstWhere('tipo_documento', 'Factura');

    expect($itemFactura)->not->toBeNull();
    expect($itemFactura['tipo_requisito'])->toBe('obligatorio');
});

test('la matriz no expone ni permite crear filas del conjunto de requisitos de adquisiciones', function () {
    $this->withoutVite();
    sembrarParaMatrizRequisitosDocumentales();
    $this->seed(ModalidadesAdquisicionSeeder::class);
    $this->seed(WorkflowAdquisicionesSeeder::class);
    $usuario = crearUsuarioConPermisoMatrizRequisitos();

    $tipoDocumento = TipoDocumento::where('codigo', 'BASES_LICITACION')->firstOrFail();
    $conjuntoAdquisiciones = ConjuntoRequisitosDocumentales::firstOrCreate(['codigo' => 'adquisiciones'], ['nombre' => 'Adquisiciones', 'activo' => true]);
    $definicionAdquisiciones = DefinicionWorkflow::where('codigo', 'adquisiciones')->firstOrFail();
    $modalidad = ModalidadAdquisicion::where('codigo', 'LICITACION_PUBLICA')->firstOrFail();

    RequisitoDocumental::create([
        'conjunto_requisitos_documentales_id' => $conjuntoAdquisiciones->id,
        'definicion_workflow_id' => $definicionAdquisiciones->id,
        'tipo_documento_id' => $tipoDocumento->id,
        'modalidad_id' => $modalidad->id,
        'tipo_requisito' => 'obligatorio',
        'activo' => true,
    ]);

    $response = $this->actingAs($usuario)->get(route('pago-proveedores.requisitos-documentales.index'));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->where('requisitos', fn ($requisitos) => collect($requisitos)
            ->where('tipo_documento_id', $tipoDocumento->id)
            ->isEmpty())
    );

    // La fila de Adquisiciones sigue existiendo intacta, sin que la matriz la haya tocado.
    expect(
        RequisitoDocumental::where('conjunto_requisitos_documentales_id', $conjuntoAdquisiciones->id)
            ->where('tipo_documento_id', $tipoDocumento->id)
            ->where('modalidad_id', $modalidad->id)
            ->exists(),
    )->toBeTrue();
});

test('un usuario sin el permiso recibe 403 al ver o actualizar la matriz', function () {
    sembrarParaMatrizRequisitosDocumentales();
    $usuario = User::factory()->create();
    $tipoDocumento = TipoDocumento::where('codigo', 'FACTURA')->firstOrFail();

    $this->actingAs($usuario)
        ->get(route('pago-proveedores.requisitos-documentales.index'))
        ->assertForbidden();

    $this->actingAs($usuario)
        ->put(route('pago-proveedores.requisitos-documentales.update', $tipoDocumento), [
            'tipo_proceso_pago_id' => null,
            'tipo_requisito' => 'obligatorio',
        ])
        ->assertForbidden();

    expect(RequisitoDocumental::where('tipo_documento_id', $tipoDocumento->id)->exists())->toBeFalse();
});

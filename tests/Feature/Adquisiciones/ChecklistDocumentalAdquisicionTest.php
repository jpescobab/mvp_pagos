<?php

use App\Models\Ccosto;
use App\Models\ConjuntoRequisitosDocumentales;
use App\Models\Documento;
use App\Models\Funcionario;
use App\Models\Institucion;
use App\Models\ModalidadAdquisicion;
use App\Models\RequisitoDocumental;
use App\Models\TipoDocumento;
use App\Models\User;
use App\Services\Adquisiciones\ProcesoAdquisicionService;
use Database\Seeders\ModalidadesAdquisicionSeeder;
use Database\Seeders\RequisitosDocumentalesAdquisicionesSeeder;
use Database\Seeders\TiposDocumentoSeeder;
use Database\Seeders\WorkflowAdquisicionesSeeder;
use Inertia\Testing\AssertableInertia as Assert;

function crearCcostoDePruebaParaChecklist(): Ccosto
{
    $sufijo = fake()->unique()->numerify('####');

    $institucion = Institucion::create(['codigo' => "CAPJ-CL-{$sufijo}", 'nombre' => 'CAPJ']);
    $jurisdiccion = $institucion->jurisdicciones()->create(['codigo' => "14-CL-{$sufijo}", 'nombre' => 'Zonal Coyhaique']);
    $cfinanciero = $jurisdiccion->cfinancieros()->create(['codigo' => "CF-CL-{$sufijo}", 'nombre' => 'Centro Financiero 1']);

    return $cfinanciero->ccostos()->create(['codigo' => "CC-CL-{$sufijo}", 'nombre' => 'Centro de Costo 1']);
}

function sembrarRequisitosDocumentalesAdquisiciones(): void
{
    test()->seed(ModalidadesAdquisicionSeeder::class);
    test()->seed(WorkflowAdquisicionesSeeder::class);
    test()->seed(TiposDocumentoSeeder::class);
    test()->seed(RequisitosDocumentalesAdquisicionesSeeder::class);
}

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function datosSolicitudParaChecklist(array $overrides = []): array
{
    $ccostoId = $overrides['ccosto_id'] ?? crearCcostoDePruebaParaChecklist()->id;

    return array_merge([
        'fecha_inicio' => now()->toDateString(),
        'nombre' => 'Compra de prueba',
        'id_requerimiento' => null,
        'ccosto_id' => $ccostoId,
        'funcionario_requirente_id' => Funcionario::create([
            'rut' => fake()->unique()->numerify('#########'),
            'nombre' => fake()->name(),
            'ccosto_id' => $ccostoId,
            'activo' => true,
        ])->id,
        'caracteristicas' => 'Compra de equipos de climatización',
        'motivo_contratacion' => 'Reposición de equipos',
        'en_plan_compras' => false,
        'id_pac' => null,
        'codigo_bip' => null,
        'convenio_marco' => true,
        'monto_estimado_solicitado' => 100000,
    ], $overrides);
}

test('el seeder crea los tipos de documento y la matriz de requisitos por modalidad', function () {
    sembrarRequisitosDocumentalesAdquisiciones();

    expect(TipoDocumento::where('codigo', 'CONTRATO')->exists())->toBeTrue();
    expect(TipoDocumento::where('codigo', 'BASES_LICITACION')->exists())->toBeTrue();
    expect(TipoDocumento::where('codigo', 'INFORME_JUSTIFICACION_TRATO_DIRECTO')->exists())->toBeTrue();

    $conjunto = ConjuntoRequisitosDocumentales::where('codigo', 'adquisiciones')->first();
    expect($conjunto)->not->toBeNull();

    $licitacionPublica = ModalidadAdquisicion::where('codigo', 'LICITACION_PUBLICA')->first();
    $codigosLicitacionPublica = RequisitoDocumental::where('conjunto_requisitos_documentales_id', $conjunto->id)
        ->where('modalidad_id', $licitacionPublica->id)
        ->with('tipoDocumento')
        ->get()
        ->pluck('tipoDocumento.codigo');

    expect($codigosLicitacionPublica)->toContain('BASES_LICITACION', 'GARANTIA', 'CONTRATO');

    $tratoDirecto = ModalidadAdquisicion::where('codigo', 'TRATO_DIRECTO')->first();
    $codigosTratoDirecto = RequisitoDocumental::where('conjunto_requisitos_documentales_id', $conjunto->id)
        ->where('modalidad_id', $tratoDirecto->id)
        ->with('tipoDocumento')
        ->get()
        ->pluck('tipoDocumento.codigo');

    expect($codigosTratoDirecto)->toContain('INFORME_JUSTIFICACION_TRATO_DIRECTO');
});

test('abrir el detalle de un proceso con modalidad trato directo genera un checklist con el informe de justificación', function () {
    $this->withoutVite();
    sembrarRequisitosDocumentalesAdquisiciones();

    $proceso = app(ProcesoAdquisicionService::class)->crear(datosSolicitudParaChecklist(['convenio_marco' => false]));

    $usuario = User::factory()->create();
    $usuario->givePermissionTo('adquisiciones.consultar_proceso');

    $response = $this->actingAs($usuario)->get(route('adquisiciones.procesos.show', $proceso));

    $response->assertOk();
    $response->assertInertia(function (Assert $page) {
        $page->component('adquisiciones/procesos/show', shouldExist: false);
        $items = $page->toArray()['props']['proceso']['proceso']['checklist']['items'];
        $tiposDocumento = array_column($items, 'tipo_documento');

        expect($tiposDocumento)->toContain('Informe de Justificación de Trato Directo');
        expect($tiposDocumento)->toContain('Resolución de Adjudicación');
    });
});

test('abrir el detalle de un proceso con modalidad convenio marco no exige el informe de justificación', function () {
    $this->withoutVite();
    sembrarRequisitosDocumentalesAdquisiciones();

    $proceso = app(ProcesoAdquisicionService::class)->crear(datosSolicitudParaChecklist(['convenio_marco' => true]));

    $usuario = User::factory()->create();
    $usuario->givePermissionTo('adquisiciones.consultar_proceso');

    $response = $this->actingAs($usuario)->get(route('adquisiciones.procesos.show', $proceso));

    $response->assertOk();
    $response->assertInertia(function (Assert $page) {
        $page->component('adquisiciones/procesos/show', shouldExist: false);
        $items = $page->toArray()['props']['proceso']['proceso']['checklist']['items'];
        $tiposDocumento = array_column($items, 'tipo_documento');

        expect($tiposDocumento)->not->toContain('Informe de Justificación de Trato Directo');
        expect($tiposDocumento)->not->toContain('Resolución de Adjudicación');
        expect($tiposDocumento)->toContain('Contrato');
    });
});

test('abrir el detalle dos veces no duplica los items del checklist', function () {
    $this->withoutVite();
    sembrarRequisitosDocumentalesAdquisiciones();

    $proceso = app(ProcesoAdquisicionService::class)->crear(datosSolicitudParaChecklist(['convenio_marco' => true]));

    $usuario = User::factory()->create();
    $usuario->givePermissionTo('adquisiciones.consultar_proceso');

    $this->actingAs($usuario)->get(route('adquisiciones.procesos.show', $proceso));
    $this->actingAs($usuario)->get(route('adquisiciones.procesos.show', $proceso));

    $cantidadItems = $proceso->proceso->checklist->items()->count();
    $cantidadRequisitosEsperados = RequisitoDocumental::whereHas('conjuntoRequisitos', fn ($query) => $query->where('codigo', 'adquisiciones'))
        ->where('modalidad_id', $proceso->modalidad_id)
        ->count();

    expect($cantidadItems)->toBe($cantidadRequisitosEsperados);
});

test('un documento de contrato ya vinculado se refleja en el checklist con documento_id y estado cargado', function () {
    $this->withoutVite();
    sembrarRequisitosDocumentalesAdquisiciones();

    $proceso = app(ProcesoAdquisicionService::class)->crear(datosSolicitudParaChecklist(['convenio_marco' => true]));

    $tipoContrato = TipoDocumento::where('codigo', 'CONTRATO')->first();
    $documento = Documento::create(['tipo_documento_id' => $tipoContrato->id, 'titulo' => 'contrato-firmado.pdf']);
    $proceso->proceso->vinculosDocumento()->create(['documento_id' => $documento->id, 'activo' => true]);

    $usuario = User::factory()->create();
    $usuario->givePermissionTo('adquisiciones.consultar_proceso');

    $response = $this->actingAs($usuario)->get(route('adquisiciones.procesos.show', $proceso));

    $response->assertOk();
    $response->assertInertia(function (Assert $page) use ($documento) {
        $page->component('adquisiciones/procesos/show', shouldExist: false);
        $items = $page->toArray()['props']['proceso']['proceso']['checklist']['items'];
        $itemContrato = collect($items)->firstWhere('tipo_documento', 'Contrato');

        expect($itemContrato['documento_id'])->toBe($documento->id);
        expect($itemContrato['estado_cumplimiento'])->toBe('cargado');
    });
});

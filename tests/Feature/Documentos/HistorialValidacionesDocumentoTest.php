<?php

use App\Models\Ccosto;
use App\Models\Documento;
use App\Models\Institucion;
use App\Models\ModalidadAdquisicion;
use App\Models\TipoDocumento;
use App\Models\User;
use App\Services\Adquisiciones\ProcesoAdquisicionService;
use Database\Seeders\ModalidadesAdquisicionSeeder;
use Database\Seeders\WorkflowAdquisicionesSeeder;
use Inertia\Testing\AssertableInertia as Assert;

function crearCcostoDePruebaParaHistorial(): Ccosto
{
    $sufijo = fake()->unique()->numerify('####');

    $institucion = Institucion::create(['codigo' => "CAPJ-HV-{$sufijo}", 'nombre' => 'CAPJ']);
    $jurisdiccion = $institucion->jurisdicciones()->create(['codigo' => "14-HV-{$sufijo}", 'nombre' => 'Zonal Coyhaique']);
    $cfinanciero = $jurisdiccion->cfinancieros()->create(['codigo' => "CF-HV-{$sufijo}", 'nombre' => 'Centro Financiero 1']);

    return $cfinanciero->ccostos()->create(['codigo' => "CC-HV-{$sufijo}", 'nombre' => 'Centro de Costo 1']);
}

test('el detalle de un proceso incluye el historial completo de validaciones de un documento', function () {
    $this->withoutVite();
    $this->seed(ModalidadesAdquisicionSeeder::class);
    $this->seed(WorkflowAdquisicionesSeeder::class);

    $proceso = app(ProcesoAdquisicionService::class)->crear([
        'codigo' => 'ADQ-HIST-001',
        'modalidad_id' => ModalidadAdquisicion::where('codigo', 'LICITACION_PUBLICA')->value('id'),
        'ccosto_id' => crearCcostoDePruebaParaHistorial()->id,
        'objeto' => 'Compra de prueba',
    ]);

    $tipoDocumento = TipoDocumento::create(['codigo' => 'TIPO_HIST', 'nombre' => 'Tipo de prueba']);
    $documento = Documento::create(['tipo_documento_id' => $tipoDocumento->id, 'titulo' => 'doc.pdf']);
    $proceso->proceso->vinculosDocumento()->create(['documento_id' => $documento->id, 'activo' => true]);

    $validador = User::factory()->create(['name' => 'Ana Revisora']);
    $documento->validaciones()->create(['estado' => 'rechazado', 'observacion' => 'Falta firma', 'validado_por' => $validador->id, 'validado_en' => now()->subDay()]);
    $documento->validaciones()->create(['estado' => 'valido', 'validado_por' => $validador->id, 'validado_en' => now()]);

    $usuario = User::factory()->create();

    $response = $this->actingAs($usuario)->get(route('adquisiciones.procesos.show', $proceso));

    $response->assertOk();
    $response->assertInertia(function (Assert $page) {
        $page->component('adquisiciones/procesos/show', shouldExist: false);
        $validaciones = $page->toArray()['props']['proceso']['proceso']['documentos'][0]['validaciones'];

        expect($validaciones)->toHaveCount(2);
        expect($validaciones[0]['estado'])->toBe('valido');
        expect($validaciones[1]['estado'])->toBe('rechazado');
    });
});

test('la observacion de un rechazo pasado sigue presente en el historial despues de una validacion posterior', function () {
    $this->withoutVite();
    $this->seed(ModalidadesAdquisicionSeeder::class);
    $this->seed(WorkflowAdquisicionesSeeder::class);

    $proceso = app(ProcesoAdquisicionService::class)->crear([
        'codigo' => 'ADQ-HIST-002',
        'modalidad_id' => ModalidadAdquisicion::where('codigo', 'LICITACION_PUBLICA')->value('id'),
        'ccosto_id' => crearCcostoDePruebaParaHistorial()->id,
        'objeto' => 'Compra de prueba 2',
    ]);

    $tipoDocumento = TipoDocumento::create(['codigo' => 'TIPO_HIST2', 'nombre' => 'Tipo de prueba 2']);
    $documento = Documento::create(['tipo_documento_id' => $tipoDocumento->id, 'titulo' => 'doc.pdf']);
    $proceso->proceso->vinculosDocumento()->create(['documento_id' => $documento->id, 'activo' => true]);

    $documento->validaciones()->create(['estado' => 'rechazado', 'observacion' => 'Falta certificado de vigencia']);
    $documento->validaciones()->create(['estado' => 'valido']);

    $usuario = User::factory()->create();

    $response = $this->actingAs($usuario)->get(route('adquisiciones.procesos.show', $proceso));

    $response->assertOk();
    $response->assertInertia(function (Assert $page) {
        $page->component('adquisiciones/procesos/show', shouldExist: false);
        $validaciones = $page->toArray()['props']['proceso']['proceso']['documentos'][0]['validaciones'];
        $observaciones = array_column($validaciones, 'observacion');

        expect($observaciones)->toContain('Falta certificado de vigencia');
    });
});

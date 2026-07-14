<?php

use App\Models\Ccosto;
use App\Models\Institucion;
use App\Models\ModalidadAdquisicion;
use App\Models\ProcesoAdquisicion;
use Database\Seeders\ModalidadesAdquisicionSeeder;
use Database\Seeders\WorkflowPagoProveedoresSeeder;

function crearCcostoDePruebaParaCfinancieroId(string $sufijo): Ccosto
{
    $institucion = Institucion::create(['codigo' => "CAPJ-CFID-{$sufijo}", 'nombre' => 'CAPJ']);
    $jurisdiccion = $institucion->jurisdicciones()->create(['codigo' => "14-CFID-{$sufijo}", 'nombre' => "Zonal {$sufijo}"]);
    $cfinanciero = $jurisdiccion->cfinancieros()->create(['codigo' => "CF-CFID-{$sufijo}", 'nombre' => "Centro {$sufijo}"]);

    return $cfinanciero->ccostos()->create(['codigo' => "CC-CFID-{$sufijo}", 'nombre' => "Costo {$sufijo}"]);
}

beforeEach(function () {
    $this->seed(WorkflowPagoProveedoresSeeder::class);
    $this->seed(ModalidadesAdquisicionSeeder::class);
});

test('un caso sin proceso_adquisicion vinculado usa el cfinanciero por defecto configurado', function () {
    $default = crearCcostoDePruebaParaCfinancieroId('DEFAULT')->cfinanciero;
    config(['pago-proveedores.cfinanciero_default_codigo' => $default->codigo]);

    $caso = crearCasoPagoProveedorDePrueba('sgf-cfid-1');

    expect($caso->proceso_adquisicion_id)->toBeNull();
    expect($caso->cfinancieroId())->toBe($default->id);
});

test('un caso con proceso_adquisicion vinculado ignora el default y usa el cfinanciero real', function () {
    $default = crearCcostoDePruebaParaCfinancieroId('DEFAULT2')->cfinanciero;
    config(['pago-proveedores.cfinanciero_default_codigo' => $default->codigo]);

    $ccostoReal = crearCcostoDePruebaParaCfinancieroId('REAL');
    $adquisicion = ProcesoAdquisicion::create([
        'codigo' => 'ADQ-CFID-1',
        'modalidad_id' => ModalidadAdquisicion::where('codigo', 'TRATO_DIRECTO')->value('id'),
        'ccosto_id' => $ccostoReal->id,
        'objeto' => 'Compra de prueba',
    ]);

    $caso = crearCasoPagoProveedorDePrueba('sgf-cfid-2');
    $caso->update(['proceso_adquisicion_id' => $adquisicion->id]);

    expect($caso->refresh()->cfinancieroId())->toBe($ccostoReal->cfinanciero_id)
        ->and($caso->cfinancieroId())->not->toBe($default->id);
});

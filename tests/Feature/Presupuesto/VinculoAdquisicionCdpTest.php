<?php

use App\Models\Ccosto;
use App\Models\Funcionario;
use App\Models\ProcesoAdquisicion;
use App\Models\User;
use App\Services\Adquisiciones\ProcesoAdquisicionService;
use App\Services\Presupuesto\CrearBorradorCertificadoDisponibilidadService;
use App\Services\Workflow\TransicionWorkflowService;
use Database\Seeders\CcostosSeeder;
use Database\Seeders\CfinancierosSeeder;
use Database\Seeders\JurisdiccionesSeeder;
use Database\Seeders\ModalidadesAdquisicionSeeder;
use Database\Seeders\WorkflowAdquisicionesSeeder;
use Database\Seeders\WorkflowPresupuestoCdpSeeder;

function crearProcesoAdquisicionDePruebaParaCdp(): ProcesoAdquisicion
{
    (new JurisdiccionesSeeder)->run();
    (new CfinancierosSeeder)->run();
    (new CcostosSeeder)->run();
    (new ModalidadesAdquisicionSeeder)->run();
    (new WorkflowAdquisicionesSeeder)->run();

    $ccosto = Ccosto::where('codigo', '1400010201')->firstOrFail();
    $funcionario = Funcionario::create([
        'rut' => fake()->unique()->numerify('#########'),
        'nombre' => fake()->name(),
        'ccosto_id' => $ccosto->id,
        'activo' => true,
    ]);

    return app(ProcesoAdquisicionService::class)->crear([
        'fecha_inicio' => now()->toDateString(),
        'nombre' => 'Adquisición vinculada a CDP de prueba',
        'ccosto_id' => $ccosto->id,
        'funcionario_requirente_id' => $funcionario->id,
        'caracteristicas' => 'Adquisición vinculada a CDP de prueba',
        'motivo_contratacion' => 'Motivo de prueba',
        'en_plan_compras' => false,
        'convenio_marco' => true,
        'monto_estimado_solicitado' => 100000,
    ]);
}

test('un cdp puede vincularse a una adquisición existente', function () {
    (new WorkflowPresupuestoCdpSeeder)->run();
    $procesoAdquisicion = crearProcesoAdquisicionDePruebaParaCdp();
    $presupuesto = crearLineaPresupuestoDePrueba();

    $cdp = app(CrearBorradorCertificadoDisponibilidadService::class)->crear(
        datosCdpDePrueba($presupuesto, ['proceso_adquisicion_id' => $procesoAdquisicion->id]),
    );

    expect($cdp->proceso_adquisicion_id)->toBe($procesoAdquisicion->id);
    expect($procesoAdquisicion->cdps()->count())->toBe(1);
});

test('un cdp sin vínculo a adquisiciones es válido', function () {
    $cdp = crearCdpBorradorDePrueba();

    expect($cdp->proceso_adquisicion_id)->toBeNull();
});

test('la existencia o ausencia de un cdp vinculado no afecta las transiciones de adquisiciones', function () {
    $procesoAdquisicion = crearProcesoAdquisicionDePruebaParaCdp();

    $resultado = app(TransicionWorkflowService::class)->execute($procesoAdquisicion->proceso, 'enviar_a_revision');

    expect($resultado->estadoActual->codigo)->toBe('en_revision');

    $usuario = User::factory()->create();
    $usuario->givePermissionTo('adquisiciones.publicar');

    $resultado = app(TransicionWorkflowService::class)->execute($procesoAdquisicion->proceso, 'publicar', user: $usuario);

    expect($resultado->estadoActual->codigo)->toBe('publicada');
});

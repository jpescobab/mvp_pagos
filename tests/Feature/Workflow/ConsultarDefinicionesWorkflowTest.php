<?php

use App\Models\DefinicionWorkflow;
use App\Models\User;
use Database\Seeders\ModalidadesAdquisicionSeeder;
use Database\Seeders\WorkflowAdquisicionesSeeder;
use Database\Seeders\WorkflowPagoProveedoresSeeder;
use Inertia\Testing\AssertableInertia as Assert;

test('un usuario autenticado puede listar las definiciones de workflow con sus conteos', function () {
    $this->seed(WorkflowPagoProveedoresSeeder::class);
    $this->seed(ModalidadesAdquisicionSeeder::class);
    $this->seed(WorkflowAdquisicionesSeeder::class);

    $usuario = User::factory()->create();

    $response = $this->actingAs($usuario)->get(route('workflow.definiciones.index'));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('workflow/definiciones/index')
        ->has('definiciones', 2)
        ->where('definiciones.0.codigo', 'adquisiciones')
        ->where('definiciones.0.estados_count', 8)
        ->where('definiciones.0.transiciones_count', 8)
        ->where('definiciones.1.codigo', 'pago_proveedores')
        ->where('definiciones.1.estados_count', 14)
        ->where('definiciones.1.transiciones_count', 17)
    );
});

test('el detalle de pago_proveedores incluye sus estados y transiciones con los flags correctos', function () {
    $this->seed(WorkflowPagoProveedoresSeeder::class);

    $usuario = User::factory()->create();
    $definicion = DefinicionWorkflow::where('codigo', 'pago_proveedores')->first();

    $response = $this->actingAs($usuario)->get(route('workflow.definiciones.show', $definicion));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('workflow/definiciones/show')
        ->has('definicion.estados', 14)
        ->has('definicion.transiciones', 17)
        ->where(
            'definicion.transiciones',
            fn ($transiciones) => collect($transiciones)
                ->firstWhere('codigo', 'registrar_en_cgu')['permiso_requerido'] === 'pago_proveedores.registrar_cgu'
                && collect($transiciones)
                    ->firstWhere('codigo', 'aprobar_finanzas')['documentos_requeridos'] === ['FACTURA']
                && collect($transiciones)
                    ->firstWhere('codigo', 'observar_finanzas')['requiere_comentario'] === true,
        )
    );
});

test('un usuario no autenticado es redirigido al login', function () {
    $response = $this->get(route('workflow.definiciones.index'));

    $response->assertRedirect(route('login'));
});

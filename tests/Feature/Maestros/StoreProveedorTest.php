<?php

use App\Models\Proveedor;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;

test('el formulario de creación de proveedor acepta rut y nombre iniciales por query string', function () {
    $this->withoutVite();
    $this->seed(RolesAndPermissionsSeeder::class);

    $actor = User::factory()->create();
    $actor->givePermissionTo('core_institucional.administrar');

    $response = $this->actingAs($actor)->get(route('maestros.proveedores.create', [
        'rutproveedor' => '76.123.456-7',
        'nombre' => 'Proveedor de Prueba SpA',
    ]));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('maestros/proveedores/create', shouldExist: false)
        ->where('valoresIniciales.rutproveedor', '76.123.456-7')
        ->where('valoresIniciales.nombre', 'Proveedor de Prueba SpA')
    );
});

test('el formulario de creación de proveedor no exige valores iniciales', function () {
    $this->withoutVite();
    $this->seed(RolesAndPermissionsSeeder::class);

    $actor = User::factory()->create();
    $actor->givePermissionTo('core_institucional.administrar');

    $response = $this->actingAs($actor)->get(route('maestros.proveedores.create'));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('maestros/proveedores/create', shouldExist: false)
        ->where('valoresIniciales', null)
    );
});

test('un usuario con core_institucional.administrar puede registrar un proveedor con datos mínimos', function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    $actor = User::factory()->create();
    $actor->givePermissionTo('core_institucional.administrar');

    $response = $this->actingAs($actor)->post(route('maestros.proveedores.store'), [
        'rutproveedor' => '76.234.567-8',
        'nombre' => 'Comercial Andes Sur Ltda.',
    ]);

    $response->assertRedirect(route('maestros.proveedores.index'));

    $proveedor = Proveedor::where('rutproveedor', '76234567-8')->first();
    expect($proveedor)->not->toBeNull();
    expect($proveedor->rutproveedor)->toBe('76234567-8');
    expect($proveedor->nombre)->toBe('Comercial Andes Sur Ltda.');
    expect($proveedor->activo)->toBeTrue();
    expect($proveedor->giro)->toBeNull();
});

test('un usuario con core_institucional.administrar puede registrar un proveedor con todos los datos y un documento de respaldo', function () {
    Storage::fake('local');
    $this->seed(RolesAndPermissionsSeeder::class);

    $actor = User::factory()->create();
    $actor->givePermissionTo('core_institucional.administrar');

    $response = $this->actingAs($actor)->post(route('maestros.proveedores.store'), [
        'rutproveedor' => '77.890.123-4',
        'nombre' => 'Tech Patagonia SpA',
        'giro' => 'Servicios de tecnología e informática',
        'tipo_contribuyente' => 'persona_juridica',
        'rubros' => ['tecnologia_informatica', 'asesoria_profesional'],
        'contacto' => 'Carlos Vega',
        'contacto_cargo' => 'Gerente comercial',
        'contacto_telefono' => '+56 9 7712 0098',
        'correo' => 'carlos@techpatagonia.cl',
        'direccion' => 'Av. Ogana 123',
        'region' => 'Aysén',
        'comuna' => 'Coyhaique',
        'banco' => 'Banco BCI',
        'tipo_cuenta' => 'cuenta_corriente',
        'numero_cuenta' => '00012345678',
        'condicion_pago' => 'dias_60',
        'moneda' => 'clp',
        'correo_pago' => 'pagos@techpatagonia.cl',
        'documento_respaldo' => UploadedFile::fake()->create('certificado.pdf', 200, 'application/pdf'),
        'notas_internas' => 'Proveedor recomendado por licitación anterior.',
        'activo' => true,
    ]);

    $response->assertRedirect(route('maestros.proveedores.index'));

    $proveedor = Proveedor::where('rutproveedor', '77890123-4')->first();
    expect($proveedor)->not->toBeNull();
    expect($proveedor->rubros)->toBe(['tecnologia_informatica', 'asesoria_profesional']);
    expect($proveedor->banco)->toBe('Banco BCI');
    expect($proveedor->documento_respaldo_path)->not->toBeNull();
    Storage::disk('local')->assertExists($proveedor->documento_respaldo_path);
});

test('registrar un proveedor con un rut ya existente falla la validación', function () {
    Proveedor::create(['rutproveedor' => '11111111-1', 'nombre' => 'Proveedor Existente']);

    $this->seed(RolesAndPermissionsSeeder::class);

    $actor = User::factory()->create();
    $actor->givePermissionTo('core_institucional.administrar');

    $response = $this->actingAs($actor)->post(route('maestros.proveedores.store'), [
        'rutproveedor' => '11111111-1',
        'nombre' => 'Otro Proveedor',
    ]);

    $response->assertInvalid(['rutproveedor']);
    expect(Proveedor::where('rutproveedor', '11111111-1')->count())->toBe(1);
});

test('registrar un proveedor con el mismo rut en otro formato (con puntos) falla la validación en vez de duplicar', function () {
    Proveedor::create(['rutproveedor' => '89862200-2', 'nombre' => 'LATAM AIRLINES GROUP S.A.']);

    $this->seed(RolesAndPermissionsSeeder::class);

    $actor = User::factory()->create();
    $actor->givePermissionTo('core_institucional.administrar');

    $response = $this->actingAs($actor)->post(route('maestros.proveedores.store'), [
        'rutproveedor' => '89.862.200-2',
        'nombre' => 'LATAM AIRLINES GROUP S.A.',
    ]);

    $response->assertInvalid(['rutproveedor']);
    expect(Proveedor::where('nombre', 'LATAM AIRLINES GROUP S.A.')->count())->toBe(1);
});

test('un usuario sin core_institucional.administrar no puede registrar un proveedor', function () {
    $actor = User::factory()->create();

    $response = $this->actingAs($actor)->post(route('maestros.proveedores.store'), [
        'rutproveedor' => '11111111-1',
        'nombre' => 'Proveedor Nuevo',
    ]);

    $response->assertForbidden();
    expect(Proveedor::where('rutproveedor', '11111111-1')->count())->toBe(0);
});

test('un usuario sin core_institucional.administrar no puede acceder al formulario de alta', function () {
    $actor = User::factory()->create();

    $response = $this->actingAs($actor)->get(route('maestros.proveedores.create'));

    $response->assertForbidden();
});

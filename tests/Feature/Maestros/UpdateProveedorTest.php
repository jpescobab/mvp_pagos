<?php

use App\Models\Proveedor;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

test('un usuario con core_institucional.administrar puede editar un proveedor', function () {
    $proveedor = Proveedor::create(['rutproveedor' => '76.234.567-8', 'nombre' => 'Comercial Andes Sur Ltda.']);

    $this->seed(RolesAndPermissionsSeeder::class);

    $actor = User::factory()->create();
    $actor->givePermissionTo('core_institucional.administrar');

    $response = $this->actingAs($actor)->patch(route('maestros.proveedores.update', $proveedor), [
        'rutproveedor' => '76.234.567-8',
        'nombre' => 'Comercial Andes Sur Limitada',
        'giro' => 'Distribución de insumos',
        'estado' => 'inactivo',
    ]);

    $response->assertRedirect(route('maestros.proveedores.show', $proveedor));

    $proveedor->refresh();
    expect($proveedor->nombre)->toBe('Comercial Andes Sur Limitada');
    expect($proveedor->giro)->toBe('Distribución de insumos');
    expect($proveedor->estado)->toBe(Proveedor::ESTADO_INACTIVO);
});

test('editar un proveedor con el rut de otro proveedor falla la validación', function () {
    Proveedor::create(['rutproveedor' => '11111111-1', 'nombre' => 'Proveedor Uno']);
    $proveedor = Proveedor::create(['rutproveedor' => '22222222-2', 'nombre' => 'Proveedor Dos']);

    $this->seed(RolesAndPermissionsSeeder::class);

    $actor = User::factory()->create();
    $actor->givePermissionTo('core_institucional.administrar');

    $response = $this->actingAs($actor)->patch(route('maestros.proveedores.update', $proveedor), [
        'rutproveedor' => '11111111-1',
        'nombre' => 'Proveedor Dos',
    ]);

    $response->assertInvalid(['rutproveedor']);
    expect($proveedor->refresh()->rutproveedor)->toBe('22222222-2');
});

test('editar un proveedor conservando su propio rut no falla la validación', function () {
    $proveedor = Proveedor::create(['rutproveedor' => '22222222-2', 'nombre' => 'Proveedor Dos']);

    $this->seed(RolesAndPermissionsSeeder::class);

    $actor = User::factory()->create();
    $actor->givePermissionTo('core_institucional.administrar');

    $response = $this->actingAs($actor)->patch(route('maestros.proveedores.update', $proveedor), [
        'rutproveedor' => '22222222-2',
        'nombre' => 'Proveedor Dos Actualizado',
    ]);

    $response->assertRedirect(route('maestros.proveedores.show', $proveedor));
    expect($proveedor->refresh()->nombre)->toBe('Proveedor Dos Actualizado');
});

test('reemplazar el documento de respaldo descarta el anterior', function () {
    Storage::fake('local');

    $proveedor = Proveedor::create(['rutproveedor' => '76.234.567-8', 'nombre' => 'Comercial Andes Sur Ltda.']);
    $rutaOriginal = UploadedFile::fake()->create('original.pdf', 100, 'application/pdf')
        ->storeAs("proveedores/{$proveedor->id}", 'documento-respaldo.pdf', 'local');
    $proveedor->update(['documento_respaldo_path' => $rutaOriginal]);

    $this->seed(RolesAndPermissionsSeeder::class);

    $actor = User::factory()->create();
    $actor->givePermissionTo('core_institucional.administrar');

    $response = $this->actingAs($actor)->patch(route('maestros.proveedores.update', $proveedor), [
        'rutproveedor' => '76.234.567-8',
        'nombre' => 'Comercial Andes Sur Ltda.',
        'documento_respaldo' => UploadedFile::fake()->image('nuevo.jpg'),
    ]);

    $response->assertRedirect(route('maestros.proveedores.show', $proveedor));

    $proveedor->refresh();
    expect($proveedor->documento_respaldo_path)->not->toBe($rutaOriginal);
    Storage::disk('local')->assertMissing($rutaOriginal);
    Storage::disk('local')->assertExists($proveedor->documento_respaldo_path);
});

test('un usuario sin core_institucional.administrar no puede editar un proveedor', function () {
    $proveedor = Proveedor::create(['rutproveedor' => '76.234.567-8', 'nombre' => 'Comercial Andes Sur Ltda.']);

    $actor = User::factory()->create();

    $responseGet = $this->actingAs($actor)->get(route('maestros.proveedores.edit', $proveedor));
    $responseGet->assertForbidden();

    $responsePatch = $this->actingAs($actor)->patch(route('maestros.proveedores.update', $proveedor), [
        'rutproveedor' => '76.234.567-8',
        'nombre' => 'Otro nombre',
    ]);
    $responsePatch->assertForbidden();
    expect($proveedor->refresh()->nombre)->toBe('Comercial Andes Sur Ltda.');
});

test('editar un proveedor permite promover un borrador a activo', function () {
    $proveedor = Proveedor::create([
        'rutproveedor' => '79.111.000-1',
        'nombre' => 'Borrador a Promover SpA',
        'estado' => Proveedor::ESTADO_BORRADOR,
    ]);

    $this->seed(RolesAndPermissionsSeeder::class);

    $actor = User::factory()->create();
    $actor->givePermissionTo('core_institucional.administrar');

    $this->actingAs($actor)->patch(route('maestros.proveedores.update', $proveedor), [
        'rutproveedor' => '79.111.000-1',
        'nombre' => 'Borrador a Promover SpA',
        'estado' => 'activo',
    ])->assertRedirect(route('maestros.proveedores.show', $proveedor));

    expect($proveedor->refresh()->estado)->toBe(Proveedor::ESTADO_ACTIVO);
});

test('editar un proveedor rechaza un estado fuera del dominio', function () {
    $proveedor = Proveedor::create([
        'rutproveedor' => '79.222.000-2',
        'nombre' => 'Estado Invalido SpA',
        'estado' => Proveedor::ESTADO_ACTIVO,
    ]);

    $this->seed(RolesAndPermissionsSeeder::class);

    $actor = User::factory()->create();
    $actor->givePermissionTo('core_institucional.administrar');

    $this->actingAs($actor)->patch(route('maestros.proveedores.update', $proveedor), [
        'rutproveedor' => '79.222.000-2',
        'nombre' => 'Estado Invalido SpA',
        'estado' => 'suspendido',
    ])->assertSessionHasErrors('estado');

    expect($proveedor->refresh()->estado)->toBe(Proveedor::ESTADO_ACTIVO);
});

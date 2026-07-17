<?php

use App\Models\User;
use App\Services\Seguridad\PermisosCompartidosResolver;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\WorkflowPagoProveedoresSeeder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

test('paraUsuario retorna una lista vacía para un usuario no autenticado', function () {
    expect(app(PermisosCompartidosResolver::class)->paraUsuario(null))->toBeEmpty();
});

// El test de abajo fuerza CACHE_STORE=database porque el store `array` de
// test (phpunit.xml) no cuesta ninguna query — bajo ese store cualquier
// assertion de conteo de queries pasaría en verde sin probar nada del
// comportamiento real de producción, donde Cache::get() es una query SQL
// real (DatabaseStore::get() delega en 1 query por invocación).

test('paraUsuario bajo CACHE_STORE=database reduce a una sola query en la segunda llamada para el mismo usuario', function () {
    config(['cache.default' => 'database']);
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->seed(WorkflowPagoProveedoresSeeder::class);

    $usuario = User::factory()->create();
    $usuario->assignRole('jefe_finanzas');

    $resolver = app(PermisosCompartidosResolver::class);
    $primero = $resolver->paraUsuario($usuario);
    expect($primero)->toContain('pago_proveedores.revisar_finanzas');

    DB::enableQueryLog();
    $segundo = $resolver->paraUsuario($usuario);
    $consultas = DB::getQueryLog();
    DB::disableQueryLog();

    expect($consultas)->toHaveCount(1);
    expect($segundo->all())->toBe($primero->all());
});

test('el valor cacheado por paraUsuario sobrevive el round-trip de serialización bajo CACHE_STORE=database como array plano', function () {
    config(['cache.default' => 'database']);
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->seed(WorkflowPagoProveedoresSeeder::class);

    $usuario = User::factory()->create();
    $usuario->assignRole('jefe_finanzas');

    $resultado = app(PermisosCompartidosResolver::class)->paraUsuario($usuario);

    $crudo = Cache::get("seguridad:permisos_compartidos:{$usuario->id}");

    expect($crudo)->toBeArray();
    expect($crudo)->toBe($resultado->all());
    expect($crudo)->toContain('pago_proveedores.revisar_finanzas');
});

test('invalidarParaUsuario hace que paraUsuario refleje el nuevo estado sin esperar el TTL', function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->seed(WorkflowPagoProveedoresSeeder::class);

    $usuario = User::factory()->create();
    $usuario->assignRole('jefe_finanzas');

    $resolver = app(PermisosCompartidosResolver::class);
    $antes = $resolver->paraUsuario($usuario);
    expect($antes)->not->toContain('pago_proveedores.revisar_zonal');

    $usuario->givePermissionTo('pago_proveedores.revisar_zonal');

    // Sin invalidar, la caché todavía vigente sigue sin reflejar el permiso nuevo.
    $todaviaCacheado = $resolver->paraUsuario($usuario);
    expect($todaviaCacheado)->not->toContain('pago_proveedores.revisar_zonal');

    $resolver->invalidarParaUsuario($usuario->id);

    $actualizado = $resolver->paraUsuario($usuario);
    expect($actualizado)->toContain('pago_proveedores.revisar_zonal');
});

test('invalidarParaRol hace que paraUsuario refleje el nuevo estado para todos los usuarios del rol, sin esperar el TTL', function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    $rol = Role::firstOrCreate(['name' => 'auditor']);
    $rol->givePermissionTo('auditoria.ver');

    $usuarioUno = User::factory()->create();
    $usuarioUno->assignRole('auditor');
    $usuarioDos = User::factory()->create();
    $usuarioDos->assignRole('auditor');

    $resolver = app(PermisosCompartidosResolver::class);
    expect($resolver->paraUsuario($usuarioUno))->not->toContain('usuarios.ver');
    expect($resolver->paraUsuario($usuarioDos))->not->toContain('usuarios.ver');

    $rol->givePermissionTo('usuarios.ver');

    // Sin invalidar, ambos usuarios siguen viendo la lista vieja cacheada.
    expect($resolver->paraUsuario($usuarioUno))->not->toContain('usuarios.ver');
    expect($resolver->paraUsuario($usuarioDos))->not->toContain('usuarios.ver');

    $resolver->invalidarParaRol($rol);

    // Los objetos $usuarioUno/$usuarioDos ya tienen roles.permissions cargado
    // en memoria (Eloquent cachea relaciones por instancia); una request real
    // siempre resuelve un modelo User nuevo, así que se refrescan aquí para
    // simular eso y no confundir un artefacto del test con un bug real.
    expect($resolver->paraUsuario($usuarioUno->fresh()))->toContain('usuarios.ver');
    expect($resolver->paraUsuario($usuarioDos->fresh()))->toContain('usuarios.ver');
});

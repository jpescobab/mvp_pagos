## 1. Rutas

- [x] 1.1 Crear `routes/pago-proveedores.php`: grupo `middleware(['auth'])->prefix('pago-proveedores')->name('pago-proveedores.')` con `GET casos` (`casos.index`), `GET casos/{caso}` (`casos.show`), `POST casos/{caso}/transiciones` (`casos.transiciones.store`), `GET egresos-cgu` (`egresos-cgu.index`), `GET egresos-cgu/crear` (`egresos-cgu.create`), `POST egresos-cgu` (`egresos-cgu.store`)
- [x] 1.2 Incluir `routes/pago-proveedores.php` desde `routes/web.php` (mismo patrón que `routes/settings.php`)

## 2. Form Requests

- [x] 2.1 Crear `App\Http\Requests\PagoProveedores\EjecutarTransicionRequest` (`codigo`: required string; `comentario`: nullable string)
- [x] 2.2 Crear `App\Http\Requests\PagoProveedores\CrearEgresoCguRequest` (`numero_egreso`: required string unique:egresos_cgu; `fecha`: required date; `observaciones`: nullable string; `casos`: required array min:1; `casos.*.caso_pago_proveedor_id`: required exists:casos_pago_proveedor,id; `casos.*.monto`: required numeric min:0) — autoriza vía `$this->user()->can('pago_proveedores.registrar_egreso')` en `authorize()`

## 3. Policies

- [x] 3.1 Crear `App\Policies\CasoPagoProveedorPolicy` (`viewAny`/`view`: cualquier usuario autenticado)
- [x] 3.2 Crear `App\Policies\EgresoCguPolicy` (`viewAny`/`view`: cualquier usuario autenticado; `create`: `$user->can('pago_proveedores.registrar_egreso')`)
- [x] 3.3 Registrar ambas en `App\Providers\AppServiceProvider::configureAuthorization()` vía `Gate::policy()`, junto a `UserPolicy`/`RolePolicy`

## 4. Resources

- [x] 4.1 Crear `App\Http\Resources\PagoProveedores\EstadoWorkflowResource` (`codigo`, `nombre`, `es_inicial`, `es_final`)
- [x] 4.2 Crear `App\Http\Resources\PagoProveedores\TransicionWorkflowResource` (`codigo`, `nombre`, `requiere_comentario`)
- [x] 4.3 Crear `App\Http\Resources\PagoProveedores\HistorialTransicionResource` (`transicion.codigo`/`nombre`, `estado_origen.codigo`, `estado_destino.codigo`, `user.name`, `comentario`, `created_at`)
- [x] 4.4 Crear `App\Http\Resources\PagoProveedores\ProcesoResource` (`estado_actual` vía `EstadoWorkflowResource`, `cerrado_en`, `historial_transiciones` vía colección de `HistorialTransicionResource`, `transiciones_disponibles` vía colección de `TransicionWorkflowResource` — las transiciones de `definicionWorkflow` cuyo `estado_origen_id` sea el `estado_actual_id` del proceso)
- [x] 4.5 Crear `App\Http\Resources\PagoProveedores\CasoPagoProveedorResource` (`id`, `sgf_id`, `proveedor.nombre`/`rutproveedor`, `monto`, `sgf_status`, `sgf_current_group_raw`, `proceso` vía `ProcesoResource`)
- [x] 4.6 Crear `App\Http\Resources\PagoProveedores\EgresoCguResource` (`numero_egreso`, `fecha`, `monto_total`, `observaciones`, `items` con `caso.sgf_id` y `monto`)

## 5. Controladores

- [x] 5.1 Crear `App\Http\Controllers\PagoProveedores\CasoPagoProveedorController::index()`: pagina `CasoPagoProveedor::with(['proveedor', 'proceso.estadoActual'])`, autoriza `viewAny`, `Inertia::render('pago-proveedores/casos/index', ['casos' => ...])`
- [x] 5.2 Crear `App\Http\Controllers\PagoProveedores\CasoPagoProveedorController::show(CasoPagoProveedor $caso)`: carga `proceso.estadoActual`, `proceso.definicionWorkflow.transiciones`, `proceso.historialTransiciones.transicion`/`estadoOrigen`/`estadoDestino`/`user`, `proceso.checklist.items`; autoriza `view`; `Inertia::render('pago-proveedores/casos/show', ['caso' => ...])`
- [x] 5.3 Crear `App\Http\Controllers\PagoProveedores\TransicionCasoPagoProveedorController::store(CasoPagoProveedor $caso, EjecutarTransicionRequest $request, TransicionWorkflowService $servicio)`: llama `$servicio->execute($caso->proceso, $request->string('codigo'), $request->string('comentario')->toString() ?: null)` dentro de un `try/catch (TransicionWorkflowException $e)`; en éxito redirige de vuelta; en error devuelve `back()->withErrors(['transicion' => $e->getMessage()])`
- [x] 5.4 Crear `App\Http\Controllers\PagoProveedores\EgresoCguController::index()`: pagina `EgresoCgu::with('items.caso')`, autoriza `viewAny`, `Inertia::render('pago-proveedores/egresos-cgu/index', ['egresos' => ...])`
- [x] 5.5 Crear `App\Http\Controllers\PagoProveedores\EgresoCguController::create()`: autoriza `create`, `Inertia::render('pago-proveedores/egresos-cgu/crear')`
- [x] 5.6 Crear `App\Http\Controllers\PagoProveedores\EgresoCguController::store(CrearEgresoCguRequest $request)`: autoriza `create` (ya cubierto por el Form Request), crea `EgresoCgu` + `egresos_cgu_items` dentro de `DB::transaction()`, calcula `monto_total` como la suma de los items, redirige a `egresos-cgu.index`

## 6. Permisos

- [x] 6.1 Agregar el permiso `pago_proveedores.registrar_egreso` a `WorkflowPagoProveedoresSeeder` (mismo arreglo `$permisos`, otorgado a `admin`)

## 7. Tests

- [x] 7.1 Test feature: `casos.index` responde con la página Inertia `pago-proveedores/casos/index` incluyendo los casos
- [x] 7.2 Test feature: `casos.show` responde con el caso, su `Proceso`, estado actual, historial y transiciones disponibles para el estado actual
- [x] 7.3 Test feature: ejecutar una transición válida con el permiso requerido cambia el estado del `Proceso` del caso
- [x] 7.4 Test feature: ejecutar una transición sin el permiso requerido no cambia el estado y la respuesta refleja el error de `TransicionWorkflowService`
- [x] 7.5 Test feature: ejecutar un código de transición no válido para el estado actual no cambia el estado del `Proceso`
- [x] 7.6 Test feature: crear un egreso CGU con el permiso `pago_proveedores.registrar_egreso` crea el egreso y sus items cubriendo varios casos
- [x] 7.7 Test feature: crear un egreso CGU sin el permiso `pago_proveedores.registrar_egreso` es rechazado y no crea ningún registro

## 8. Validación

- [x] 8.1 `composer lint:check`
- [x] 8.2 `composer types:check`
- [x] 8.3 `php artisan test --compact`

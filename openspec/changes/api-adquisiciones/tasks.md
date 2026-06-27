## 1. Rutas

- [x] 1.1 Crear `routes/adquisiciones.php`: grupo `middleware(['auth'])->prefix('adquisiciones')->name('adquisiciones.')` con `GET procesos` (`procesos.index`), `GET procesos/crear` (`procesos.create`), `POST procesos` (`procesos.store`), `GET procesos/{proceso}` (`procesos.show`), `POST procesos/{proceso}/transiciones` (`procesos.transiciones.store`)
- [x] 1.2 Incluir `routes/adquisiciones.php` desde `routes/web.php`, mismo patrón que `routes/pago-proveedores.php`

## 2. Form Requests

- [x] 2.1 Crear `App\Http\Requests\Adquisiciones\EjecutarTransicionRequest` (`codigo`: required string; `comentario`: nullable string) — mismas reglas que `App\Http\Requests\PagoProveedores\EjecutarTransicionRequest`
- [x] 2.2 Crear `App\Http\Requests\Adquisiciones\CrearProcesoAdquisicionRequest` (`codigo`: required string unique:procesos_adquisicion,codigo; `modalidad_id`: required exists:modalidades_adquisicion,id; `ccosto_id`: required exists:ccostos,id; `proveedor_id`: nullable exists:proveedores,id; `monto`: nullable numeric min:0; `objeto`: required string) — solo valida forma, no `activo` de la modalidad (eso lo valida el Service)

## 3. Policy

- [x] 3.1 Registrar `ProcesoAdquisicionPolicy` (ya existe) en `App\Providers\AppServiceProvider::configureAuthorization()` vía `Gate::policy(ProcesoAdquisicion::class, ProcesoAdquisicionPolicy::class)`, junto a las demás

## 4. Resources

- [x] 4.1 Crear `App\Http\Resources\Adquisiciones\ProcesoAdquisicionResource` (`id`, `codigo`, `modalidad.codigo`/`nombre`, `ccosto.codigo`/`nombre`, `proveedor.nombre`/`rutproveedor`, `monto`, `objeto`, `proceso` vía `App\Http\Resources\PagoProveedores\ProcesoResource` reutilizado sin cambios)

## 5. Controladores

- [x] 5.1 Crear `App\Http\Controllers\Adquisiciones\ProcesoAdquisicionController::index()`: pagina `ProcesoAdquisicion::with(['modalidad', 'ccosto', 'proveedor', 'proceso.estadoActual'])`, autoriza `viewAny`, `Inertia::render('adquisiciones/procesos/index', ['procesos' => ...])`
- [x] 5.2 Crear `App\Http\Controllers\Adquisiciones\ProcesoAdquisicionController::show(ProcesoAdquisicion $proceso)`: carga `modalidad`, `ccosto`, `proveedor`, `proceso.estadoActual`, `proceso.definicionWorkflow.transiciones`, `proceso.historialTransiciones.transicion`/`estadoOrigen`/`estadoDestino`/`user`, `proceso.checklist.items`; autoriza `view`; `Inertia::render('adquisiciones/procesos/show', ['proceso' => ...])`
- [x] 5.3 Crear `App\Http\Controllers\Adquisiciones\ProcesoAdquisicionController::create()`: autoriza `create`; entrega `modalidades` (activas, `{id, codigo, nombre}`), `ccostos` (`{id, codigo, nombre}`) y `proveedores` (`{id, nombre, rutproveedor}`) como arreglos planos; `Inertia::render('adquisiciones/procesos/crear', [...])`
- [x] 5.4 Crear `App\Http\Controllers\Adquisiciones\ProcesoAdquisicionController::store(CrearProcesoAdquisicionRequest $request, ProcesoAdquisicionService $servicio)`: autoriza `create`; llama `$servicio->crear($request->validated())` dentro de un `try/catch (ProcesoAdquisicionException $e)`; en éxito redirige a `procesos.show`; en error devuelve `back()->withErrors(['modalidad_id' => $e->getMessage()])`
- [x] 5.5 Crear `App\Http\Controllers\Adquisiciones\TransicionProcesoAdquisicionController::store(ProcesoAdquisicion $proceso, EjecutarTransicionRequest $request, TransicionWorkflowService $servicio)`: mismo patrón que `TransicionCasoPagoProveedorController::store()`, llamando `$servicio->execute($proceso->proceso, ...)`

## 6. Tests

- [x] 6.1 Test feature: `procesos.index` responde con la página Inertia `adquisiciones/procesos/index` incluyendo los procesos
- [x] 6.2 Test feature: `procesos.show` responde con el proceso, su `Proceso` de workflow, estado actual, historial y transiciones disponibles para el estado actual
- [x] 6.3 Test feature: `procesos.create` responde con las modalidades activas, ccostos y proveedores disponibles
- [x] 6.4 Test feature: crear un proceso de adquisición con datos válidos crea el `proceso_adquisicion` y su `Proceso` en el estado inicial del workflow
- [x] 6.5 Test feature: crear un proceso de adquisición con una modalidad inexistente o inactiva es rechazado con un error de validación y no crea ningún registro
- [x] 6.6 Test feature: ejecutar una transición válida con el permiso requerido cambia el estado del `Proceso`
- [x] 6.7 Test feature: ejecutar una transición sin el permiso requerido no cambia el estado y la respuesta refleja el error de `TransicionWorkflowService`

## 7. Validación

- [x] 7.1 `composer lint:check`
- [x] 7.2 `composer types:check`
- [x] 7.3 `php artisan test --compact`

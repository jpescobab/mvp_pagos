## 1. Permiso y seeder

- [x] 1.1 `adquisiciones.editar_proceso` agregado en `WorkflowAdquisicionesSeeder` (arreglo `$permisos` → admin; y grant explícito a `administrativo_adquisiciones`).

## 2. Dominio (Service + excepción + policy)

- [x] 2.1 `ProcesoAdquisicionException::noEditableEnEstado()` agregado.
- [x] 2.2 `ProcesoAdquisicionService::actualizar()`: transaccional, valida estado `borrador` (lanza excepción si no) y modalidad activa, actualiza el `ProcesoAdquisicion` y sincroniza `modalidad_id`/`monto` del `Proceso`.
- [x] 2.3 `ProcesoAdquisicionPolicy::update()` → `adquisiciones.editar_proceso`.

## 3. HTTP (request + controller + rutas)

- [x] 3.1 `ActualizarProcesoAdquisicionRequest` con `codigo` unique ignorando el propio registro.
- [x] 3.2 `ProcesoAdquisicionController::edit()` (payload = proceso con IDs + modalidades/ccostos/proveedores, vía helpers privados reutilizados por `create`).
- [x] 3.3 `ProcesoAdquisicionController::update()` liviano: autoriza y delega en `actualizar()`, traduce la excepción a error de sesión.
- [x] 3.4 Rutas `procesos.edit` (GET) y `procesos.update` (PUT/PATCH).
- [x] 3.5 Wayfinder regenerado (`--with-form`).

## 4. Frontend

- [x] 4.1 `editar.tsx` (espejo de `crear.tsx`, precargado, envía PUT vía `procesos.update`).
- [x] 4.2 Botón "Editar" en `show.tsx`, visible solo si estado `borrador` y `auth.permissions` incluye `adquisiciones.editar_proceso`.

## 5. Tests

- [x] 5.1 Dominio: sync del `Proceso`, re-resolución del checklist al cambiar modalidad, rechazo fuera de `borrador`, modalidad inactiva (`EditarProcesoAdquisicionTest`).
- [x] 5.2 HTTP: 403 sin permiso, edit+update happy path, error fuera de `borrador`, `codigo` unique ignora el propio y rechaza el de otro proceso.
- [x] 5.3 `ApiAdquisicionesTest` seeder-assert extendido con `adquisiciones.editar_proceso`.

## 6. Validación

- [x] 6.1 Suite completa `tests/Feature` en verde (762 passed, 4 skips preexistentes); `tests/Feature/Adquisiciones` 114/114.
- [x] 6.2 Pint, tsc, ESLint (archivos propios) y PHPStan limpios. (Los 282 errores de ESLint provienen de `.agents/skills/**`, scripts Node untracked ajenos al change y ausentes en CI.)
- [x] 6.3 Resembrado + caché de permisos invalidada para `admin` y `administrativo_adquisiciones` (verificado `editar_proceso`).

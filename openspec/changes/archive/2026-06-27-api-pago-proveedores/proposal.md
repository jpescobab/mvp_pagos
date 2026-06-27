## Why

Las tareas 1-10 construyeron el dominio institucional completo (workflow, expediente documental, SGF, integraciones, reportabilidad y el módulo funcional de pago de proveedores), pero ninguna expuso una capa HTTP: hoy el proyecto solo tiene el scaffolding del starter kit (`Settings\ProfileController`, `Settings\SecurityController`) y dos Policies (`UserPolicy`, `RolePolicy`) sin ningún controlador que las use. No hay forma de operar `pago_proveedores` salvo por tinker o tests. Esta tarea construye el primer puente entre los servicios de dominio ya probados y una futura interfaz, validando el patrón controlador→servicio antes de replicarlo en los demás módulos.

## What Changes

- Crear rutas, controladores, Form Requests, Policy y Resources para un recorte acotado de `pago_proveedores`:
  - Listar y ver `casos_pago_proveedor` (con su `Proceso`: estado actual, historial de transiciones, checklist documental).
  - Ejecutar transiciones de workflow sobre un caso mediante un único endpoint genérico que delega en `TransicionWorkflowService::execute()` (no un endpoint por transición).
  - Listar y crear `egresos_cgu` que cubren uno o más `casos_pago_proveedor`.
- Los controladores devuelven `Inertia::render()` con sus props; no se crea ningún componente `.tsx` en esta tarea (paso explícitamente posterior y separado). Las pruebas usan `assertInertia()`, que no requiere que el componente exista.
- Crear `CasoPagoProveedorPolicy` (viewAny/view, sin restricción adicional más allá de estar autenticado) y reutilizar la autorización ya existente dentro de `TransicionWorkflowService` para las transiciones (no se duplica el chequeo de permiso en el controlador).
- Crear permiso `pago_proveedores.registrar_egreso` para gatear la creación de `egresos_cgu`, siguiendo la convención `modulo.accion` ya usada por `WorkflowPagoProveedoresSeeder`.

**Fuera de alcance (decisión explícita):** páginas React/Inertia; endpoints independientes para `registros_contables_cgu` y `registros_pago_bancario` (ningún servicio los orquesta hoy y el workflow no los exige explícitamente).

## Capabilities

### New Capabilities
- `api-pago-proveedores`: capa de presentación HTTP/Inertia para el dominio `pago-proveedores-sgf`. Traduce peticiones HTTP a los servicios de dominio ya existentes (`TransicionWorkflowService`); no introduce reglas de negocio nuevas, no reemplaza la autorización ya centralizada en el servicio de workflow.

### Modified Capabilities
(ninguna — no cambia ningún Requirement de `pago-proveedores-sgf` ni `workflow-core`; solo los expone vía HTTP.)

## Impact

- Rutas nuevas: `routes/pago-proveedores.php`, incluido desde `routes/web.php` bajo middleware `auth`.
- Código nuevo: `App\Http\Controllers\PagoProveedores\CasoPagoProveedorController`, `App\Http\Controllers\PagoProveedores\TransicionCasoPagoProveedorController`, `App\Http\Controllers\PagoProveedores\EgresoCguController`; `App\Http\Requests\PagoProveedores\EjecutarTransicionRequest`, `CrearEgresoCguRequest`; `App\Policies\CasoPagoProveedorPolicy`; `App\Http\Resources\PagoProveedores\CasoPagoProveedorResource`, `ProcesoResource`, `EgresoCguResource`.
- Seeder modificado: `WorkflowPagoProveedoresSeeder` agrega el permiso `pago_proveedores.registrar_egreso`.
- Primer uso real de Policies en un controlador del proyecto; primer directorio `app/Http/Resources/`.
- No se ejecuta `wayfinder:generate` en esta tarea (no hay build de frontend corriendo); quedará pendiente para cuando se construyan las páginas React.

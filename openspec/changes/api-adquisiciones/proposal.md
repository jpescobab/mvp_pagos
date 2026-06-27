## Why

`dominio-workflow-adquisiciones` construyó el modelo interno y el workflow de Adquisiciones (`ProcesoAdquisicion`, `WorkflowAdquisicionesSeeder`, `ProcesoAdquisicionService::crear()`), pero solo es operable por tinker o tests. A diferencia de Pago de Proveedores — donde los casos llegan ya importados desde SGF y la capa HTTP solo necesitó listar/ver/transicionar — Adquisiciones no tiene origen externo todavía: sin un endpoint de creación, nadie puede generar un proceso de adquisición real fuera de un test. Esta tarea expone el dominio ya probado vía HTTP/Inertia, replicando el patrón validado en `api-pago-proveedores`.

## What Changes

- Crear rutas, controladores, Form Requests, Resources y reutilizar `ProcesoAdquisicionPolicy` (ya existente) para:
  - Listar `procesos_adquisicion` paginados (proveedor si existe, modalidad, ccosto, monto, estado actual del `Proceso`).
  - Ver el detalle de un proceso (estado actual, historial de transiciones, checklist documental de su `Proceso` si existe — mismo patrón que `ProcesoResource.checklist`, reutilizando ese Resource sin duplicarlo).
  - Mostrar un formulario de creación con las modalidades activas, ccostos y proveedores disponibles para elegir.
  - Crear un nuevo proceso de adquisición, delegando en `ProcesoAdquisicionService::crear()` ya existente y traduciendo `ProcesoAdquisicionException` a un error de validación legible (`withErrors`), sin duplicar la validación de modalidad activa en el controlador.
  - Ejecutar transiciones de workflow sobre un proceso mediante un único endpoint genérico que delega en `TransicionWorkflowService::execute()`, mismo patrón que `TransicionCasoPagoProveedorController` pero vinculado a `ProcesoAdquisicion`.
- Los controladores devuelven `Inertia::render()` con sus props; no se crea ningún componente `.tsx` en esta tarea (paso explícitamente posterior y separado, igual que pasó entre `api-pago-proveedores` y `paginas-pago-proveedores`).

**Fuera de alcance (decisión explícita):** páginas React/Inertia; integración con Mercado Público; edición o eliminación de un proceso ya creado (el dominio no define esas operaciones).

## Capabilities

### New Capabilities
- `api-adquisiciones`: capa de presentación HTTP/Inertia para el dominio `adquisiciones`. Traduce peticiones HTTP a los servicios de dominio ya existentes (`ProcesoAdquisicionService`, `TransicionWorkflowService`); no introduce reglas de negocio nuevas.

### Modified Capabilities
(ninguna — no cambia ningún Requirement de `adquisiciones` ni `workflow-core`; solo los expone vía HTTP.)

## Impact

- Rutas nuevas: `routes/adquisiciones.php`, incluido desde `routes/web.php` bajo middleware `auth`, prefijo/nombre `adquisiciones.`.
- Código nuevo: `App\Http\Controllers\Adquisiciones\ProcesoAdquisicionController`, `App\Http\Controllers\Adquisiciones\TransicionProcesoAdquisicionController`; `App\Http\Requests\Adquisiciones\CrearProcesoAdquisicionRequest`, `EjecutarTransicionRequest`; `App\Http\Resources\Adquisiciones\ProcesoAdquisicionResource`.
- Reutilizado sin cambios: `ProcesoAdquisicionPolicy`, `App\Http\Resources\PagoProveedores\ProcesoResource` (ya genérico para cualquier `Proceso`, no específico de pago-proveedores a pesar de su namespace), `App\Exceptions\TransicionWorkflowException`.
- No se ejecuta `wayfinder:generate` en esta tarea (no hay páginas que consuman las rutas todavía); quedará pendiente para cuando se construyan.

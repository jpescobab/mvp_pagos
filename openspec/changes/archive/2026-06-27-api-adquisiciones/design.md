## Context

`api-pago-proveedores` ya validó el patrón controlador liviano → Form Request → Service/Eloquent → Resource para exponer un dominio de workflow vía HTTP/Inertia. `dominio-workflow-adquisiciones` dejó el dominio probado (`ProcesoAdquisicion`, `ProcesoAdquisicionService::crear()`, workflow "adquisiciones") pero sin ningún controlador. A diferencia de `pago_proveedores` (donde los casos llegan importados desde SGF y no hace falta un endpoint de creación), Adquisiciones no tiene origen externo todavía — sin un endpoint de creación, el dominio queda inalcanzable fuera de tests/tinker. Esta tarea replica el patrón ya validado y agrega lo que pago-proveedores no necesitó: creación.

## Goals / Non-Goals

**Goals:**
- Exponer vía HTTP/Inertia el ciclo ya probado en `ProcesoAdquisicionServiceTest`: crear un proceso, verlo, transicionarlo.
- Reutilizar `ProcesoResource` (ya genérico, sin cambios) para el `Proceso` anidado, igual que `CasoPagoProveedorResource` lo hace.
- No duplicar autorización: el controlador de transición delega 100% en `TransicionWorkflowService`, igual que `TransicionCasoPagoProveedorController`. El controlador de creación delega 100% en `ProcesoAdquisicionService::crear()` (incluida la validación de modalidad activa), traduciendo su excepción a un error de formulario.
- Mantener la misma convención de nombres ya fijada por `api-pago-proveedores` (`routes/<modulo>.php`, `App\Http\Controllers\<Modulo>\...`, `App\Http\Resources\<Modulo>\...`).

**Non-Goals:**
- No se construyen páginas `.tsx` ni se ejecuta `wayfinder:generate` — paso separado y posterior, igual que pasó entre `api-pago-proveedores` y `paginas-pago-proveedores`.
- No se integra Mercado Público — el formulario de creación recibe modalidades/ccostos/proveedores ya existentes en la base, no datos importados.
- No se exponen edición ni eliminación de un proceso ya creado — el dominio (`ProcesoAdquisicionService`) no define esas operaciones; agregarlas aquí sería inventar alcance no solicitado.

## Decisions

1. **Un solo endpoint genérico para transiciones**, igual razón que `api-pago-proveedores` decisión 1: `POST /adquisiciones/procesos/{proceso}/transiciones` recibe `{codigo, comentario?}` y llama `TransicionWorkflowService::execute($proceso->proceso, $codigo, $comentario)`.

2. **El controlador de transición no tiene Policy propia**, igual razón que `api-pago-proveedores` decisión 2: la única autorización es la que ya aplica `TransicionWorkflowService` vía `permiso_requerido`. Captura `TransicionWorkflowException` y la traduce igual que `TransicionCasoPagoProveedorController` (`withErrors(['transicion' => ...])`).

3. **`ProcesoAdquisicionPolicy::create()` (ya existe, retorna `true`) gatea el formulario y el endpoint de creación.** A diferencia de `egresos_cgu` (que sí necesitó un permiso nuevo porque es una operación de registro de evidencia paralela al workflow), crear un proceso de adquisición es la operación de entrada al dominio — cualquier usuario autenticado puede iniciar uno; las restricciones reales llegan después, vía las transiciones (`publicar`/`adjudicar`/`anular`, ya gatedas por permiso en el workflow). Si en el futuro se necesita restringir quién puede crear, este es el punto de extensión natural.

4. **El controlador de creación traduce `ProcesoAdquisicionException` a `withErrors(['modalidad_id' => ...])`**, no a una validación duplicada en el Form Request. El Form Request (`CrearProcesoAdquisicionRequest`) solo valida forma (`required`/`exists`/`numeric`), no estado de negocio (`activo`) — esa regla vive en el Service y no se repite, igual criterio que ya se siguió con `ProcesoAdquisicionService::crear()` en la tarea anterior.

5. **`procesos.create()` entrega modalidades activas, ccostos y proveedores como arreglos planos** (`{id, codigo, nombre}` / `{id, codigo, nombre}` / `{id, nombre, rutproveedor}`), no como Resources — mismo criterio que `EgresoCguController::create()` (decisión documentada en `paginas-pago-proveedores` tarea 1.2): son selectores de formulario, no vistas de detalle, y envolverlos en Resources pensados para otro propósito sería sobre-ingeniería.

6. **`ProcesoAdquisicionResource` nuevo en `App\Http\Resources\Adquisiciones\`** incluye el `Proceso` anidado vía `ProcesoResource` (reutilizado sin cambios, namespace `PagoProveedores` a pesar de ser genérico — no se renombra en esta tarea, fuera de alcance).

7. **Inertia sin página todavía**: los controladores llaman `Inertia::render('adquisiciones/procesos/index', [...])` etc., fijando el nombre del componente futuro. Las pruebas usan `assertInertia()`, que no requiere que el `.tsx` exista, igual que `api-pago-proveedores`.

## Risks / Trade-offs

- **[Riesgo] Rutas "ciegas" sin UI** (un navegador real fallaría al renderizar) → **Mitigación**: aceptado explícitamente, igual que en `api-pago-proveedores`; las pruebas automatizadas no se ven afectadas.
- **[Riesgo] `ProcesoResource` vive en un namespace `PagoProveedores` aunque ya se usa para dos módulos distintos** → **Mitigación**: renombrar/mover el namespace es un cambio de organización de código sin impacto funcional; se documenta aquí como deuda menor en vez de mezclarlo con esta tarea.

## Migration Plan

Sin migraciones de base de datos. Cambios de código y rutas únicamente.

## Open Questions

Ninguna pendiente.

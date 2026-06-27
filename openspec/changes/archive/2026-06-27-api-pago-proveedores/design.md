## Context

Hoy `pago_proveedores` solo es operable mediante `CasoPagoProveedorImporter`, `TransicionWorkflowService` y acceso directo a Eloquent desde tests/tinker. No existe ningún controlador de dominio en el proyecto; el único precedente de Policy (`UserPolicy`, `RolePolicy`) no tiene controlador que lo invoque. Esta tarea construye el primer recorte HTTP real, deliberadamente acotado, para validar el patrón antes de replicarlo en los otros 9 dominios.

## Goals / Non-Goals

**Goals:**
- Exponer vía HTTP/Inertia el ciclo ya probado en `CasoPagoProveedorImporterTest`: ver casos, transicionarlos, registrar egresos CGU.
- No duplicar autorización: el controlador de transición delega 100% en `TransicionWorkflowService` (que ya valida permiso, comentario y documentos requeridos) y traduce su excepción a una respuesta HTTP apropiada, en vez de repetir la lógica de permisos en una Policy paralela.
- Dejar una convención clara y replicable (`Controller` liviano → `FormRequest` → `Service`/Eloquent → `Resource`) para cuando se aborden los demás 9 dominios.

**Non-Goals:**
- No se construyen páginas `.tsx` ni se ejecuta `wayfinder:generate` — la UI es una tarea separada y posterior, ya decidida con el usuario.
- No se exponen `registros_contables_cgu` ni `registros_pago_bancario`: hoy no hay ningún servicio que los orqueste junto a una transición de workflow, y agregarlo aquí sería inventar lógica de negocio no solicitada. Si se necesitan, es una tarea propia.
- No se construye un endpoint por cada una de las 13 transiciones de `pago_proveedores`; un único endpoint genérico que recibe el código de transición es suficiente y ya es como lo prueba `CasoPagoProveedorImporterTest`.

## Decisions

1. **Un solo endpoint genérico para transiciones, no 13.** `POST /pago-proveedores/casos/{caso}/transiciones` recibe `{codigo, comentario?}` y llama `TransicionWorkflowService::execute($caso->proceso, $codigo, $comentario)`. Igual de válido y muchísimo menos código que un controlador por transición; el propio servicio ya rechaza códigos no permitidos desde el estado actual (`TransicionWorkflowException::transicionNoPermitida()`).

2. **El controlador de transición no tiene Policy propia.** Su única autorización es la que ya aplica `TransicionWorkflowService` (permiso por transición, vía `permiso_requerido` en `transiciones_workflow`). Envolver eso en una Policy adicional duplicaría la fuente de verdad. El controlador captura `TransicionWorkflowException` y la traduce: `sinPermiso`/`comentarioRequerido`/`documentosFaltantes` → 422 con mensaje; `transicionNoPermitida`/`procesoCerrado`/`moduloInactivo` → 409.

3. **`CasoPagoProveedorPolicy` solo gatea `viewAny`/`view`, no las transiciones.** Ver casos es una acción de lectura sin permiso especial hoy (cualquier usuario autenticado); si en el futuro se necesita restringir la visibilidad por unidad/ccosto, esta Policy es el punto de extensión natural sin tocar el controlador.

4. **`egresos_cgu` se gatea con un permiso nuevo (`pago_proveedores.registrar_egreso`), vía Policy.** A diferencia de las transiciones (ya gobernadas por el workflow), crear un egreso no pasa por `TransicionWorkflowService` — es una operación de registro de evidencia, igual de espíritu a `EgresoCgu` en la tarea 8. Necesita su propio chequeo de autorización, igual que `RegistroContableCgu`/`RegistroPagoBancario` lo necesitarán si se construyen después.

5. **Resources nuevos en `app/Http/Resources/PagoProveedores/`**, primer uso de Eloquent API Resources en el proyecto. `CasoPagoProveedorResource` incluye el `Proceso` anidado (estado actual + historial reciente) para que la futura página de detalle no necesite una segunda petición.

6. **Inertia sin página**: los controladores llaman `Inertia::render('pago-proveedores/casos/index', [...])` etc. usando el nombre de componente que tendrá la página futura. Esto es intencional: cuando se construya la UI, el nombre ya está fijado y no hay que tocar el controlador. Las pruebas usan `assertInertia(fn ($page) => $page->component('pago-proveedores/casos/index')->has('casos'))`, que no requiere que el archivo `.tsx` exista.

## Risks / Trade-offs

- **[Riesgo] Construir HTTP antes que UI deja rutas "ciegas"** (un navegador real fallaría al renderizar, porque Vite no encuentra el componente) → **Mitigación**: aceptado explícitamente por el usuario como secuencia de trabajo; documentado en Non-Goals. Las pruebas automatizadas no se ven afectadas.
- **[Riesgo] Repetir este patrón controlador-por-controlador en los otros 9 dominios podría divergir en convenciones** → **Mitigación**: esta tarea es deliberadamente el piloto; las decisiones de nombres de rutas, ubicación de Resources/Requests y manejo de excepciones quedan documentadas aquí para copiarse, no reinventarse, en las próximas.

## Migration Plan

Sin migraciones de base de datos. Cambios de código y rutas únicamente. No hay datos previos que migrar.

## Open Questions

Ninguna pendiente — el alcance se acordó explícitamente con el usuario antes de este design (recorte acotado, sin registros CGU/bancarios, sin páginas React).

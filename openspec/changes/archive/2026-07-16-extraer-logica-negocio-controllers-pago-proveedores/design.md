## Context

`app/Http/Controllers/PagoProveedores/` ya tiene el patrón correcto establecido en varios controllers (`RevisionTotalesController`, `RevisionTransicionEgresoController`, `TransicionCasoPagoProveedorController`) que delegan enteramente a `RevisionEgresoService`/`TransicionWorkflowService`. Tres controllers no siguen ese patrón: `EgresoCguController::store()` (una transacción completa), `RequisitoDocumentalController` (queries y una decisión de tres ramas), y `RevisionPagosController::egresosEnRevision()` (lista de estados hardcodeada + query + filtro). Los tres son preexistentes, sin relación con changes en curso.

Nota: la auditoría inicial también encontró `CasoPagoProveedorController::index()` y `EgresoCguController::create()` con el mismo problema, pero esos dos métodos son la implementación —completa, pendiente solo de archivar— de los changes `indicador-listo-revision-y-filtro-estado-casos-pago-proveedores` y `acceso-directo-crear-egreso-cgu-desde-detalle-caso`. Se corrigen dentro de esos changes, no en este.

Los Services existentes en `app/Services/PagoProveedores/` (`RevisionEgresoService`, `RevisionEgresoPresenter`, `ListoParaEgresoResolver`, `CasoPagoProveedorImporter`, `ValidacionDocumentoInstanciaService`, `CfinancieroPorDefectoResolver`) fijan las convenciones de nombrado a seguir: sufijo `Service` para orquestación con múltiples operaciones relacionadas, `Presenter` para armado de payload de una pantalla, `Resolver` para una única pregunta booleana/de negocio, e `Importer`/`Creador` para creación transaccional de una entidad a partir de datos externos o de un formulario.

## Goals / Non-Goals

**Goals:**
- Cada controller termina con métodos que autorizan (Gate), validan (Form Request) y delegan — sin queries de negocio, sin `DB::transaction`, sin listas de códigos de estado hardcodeadas, sin `app(...)` dentro de un método.
- Cero cambio de comportamiento observable: mismas reglas de creación de egreso, revisión y administración de requisitos, mismas respuestas Inertia (misma forma de props, mismos códigos de estado HTTP).
- Cada Service nuevo queda cubierto por un test que ejerce su lógica directamente (unit o feature contra el Service), además de que los tests de feature existentes por controller sigan pasando sin tocar sus aserciones.

**Non-Goals:**
- No se refactorizan los controllers "borderline" (`RegistroContableCguController`, `RegistroPagoBancarioController`, `FacturaController`, `TipoProcesoPagoCasoPagoProveedorController`, `VinculoAdquisicionCasoPagoProveedorController`, `BuscarProcesoAdquisicionController`) — su `store()`/`__invoke()` es creación 1:1 desde el request o una única query acotada, no lo amerita.
- No se cambian reglas de negocio, permisos, rutas, migraciones ni el front-end React.
- No se toca `openspec/specs/*` — no hay requirement de spec que cambie (ver proposal.md).

## Decisions

**1. `EgresoCguCreador`** (nuevo, mismo patrón que `CasoPagoProveedorImporter`) — reemplaza la `DB::transaction` de `EgresoCguController::store()`.
- Constructor inyecta `RevisionEgresoService`.
- Método: `crear(array $datosValidados, User $user): EgresoCgu` — encapsula toda la transacción: cálculo de `monto_total`, creación del `EgresoCgu`, query+`keyBy` de casos, bucle de creación de ítems + `actualizarCfinancieroSiFalta` + `iniciarRevision`.
- La excepción `TransicionWorkflowException` sigue propagándose fuera del Service; el controller conserva su `try/catch` con `back()->withErrors(...)` — es manejo de respuesta HTTP, corresponde al controller.

**2. `RequisitoDocumentalPagoProveedorService`** (nuevo) — reemplaza la lógica de `RequisitoDocumentalController`.
- Métodos: `conjunto(): ConjuntoRequisitosDocumentales` (el `firstOrCreate` actual), `vigentes(): Collection` (el query de `index()`), `actualizar(TipoDocumento $tipoDocumento, ?int $tipoProcesoPagoId, ?string $tipoRequisito): void` (las tres ramas de `update()`, incluyendo el query de 6 `where` para encontrar el existente).
- `index()` y `update()` del controller quedan reducidos a: autorizar, llamar al Service, renderizar/redirigir.

**3. `RevisionEgresoPresenter::listadoEnRevision(User $user): Collection`** (extiende Service existente, no uno nuevo) — reemplaza `RevisionPagosController::egresosEnRevision()` completo, incluida la constante `ESTADOS_EN_REVISION`.
- Se agrega a `RevisionEgresoPresenter` en vez de crear un Service nuevo porque ya es la clase responsable de construir el payload de esta pantalla (`detalle()`); mover el query+filtro+map ahí evita que el controller conozca la forma del payload intermedio.
- `RevisionPagosController::index()` y `::show()` quedan con una sola llamada: `$this->presenter->listadoEnRevision($user)`.

## Risks / Trade-offs

- **[Riesgo] `EgresoCguCreador::crear()` encapsula la transacción completa — si el rollback implícito de `DB::transaction` dependía de alguna variable de estado del controller (`$request` solo se usaba para `$request->user()`), hay que pasar explícitamente `User $user` como parámetro en vez de `Request`.** → Mitigación: firma del método recibe `User $user`, no `Request`, dejando explícita la única dependencia real.
- **[Trade-off] `RequisitoDocumentalPagoProveedorService::actualizar()` no está atado a `TransicionWorkflowService` porque `RequisitoDocumental` no es una entidad de workflow (es configuración administrativa) — no aplica la regla "todo cambio de estado pasa por TransicionWorkflowService" aquí.** Se documenta explícitamente para que una revisión futura no lo marque como violación.

## Migration Plan

Cambio de solo-código, sin migraciones de base de datos ni cambios de ruta. Se implementa controller por controller (cada uno es independiente de los otros dos), corriendo `composer test` después de cada uno para aislar cualquier regresión antes de continuar con el siguiente. No requiere plan de rollback más allá de revertir el commit — no hay estado persistente nuevo.

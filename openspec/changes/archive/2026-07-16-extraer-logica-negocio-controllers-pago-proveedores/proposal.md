## Why

CLAUDE.md exige controladores livianos con la lógica de negocio delegada a Services. Una auditoría de `app/Http/Controllers/PagoProveedores/` encontró varios controllers con queries de negocio (listas de códigos de estado hardcodeadas), transacciones de base de datos completas y decisiones de tres ramas viviendo directamente en el controller. Esto contradice la convención ya establecida en el propio módulo (`RevisionEgresoService`, `RevisionEgresoPresenter`, `ListoParaEgresoResolver`, `CasoPagoProveedorImporter`) y dificulta testear y reutilizar esa lógica. Se corrige ahora, antes de que estos controllers acumulen más lógica.

Nota de alcance: la auditoría inicial también encontró violaciones en `CasoPagoProveedorController::index()` y `EgresoCguController::create()`, pero esos dos métodos son la implementación —ya completa, solo pendiente de archivar— de dos changes OpenSpec en curso (`indicador-listo-revision-y-filtro-estado-casos-pago-proveedores` y `acceso-directo-crear-egreso-cgu-desde-detalle-caso`). Esas dos correcciones se hacen dentro de esos changes antes de archivarlos, no aquí, para no tener dos changes distintos tocando el mismo método a la vez.

## What Changes

- `EgresoCguController::store()` deja de orquestar la `DB::transaction` (cálculo de `monto_total`, creación del egreso, creación de ítems, actualización de cfinanciero, inicio de revisión) directamente; delega a un Service nuevo que encapsula toda la operación transaccional.
- `RequisitoDocumentalController` deja de resolver el conjunto de requisitos, listar los vigentes y decidir la rama de borrar/actualizar/crear directamente; delega todo a un Service nuevo.
- `RevisionPagosController::egresosEnRevision()` deja de tener la constante `ESTADOS_EN_REVISION` y el query/filtro/map inline; esa responsabilidad se mueve a `RevisionEgresoPresenter`, que ya construye el payload de esta pantalla.
- Ningún comportamiento observable cambia: mismas reglas de elegibilidad y revisión, mismas respuestas Inertia/JSON. Es una reorganización interna de dónde vive el código, no una nueva funcionalidad.

## Capabilities

### New Capabilities

(ninguna)

### Modified Capabilities

(ninguna — este change reorganiza implementación interna sin alterar el comportamiento observable descrito en `pago-proveedores-sgf`, `paginas-pago-proveedores`, `administracion-requisitos-documentales-pago-proveedores` ni `revision-pagos-dos-instancias`; no hay requirement de spec que cambie)

## Impact

- **Código afectado**: `app/Http/Controllers/PagoProveedores/{EgresoCguController,RequisitoDocumentalController,RevisionPagosController}.php`.
- **Services nuevos**: `app/Services/PagoProveedores/{EgresoCguCreador,RequisitoDocumentalPagoProveedorService}.php`.
- **Services existentes modificados**: `app/Services/PagoProveedores/RevisionEgresoPresenter.php` (nuevo método `listadoEnRevision`).
- **Tests**: nuevos tests unitarios/feature para cada Service nuevo; los tests de feature existentes en `tests/Feature/PagoProveedores/` deben seguir pasando sin modificar sus aserciones sobre la respuesta HTTP.
- **Sin cambios** en rutas, migraciones, permisos, front-end React, ni en `openspec/specs/*`.
- **Fuera de este change** (se resuelven enmendando los changes en curso que los originaron, antes de archivarlos): `CasoPagoProveedorController::index()` → `indicador-listo-revision-y-filtro-estado-casos-pago-proveedores`; `EgresoCguController::create()` → `acceso-directo-crear-egreso-cgu-desde-detalle-caso`.

## Context

`Asignacion` y `Catalogo` (`app/Models/{Asignacion,Catalogo}.php`) ya existen con el mismo esquema que `Item`: `item_id` (FK a `items`, `restrictOnDelete`), `codigo` (unique), `nombre`, `descripcion` (nullable), `activo` (boolean, default true), soft deletes. Ambos son `belongsTo(Item)`; `Item` ya expone `hasMany` hacia los dos (usados en `ItemController::destroy` para bloquear el borrado). Hoy no tienen controlador, policy, rutas ni UI.

A diferencia de `Item` (catálogo transversal con su propio listado en el sidebar), `Asignacion`/`Catalogo` son hijos directos de un `Item` específico — no tiene sentido un listado global de "todas las asignaciones de todos los ítems". El proyecto ya tiene un precedente exacto para este caso: `FacturaController::store` (`app/Http/Controllers/PagoProveedores/FacturaController.php`), anidado bajo `casos/{caso}/facturas`, sin rutas `index`/`show` propias — sus datos se muestran dentro de la sección "Facturas" de `casos/show.tsx` (`resources/js/pages/pago-proveedores/casos/show.tsx:966-997`): lista `<ul className="divide-y">` + formulario inline de alta, todo dentro de un `<section className="space-y-3 rounded-xl border p-4">`.

## Goals / Non-Goals

**Goals:**
- CRUD completo (alta, edición, eliminación) de `Asignacion` y `Catalogo`, anidado bajo su `Item` padre.
- Mostrar y administrar ambos desde `items/show.tsx`, sin páginas nuevas de listado/alta/edición independientes.
- Reutilizar `core_institucional.administrar` — mismo permiso que `Item`.

**Non-Goals:**
- No se crea un listado global de asignaciones o catálogos de todos los ítems a la vez.
- No se conecta `Asignacion`/`Catalogo` a ningún módulo funcional (Pago de Proveedores, Consumo Eléctrico) todavía — eso depende de trabajo futuro fuera de este change.
- No se modifica el esquema de `asignaciones`/`catalogos`.

## Decisions

1. **Sin rutas `index`/`create`/`edit`/`show` propias para Asignación/Catálogo — solo `store`/`update`/`destroy` anidadas.** Sigue el precedente de `FacturaController` (anidado bajo `casos/{caso}/facturas`, solo `store`). Los datos de listado viajan ya cargados dentro de la respuesta de `ItemController::show` (`with(['asignaciones', 'catalogos'])`), no por una consulta HTTP aparte.
2. **UI: secciones inline dentro de `items/show.tsx`, no diálogos modales ni páginas separadas.** Mismo patrón visual que la sección "Facturas" de `casos/show.tsx` (lista `divide-y` + formulario inline en la misma sección `rounded-xl border p-4`), extendido con botones de editar/eliminar por fila — a diferencia de Facturas (solo alta), acá si hace falta editar/eliminar porque `activo` se usa para habilitar/deshabilitar una asignación o catálogo para su uso. Se descarta un modal separado por fila: con listas cortas (pocas asignaciones/catálogos por ítem) alcanza con inputs inline por fila al hacer clic en "Editar", similar a como ya funciona el flujo de edición de `Item` pero condensado en la misma sección en vez de navegar a otra página.
3. **`AsignacionPolicy`/`CatalogoPolicy` calcadas de `ItemPolicy`**, en vez de un único gate genérico compartido. Mantiene el mismo patrón ya establecido (una Policy por modelo, cada método reenviando a `core_institucional.administrar`), consistente con cómo se autoriza el resto de Maestros.
4. **`ItemResource` expone `asignaciones`/`catalogos` solo si la relación viene cargada** (`$this->whenLoaded('asignaciones')`), para no forzar esas consultas en el listado (`ItemController::index` no las necesita, solo `show`).
5. **Bloqueo de eliminación**: a diferencia de `Item` (que bloquea su borrado si tiene asignaciones/catálogos), `Asignacion`/`Catalogo` no tienen hijos propios todavía en este change (nada las referencia desde otras tablas), así que su `destroy` no necesita una verificación de relaciones dependientes — se eliminan (soft delete) sin más validación que el permiso.

## Risks / Trade-offs

- **[Riesgo]** Concentrar alta/edición/eliminación de dos entidades más dentro de `items/show.tsx` puede hacer esa página larga si un ítem llega a tener muchas asignaciones/catálogos. → **Mitigación**: no se pagina la lista en este change (se asume un volumen bajo por ítem, consistente con ser un clasificador); si el volumen crece, paginar o mover a pestañas es una mejora futura, no bloqueante ahora.
- **[Riesgo]** Reutilizar `core_institucional.administrar` para tres modelos distintos (Item, Asignacion, Catalogo) sin granularidad. → **Mitigación**: ya es el comportamiento aceptado para `Item` y el resto de Maestros; no es una regresión nueva.

## Migration Plan

Sin migraciones de base de datos. Revertir el commit es suficiente si algo sale mal.

## Open Questions

Ninguna bloqueante para implementar.

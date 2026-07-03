## Why

El cambio `mejorar-catalogo-proveedores` (ya archivado) resolvió un problema real de densidad y desbordamiento horizontal en la tabla de proveedores, pero el patrón resultante (tabla `table-fixed`, avatar con iniciales, badge de estado con tokens semánticos, columnas truncadas con tooltip, menú de acciones desplegable, densidad reducida de fuente/padding) solo quedó documentado como una decisión de diseño puntual de esa página. Faltan por construir varios catálogos de tablas maestras (Instituciones, Jurisdicciones, Centros Financieros, Centros de Costo, Items, Asignaciones, Catálogos) que tendrán el mismo problema si cada uno reinventa su propio layout de tabla. Formalizar el patrón ahora, antes de construir esos índices, evita reescribir tablas inconsistentes o repetir el mismo problema de desbordamiento.

## What Changes

- Se agrega un requirement nuevo a la capability `tema-visual-layout` que generaliza el patrón de tabla de listado ya implementado en el catálogo de proveedores, aplicable a **cualquier** página de listado/índice de la aplicación (no solo Maestros).
- Se actualiza `HARNESS_IA.md` con una referencia breve (no un duplicado del detalle) que apunta a la spec `tema-visual-layout` y al componente de referencia `resources/js/pages/maestros/proveedores/index.tsx`, para que cualquier índice nuevo lo siga por defecto.
- No se construye código nuevo en este cambio: es puramente documentación/especificación. Las páginas de tablas maestras pendientes se implementan en changes futuros separados, que deberán seguir este patrón desde su primer borrador.

## Capabilities

### New Capabilities
(ninguna)

### Modified Capabilities
- `tema-visual-layout`: se agrega el requirement "Listados tabulares densos", generalizando a cualquier índice el patrón de tabla ya usado en el catálogo de proveedores.

## Impact

- Documentación: `HARNESS_IA.md` (referencia breve en la sección de reglas de implementación), `openspec/specs/tema-visual-layout/spec.md` (nuevo requirement).
- Código: sin cambios en este change.
- Changes futuros que construyan índices nuevos (tablas maestras institucionales u otros catálogos) deberán referenciar este requirement en su propio `design.md`.

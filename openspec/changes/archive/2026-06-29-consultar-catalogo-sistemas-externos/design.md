## Context

`SistemaExterno` (tabla `sistemas_externos`) ya existe con 6 registros sembrados por `IntegracionesSeeder` (`codigo`, `nombre`, `tipo_integracion`, `activo`). No tiene controlador. El patrón ya está resuelto para catálogos similares (`consulta-catalogo-proveedores`, `consultar-definiciones-workflow`): listado simple sin paginación cuando el catálogo es pequeño y estático.

## Goals / Non-Goals

**Goals:**
- Listar los `sistemas_externos` con sus campos y la cantidad de `trabajos_integracion` asociados (señal útil sin necesitar la página de detalle de la tarea siguiente).

**Non-Goals:**
- No se gestiona (crear/editar) el catálogo desde la UI — sigue siendo responsabilidad del seeder/configuración institucional.
- No se muestra el detalle de cada `trabajo_integracion` (eso es la siguiente tarea de este mismo plan).

## Decisions

1. **Sin paginación ni búsqueda**: 6 registros estáticos, mismo criterio que `DefinicionWorkflowController::index()`.
2. **`withCount('trabajosIntegracion')`** en vez de cargar la relación completa, para no acoplar esta consulta a la tabla de trabajos de integración (que se expone en una tarea separada).
3. **Sin permiso ni Policy**: mismo nivel de acceso que el resto de catálogos de solo lectura ya expuestos (`auth` middleware únicamente).

## Risks / Trade-offs

- [Riesgo] Ninguno relevante — catálogo estático, de solo lectura, sin datos sensibles.

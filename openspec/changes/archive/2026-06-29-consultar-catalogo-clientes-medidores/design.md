## Context

`ClienteMedidor` ya existe con 39 registros sembrados por `ClientesMedidoresSeeder`. No tiene controlador. Mismo patrón ya resuelto en `consulta-catalogo-proveedores` y `consultar-catalogo-sistemas-externos`.

## Goals / Non-Goals

**Goals:**
- Listar los `clientes_medidores` con su proveedor y centro de costo, sin paginación (volumen bajo, ~39 registros).

**Non-Goals:**
- No se modela consumo eléctrico, lecturas ni facturación — eso pertenece al módulo funcional "Consumo eléctrico" cuando se construya, que todavía no tiene ninguna otra tabla más allá de este maestro.

## Decisions

1. **Sin paginación ni búsqueda**: igual criterio que `DefinicionWorkflowController::index()` y `SistemaExternoController::index()` — volumen bajo y estático.
2. **Sin permiso ni Policy**: mismo nivel de acceso que el resto de catálogos de solo lectura.

## Risks / Trade-offs

- [Riesgo] Ninguno relevante — catálogo de solo lectura sin datos sensibles.

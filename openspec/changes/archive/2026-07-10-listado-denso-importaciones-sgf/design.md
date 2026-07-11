## Context

`sgf/importaciones/index.tsx` es la única vista de listado tabular que quedó fuera del patrón de "listado denso" ya validado en `resources/js/pages/adquisiciones/ordenes-compra-mercado-publico/index.tsx` (mismo patrón usado por `licitaciones-mercado-publico/index.tsx`, `maestros/cfinancieros/index.tsx`, `maestros/ccostos/index.tsx`). `ImportacionSgfController::index` hoy no acepta ningún parámetro de filtro; solo pagina `trabajos_integracion` del sistema `SGF`.

## Goals / Non-Goals

**Goals:**
- Llevar `sgf/importaciones/index.tsx` a paridad visual con el patrón denso de referencia.
- Agregar búsqueda simple (tipo o usuario que inició la corrida) con el mismo mecanismo de debounce ya usado en el resto de la app.
- Mantener intacto el flujo de disparo de importación (`ImportarCasosPendientesSgfController`) y el detalle (`show.tsx`).

**Non-Goals:**
- No se agrega ninguna capacidad nueva de importación (filtros por rango de fecha, cancelación de trabajos en curso, reintentos, etc.).
- No se toca `ImportacionSgfResource` ni el modelo `TrabajoIntegracion`.
- No se crean nuevas tablas ni columnas.

## Decisions

- **Badge de estado**: se crea `components/sgf/importacion-estado-badge.tsx` en vez de reutilizar `OrdenCompraEstadoBadge` (los valores de estado son distintos: `en_progreso`/`completado`/`error` vs. los estados de Mercado Público). Sigue el mismo patrón de tokens semánticos (`success` para `completado`, `danger` para `error`, variante neutra/ámbar para `en_progreso`).
- **Búsqueda**: filtra por `tipo` (`importar_pendientes`/`verificar_caso`) con coincidencia exacta vía `whereIn`/`where` simple, y por nombre de `iniciadoPor` con `like`, combinados con `orWhere` bajo un mismo `when($q !== '', ...)`, replicando la forma en que `renderizarListado()` de `OrdenCompraMercadoPublicoController` aplica su filtro `q`. No se introduce full-text search ni un índice nuevo: el volumen esperado de `trabajos_integracion` de SGF es bajo (decenas/cientos, no miles) y no justifica esa complejidad.
- **Fila clicable + menú de acciones**: se conserva la fila completa como clicable hacia el detalle (como hoy) y se añade además un menú desplegable con "Ver detalle", igual que en `ordenes-compra-mercado-publico/index.tsx`, para cumplir el requirement de "menú de acciones" del tema visual sin quitar la navegación por clic de fila ya existente.

## Risks / Trade-offs

- [Cambiar la firma de `ImportacionSgfController::index` podría afectar algún test existente que llame la ruta sin parámetros] → Los tests actuales (`ConsultarImportacionesSgfTest.php`) no pasan `q`; el filtro es opcional (`when($q !== '', ...)`), por lo que el comportamiento sin parámetro no cambia.

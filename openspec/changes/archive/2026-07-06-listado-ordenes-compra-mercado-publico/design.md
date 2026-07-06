## Context

`OrdenCompraMercadoPublicoController::index()` hoy siempre renderiza `adquisiciones/ordenes-compra-mercado-publico/buscar` (con `codigo: null` si no llega query string). No existe ningún listado de las OC ya guardadas en `ordenes_compra_mercado_publico`. El requirement global "Listados tabulares densos" (`openspec/specs/tema-visual-layout/spec.md`) ya define el patrón visual obligatorio para cualquier índice nuevo; no se modifica en este change, solo se aplica.

## Goals / Non-Goals

**Goals:**
- Que `GET /adquisiciones/ordenes-compra-mercado-publico` (sin `codigo`) muestre un listado paginado de las OC ya guardadas localmente.
- Reutilizar el patrón de listado denso ya implementado en `resources/js/pages/maestros/cfinancieros/index.tsx` / `.../ccostos/index.tsx`.
- Mantener intacto el flujo de búsqueda por código existente (no se toca `buscar()`, `verificar()`, `actualizar()`, `guardar()`, `show()`).

**Non-Goals:**
- No se agrega ningún permiso nuevo (se reutiliza `viewAny` de `OrdenCompraMercadoPublicoPolicy`).
- No se modifica el servicio de dominio `OrdenCompraMercadoPublicoService` ni la integración con Mercado Público.
- No se fusiona el listado con la página de búsqueda por código en un solo componente.

## Decisions

- **`index()` bifurca por la presencia de `codigo`, no se crea una acción nueva**: cuando llega `codigo` en query string, se mantiene el comportamiento actual (delegar a `renderizarBusqueda()`); cuando no llega, se renderiza el nuevo componente de listado. Evita duplicar la ruta/permiso ya existentes y respeta que el sidebar ya apunta a esta misma ruta `index`. Como consecuencia, el formulario vacío de `buscar.tsx` (antes el render por defecto de `index()` sin `codigo`) pierde su punto de entrada; se agrega un tercer flag `?nuevo=1` en la misma ruta `index` para renderizarlo explícitamente, en vez de crear una ruta nueva.
- **Componente de listado separado (`index.tsx`) en vez de extender `buscar.tsx`**: son responsabilidades distintas (listar lo ya guardado vs. traer algo nuevo de la API); mezclarlas complicaría el estado de cada página sin necesidad. El listado incluye un acceso directo (botón/enlace) hacia `buscar` para cubrir el caso "quiero traer una OC nueva".
- **Paginación con `Model::paginate()` estándar de Laravel**, igual que `cfinancieros`/`ccostos`, sin cursor pagination — el volumen esperado de OC guardadas es bajo (cada una requiere confirmación manual de un usuario).
- **Búsqueda por código con debounce 300ms** sobre el mismo listado (filtra por `codigo LIKE`), consistente con el patrón ya establecido; no reemplaza la búsqueda/creación por código exacto de `buscar.tsx`, que sigue existiendo para traer algo que no está en el listado.
- **Eager loading de `proveedor` y `procesoAdquisicion`** en la query del listado para evitar N+1 al mostrar esas columnas (mismo criterio que otros índices del proyecto).

## Risks / Trade-offs

- [Confundir "buscar/crear por código" con "filtrar el listado por código"] → el listado usa un input de filtro claramente rotulado como "Buscar en el listado", separado visualmente del acceso al flujo de `buscar.tsx` ("Consultar código nuevo en Mercado Público").
- [Test HTTP existente de `index()` podría asumir el render actual de `buscar`] → se revisan y actualizan los tests de Feature existentes para `index` antes de cambiar el controlador.

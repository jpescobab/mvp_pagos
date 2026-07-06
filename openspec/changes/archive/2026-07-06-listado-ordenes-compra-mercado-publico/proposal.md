## Why

El change `integrar-ordenes-compra-mercado-publico` (ya archivado) dejó la ruta `GET /adquisiciones/ordenes-compra-mercado-publico` (`adquisiciones.ordenes_compra_mp.index`) apuntando al mismo componente de búsqueda por código (`buscar.tsx`), sin código, en vez de a un listado real. Hoy no existe ninguna forma de ver de un vistazo qué Órdenes de Compra ya se guardaron localmente: para revisar una OC ya cargada hay que conocer su código de antemano y volver a escribirlo en el buscador. El ítem de sidebar "Órdenes de Compra (Mercado Público)" ya existe y apunta a esa misma ruta `index`, reforzando que el usuario espera encontrar ahí un listado, no un formulario vacío.

## What Changes

- `OrdenCompraMercadoPublicoController::index()` deja de renderizar el componente de búsqueda vacío por defecto: cuando no se pasa `codigo` de búsqueda, devuelve un listado paginado de `OrdenCompraMercadoPublico` (con `proveedor` y `procesoAdquisicion` cargados), siguiendo el patrón de "listado denso" ya usado en `maestros/cfinancieros` y `maestros/ccostos`.
- Nueva página `resources/js/pages/adquisiciones/ordenes-compra-mercado-publico/index.tsx`: columnas código de OC, proveedor (avatar+iniciales+nombre), organismo comprador, monto, estado (badge semántico), proceso de adquisición vinculado (o "—"), fecha de emisión; búsqueda por código con debounce 300ms; paginación simple; cada fila navega al detalle (`show.tsx`).
- El listado ofrece acceso destacado al flujo existente de búsqueda/creación por código (`buscar.tsx`) sin fusionar ambas páginas: el listado es solo para las OC ya guardadas, `buscar.tsx` sigue siendo el punto de entrada para traer una OC nueva desde Mercado Público.
- Reutiliza el permiso `viewAny` de `OrdenCompraMercadoPublico` ya definido en `OrdenCompraMercadoPublicoPolicy` — no se agrega ningún permiso nuevo.

## Capabilities

### Modified Capabilities
- `paginas-ordenes-compra-mercado-publico`: se agrega el requirement de listado tabular de Órdenes de Compra guardadas localmente, con búsqueda y paginación; el requirement existente de "búsqueda por código" no cambia (sigue siendo su propia página, alcanzable desde el listado).

## Impact

- `app/Http/Controllers/Adquisiciones/OrdenCompraMercadoPublicoController.php`: cambia el comportamiento de `index()`.
- Nueva página React `resources/js/pages/adquisiciones/ordenes-compra-mercado-publico/index.tsx`.
- Tests de Feature HTTP existentes para `index` (si los hay) más los nuevos para paginación/búsqueda/autorización del listado.
- Sin cambios en rutas, permisos, sidebar ni modelos: todo ya existe.

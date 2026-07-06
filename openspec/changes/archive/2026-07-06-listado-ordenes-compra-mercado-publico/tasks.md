## 1. Backend

- [x] 1.1 Revisar los tests de Feature existentes que cubren `OrdenCompraMercadoPublicoController::index()` sin `codigo` (asumen hoy el render de `buscar`); actualizarlos para reflejar el nuevo comportamiento de listado antes de tocar el controlador. Ningún test existente cubría `index()` sin `codigo`; no requirió actualización, solo tests nuevos.
- [x] 1.2 Modificar `index()`: cuando no llega `codigo` de búsqueda por código exacto, ejecutar una query paginada de `OrdenCompraMercadoPublico::query()->with(['proveedor', 'procesoAdquisicion'])`, con filtro opcional `q` (like sobre `codigo`), orden por fecha de emisión descendente, `paginate(20)->withQueryString()`, y renderizar `adquisiciones/ordenes-compra-mercado-publico/index` con el recurso paginado y `q`. Se agregó además un flag `?nuevo=1` en la misma ruta para llegar al formulario vacío de `buscar.tsx` (ver design.md).
- [x] 1.3 Confirmar que `Gate::authorize('viewAny', OrdenCompraMercadoPublico::class)` sigue aplicándose para esta rama (ya está al inicio del método).
- [x] 1.4 Tests de Feature HTTP: listado vacío, listado con datos (proveedor y proceso de adquisición cargados, o "—" si no vinculado), filtro `q` por código, paginación, usuario sin permiso recibe 403.

## 2. Frontend

- [x] 2.1 Crear `resources/js/pages/adquisiciones/ordenes-compra-mercado-publico/index.tsx` siguiendo el patrón de listado denso de `resources/js/pages/maestros/cfinancieros/index.tsx`: columnas de ancho fijo (código de OC con avatar/iniciales del proveedor, proveedor, RUT del proveedor, monto, estado con badge semántico, proceso de adquisición vinculado o "—", fecha de emisión), búsqueda `q` con debounce 300ms, paginación simple, menú de acciones en dropdown por fila (ver detalle; resto "Disponible próximamente" si no aplica), estado vacío explícito cuando no hay OC guardadas. Ajuste posterior: se reemplazó la columna "Organismo comprador" por "RUT proveedor" a pedido del usuario.
- [x] 2.2 Agregar en el encabezado del listado un acceso explícito y claramente rotulado hacia la página de búsqueda por código (`buscar.tsx`) para consultar/traer una OC que no esté en el listado.
- [x] 2.3 Cada fila navega a `adquisiciones.ordenes_compra_mp.show` de esa OC.
- [x] 2.4 Regenerar tipos de Wayfinder si cambia la forma de props de `index` (`php artisan wayfinder:generate --with-form`). Ejecutado; sin cambios generados.

## 3. Verificación end-to-end

- [x] 3.1 Verificar en el navegador (dev server): listado con OC guardadas muestra columnas correctas y respeta el patrón de listado denso; filtro por código con debounce; paginación; acceso al buscador de código nuevo; fila navega al detalle; estado vacío cuando no hay datos. Verificado con los 3 registros reales de la BD local (LATAM Airlines, Automotriz Montino, Sky Airline): filtro por código funciona, clic en fila navega a `show`, enlace `?nuevo=1` navega al formulario de búsqueda vacío. Estado vacío verificado por el test de Feature (no se forzó en navegador para no alterar datos reales).

## 4. Especificación y cierre

- [x] 4.1 Ejecutar `openspec validate listado-ordenes-compra-mercado-publico --strict` y corregir lo que señale. Válido sin hallazgos.
- [x] 4.2 Ejecutar `composer test` / `composer ci:check` (lint, types, test) y corregir hallazgos. `composer test`: Pint, PHPStan y Pest (418 passed, 4 skipped, 0 fallos) en verde. `composer ci:check` señala `format:check` fallando en 23 archivos preexistentes no tocados por este change (drift de Prettier previo, confirmado con `git status`); fuera de alcance, no se corrigió.
- [x] 4.3 Ejecutar `vendor/bin/pint --dirty --format agent` sobre los archivos PHP modificados. Pasa sin cambios.
- [ ] 4.4 Tras aprobación, archivar el change con `/opsx:archive` para fusionar el spec delta en `openspec/specs/paginas-ordenes-compra-mercado-publico/spec.md`.

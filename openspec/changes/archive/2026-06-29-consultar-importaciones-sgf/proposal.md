## Why

`ImportadorSgf` (tarea 7) ya implementa el ciclo completo de una corrida de importación (`iniciarImportacion()`, `importarFila()`, `finalizarImportacion()`), registrando fuente, usuario o job responsable, fecha de inicio/fin, total de filas y estado (`en_progreso`/`completado`) en `importaciones_sgf`. Sin embargo, ningún controlador expone ese historial: hoy es imposible saber, sin consultar la base de datos directamente, cuándo se ejecutó una importación, desde qué fuente, cuántas filas trajo, o si una corrida quedó atascada en `en_progreso` sin finalizar. El historial de snapshots por caso (ya expuesto en `mostrar-historial-snapshots-sgf`) responde "qué evidencia trajo este `sgf_id`", pero no "qué corridas existieron y qué tan bien les fue".

## What Changes

- Exponer un listado paginado de las `ImportacionSgf` existentes (fuente, quién la inició, fecha de inicio/fin, total de filas, estado), ordenado de la más reciente a la más antigua.
- Exponer el detalle de una importación: sus datos y todos los `snapshots_sgf` que produjo (sgf_id, hash, fecha de captura).
- Es de solo lectura, abierto a cualquier usuario autenticado — mismo criterio ya usado para `indicadores-economicos` y `consulta-definiciones-workflow` (visibilidad operativa/institucional, sin dato sensible de ningún caso o proveedor concreto más allá del que ya se ve en el propio caso).

## Capabilities

### New Capabilities
- `consulta-importaciones-sgf`: listar y ver el detalle de las corridas de importación SGF y los snapshots que cada una produjo.

## Impact

- Nuevos: `App\Http\Controllers\Sgf\ImportacionSgfController`, `App\Http\Resources\Sgf\ImportacionSgfResource`, `routes/sgf.php`, páginas `resources/js/pages/sgf/importaciones/{index,show}.tsx`.
- Modificados: `routes/web.php` (require del nuevo archivo), `resources/js/components/app-sidebar.tsx` (nuevo ítem bajo "Pago de Proveedores", único consumidor actual de SGF).
- Sin cambios de esquema ni de permisos.

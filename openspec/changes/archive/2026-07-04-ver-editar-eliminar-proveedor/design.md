## Context

`registrar-proveedor` (ya archivado) dejó el alta funcionando con un formulario por pasos de ~700 líneas en `create.tsx`. Faltan las otras tres operaciones del CRUD. `Proveedor` usa `SoftDeletes` desde su migración original, pero nada lo aprovecha todavía. `Proveedor` es referenciado por `clientes_medidores`, `casos_pago_proveedor`, `facturas` y `procesos_adquisicion` (todas con `proveedor_id`); ninguna de esas relaciones está declarada hoy en el modelo `Proveedor` (solo existe `clientesMedidores()`).

## Goals / Non-Goals

**Goals:**
- Ver el detalle completo de un proveedor ya registrado.
- Editar cualquier campo del alta (mismos 5 pasos), reutilizando el formulario existente en vez de duplicarlo.
- Eliminar (soft delete) un proveedor, sin dejar relaciones huérfanas apuntando a un registro oculto.

**Non-Goals:**
- Descargar el documento de respaldo bancario desde la vista de detalle — sigue sin endpoint de descarga, igual que en `registrar-proveedor`. El detalle solo indica si existe un documento adjunto.
- Restaurar un proveedor eliminado (soft-deleted) desde la UI — queda como capacidad futura; por ahora la restauración es solo vía Tinker/DB si se necesita revertir un error.
- Eliminación física (`forceDelete`) — el soft delete es la única eliminación soportada.

## Decisions

- **Formulario compartido `ProveedorFormulario`**: se extrae el JSX + estado de `create.tsx` a `resources/js/components/maestros/proveedor-formulario.tsx`, parametrizado por `modo: 'crear' | 'editar'`, `catalogos`, `valoresIniciales?` y `accionUrl` + `metodoHttp`. `create.tsx` y el nuevo `edit.tsx` quedan como wrappers delgados (breadcrumbs, título, paso de props). Evita duplicar ~700 líneas casi idénticas; es la misma razón por la que ya no se escribe el formulario de cero para cada paso.
- **`UpdateProveedorRequest` reutiliza las reglas de `StoreProveedorRequest`** salvo el `unique` de `rutproveedor`, que excluye el propio registro (`Rule::unique('proveedores', 'rutproveedor')->ignore($proveedor->id)`).
- **Reemplazo de documento de respaldo**: si `update()` recibe un archivo nuevo, se borra el archivo anterior (si existía) antes de guardar el nuevo bajo la misma convención de ruta (`proveedores/{id}/documento-respaldo.{ext}`), para no dejar archivos huérfanos cuando cambia la extensión.
- **`destroy()` bloquea la eliminación si el proveedor tiene relaciones activas**: se verifica existencia en `clientes_medidores`, `casos_pago_proveedor`, `facturas` y `procesos_adquisicion` por `proveedor_id` (consulta directa, sin agregar las 3 relaciones que faltan al modelo `Proveedor` — no se necesitan para nada más). Si existe alguna, se redirige con un error explicando qué lo bloquea; si no, se hace soft delete (`$proveedor->delete()`).
- **Autorización**: `ProveedorPolicy` gana `view`, `update`, `delete`, las tres sobre `core_institucional.administrar` — mismo permiso que ya gobierna `create` y el resto de tablas maestras. A diferencia de `UserPolicy::delete()` (que retorna `false` a propósito porque los usuarios nunca se borran, solo se desactivan), aquí sí se permite eliminar porque el proveedor tiene su propio campo `activo` para el caso "dejar de usarlo sin borrar el historial", y el guard de relaciones activas cubre el riesgo real (huérfanos), no la existencia del RUT en sí.
- **Menú de acciones**: `ProveedorActionsMenu` pasa de no recibir props a recibir el `Proveedor` completo; sigue el patrón de `resources/js/pages/seguridad/roles/index.tsx` para el diálogo de confirmación de borrado (`Dialog` + `router.delete()`), no uno nuevo — no existe un componente de confirmación genérico reutilizable en el proyecto todavía y crear uno ahora sería alcance no pedido.

## Risks / Trade-offs

- [Riesgo] Bloquear el borrado por relaciones activas puede sorprender a quien esperaba que "eliminar" simplemente ocultara el proveedor. → Mitigación: el mensaje de error enumera explícitamente qué lo referencia, y el campo `activo` sigue disponible desde `edit.tsx` para desactivarlo sin borrar.
- [Riesgo] Extraer `ProveedorFormulario` en medio de dos consumidores (`create`/`edit`) puede introducir una regresión visual en el alta si el refactor no es 1:1. → Mitigación: se corre la suite de tests de `create` existente (`StoreProveedorTest`) sin cambios tras el refactor, y se verifica visualmente el alta en el navegador antes de continuar con `edit`.

## Migration Plan

1. Refactor `create.tsx` → extraer `ProveedorFormulario` sin cambiar comportamiento (verificar `StoreProveedorTest` sigue pasando).
2. Backend: `UpdateProveedorRequest`, `ProveedorPolicy` (view/update/delete), `show()`/`edit()`/`update()`/`destroy()`, rutas.
3. Frontend: `show.tsx`, `edit.tsx` (usa `ProveedorFormulario`), `ProveedorActionsMenu` reescrito.
4. Rollback: cada pieza es independiente del alta ya archivada; revertir este change no afecta `registrar-proveedor`.

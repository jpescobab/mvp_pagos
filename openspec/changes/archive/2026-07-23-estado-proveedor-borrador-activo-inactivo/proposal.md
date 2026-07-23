## Why

El formulario de alta de proveedor ofrece un botón "Borrador" deshabilitado con la indicación "Disponible próximamente" — la última acción diferida que queda en todo el repositorio. Al investigar qué debía hacer ese botón aparecieron dos cosas que cambian el planteamiento:

1. **Guardar incompleto ya funciona.** Solo RUT y razón social son obligatorios; giro, rubros, contacto, domicilio y datos bancarios son todos `nullable`, y el requirement vigente incluye el escenario "Alta exitosa con datos mínimos". Un borrador entendido como "guardar a medias para retomar después" no agregaría nada.
2. **`proveedores.activo` no gobierna nada.** No existe una sola consulta que filtre proveedores por ese campo. Los dos selectores que ofrecen proveedores para operar —crear un proceso de adquisición y asociar un cliente-medidor— usan `Proveedor::all()` sin filtrar, así que **hoy un proveedor marcado como inactivo se puede seleccionar igual**. El campo se guarda, se muestra en el listado y en el detalle, y no tiene ningún efecto.

Es decir: agregar "Borrador" como un flag más lo dejaría inerte al lado de uno que ya lo está. El problema real detrás del botón deshabilitado es que el estado de un proveedor no significa nada.

## What Changes

- El estado de un proveedor SHALL pasar de un booleano `activo` a un `estado` con tres valores: `borrador` (registro a medio cargar, todavía no habilitado para operar), `activo` (habilitado) e `inactivo` (dado de baja). Un booleano no puede distinguir "nunca estuvo operativo" de "se dio de baja", y esa distinción es justamente la que el botón "Borrador" prometía.
- **BREAKING (esquema)**: se elimina la columna `activo` de `proveedores` y se reemplaza por `estado`. No hay datos de producción; la migración de creación se unifica en vez de acumular un parche más (ver design).
- El estado SHALL gobernar de verdad: los selectores de proveedor para operar —crear un proceso de adquisición y asociar un cliente-medidor— SHALL ofrecer únicamente proveedores en estado `activo`. Esto cierra de paso el defecto latente de que un proveedor inactivo sea seleccionable.
- El catálogo de proveedores (listado, búsqueda y detalle) SHALL seguir mostrando los tres estados: es la pantalla de administración del catálogo, no un selector operativo.
- El formulario de alta SHALL ofrecer "Guardar como borrador" como acción real junto a "Registrar proveedor", ambas con los mismos campos obligatorios (RUT y razón social) y la misma validación. La diferencia es el estado con el que nace el registro, no qué datos se exigen.
- El formulario de edición SHALL permitir cambiar el estado del proveedor, incluyendo promover un borrador a activo.
- Ninguna acción del formulario de proveedor SHALL quedar deshabilitada con la indicación "Disponible próximamente". Con eso **no queda ninguna acción diferida en el repositorio**.
- Sin permisos nuevos: todo se autoriza con `core_institucional.administrar`, que ya gobierna el alta y la edición de proveedores.

## Capabilities

### New Capabilities

Ninguna. El comportamiento pertenece a la capability que ya gobierna el ciclo de vida del proveedor.

### Modified Capabilities

- `registrar-proveedor`: el registro pasa de tener un booleano `activo` a un `estado` de tres valores; se agrega la acción de guardar como borrador en el alta, el cambio de estado en la edición, y el requirement nuevo de que solo los proveedores activos se ofrezcan en los selectores operativos.

## Impact

- **Esquema**: `proveedores.activo` (boolean, default `true`) se reemplaza por `proveedores.estado` (string, default `activo`), con índice, dentro de la migración de creación unificada. La migración de parche `add_datos_completos_to_proveedores_table` se absorbe en la misma. Requiere `php artisan migrate:fresh --seed` en los entornos locales.
- **Backend**: `Proveedor` (fillable, casts, constantes de estado y scope de activos); `ProveedorResource`; `StoreProveedorRequest` y `UpdateProveedorRequest`; `ProveedorController` (alta con estado según la acción); `ProcesoAdquisicionController` y `ClienteMedidorController` (filtrar por activos).
- **Frontend**: `proveedor-status-badge.tsx` pasa de `activo: boolean` a `estado`, con un tercer distintivo para borrador; `proveedor-formulario.tsx` reemplaza el switch de activo por el control de estado y estrena la acción "Guardar como borrador" en el alta; `index.tsx` y `show.tsx` de proveedores; tipos en `types/maestros.ts`. Rutas tipadas vía Wayfinder.
- **Tests**: `tests/Feature/Maestros/StoreProveedorTest.php` y `UpdateProveedorTest.php` ya afirman sobre `activo` y deben migrarse; se agregan los del filtrado en los dos selectores.
- **Trazabilidad**: el estado del proveedor es un dato maestro, no un estado de workflow — no pasa por `TransicionWorkflowService` ni genera transiciones, y este cambio no altera eso. Queda anotado un hallazgo colateral que **no** se aborda acá: el alta y la edición de proveedores no registran nada en `AuditLogger` hoy, a diferencia de la gestión de usuarios. Agregarlo es un cambio con valor propio y alcance distinto (cubriría todo el CRUD de proveedores, no solo el estado); mezclarlo acá ampliaría este change sin cerrarlo bien.
- Sin permisos nuevos, sin cambios en seeders de roles.

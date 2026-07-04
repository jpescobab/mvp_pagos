## Why

El catálogo de `proveedores` ya permite listar (`consulta-catalogo-proveedores`) y registrar (`registrar-proveedor`), pero no hay forma de ver el detalle completo de un proveedor ya registrado, corregir un dato mal ingresado, ni eliminar uno que se dio de alta por error. El menú de acciones del índice ya anuncia "Ver detalle" y "Editar" como "Disponible próximamente" — hay que completar ese CRUD.

## What Changes

- Se agrega `ProveedorController::show()`: vista de detalle de solo lectura con todos los campos (identificación, clasificación, contacto, domicilio, datos bancarios, notas internas).
- Se agrega `ProveedorController::edit()`/`update()`, reutilizando el mismo formulario por pasos del alta (extraído a un componente compartido `ProveedorFormulario`) precargado con los datos actuales, con `UpdateProveedorRequest` (mismas reglas que el alta, RUT único excluyendo el propio registro).
- Se agrega `ProveedorController::destroy()`: elimina (soft delete, el modelo ya usa `SoftDeletes`) un proveedor, **rechazando la operación** si tiene `clientes_medidores`, `casos_pago_proveedor`, `facturas` o `procesos_adquisicion` asociados, para no dejar esas relaciones apuntando a un proveedor oculto.
- Se extiende `ProveedorPolicy` con `view`, `update` y `delete`, todas sobre `core_institucional.administrar` (mismo permiso que gobierna el resto de tablas maestras).
- Se actualiza `ProveedorActionsMenu` para habilitar "Ver detalle" y "Editar" (navegación) y agregar "Eliminar" con diálogo de confirmación (`router.delete()`), siguiendo el patrón ya usado en `seguridad/roles/index.tsx`.

## Capabilities

### New Capabilities
(ninguna)

### Modified Capabilities
- `registrar-proveedor`: se agregan los requirements de ver detalle, editar y eliminar un proveedor al ciclo de vida que ya cubre el alta.

## Impact

- **Backend**: `ProveedorController` (show/edit/update/destroy), `App\Http\Requests\Maestros\UpdateProveedorRequest`, `ProveedorPolicy` (view/update/delete).
- **Frontend**: `resources/js/pages/maestros/proveedores/{show,edit}.tsx`, `resources/js/components/maestros/proveedor-formulario.tsx` (extraído de `create.tsx` para reutilizar en `edit.tsx`), `resources/js/components/maestros/proveedor-actions-menu.tsx` (reescrito).
- **Rutas**: `GET /maestros/proveedores/{proveedor}`, `GET /maestros/proveedores/{proveedor}/editar`, `PATCH /maestros/proveedores/{proveedor}`, `DELETE /maestros/proveedores/{proveedor}` en `routes/maestros.php`.

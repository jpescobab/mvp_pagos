## Why

`ClienteMedidor` es una tabla maestra ya modelada (`app/Models/ClienteMedidor.php`, migración `create_clientes_medidores_table`) y ya listada en el sidebar bajo "Administración", pero `ClienteMedidorController` solo tiene `index()` de solo lectura — igual que `Ccosto`/`Cfinanciero` antes de que `Item` recibiera su CRUD completo. Su menú de acciones (`ClienteMedidorActionsMenu`) es el mismo placeholder "Disponible próximamente" que usan los catálogos de solo lectura. No hay forma de dar de alta, editar ni eliminar un cliente medidor desde la UI.

## What Changes

- Backend: `ClienteMedidorPolicy` (view/create/update/delete, reutilizando `core_institucional.administrar` — mismo permiso que `Item`/`Proveedor`/`Ccosto`/`Cfinanciero`), `StoreClienteMedidorRequest`/`UpdateClienteMedidorRequest`, y se agregan `create`/`store`/`show`/`edit`/`update`/`destroy` a `ClienteMedidorController` (su `index()` actual no cambia). Rutas nuevas en `routes/maestros.php` bajo el prefijo `clientes-medidores` ya registrado.
- `destroy` sin verificación de relaciones dependientes: nada en el código referencia `ClienteMedidor` hoy (verificado — solo `Proveedor` lo referencia para bloquear SU PROPIO borrado, no al revés), mismo caso que `Asignacion`/`Catalogo`.
- Frontend: `create.tsx`/`edit.tsx` con formulario plano (campos: `numero_cliente`, `ccosto_id` vía `<Select>` requerido, `proveedor_id` vía `<Select>` opcional con opción "Sin proveedor" — mismo patrón que `adquisiciones/procesos/crear.tsx`, `tipo_suministro` como texto libre, `direccion_suministro`, `activo`), `show.tsx` de detalle. `ClienteMedidorActionsMenu` deja de ser el placeholder "Disponible próximamente" y pasa a tener acciones reales (ver/editar/eliminar), calcando `ProveedorActionsMenu`.
- Tests Feature de CRUD siguiendo la convención ya usada para `Item`/`Proveedor`.
- **Sin migraciones nuevas** ni cambios de esquema.

## Capabilities

### New Capabilities
(ninguna)

### Modified Capabilities
- `tablas-maestras-institucionales`: el requirement "Incorporar tablas maestras institucionales desde el inicio" ya tiene el escenario "Registrar cliente medidor" (creación); se agregan los escenarios de listar con acciones, editar y eliminar, análogos a los ya documentados para el clasificador presupuestario.

## Impact

- `app/Policies/ClienteMedidorPolicy.php` (nuevo)
- `app/Http/Requests/Maestros/{Store,Update}ClienteMedidorRequest.php` (nuevo)
- `app/Http/Controllers/Maestros/ClienteMedidorController.php`: agrega los 6 métodos de mutación.
- `routes/maestros.php`: rutas nuevas.
- `resources/js/pages/maestros/clientes-medidores/{create,edit,show}.tsx` (nuevo)
- `resources/js/components/maestros/cliente-medidor-actions-menu.tsx`: pasa de placeholder a acciones reales.
- `tests/Feature/Maestros/{Store,Show,Update,Destroy}ClienteMedidorTest.php` (nuevo)
- `app/Providers/AppServiceProvider.php`: registro de la policy nueva.

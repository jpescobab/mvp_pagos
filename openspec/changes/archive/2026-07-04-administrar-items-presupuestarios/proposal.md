## Why

`openspec/specs/tablas-maestras-institucionales/spec.md` ya especifica el clasificador presupuestario institucional (`items`, `asignaciones`, `catalogos`) y el modelo `Item` (`app/Models/Item.php`) junto con su migración (`create_items_table`) ya existen — `codigo` único, `nombre`, `descripcion`, `activo`, soft deletes, y relaciones `hasMany` a `Asignacion`/`Catalogo` para cuando esos se implementen. Pero no existe ningún controlador, ruta, policy, página React ni ítem de sidebar sobre ese modelo: hoy es una tabla sin ninguna forma de administrarla. Se necesita el CRUD para que Administración pueda mantener el catálogo de ítems presupuestarios (alta, edición, activar/desactivar, baja), como paso previo a que `asignaciones`/`catalogos` (que dependen de `item_id`) tengan de dónde colgar.

## What Changes

- Backend: `ItemController` en el namespace `Maestros` con `index/create/store/show/edit/update/destroy`, siguiendo exactamente el patrón ya usado por `ProveedorController` (único CRUD completo existente en Maestros hoy; `Ccosto`/`Cfinanciero`/`ClienteMedidor` solo tienen `index` de solo lectura).
- `ItemPolicy` con `view`/`create`/`update`/`delete` gateando `core_institucional.administrar`, igual que `ProveedorPolicy`.
- `StoreItemRequest`/`UpdateItemRequest` (Form Requests) con `authorize()` chequeando el mismo permiso, y validación de `codigo` (`required`, `unique:items,codigo` ignorando el propio registro en update), `nombre` (`required`), `descripcion` (`nullable`), `activo` (`boolean`).
- `ItemResource` exponiendo `id`, `codigo`, `nombre`, `descripcion`, `activo`.
- Rutas nuevas en `routes/maestros.php` bajo el mismo prefijo/grupo `maestros` ya registrado, con el mismo shape que las de `proveedores` (index, create, store, show, edit, update, destroy).
- Frontend: páginas `resources/js/pages/maestros/items/{index,create,edit,show}.tsx` — el listado sigue el patrón de "Listados tabulares densos" (`openspec/specs/tema-visual-layout/spec.md`), y crear/editar/ver son formularios simples de una sola sección (no un wizard, a diferencia de Proveedor) dado que el ítem solo tiene 3 campos editables.
- Nuevo ítem "Ítems Presupuestarios" en el sidebar (`resources/js/components/app-sidebar.tsx`), dentro del grupo "Administración", junto a Proveedores/Clientes Medidores/Centros Financieros/Centros de Costos — usando import con nombre de la función `index` de Wayfinder, igual que el resto del sidebar ya corregido.
- Tests Feature siguiendo la convención de nombres ya usada en `tests/Feature/Maestros/` (`ConsultarCatalogoXTest`, `StoreXTest`, `ShowXTest`, `UpdateXTest`, `DestroyXTest`).
- **Sin migraciones nuevas**: la tabla `items` y el modelo `Item` ya existen tal cual se necesitan; no se modifica su esquema.

## Capabilities

### New Capabilities
(ninguna)

### Modified Capabilities
- `tablas-maestras-institucionales`: el requirement "Modelar el clasificador presupuestario institucional" ya cubre la existencia del modelo `Item`; se agrega el requirement de que el sistema exponga un CRUD administrable (HTTP + UI) sobre ese catálogo, no solo el modelo de datos.

## Impact

- `app/Http/Controllers/Maestros/ItemController.php` (nuevo)
- `app/Policies/ItemPolicy.php` (nuevo)
- `app/Http/Requests/Maestros/{Store,Update}ItemRequest.php` (nuevo)
- `app/Http/Resources/Maestros/ItemResource.php` (nuevo)
- `routes/maestros.php` (rutas nuevas, mismo grupo existente)
- `resources/js/pages/maestros/items/{index,create,edit,show}.tsx` (nuevo)
- `resources/js/components/app-sidebar.tsx` (nuevo ítem de navegación)
- `tests/Feature/Maestros/*ItemTest.php` (nuevo)
- `database/seeders/RolesAndPermissionsSeeder.php`: sin cambios si `core_institucional.administrar` ya cubre esto (a confirmar en design/tasks).

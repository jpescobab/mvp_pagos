## 1. Esquema y modelo

- [x] 1.1 Unificar las migraciones de `proveedores` (decisión 5 del design): editar `database/migrations/2026_06_26_002546_create_proveedores_table.php` para que declare el esquema final —los campos que hoy agrega `add_datos_completos_to_proveedores_table` más `estado` (string, default `'activo'`, indexado) en el lugar que ocupaba `activo`— y **eliminar** `database/migrations/2026_07_04_164702_add_datos_completos_to_proveedores_table.php`. La columna `activo` deja de existir.
- [x] 1.2 En `app/Models/Proveedor.php`: agregar las constantes `ESTADO_BORRADOR`, `ESTADO_ACTIVO`, `ESTADO_INACTIVO` y la lista `ESTADOS`; reemplazar `activo` por `estado` en `$fillable` y quitar su cast booleano. Agregar el scope `activos()` que filtra por `ESTADO_ACTIVO` — decisión 2 del design: el filtro se expresa una sola vez en el modelo, no como un `where` repetido en cada controlador.

## 2. Backend — validación, recursos y filtrado

- [x] 2.1 En `app/Http/Requests/Maestros/StoreProveedorRequest` y `UpdateProveedorRequest`, reemplazar la regla `'activo' => ['boolean']` por `'estado' => ['required', Rule::in(Proveedor::ESTADOS)]`. Las dos acciones del alta comparten Request y campos obligatorios (decisión 3): no relajar nada para el borrador.
- [x] 2.2 En `app/Http/Resources/Maestros/ProveedorResource`, reemplazar `activo` por `estado`.
- [x] 2.3 En `app/Http/Controllers/Maestros/ProveedorController`, hacer que `store` persista el estado recibido (el formulario envía `borrador` o `activo` según la acción elegida) y que `update` lo actualice. Controlador liviano: sin ramas de negocio nuevas más allá de pasar el campo.
- [x] 2.4 En `app/Http/Controllers/Adquisiciones/ProcesoAdquisicionController` (~línea 92) y `app/Http/Controllers/Maestros/ClienteMedidorController` (~línea 120), reemplazar `Proveedor::all()` por `Proveedor::activos()->get()`. Estos son los dos selectores operativos; el listado del catálogo en `ProveedorController::index` **no** se filtra (decisión 2).

## 3. Frontend

- [x] 3.1 En `resources/js/types/maestros.ts`, reemplazar `activo: boolean` por `estado: 'borrador' | 'activo' | 'inactivo'` en el tipo de proveedor.
- [x] 3.2 En `resources/js/components/maestros/proveedor-status-badge.tsx`, cambiar la prop de `activo: boolean` a `estado` y agregar el distintivo de borrador, usando tokens semánticos del tema (`success` para activo, `danger` para inactivo, un tono neutro/`warning` para borrador — no colores literales).
- [x] 3.3 En `resources/js/components/maestros/proveedor-formulario.tsx`: reemplazar el switch de activo por el control de estado (visible en edición, con los tres valores); en modo `crear`, reemplazar el botón "Borrador" deshabilitado con tooltip "Disponible próximamente" por una acción real que envíe el formulario con estado `borrador`, junto a la de registrar. Ambas comparten la condición de habilitación (RUT + razón social completos). Verificar que no queden imports de `Tooltip*` sin uso.
- [x] 3.4 En `resources/js/pages/maestros/proveedores/index.tsx` y `show.tsx`, pasar `estado` al badge en vez de `activo`.
- [x] 3.5 Regenerar rutas tipadas con `php artisan wayfinder:generate --with-form` si cambió alguna firma de ruta (no debería; confirmar).

## 4. Tests y validaciones

- [x] 4.1 Migrar las afirmaciones existentes sobre `activo` en `tests/Feature/Maestros/StoreProveedorTest.php` y `UpdateProveedorTest.php` al campo `estado`, incluyendo que el alta por defecto deja el proveedor en `activo`.
- [x] 4.2 Agregar en `StoreProveedorTest` la cobertura del borrador: guardar como borrador con RUT y razón social crea el proveedor en estado `borrador`; intentarlo sin RUT o sin razón social falla con el mismo error de validación que el alta normal y no crea nada.
- [x] 4.3 Agregar en `UpdateProveedorTest` la promoción de borrador a activo, y el rechazo de un estado fuera del dominio (`Rule::in`).
- [x] 4.4 Crear la cobertura del filtrado en los dos selectores operativos: con un proveedor de cada estado, el formulario de creación de proceso de adquisición y el de cliente medidor ofrecen **solo** el activo. Afirmar también que el listado del catálogo de proveedores sigue mostrando los tres.
- [x] 4.5 Correr `php artisan test --compact tests/Feature/Maestros/` y `tests/Feature/Adquisiciones/` y dejarlos verdes. Como se quitó una migración, correr además la suite completa: cualquier test que cree proveedores directamente con `activo` va a fallar y hay que migrarlo.
- [x] 4.6 Correr `vendor/bin/pint --dirty --format agent`, `composer types:check` (PHPStan), `npm run types:check` y `npm run lint:check` — todo verde.
- [x] 4.7 Dejar constancia en el resumen final de que los entornos locales necesitan `php artisan migrate:fresh --seed` por la unificación de migraciones, con el comando exacto. La implementación **no** ejecuta ese comando contra la base del usuario.

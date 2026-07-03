## 1. Backend

- [x] 1.1 Editar `app/Http/Controllers/Seguridad/UserController.php::index()`: quitar el parsing y las cláusulas `->when()` de `estado`, `rol_id`, `jurisdiccion_id`, `centro_financiero_id`, `centro_costo_id`; quitar esas claves del array `filters`; quitar `'catalogs' => $this->catalogos()` de la respuesta del índice.
- [x] 1.2 Editar `catalogos()`: quitar la clave `jurisdicciones` (ya sin consumidores).

## 2. Frontend

- [x] 2.1 Eliminar `resources/js/components/seguridad/user-filters.tsx`.
- [x] 2.2 Editar `resources/js/types/seguridad.ts`: quitar `estado`, `rol_id`, `jurisdiccion_id`, `centro_financiero_id`, `centro_costo_id` de `FiltrosUsuarios`; quitar `jurisdicciones` de `CatalogosUsuarios`.
- [x] 2.3 Editar `resources/js/pages/seguridad/usuarios/index.tsx`: quitar `catalogs` de `PageProps` y de la desestructuración; reemplazar `<UserFilters>` por un `<Input>` de búsqueda simple (mismo patrón que `proveedores/index.tsx`); simplificar `navegar()` para no enviar los filtros eliminados; simplificar el estado vacío a un solo mensaje ("No se encontraron usuarios con esa búsqueda.") con botón para limpiar la búsqueda.

## 3. Tests

- [x] 3.1 Editar `tests/Feature/Seguridad/UserControllerTest.php`: eliminar el test `'los filtros institucionales acotan el listado'`; quitar `->has('catalogs')` del test `'un usuario con el permiso usuarios.ver puede listar usuarios'`.

## 4. Verificación

- [x] 4.1 Ejecutar `tests/Feature/Seguridad/*` completo (56 tests, 0 fallos) para confirmar que no se rompió nada.
- [x] 4.2 Levantar el servidor de desarrollo y verificar en el preview que el índice de usuarios solo muestra búsqueda (sin selects de filtro), que la búsqueda sigue funcionando (incluido el estado vacío "No se encontraron usuarios con esa búsqueda."), y que "Nuevo usuario"/orden/paginación/menú de acciones no se vieron afectados.
- [x] 4.3 Ejecutar `npm run lint:check`, `npm run format:check` y `npm run types:check` (limpios); `composer test` corriendo la suite completa.

## 5. Documentación y cierre

- [x] 5.1 Ejecutar `/opsx:archive` para fusionar la spec delta en `openspec/specs/listar-usuarios-institucionales/spec.md` y archivar el change.

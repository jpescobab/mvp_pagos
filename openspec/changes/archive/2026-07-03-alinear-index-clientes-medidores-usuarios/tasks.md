## 1. Backend: Clientes Medidores

- [x] 1.1 Editar `app/Http/Controllers/Maestros/ClienteMedidorController.php`: agregar `q` (búsqueda por `numero_cliente`, `proveedor.nombre` vía `whereHas`, `ccosto.codigo`/`ccosto.nombre` vía `whereHas`), `orderBy('numero_cliente')`, `paginate(20)->withQueryString()`.
- [x] 1.2 Actualizar `resources/js/types/maestros.ts`: `ClienteMedidor` no cambia de forma, pero el prop de página pasa a `Paginated<ClienteMedidor>` en el frontend.

## 2. Frontend: componentes de Clientes Medidores

- [x] 2.1 Crear `resources/js/components/maestros/cliente-medidor-status-badge.tsx` (mismo patrón que `proveedor-status-badge.tsx`, tokens `success`/`danger`).
- [x] 2.2 Crear `resources/js/components/maestros/cliente-medidor-actions-menu.tsx` (mismo patrón placeholder que `proveedor-actions-menu.tsx`, "Ver detalle" deshabilitado con "Disponible próximamente").

## 3. Frontend: página de Clientes Medidores

- [x] 3.1 Reescribir `resources/js/pages/maestros/clientes-medidores/index.tsx` siguiendo el patrón denso: tabla `table-fixed`, avatar con iniciales junto al número de cliente, columnas proveedor/centro de costo/tipo de suministro/dirección truncadas con tooltip y ocultas progresivamente, badge de estado, menú de acciones, búsqueda con debounce 300ms, paginación con contador "Mostrando X–Y de Z".

## 4. Frontend: Usuarios

- [x] 4.1 Editar `resources/js/components/seguridad/users-table.tsx` (bloque de escritorio `hidden ... md:block`): agregar `table-fixed` con anchos por columna, `Avatar`/`AvatarFallback` con `useInitials` junto al nombre, `truncate` + `title` en columnas secundarias (email, cargo, unidad, jurisdicción, centro financiero, centro de costo), reducir celdas a `px-2.5 py-1`. No tocar la vista de tarjetas mobile ni la lógica de filtros/orden/paginación.
- [x] 4.2 Editar `resources/js/components/seguridad/user-status-badge.tsx`: reemplazar colores crudos por `border-transparent bg-success-soft text-success` (activo) y `border-transparent bg-danger-soft text-destructive` (inactivo).

## 5. Tests

- [x] 5.1 Actualizar `tests/Feature/Maestros/ConsultarCatalogoClientesMedidoresTest.php` (ya existía con un único test desactualizado): listar paginado, buscar por número de cliente, buscar por nombre de proveedor, buscar por código/nombre de centro de costo, usuario no autenticado redirigido al login.
- [x] 5.2 Ejecutar `tests/Feature/Seguridad/*` existentes (57 tests, filtros, orden, paginación de usuarios) para confirmar que no se rompió nada al tocar `UsersTable`/`UserStatusBadge`.

## 6. Verificación

- [x] 6.1 Levantar el servidor de desarrollo y verificar en el preview: Clientes Medidores (búsqueda, paginación, densidad visual, columnas relacionadas) y Usuarios (tabla densa con avatar, badges con tokens correctos, truncado con ellipsis, filtros/orden/paginación siguen funcionando).
- [x] 6.2 Ejecutar `composer test` (279 tests, 0 fallos), `npm run lint:check`, `npm run format:check` y `npm run types:check` (limpios en los archivos tocados).

## 7. Documentación y cierre

- [x] 7.1 Ejecutar `/opsx:archive` para fusionar la spec delta en `openspec/specs/consultar-catalogo-clientes-medidores/spec.md` y archivar el change.

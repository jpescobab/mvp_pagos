## Why

La revisión de los índices existentes contra el requirement "Listados tabulares densos" de `tema-visual-layout` encontró que `maestros/clientes-medidores/index.tsx` y `seguridad/usuarios/index.tsx` no cumplen la convención ya formalizada (implementada por primera vez en Proveedores y luego en Centros Financieros/Costos): tablas sin ancho fijo, sin identidad visual por fila, badges con colores crudos en vez de tokens semánticos, sin truncado/tooltip, y en el caso de Clientes Medidores, sin búsqueda, paginación ni menú de acciones. No se está proponiendo ningún requisito nuevo — es una corrección de implementación para que el código cumpla la spec ya vigente.

## What Changes

- `ClienteMedidorController::index()` agrega búsqueda por `q` (número de cliente, proveedor o centro de costo) y paginación (`paginate(20)->withQueryString()`), igual que `ProveedorController`.
- `maestros/clientes-medidores/index.tsx` se reescribe siguiendo el patrón denso: tabla `table-fixed`, avatar con iniciales, badge de estado con tokens semánticos, columnas secundarias truncadas con tooltip y ocultas progresivamente, menú de acciones desplegable (placeholder "Disponible próximamente", ya que no hay CRUD de clientes medidores todavía), búsqueda con debounce, paginación.
- Nuevos componentes `cliente-medidor-status-badge.tsx` y `cliente-medidor-actions-menu.tsx` en `resources/js/components/maestros/`.
- `UsersTable` (`resources/js/components/seguridad/users-table.tsx`) se ajusta a `table-fixed` con anchos por columna, identidad visual (avatar) junto al nombre, truncado con tooltip en columnas secundarias, y densidad `px-2.5 py-1`. Se conservan los filtros, el orden, la paginación con selector de por-página y el menú de acciones ya implementados (funcionalidad adicional válida, no forma parte de lo que hay que corregir).
- `user-status-badge.tsx` se corrige para usar los tokens semánticos del tema (`bg-success-soft text-success` / `bg-danger-soft text-destructive`) en vez de colores Tailwind crudos y `variant="secondary"`.
- Sin cambios de comportamiento funcional en Usuarios (filtros, orden, acciones) — solo densidad visual y tokens de color.

## Capabilities

### Modified Capabilities
- `consultar-catalogo-clientes-medidores`: el listado pasa de devolver todos los registros sin filtro a devolver un listado paginado con búsqueda por número de cliente, proveedor o centro de costo. No cambian requisitos de `tema-visual-layout` — esta corrección solo alinea la implementación con el requirement "Listados tabulares densos" ya vigente.

## Impact

- Código: `app/Http/Controllers/Maestros/ClienteMedidorController.php`, `resources/js/pages/maestros/clientes-medidores/index.tsx`, `resources/js/components/maestros/cliente-medidor-status-badge.tsx` (nuevo), `resources/js/components/maestros/cliente-medidor-actions-menu.tsx` (nuevo), `resources/js/components/seguridad/users-table.tsx`, `resources/js/components/seguridad/user-status-badge.tsx`.
- Tests: actualizar/agregar tests de `ClienteMedidorController` para cubrir búsqueda y paginación; los tests existentes de Usuarios no deberían romperse (sin cambio de comportamiento), se re-ejecutan para confirmar.
- Sin cambios de modelos, migraciones ni permisos.

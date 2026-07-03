## 1. Componentes reutilizables

- [x] 1.1 Crear `resources/js/components/maestros/proveedor-status-badge.tsx`: badge de estado (activo/inactivo) usando los tokens semánticos `--success`/`--danger-soft` del tema (agregados en `tema-visual-capj-v2`), estructura de componente igual a `UserStatusBadge` (`resources/js/components/seguridad/user-status-badge.tsx`) pero con esos tokens en vez de los verdes/grises hardcodeados que usa ese componente.
- [x] 1.2 Crear `resources/js/components/maestros/proveedor-actions-menu.tsx`: menú desplegable (`dropdown-menu` de shadcn) con un único ítem "Ver detalle" deshabilitado y tooltip "Disponible próximamente", mismo patrón que `resources/js/components/seguridad/user-actions-menu.tsx` (la parte de acciones diferidas).

## 2. Rediseño de la tabla

- [x] 2.1 Reescribir `resources/js/pages/maestros/proveedores/index.tsx`: reducir el padding vertical de celdas (`py-2` → `py-1.5`), agregar columna de avatar con iniciales (`useInitials`) junto al nombre, reemplazar la columna "Activo" por `ProveedorStatusBadge`, agregar columna final con `ProveedorActionsMenu`. Mantener buscador por RUT/nombre y paginación existentes sin cambios de comportamiento.
- [x] 2.2 Verificar que la tabla siga siendo responsive (scroll horizontal o colapso de columnas secundarias en mobile, igual al criterio ya usado en `UsersTable`).

## 3. Validación

- [x] 3.1 `npm run lint:check` y `npm run types:check`.
- [x] 3.2 `php artisan test --filter=ConsultarCatalogoProveedoresTest` (no debe romperse: mismo contrato de datos/props).
- [x] 3.3 Verificación en navegador: más filas visibles que antes en la misma altura de viewport, avatar con iniciales, badge de estado con color correcto para proveedores activos e inactivos, menú de acciones abre y muestra "Ver detalle" deshabilitado con tooltip, buscador y paginación siguen funcionando.

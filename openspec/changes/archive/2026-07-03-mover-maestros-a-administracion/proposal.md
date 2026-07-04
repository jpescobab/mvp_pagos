## Why

El usuario pidió mover las funcionalidades del grupo "Maestros" del sidebar (Proveedores, Clientes Medidores, Centros Financieros, Centros de Costos) al grupo "Administración", eliminando el grupo "Maestros" como agrupación separada.

## What Changes

- `resources/js/components/app-sidebar.tsx`: los ítems "Proveedores", "Clientes Medidores", "Centros Financieros" y "Centros de Costos" pasan a `administracionNavItems`; se elimina el `NavGroup` "Maestros" y el array `maestrosNavItems`/`estructuraInstitucionalNavItems` se consolidan dentro de "Administración".
- "Centros Financieros" y "Centros de Costos" conservan su visibilidad condicionada al permiso `core_institucional.administrar` (ya implementada), ahora dentro del grupo "Administración".
- Sin cambios de rutas, controladores, permisos ni páginas — es exclusivamente una reorganización de navegación.

## Capabilities

### Modified Capabilities
- `tema-visual-layout`: el escenario "Grupos por módulo implementado" del requirement "Navegación principal como riel de íconos" deja de listar "Maestros" como grupo; sus ítems pasan a "Administración".

## Impact

- Código: `resources/js/components/app-sidebar.tsx`.
- Sin impacto en backend, permisos ni tests de otros módulos.

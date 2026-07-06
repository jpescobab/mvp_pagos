## ADDED Requirements

### Requirement: Imports de rutas del sidebar con nombre, no por defecto
El sidebar principal SHALL importar con nombre únicamente las funciones de ruta de Wayfinder que efectivamente usa (por ejemplo, `index`), en vez de importar el export por defecto de un módulo de rutas (que agrupa todos los métodos del controlador), por consistencia con el patrón ya usado en el resto de componentes globales (`app-header.tsx`, `user-menu-content.tsx`). Nota: en la práctica esto no logró reducir el tamaño del bundle (ver `openspec/changes/archive/*-sidebar-route-imports-tree-shaking/proposal.md`, sección "Resultado medido") porque el código generado por Wayfinder ata sus métodos al objeto exportado vía asignaciones a nivel de módulo, lo que impide a Rollup tree-shakearlos mientras cualquier otro consumidor de la app siga usando el export por defecto del mismo archivo.

#### Scenario: Import con nombre de la función de ruta usada
- **WHEN** se revisa el código fuente de `app-sidebar.tsx`
- **THEN** cada ítem de navegación importa la función de ruta correspondiente con import con nombre (p. ej. `import { index as proveedores } from '@/routes/maestros/proveedores'`)
- **AND** ningún ítem importa el export por defecto de un módulo de rutas

#### Scenario: Los enlaces del sidebar no cambian
- **WHEN** un usuario autenticado visualiza el sidebar principal tras el cambio de imports
- **THEN** cada ítem de navegación apunta exactamente a la misma URL que antes del cambio

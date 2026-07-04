## Context

El sidebar agrupa la navegación por módulo en `resources/js/components/app-sidebar.tsx` (arrays `*NavItems` + `<NavGroup label="..." items={...} />`). El grupo "Maestros" (`maestrosNavItems`: Proveedores, Clientes Medidores) y las dos entradas condicionadas por permiso (`estructuraInstitucionalNavItems`: Centros Financieros, Centros de Costos, visibles solo con `core_institucional.administrar`) se fusionan dentro de "Administración".

## Goals / Non-Goals

**Goals:**
- Que las cuatro entradas aparezcan bajo "Administración", en vez de en un grupo "Maestros" separado.
- Conservar la condición de permiso existente para Centros Financieros/Costos.

**Non-Goals:**
- No se cambian rutas, iconos, ni el orden interno de los ítems ya existentes de Administración (Usuarios, Auditoría, Definiciones de Workflow, Roles y Permisos).

## Decisions

- Las cuatro entradas se agregan al final de `administracionNavItems`, en el mismo orden mostrado en la captura del usuario (Proveedores, Clientes Medidores, Centros Financieros, Centros de Costos), calculando el array final dentro del componente (igual que ya se hacía para el grupo "Maestros") para poder condicionar las dos últimas por permiso.
- Se elimina el `NavGroup` "Maestros" y las constantes `maestrosNavItems`/`estructuraInstitucionalNavItems` se fusionan en una sola lista de administración; no se crean nuevas abstracciones.

## Risks / Trade-offs

Ninguno relevante: cambio de UI puro, sin lógica de negocio ni datos involucrados.

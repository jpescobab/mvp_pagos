## Why

`api-adquisiciones` construyó los controladores/Resources/rutas para `procesos_adquisicion`, pero deliberadamente sin páginas `.tsx` ("paso explícitamente posterior y separado", mismo patrón que `api-pago-proveedores` → `paginas-pago-proveedores`). Hoy esas rutas devuelven `Inertia::render()` apuntando a componentes que no existen. Con `paginas-pago-proveedores` ya resuelto, este change replica exactamente ese patrón sobre el dominio de Adquisiciones, reutilizando los componentes compartidos que ya probaron servir a más de un módulo (`EstadoBadge`).

## What Changes

- Crear 3 páginas Inertia en `resources/js/pages/adquisiciones/`:
  - `procesos/index.tsx`: tabla paginada de procesos de adquisición (código, modalidad, ccosto, proveedor si existe, monto, badge de estado del workflow), enlace a cada fila y a `procesos/crear`.
  - `procesos/show.tsx`: detalle de un proceso — cabecera (código, modalidad, ccosto, proveedor, monto), botones de transición disponibles (con diálogo de comentario cuando la transición lo requiere), historial de transiciones, checklist documental — mismo patrón que `casos/show.tsx`.
  - `procesos/crear.tsx`: formulario de creación con selects (modalidad, ccosto, proveedor opcional, todos recibidos del backend, nunca hardcodeados) y campos código/monto/objeto, mostrando errores de validación por campo sin perder los valores ingresados.
- Crear `resources/js/types/adquisiciones.ts` con los tipos que reflejan `ProcesoAdquisicionResource` — reutiliza `Proceso`, `EstadoWorkflow`, `TransicionWorkflow`, `HistorialTransicion`, `ChecklistItem`, `Paginated` ya definidos en `pago-proveedores.ts` (son genéricos, no específicos de ese módulo) en vez de duplicarlos.
- Reutilizar sin cambios `EstadoBadge` (`resources/js/components/pago-proveedores/estado-badge.tsx`) — ya es genérico, no específico de pago-proveedores a pesar de su ubicación.
- Agregar navegación: grupo "Adquisiciones" en el sidebar con un ítem "Procesos" hacia `adquisiciones.procesos.index`.
- A diferencia de `paginas-pago-proveedores`, no se esperan brechas que cerrar en la capa HTTP: `api-adquisiciones` ya eager-carga y expone `checklist` (vía el mismo `ProcesoResource` ya corregido) y ya entrega `modalidades`/`ccostos`/`proveedores` en `procesos.create()` desde su propio diseño.

**Fuera de alcance (decisión explícita):** filtros/búsqueda en el listado; ocultar en el frontend transiciones sin permiso (mismo criterio que `paginas-pago-proveedores`); edición o eliminación de un proceso ya creado (el dominio no define esas operaciones).

## Capabilities

### New Capabilities
- `paginas-adquisiciones`: páginas React/Inertia para listar, ver y crear procesos de adquisición, consumiendo exclusivamente los datos que ya entregan los controladores de `api-adquisiciones`.

### Modified Capabilities
(ninguna — a diferencia de `paginas-pago-proveedores`, no se requiere ningún ajuste a `api-adquisiciones`; sus Resources y controladores ya entregan todo lo que las páginas necesitan.)

## Impact

- Archivos nuevos: 3 páginas `.tsx` en `resources/js/pages/adquisiciones/procesos/`, `resources/js/types/adquisiciones.ts`.
- Archivos modificados: `resources/js/components/app-sidebar.tsx` (nuevo grupo de navegación), `resources/js/components/nav-main.tsx` no requiere cambios (el prop `label` ya existe desde `paginas-pago-proveedores`).
- Sin migraciones ni cambios de rutas/permisos — los endpoints HTTP ya existen tal cual.
- Requiere `npm run build`/`composer dev` para que Wayfinder genere `resources/js/routes/adquisiciones/...` antes de que las páginas puedan importar las funciones de ruta tipadas.

## Context

`resources/js/pages/maestros/proveedores/index.tsx` es una tabla de solo lectura (spec `consulta-catalogo-proveedores`) con espaciado amplio (`px-4 py-2`) y sin columna de acciones. El backend (`ProveedorController::index()`, `ProveedorResource`) ya expone `id`, `rutproveedor`, `nombre`, `correo`, `direccion`, `contacto`, `activo` — este cambio no toca ese contrato.

El usuario compartió un mockup con columnas y estados que no existen en el modelo actual (`giro`, `región`, `condición de pago`, estado "Pendiente"). Se acordó explícitamente no inventarlos: este cambio es solo de presentación sobre los datos reales.

## Goals / Non-Goals

**Goals:**
- Reducir el espaciado vertical de filas para que quepan más proveedores por pantalla.
- Añadir identidad visual por fila (avatar con iniciales) y un badge de estado con color semántico.
- Mover la interacción por fila a un menú desplegable de acciones, siguiendo el patrón ya usado en `user-actions-menu.tsx`.

**Non-Goals:**
- No se agregan campos nuevos (`giro`, `región`, `condición de pago`) ni el estado "Pendiente".
- No se agrega CRUD (crear/editar/eliminar/restaurar) de proveedores.
- No se agregan chips de filtro por estado con conteos (requeriría un endpoint de conteos que no existe; queda fuera de alcance).

## Decisions

- **Contenido del menú de acciones**: hoy no existe ninguna acción real sobre un proveedor (ni ver detalle: no hay página `show`). En vez de inventar un ítem "Ver detalle" que no lleva a ningún sitio, el menú desplegable se implementa igual que el patrón de `user-actions-menu.tsx` con un único ítem diferido: "Ver detalle" deshabilitado con tooltip "Disponible próximamente". Esto documenta la intención futura (consistente con el resto de catálogos Maestros) sin fingir una funcionalidad que no existe ni dejar la columna vacía sin explicación.
- **Badge de estado**: se reutilizan los tokens semánticos `--success`/`--danger` ya definidos en `resources/css/app.css` (agregados en el cambio `tema-visual-capj-v2`) vía las utilidades Tailwind `text-success`/`bg-danger-soft` — no se agregan colores nuevos.
- **Avatar con iniciales**: se reutiliza el hook `useInitials` ya existente (mismo usado en el avatar del topbar) en vez de crear una función de iniciales nueva.
- **Densidad**: se reduce el padding de celdas de `py-2` a algo más ajustado (`py-1.5`) y se usa `text-xs`/`text-sm` combinados según la importancia de cada columna, en vez de reducir el tamaño de fuente global (que afectaría accesibilidad). Sin cambiar el `paginate(20)` del backend en este cambio.

## Risks / Trade-offs

- [Riesgo: el ítem "Ver detalle" diferido puede leerse como promesa de una funcionalidad que tardará en llegar] → Mitigación: es el mismo patrón ya aceptado y usado en la bandeja de Usuarios; consistencia de producto por sobre no mostrar nada.
- [Trade-off: no hay chips de filtro por estado con conteos del mockup] → Aceptado explícitamente; agregar conteos por estado implicaría tocar `ProveedorController::index()` (fuera del alcance acordado de "solo presentación").

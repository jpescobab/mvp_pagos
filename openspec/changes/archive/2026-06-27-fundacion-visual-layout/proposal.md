## Why

Hoy la interfaz es el scaffolding sin modificar de `laravel/react-starter-kit`: branding literal "Laravel Starter Kit", ícono genérico, paleta gris neutra de shadcn/ui y un sidebar de una sola entrada ("Dashboard") sin identidad institucional. El usuario aportó tres referencias de diseño ya elaboradas (formulario "Registrar Proveedor", listado "Proveedores" con drawer de detalle, y el chrome de un "Dashboard") que definen una identidad visual concreta — marca "CAPJ +", paleta azul/Manrope, navegación tipo riel de íconos con tooltips — y stated explícitamente que la prioridad de navegación es un dashboard interactivo, no listas largas de menú. Antes de construir cualquier página de dominio (pago de proveedores u otro), la base visual y el shell de navegación deben reflejar esa identidad en lugar del starter kit genérico.

## What Changes

- Reemplazar los tokens de tema en `resources/css/app.css` (`@theme`): paleta de colores (primario azul `#2563eb`, fondo `#f4f6fb`, semánticos verde/rojo/ámbar/violeta, variantes dark-mode), tipografía (`Manrope` como sans principal, `JetBrains Mono` para campos numéricos/monoespaciados) y radios de borde más generosos, en vez de la paleta neutra oklch del starter kit.
- Cargar las fuentes `Manrope` y `JetBrains Mono` (vía `@fonts`/Google Fonts o paquete npm, siguiendo el patrón ya usado por `Instrument Sans`) y referenciarlas desde el tema.
- Reemplazar el branding del starter kit: `resources/js/components/app-logo.tsx` y `app-logo-icon.tsx` pasan de "Laravel Starter Kit" + ícono genérico a "CAPJ +" (con subtítulo de módulo, ej. "Finanzas y Ppto", como prop configurable — hoy solo existe el módulo institucional/dashboard, no hay módulos funcionales activados todavía).
- Reestructurar el sidebar (`resources/js/components/app-sidebar.tsx` y los componentes de navegación que dependa) a un layout tipo riel de íconos con tooltip al hover, en vez de íconos + texto inline siempre visible — manteniendo únicamente el único ítem de navegación real que existe hoy (`Dashboard`). No se inventan ítems de navegación para módulos que no existen aún (Proveedores, Facturas, Reportes, etc. del diseño de referencia quedan fuera hasta que esos módulos se construyan).
- Actualizar metadatos de página (`<title>`, favicon si aplica) para reflejar "CAPJ +" en vez de "Laravel".

**Fuera de alcance (decisión explícita):** contenido nuevo del dashboard (KPIs, gráficos, widgets) — los archivos de referencia del Dashboard usan datos de una plantilla genérica de e-commerce/analítica (Vistas de página, Visitantes, Productos más vendidos) que no corresponden a ningún dominio real de este proyecto; definir y construir contenido real del dashboard es una tarea separada y posterior. Tampoco se construyen páginas de dominio (`pago-proveedores/*`, etc.) ni se agregan ítems de sidebar para módulos no implementados.

## Capabilities

### New Capabilities
- `tema-visual-layout`: identidad visual base (tokens de color/tipografía/radio) y shell de navegación (sidebar tipo riel de íconos, branding) de la aplicación, independiente de cualquier dominio funcional.

### Modified Capabilities
(ninguna — no existe spec de UI/layout previa; esta es la primera vez que se especifica la capa visual.)

## Impact

- Archivos modificados: `resources/css/app.css`, `resources/js/components/app-logo.tsx`, `resources/js/components/app-logo-icon.tsx`, `resources/js/components/app-sidebar.tsx`, `resources/js/components/nav-main.tsx` (si aplica), `resources/views/app.blade.php` (carga de fuentes / `<title>`).
- Sin cambios de backend, base de datos ni rutas — es una tarea puramente de frontend/diseño.
- Requiere `npm run build` (o `composer dev` en desarrollo) para ver el resultado; no hay tests Pest aplicables, pero si existen smoke tests de Playwright/Pest Browser para páginas existentes (login, dashboard, settings) deben seguir pasando sin cambios funcionales.

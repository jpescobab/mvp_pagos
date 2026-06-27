## Context

El proyecto parte de `laravel/react-starter-kit` sin modificar su capa visual: `resources/css/app.css` define tokens shadcn/ui neutros (oklch grises), `resources/js/components/app-logo*.tsx` muestra "Laravel Starter Kit" con un ícono genérico, y `resources/js/components/app-sidebar.tsx` tiene un único ítem real (`Dashboard`) más enlaces de footer al repo de `laravel/react-starter-kit`.

El usuario aportó tres referencias de diseño (exports HTML del Design tool de Claude, fuera del repo): un formulario "Registrar Proveedor", un listado "Proveedores" con drawer de detalle, y el chrome de un "Dashboard". De ahí se extrajo: marca "CAPJ +" (subtítulo de módulo, ej. "Finanzas y Ppto"), paleta azul (`#2563eb` primario, fondo `#f4f6fb`, semánticos verde/rojo/ámbar/violeta, variantes dark-mode), tipografía `Manrope` (sans) + `JetBrains Mono` (monoespaciada, campos como RUT), radios de borde generosos (8-14px), y un sidebar tipo riel de íconos con tooltip al hover en vez de texto inline siempre visible.

Importante: el componente `Sidebar` de shadcn/ui ya instalado (`resources/js/components/ui/sidebar.tsx`) soporta nativamente `collapsible="icon"` — y `AppSidebar` ya lo usa (`<Sidebar collapsible="icon" variant="inset">`) — con tooltip incorporado en `SidebarMenuButton` (`NavMain` ya pasa `tooltip={{ children: item.title }}`). El patrón de riel de íconos del diseño de referencia ya está disponible en el primitive; no hay que construirlo desde cero.

## Goals / Non-Goals

**Goals:**
- Reemplazar los tokens de color/tipografía/radio del tema por los extraídos del diseño de referencia, manteniendo los mismos *nombres* de variable CSS (`--primary`, `--sidebar`, etc.) para que todo componente shadcn/ui existente herede la nueva paleta sin tocarlo uno por uno.
- Reemplazar el branding del starter kit (`AppLogo`, `AppLogoIcon`, `<title>`, footer del sidebar) por la identidad "CAPJ +".
- Dejar el sidebar visualmente alineado al diseño (radios, colores, agrupación "General") usando el primitive ya existente, sin inventar ítems de navegación para módulos no construidos.

**Non-Goals:**
- No se definen ni construyen los KPIs/widgets reales del dashboard — el archivo de referencia del Dashboard trae contenido de una plantilla genérica de e-commerce (Vistas de página, Visitantes, Productos más vendidos) que no aplica a este dominio; ese contenido requiere su propia decisión de producto y queda para un change posterior.
- No se agregan ítems de sidebar para módulos no implementados (Proveedores, Facturas, Reportes del diseño de referencia) — el harness prohíbe construir UI que no tenga backend real detrás.
- No se construye un favicon/ícono institucional nuevo (requiere un asset gráfico real); se deja el favicon actual del starter kit.
- No se cambia ningún comportamiento, ruta o dato — es un cambio puramente visual/de branding.

## Decisions

1. **Reusar el primitive `Sidebar` de shadcn/ui en modo `collapsible="icon"` en vez de construir un riel de íconos custom.** Ya soporta tooltips al colapsar y ya está en uso. Construir un componente paralelo duplicaría accesibilidad (focus management, ARIA) ya resuelta. Único cambio: renombrar el label del grupo de navegación ("Platform" → "General", igual que el diseño de referencia) en `nav-main.tsx`.

2. **Los tokens de color se reescriben en `:root`/`.dark` de `resources/css/app.css`, no se agregan tokens nuevos.** Mantener los mismos nombres de variable (`--primary`, `--background`, `--sidebar-*`, etc.) significa que ningún otro componente (`button.tsx`, `card.tsx`, `input.tsx`, etc.) necesita cambios — todos consumen estas variables vía Tailwind (`bg-primary`, `text-muted-foreground`). Se usan valores hex directos (no oklch) donde el diseño de referencia especifica hex exacto (ej. `#2563eb`), igual que cualquier color CSS válido es aceptado dentro de `@theme`/`:root`.

3. **Tipografía: agregar `Manrope` y `JetBrains Mono` al arreglo `fonts` de `laravel()` en `vite.config.ts`, vía el helper `bunny(...)` de `laravel-vite-plugin/fonts`** — el mismo mecanismo que ya autohospeda `Instrument Sans` (Bunny Fonts, sin llamadas a Google Fonts en runtime). `--font-sans` pasa a `'Manrope', ui-sans-serif, ...` (mismo fallback stack que ya existe); se agrega `--font-mono` con `'JetBrains Mono', ui-monospace, ...` (no existe hoy en el tema, es nuevo) para uso futuro en campos tipo RUT/folio.

4. **`AppLogo` recibe un subtítulo opcional, no hardcodeado a "Finanzas y Ppto".** Hoy no hay ningún módulo funcional con páginas reales; mostrar el nombre de un módulo inactivo sería inconsistente con la regla del harness de que los módulos se activan/desactivan explícitamente. El componente queda con la marca "CAPJ +" sola por defecto, listo para aceptar un subtítulo de módulo cuando exista un layout específico de ese módulo.

5. **Se eliminan los enlaces de footer del sidebar hacia el repo de `laravel/react-starter-kit`** (`nav-footer` con "Repository"/"Documentation") — son artefactos del scaffolding sin relación con CAPJ App Pagos; no se reemplazan por otros enlaces (no hay aún destinos reales como changelog o soporte interno).

6. **`APP_NAME` en `.env`/`.env.example` pasa a `"CAPJ +"`** — es lo que alimenta `<title>{{ config('app.name', 'Laravel') }}</title>` en `app.blade.php` y el prop `name` compartido por `HandleInertiaRequests`. No requiere tocar el blade.

## Risks / Trade-offs

- **[Riesgo] Cambiar tokens de color globales puede alterar el contraste/legibilidad de componentes shadcn/ui no revisados individualmente (badges, alerts, etc.)** → **Mitigación**: mantener la misma estructura semántica de tokens (foreground/background pareados) y revisar visualmente las páginas existentes (login, dashboard, settings) tras el cambio antes de cerrar la tarea.
- **[Riesgo] Sin contenido real de dashboard, la pantalla principal queda visualmente "vacía" tras este cambio** (solo branding/colores nuevos, sin KPIs) → **Mitigación**: aceptado explícitamente como alcance acotado; el contenido del dashboard es la siguiente tarea natural y se beneficia de tener ya la base visual correcta.
- **[Riesgo] Fuentes nuevas (Manrope/JetBrains Mono) añaden una dependencia de red (Google Fonts) o un paquete npm nuevo** → **Mitigación**: replicar exactamente el mecanismo ya usado por `Instrument Sans` (mismo proveedor/patrón), sin introducir un mecanismo de carga de fuentes distinto.

## Migration Plan

Sin migraciones de base de datos ni cambios de API. Cambios de archivos estáticos (CSS, componentes React, `.env`). Rollback trivial: revertir el commit. Requiere `npm run build` (o `composer dev`/Vite dev server) para reflejarse en el navegador.

## Open Questions

Ninguna pendiente — alcance, nombre de marca y profundidad ya acordados explícitamente con el usuario antes de este design.

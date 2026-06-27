## 1. Tipografía

- [x] 1.1 En `vite.config.ts`, agregar al arreglo `fonts` de `laravel(...)`: `bunny('Manrope', { weights: [400, 500, 600, 700] })` y `bunny('JetBrains Mono', { weights: [400, 500] })`, junto al `bunny('Instrument Sans', ...)` existente
- [x] 1.2 En `resources/css/app.css`, actualizar `--font-sans` a `'Manrope', ui-sans-serif, system-ui, sans-serif, 'Apple Color Emoji', 'Segoe UI Emoji', 'Segoe UI Symbol', 'Noto Color Emoji'` y agregar `--font-mono: 'JetBrains Mono', ui-monospace, SFMono-Regular, Menlo, monospace;` dentro del bloque `@theme`

## 2. Tema de colores

- [x] 2.1 En `resources/css/app.css` bloque `:root`, reemplazar `--background` por `#f4f6fb`, `--foreground` por `#0b1220`, `--card`/`--popover` por `#ffffff`, `--primary` por `#2563eb` con `--primary-foreground` `#ffffff`, `--destructive` por `#dc2626` con `--destructive-foreground` `#ffffff`, `--secondary`/`--muted`/`--accent` por tonos claros derivados de la paleta (ej. `#f8fafc`) manteniendo sus `-foreground` legibles, `--border`/`--input` por un gris azulado claro (ej. `#e7ecf4`), `--ring` por una variante del primario
- [x] 2.2 En el mismo bloque `:root`, actualizar `--sidebar`, `--sidebar-foreground`, `--sidebar-primary` (`#2563eb`), `--sidebar-primary-foreground` (`#ffffff`), `--sidebar-accent`, `--sidebar-border` siguiendo la misma paleta
- [x] 2.3 En el bloque `.dark`, reemplazar los equivalentes (`--background`/`--foreground`/`--card`/`--primary`/etc.) por las variantes dark-mode de la paleta extraída del diseño (azules claros para `--primary` en dark, fondos cercanos a negro-azulado para `--background`/`--card`), preservando el mismo contraste relativo que el bloque `:root`
- [x] 2.4 Actualizar `--radius` en `:root` a un valor más generoso si es necesario para igualar el aspecto "redondeado" del diseño de referencia (ej. `0.75rem`)
- [x] 2.5 Actualizar `--chart-1` a `--chart-5` usando la paleta semántica extraída (azul, verde, violeta, ámbar, rojo) en vez de los oklch genéricos originales

## 3. Branding

- [x] 3.1 En `resources/js/components/app-logo.tsx`, reemplazar el texto "Laravel Starter Kit" por "CAPJ +"; agregar un prop opcional `subtitle?: string` que, si se provee, se renderiza debajo del nombre (sin valor por defecto, ya que hoy ningún módulo funcional tiene página propia)
- [x] 3.2 Revisar `resources/js/components/app-logo-icon.tsx`: mantener el ícono genérico actual (no se crea un ícono institucional nuevo en este change) pero verificar que sus colores respondan a los tokens del tema ya actualizados
- [x] 3.3 Actualizar `APP_NAME` en `.env` y `.env.example` a `"CAPJ +"`

## 4. Sidebar

- [x] 4.1 En `resources/js/components/nav-main.tsx`, cambiar el texto de `SidebarGroupLabel` de "Platform" a "General"
- [x] 4.2 En `resources/js/components/app-sidebar.tsx`, eliminar `footerNavItems` (enlaces "Repository"/"Documentation" hacia `laravel/react-starter-kit`) y el uso de `NavFooter` que los renderiza; conservar `NavUser` en el footer
- [x] 4.3 Si `resources/js/components/nav-footer.tsx` queda sin otros usos en el proyecto tras 4.2, eliminarlo

## 5. Validación

- [x] 5.1 `npm run build`
- [x] 5.2 `npm run lint:check`
- [x] 5.3 `npm run types:check`
- [x] 5.4 Levantar `composer dev` y verificar visualmente: login, dashboard y una página de settings reflejan la nueva paleta/tipografía/marca sin romper legibilidad ni el modo oscuro — verificado vía Claude Preview (login en claro y oscuro: fondo/texto/botón primario con los hex correctos y `font-family: Manrope`; tras iniciar sesión, el sidebar a 1280px muestra "CAPJ +" → "General" → "Dashboard", sin "Platform" ni "Repository"/"Documentation")
- [x] 5.5 `composer test` (confirmar que ningún test Pest/Feature existente se rompe por los cambios de branding, ej. tests que aserten texto "Laravel" si los hubiera)

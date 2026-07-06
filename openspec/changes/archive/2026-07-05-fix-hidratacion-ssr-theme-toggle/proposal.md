## Why

El `ThemeToggle` (`resources/js/components/theme-toggle.tsx`) produce un error de hidratación de React en cada carga de página cuando el usuario tiene el tema oscuro activo: el servidor (SSR) siempre renderiza asumiendo apariencia `'light'` (ícono `Sun`, aria-label "Cambiar a tema oscuro"), mientras el cliente hidrata inmediatamente con el valor real guardado en `localStorage` (ícono `Moon`, aria-label "Cambiar a tema claro"). React descarta el árbol completo y lo regenera, y además dispara un segundo error (`Failed to execute 'removeChild' on 'Node'`) — visible en la consola del navegador en cada carga con tema oscuro.

La causa: `useAppearance()` (`resources/js/hooks/use-appearance.tsx`) usa `useSyncExternalStore` con un `getServerSnapshot` fijo (`() => 'system'`, que además resuelve a `'light'` en SSR porque `prefersDark()` no tiene `window`). El backend ya conoce la apariencia real vía la cookie `appearance` (`app/Http/Middleware/HandleAppearance.php` la comparte a Blade como `$appearance`, usada para la clase `dark` del `<html>`), pero esa información nunca llega al árbol de React que se renderiza en el proceso SSR de Inertia, así que SSR y la primera pintura del cliente parten de fuentes distintas.

## What Changes

- `HandleInertiaRequests::share()` agrega `appearance` a los props compartidos de Inertia, leyendo la misma cookie `appearance` que ya lee `HandleAppearance` (mismo patrón que el `sidebarOpen` existente, que ya lee `sidebar_state` de la cookie).
- `useAppearance()` deja de asumir `'system'` fijo como snapshot de servidor: usa el prop compartido de Inertia (disponible tanto en el render SSR real como en la primera pintura del cliente antes de hidratar, porque ambos parten del mismo payload de página) como valor inicial determinista para `getServerSnapshot`.
- La reconciliación con `localStorage` (que puede diferir de la cookie en casos raros) pasa a ocurrir en un efecto posterior al montaje, no de forma síncrona antes de que React hidrate — así la hidratación inicial siempre coincide byte a byte entre servidor y cliente, y cualquier corrección se aplica como una actualización de estado normal post-hidratación (sin error).

## Capabilities

### New Capabilities
(ninguna)

### Modified Capabilities
(ninguna — es un bug fix de una implementación existente, no cambia comportamiento observable de ningún requirement documentado en `openspec/specs/`; el spec `tema-visual-layout` ya asume que el toggle de tema funciona correctamente, este change corrige una regresión de esa expectativa implícita sin alterar su texto)

## Impact

- Código afectado: `app/Http/Middleware/HandleInertiaRequests.php` (nuevo prop compartido), `resources/js/hooks/use-appearance.tsx` (fuente del snapshot SSR), `resources/js/components/theme-toggle.tsx` si necesita leer el prop compartido.
- Sin cambios de esquema de base de datos, permisos ni workflow.
- Elimina un error de consola en producción/desarrollo para todo usuario con tema oscuro activo — relevante para una app financiera donde la consola limpia importa para diagnósticos reales.

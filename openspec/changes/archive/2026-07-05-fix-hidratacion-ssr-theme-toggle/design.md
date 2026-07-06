## Context

`useAppearance()` implementa un store externo a React (variable de módulo `currentAppearance` + `Set` de listeners) sincronizado con `useSyncExternalStore`. Esto permite que múltiples componentes (ej. `ThemeToggle`, `AppearanceTabs`) compartan el mismo estado sin Context. El patrón es correcto para el cliente, pero su tercer argumento (`getServerSnapshot`) está hardcodeado a `'system'`, sin relación con la cookie `appearance` que el backend ya resuelve por request en `HandleAppearance` (middleware que hace `View::share('appearance', $request->cookie('appearance') ?? 'system')`, solo visible para Blade, no para el árbol de Inertia/React).

Inertia SSR (modo automático de `@inertiajs/vite`, sin `resources/js/ssr.tsx` propio en este proyecto) renderiza el mismo `resources/js/app.tsx` tanto en el proceso Node de SSR como en el navegador, usando el mismo payload de props de la página para ambos. Esto significa que si el valor de apariencia viaja como prop compartido de Inertia (en vez de solo como variable Blade), tanto el render SSR real como la primera pintura del cliente (antes de que se ejecute cualquier efecto) pueden leer exactamente el mismo valor — eliminando la fuente de la discrepancia.

`initializeTheme()` (llamada en `app.tsx` justo después de `createInertiaApp()`) aplica la clase `dark` al `<html>` según `localStorage`, mutando `currentAppearance` antes de que React hidrate. Esto en sí no es un problema: `useSyncExternalStore` solo usa `getServerSnapshot` durante el propio render de hidratación (nunca `getSnapshot`), así que lo único que decide si la hidratación coincide es que `getServerSnapshot` devuelva el mismo valor que usó el servidor — no importa que `currentAppearance` ya haya sido mutado para ese momento. El bug real es que `getServerSnapshot` nunca reflejó ese valor: estaba fijo en `'system'`.

## Goals / Non-Goals

**Goals:**
- Eliminar el error de hidratación y el error secundario de `removeChild` en el `ThemeToggle` para cualquier usuario con tema oscuro (guardado en cookie/localStorage) activo.
- Mantener el comportamiento visual actual: el `<html>` recibe la clase `dark` antes del primer paint (vía el script inline de `resources/views/app.blade.php`, que no cambia) y el ícono/aria-label del toggle reflejan el tema real.
- Mantener `localStorage` como mecanismo de persistencia del lado cliente (no se retira).

**Non-Goals:**
- No se cambia el mecanismo de cookies/localStorage en sí (`setCookie`, nombres, TTL).
- No se toca `resources/views/app.blade.php` ni el script inline que fija la clase `dark` antes del primer paint (ya es correcto y no depende de React).
- No se introduce Context de React ni se reemplaza el store singleton existente en `use-appearance.tsx`.

## Decisions

- **Compartir `appearance` como prop de Inertia en `HandleInertiaRequests::share()`, leyendo la cookie directamente (mismo patrón que `sidebarOpen`/`sidebar_state`).** Alternativa descartada: duplicar la cookie a través de `View::share` de `HandleAppearance` hacia Inertia — no existe un puente directo entre `View::share` y los props de Inertia, así que hay que leer la cookie de nuevo en `HandleInertiaRequests`; es la misma cantidad de código que ya existe para `sidebarOpen`, no una duplicación nueva de lógica compleja.
- **`useAppearance(appearanceCompartida?: Appearance)` recibe el valor real como parámetro, no lo lee llamando `usePage()` internamente.** Descubrimiento durante la implementación (no anticipado en el diseño inicial): `<Toaster>` (`resources/js/components/ui/sonner.tsx`) también usa `useAppearance()`, pero se monta en `app.tsx` como **hermano** de `<App>` dentro de `withApp()` (`<TooltipProvider>{app}<Toaster /></TooltipProvider>`), es decir, **fuera** del árbol que provee el contexto de Inertia. Si `useAppearance()` llama `usePage()` internamente, `<Toaster>` explota con `"usePage must be used within the Inertia component"` — confirmado con el log real del servidor SSR de Vite en dev. La solución: `useAppearance()` recibe el valor ya resuelto como argumento (default `'system'`); `theme-toggle.tsx` y `appearance-tabs.tsx` (ambos renderizados dentro de páginas, o sea dentro del árbol de Inertia) llaman ellos mismos `usePage().props.appearance` y se lo pasan; `<Toaster>` sigue llamando `useAppearance()` sin argumentos, igual que antes del fix (su tema de fondo no es lo que reportó el bug y no tiene forma de acceder a props de Inertia por su posición en el árbol).
- **Con ese parámetro ya resuelto, `getServerSnapshot` lo usa directamente — no hizo falta tocar `initializeTheme()` ni diferir nada a un efecto.** `useSyncExternalStore` solo invoca `getServerSnapshot` durante el propio render de hidratación, y compara con `getSnapshot()` después de montar — si difieren (ej. cookie y `localStorage` divergieron), React programa un re-render normal, no un error.
- **Se resuelve también el caso `appearance === 'system'` con preferencia real de sistema oscuro (originalmente iba a quedar fuera de alcance), con una bandera `hidratado`.** Implementada como un segundo `useSyncExternalStore` sin suscripción real (`getServerSnapshot` devuelve `false`, `getSnapshot` devuelve `true`) en vez de `useState` + `useEffect`: ese patrón dispara la regla de lint `react-hooks/set-state-in-effect` ("Calling setState synchronously within an effect can trigger cascading renders"), y el truco de `useSyncExternalStore` logra el mismo resultado (mismatch detectado automáticamente tras la hidratación → un solo re-render) sin necesitar un efecto. Sin esta bandera, `prefersDark()` se evaluaría de inmediato durante el propio render de hidratación en el navegador (que sí tiene `window.matchMedia`), mientras el servidor siempre asume `false` — el mismo tipo de mismatch que el bug original, pero para `'system'`.
- **No se introduce Context de React ni se reemplaza el store singleton existente.** Sería un cambio de mayor superficie para un bug puntual de timing; el store externo actual es válido, solo le faltaba una fuente de verdad consistente para SSR.

## Risks / Trade-offs

- [Riesgo: si la cookie `appearance` y `localStorage` llegaran a divergir (ej. usuario borra uno pero no el otro, o cambia de dispositivo)] → Mitigación: `useSyncExternalStore` resuelve esto con un re-render normal post-hidratación (no un error), igual que maneja cualquier cambio del store externo.
- [Riesgo: agregar un prop compartido más a Inertia aumenta ligeramente el payload de cada respuesta] → Mitigación: es un string corto (`'light' | 'dark' | 'system'`), despreciable comparado con `indicadoresTopbar` u otros props ya compartidos.
- [Riesgo: con la bandera `hidratado`, un usuario con `appearance: 'system'` y SO en modo oscuro ve un frame en claro antes de que el efecto corrija a oscuro] → Mitigación: es el mismo frame de transición que ya ocurre hoy en cualquier app SSR con detección de preferencia de sistema; no genera error de hidratación, que era el problema a resolver.

## Migration Plan

1. Agregar `appearance` a `HandleInertiaRequests::share()` y su tipo en `resources/js/types/global.d.ts`.
2. Ajustar `useAppearance()` para recibir el valor como parámetro (no llamar `usePage()` internamente) y agregar la bandera `hidratado` para el caso `system`.
3. Actualizar `theme-toggle.tsx` y `appearance-tabs.tsx` para leer `usePage().props.appearance` y pasarlo a `useAppearance()`; dejar `sonner.tsx` sin cambios (sigue llamando `useAppearance()` sin argumentos).
4. Probar en el preview/con `curl`: cargar con `appearance=dark|light|system` y confirmar que el `aria-label` del toggle en el HTML servido coincide con el tema, y que no aparece el error de hidratación ni el de `<Toaster>` en consola.
5. `npm run lint:check` y `npm run types:check`.

Sin rollback especial: cambios acotados a un middleware y un hook, revertibles con `git revert`.

## Open Questions

Ninguna.

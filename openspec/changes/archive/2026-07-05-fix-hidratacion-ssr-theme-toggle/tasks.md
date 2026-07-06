## 1. Backend: compartir apariencia real a Inertia

- [x] 1.1 Agregar `appearance` a `HandleInertiaRequests::share()` en `app/Http/Middleware/HandleInertiaRequests.php`, leyendo `$request->cookie('appearance') ?? 'system'` (mismo patrón que `sidebarOpen`/`sidebar_state`). También se agregó el tipo `appearance: Appearance` a `resources/js/types/global.d.ts`.

## 2. Frontend: snapshot SSR-seguro en useAppearance

- [x] 2.1 Ajustar `resources/js/hooks/use-appearance.tsx` para que `useAppearance(appearanceCompartida?)` reciba el valor real como parámetro y lo use como fuente de `getServerSnapshot` en `useSyncExternalStore`, en vez del string fijo `'system'`.
- [x] 2.2 Revisado: no hizo falta mover nada a un efecto post-montaje — `useSyncExternalStore` solo usa `getServerSnapshot` durante el propio render de hidratación, así que corregir esa función basta. En su lugar se agregó una bandera `hidratado` (vía un segundo `useSyncExternalStore` sin efectos) para resolver también el caso `appearance === 'system'` con preferencia real de sistema oscuro, que originalmente iba a quedar fuera de alcance.
- [x] 2.3 Confirmado: `initializeTheme()` sigue aplicando la clase `dark` al `<html>` inmediatamente, sin cambios (efecto visual fuera del árbol de React, no afecta hidratación).
- [x] 2.4 **Hallazgo durante la implementación, no contemplado en el diseño original**: `useAppearance()` no puede llamar `usePage()` internamente, porque `<Toaster>` (`resources/js/components/ui/sonner.tsx`) también la usa y se renderiza en `app.tsx` como hermano de `<App>` (fuera del árbol de Inertia) — llamar `usePage()` ahí rompe con "usePage must be used within the Inertia component" (confirmado con el log real del SSR dev de Vite). Se resolvió cambiando `useAppearance()` para que reciba el valor como parámetro opcional (default `'system'`): `theme-toggle.tsx` y `appearance-tabs.tsx` (ambos dentro del árbol de Inertia) llaman `usePage().props.appearance` y se lo pasan; `sonner.tsx` sigue llamando `useAppearance()` sin argumentos, igual que antes del fix.

## 3. Validación

- [x] 3.1 Verificado en el preview: con cookie `appearance=dark`, la respuesta HTTP real (`curl`) ya trae el SSR renderizado (antes el `<div id="app">` llegaba vacío por el error de `<Toaster>`) con `aria-label="Cambiar a tema claro"` (coincide con tema oscuro activo); en el navegador, tras recargar, `preview_console_logs` en nivel `error` no muestra nada, y el conteo de advertencias no crece entre recargas (quedan solo las acumuladas de intentos previos durante la depuración).
- [x] 3.2 Verificado con `curl` para cookie `appearance=light` y sin cookie (`system`): ambos devuelven `aria-label="Cambiar a tema oscuro"` (tema claro), consistente.
- [x] 3.3 `npm run lint:check` y `npm run types:check` pasan sin errores.

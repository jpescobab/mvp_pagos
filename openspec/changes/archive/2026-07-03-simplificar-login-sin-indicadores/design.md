## Context

`auth-simple-layout.tsx` hoy muestra: (1) el logo del Poder Judicial en una caja en la barra superior, (2) chips de indicadores económicos flotantes bajo la barra superior (solo si hay datos), (3) la tarjeta central con el formulario. `login.tsx` define el botón de submit con clases propias que reintroducen un degradado sólido, pasando por encima de la convención de "botones sin relleno" ya vigente para toda la app.

## Goals / Non-Goals

**Goals:**
- Quitar los chips de indicadores del login (capacidad completa, no solo ocultarlos).
- Mover el logo a ser un elemento de fondo dentro de la tarjeta de login.
- Que el botón "Iniciar sesión" cumpla la misma convención de botones sin relleno que ya rige en el resto de la app.

**Non-Goals:**
- No se tocan `reset-password.tsx`, `forgot-password.tsx`, `confirm-password.tsx` ni su layout compartido más allá de lo que cambia en `AuthSimpleLayout` (que es compartido) — se revisa que no dependan de los chips/logo eliminados.
- No se cambia `IndicadorEconomicoSelector` ni el panel general (`dashboard.tsx`), que consume indicadores para un propósito distinto.

## Decisions

1. **Eliminar la capacidad de chips de indicadores en el login, no solo ocultarla condicionalmente.**
   Se quita `Fortify::loginView()` → `'indicadores' => ...`, el tipo `IndicadorLogin`, `ETIQUETAS`, `formatearIndicador` y el bloque JSX de chips en `AuthSimpleLayout`. Dejar el código "por si se reactiva" sería código muerto sin un pedido concreto que lo justifique.

2. **El logo se posiciona como fondo con `absolute` + opacidad baja dentro de la tarjeta, no como imagen decorativa fuera de flujo en toda la página.**
   La tarjeta (`div.rounded-[22px]`) pasa a `relative overflow-hidden`; el logo (ambas variantes light/dark, mismo criterio `dark:hidden`/`dark:block` que ya usa el código) se agrega como primer hijo `absolute inset-0 m-auto ... opacity-[0.06] pointer-events-none`, y el contenido existente (título, formulario, footer de la tarjeta) se envuelve en un contenedor `relative z-10` para quedar por encima. Se quita la caja de logo de la barra superior (`<header>`), que queda solo con el `ThemeToggle`.

3. **El botón de login deja de tener className propio de color/relleno; usa la variante `default` de `Button` sin overrides.**
   Se quitan `bg-gradient-to-b from-primary to-[#1e40af] shadow-lg shadow-primary/30 dark:to-[#60a5fa]`; se conservan las clases de tamaño/forma (`mt-2 h-12 w-full rounded-xl text-sm font-semibold`). La variante `default` ya está definida en `resources/js/components/ui/button.tsx` como borde 1px + texto en `primary` + fondo suave solo en `hover`, aplicable en claro/oscuro sin cambios adicionales (ya verificado en un change anterior de esta misma sesión).

## Risks / Trade-offs

- [Riesgo] Quitar el logo de la barra superior podría dejarla visualmente vacía → Mitigación: la barra superior conserva el `ThemeToggle`; el logo sigue presente (como fondo) y la identidad de marca "CAPJ +" ya está en el badge dentro de la tarjeta.
- [Riesgo] El logo de fondo con opacidad muy baja podría no leerse en ciertos fondos → Mitigación: se ajusta la opacidad visualmente en el preview antes de cerrar el cambio (claro y oscuro).

## Migration Plan

Sin migraciones de base de datos. Cambio de frontend + un provider; rollback trivial revirtiendo el commit.

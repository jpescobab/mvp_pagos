## Why

El usuario pidió simplificar visualmente la vista de login: quitar las tarjetas de indicadores económicos (UF/UTM/UTA/IPC), trasladar el logo del Poder Judicial desde la barra superior a un fondo dentro de la tarjeta de login, y corregir el botón "Iniciar sesión" — hoy tiene un degradado de color sólido con poco contraste entre el texto y el relleno, en vez de seguir la convención institucional ya vigente de botones sin relleno sólido (`tema-visual-layout`, requirement "Botones institucionales sin relleno de color sólido").

## What Changes

- `resources/js/layouts/auth/auth-simple-layout.tsx`: se elimina la sección de chips de indicadores económicos y la caja de logo en la barra superior; el logo pasa a renderizarse como fondo (baja opacidad, `absolute`, detrás del contenido) dentro de la tarjeta central de login.
- `app/Providers/FortifyServiceProvider.php`: `Fortify::loginView()` deja de compartir la prop `indicadores` (sin consumidores tras el cambio anterior).
- `resources/js/pages/auth/login.tsx`: el botón "Iniciar sesión" deja de usar clases que reintroducían un relleno de degradado (`bg-gradient-to-b ...`, `shadow-lg shadow-primary/30`) y pasa a usar la variante `default` del componente `Button` sin overrides, ya sin relleno sólido (borde 1px + texto en el color primario, con fondo suave solo en `hover`), consistente en claro/oscuro.
- Sin cambios en la lógica de autenticación (Fortify) ni en las demás páginas de auth.

## Capabilities

### Modified Capabilities
- `tema-visual-layout`: el requirement "Login institucional con indicadores económicos" se retira y se reemplaza por "Login institucional" — ya no incluye chips de indicadores económicos; el logo del Poder Judicial se presenta como fondo dentro de la tarjeta central en vez de en la barra superior.

## Impact

- Código: `resources/js/layouts/auth/auth-simple-layout.tsx`, `resources/js/pages/auth/login.tsx`, `app/Providers/FortifyServiceProvider.php`.
- Tests: `tests/Feature/Auth/AuthenticationTest.php` — se eliminan los dos tests que verifican la prop `indicadores` del login.
- Sin impacto en el resto de módulos ni en el uso de `IndicadorEconomicoSelector` fuera del login (el panel general sigue mostrando sus propios indicadores, sin relación con este cambio).

## Why

Al revisar el centrado de la tarjeta de login se confirmó que centra perfectamente en desktop (1440×900), tablet (768×1024) y mobile (375×812), pero falla en viewports de poca altura (ej. 812×375): la tarjeta queda pegada arriba en vez de centrada verticalmente. El usuario confirmó que la app se usa principalmente en dispositivos grandes, por lo que se prioriza no regresionar ese caso: se probó `scrollbar-gutter: stable` como fix universal, pero se midió que desplaza el centrado horizontal ~15px en desktop **incluso sin scroll activo** (reserva el espacio de la barra siempre), empeorando el caso que más importa — se descartó. Se aplica solo el ajuste de padding responsivo, que no tiene esa contrapartida.

## What Changes

- `resources/js/layouts/auth/auth-simple-layout.tsx`: el padding vertical fijo del `<main>` (`pt-24 pb-24`) pasa a ser menor en viewports angostos y se mantiene igual en `md:` y superiores (`pt-16 pb-16 md:pt-24 md:pb-24`), reduciendo el desborde vertical en pantallas angostas y bajas sin cambiar nada en desktop/tablet (medido: sigue centrado exacto en 1440×900, 768×1024 y 375×812).
- Sin cambios funcionales ni de contenido — es un ajuste de espaciado puro, acotado al login.

## Capabilities

### Modified Capabilities
- `tema-visual-layout`: el requirement "Login institucional" incorpora el comportamiento esperado de centrado responsivo de la tarjeta central.

## Impact

- Código: `resources/js/layouts/auth/auth-simple-layout.tsx`.
- Sin impacto en backend, tests de autenticación ni en el resto de páginas de auth que comparten el layout.

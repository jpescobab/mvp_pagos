## 1. CSS global (descartado)

- [x] 1.1 Probar `scrollbar-gutter: stable;` en `html` (`resources/css/app.css`) — se midió que desplaza el centrado ~15px en desktop sin scroll activo (regresión en el caso principal de uso); se revirtió, sin dejar el cambio en el código final.

## 2. Layout de login

- [x] 2.1 En `resources/js/layouts/auth/auth-simple-layout.tsx`, cambiar el padding vertical del `<main>` de `pt-24 pb-24` a `pt-16 pb-16 md:pt-24 md:pb-24`.

## 3. Verificación

- [x] 3.1 Ejecutar `npm run lint:check`, `npm run format:check` y `npm run types:check`.
- [x] 3.2 Remedir el centrado de la tarjeta en desktop (1440×900), tablet (768×1024) y mobile (375×812): centrado exacto en los tres (diferencias de gap sub-píxel o nulas). Confirmado que `scrollbar-gutter` habría regresionado desktop y por eso se descartó.

## 4. Documentación y cierre

- [x] 4.1 Ejecutar `/opsx:archive` para fusionar la spec delta en `openspec/specs/tema-visual-layout/spec.md` y archivar el change.

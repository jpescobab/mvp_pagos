## 1. Tema tipográfico

- [x] 1.1 Redefinir en `resources/css/app.css` (`@theme`) las variables `--text-xs`, `--text-sm`, `--text-base`, `--text-lg`, `--text-xl`, `--text-2xl` con los valores reducidos definidos en `design.md`, cada una con su `--text-{size}--line-height` proporcional.
- [x] 1.2 Reducir los títulos con tamaño arbitrario en píxeles que no heredan la escala de tema: `h1` de `dashboard.tsx` (22px → 17px) y `h1` de `auth-simple-layout.tsx` (28px → 20px), detectados en la verificación visual.

## 2. Botones sin relleno sólido

- [x] 2.1 En `resources/js/components/ui/button.tsx`, cambiar la variante `default` de `bg-primary text-primary-foreground` a borde + texto `text-primary` sin fondo sólido, con fondo suave solo en `hover`.
- [x] 2.2 Cambiar la variante `secondary` de `bg-secondary text-secondary-foreground` al mismo patrón (borde + texto, sin relleno sólido, fondo suave en `hover`).
- [x] 2.3 Cambiar la variante `destructive` de `bg-destructive text-white` al mismo patrón con el color destructivo (borde + texto, sin relleno sólido, fondo suave en `hover`).
- [x] 2.4 Confirmar que `outline`, `ghost` y `link` no requieren cambios (ya cumplen la convención de sin relleno).

## 3. Verificación visual

- [x] 3.1 Levantar el servidor de desarrollo y revisar en el preview: sidebar de navegación, el listado denso de Proveedores, un formulario con botón primario (ej. crear usuario) y la página de login, en modo claro y oscuro.
- [x] 3.2 Confirmar que ningún layout se rompe por el cambio de tamaño de texto (columnas de listados densos, tarjetas del dashboard, badges).
- [x] 3.3 Ejecutar `npm run lint:check`, `npm run format:check` y `npm run types:check` para confirmar que no hay regresiones de tipado/formato (los warnings de `format:check` son preexistentes en archivos no tocados por este change).

## 4. Documentación y cierre

- [ ] 4.1 Ejecutar `/opsx:sync` o `/opsx:archive` para fusionar la spec delta de `tema-visual-layout` en `openspec/specs/tema-visual-layout/spec.md` y archivar el change.

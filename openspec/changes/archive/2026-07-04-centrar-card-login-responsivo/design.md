## Context

Medición empírica (viewport vs. bounding box de la tarjeta) confirmó centrado correcto en 1440×900, 768×1024 y 375×812 (diferencia sub-píxel). En 812×375 (altura menor que tarjeta + padding) el contenedor `min-h-svh` crece para acomodar el contenido, la tarjeta queda con `topGap` fijo (= `pt-24`) en vez de centrada, y una scrollbar vertical activa reduce el ancho disponible a la derecha, desplazando el centrado horizontal ~15px.

## Goals / Non-Goals

**Goals:**
- Que ninguna página con scroll vertical intermitente sufra el desplazamiento horizontal causado por la aparición/desaparición de la scrollbar.
- Reducir la probabilidad de desborde vertical del login en pantallas bajas, sin modificar su aspecto en viewports normales (desktop/tablet/mobile en orientación portrait, que ya centran perfecto).

**Non-Goals:**
- No se garantiza centrado vertical exacto cuando la tarjeta es literalmente más alta que el viewport (ej. 375px de alto) — en ese caso extremo, scroll es inevitable y aceptable; el usuario confirmó que el uso principal es en dispositivos grandes.
- No se cambia el contenido, tamaño ni estructura de la tarjeta de login.

## Decisions

1. **Descartado: `scrollbar-gutter: stable` a nivel global.**
   Se probó como fix universal para el desplazamiento horizontal por aparición de scrollbar. Al medirlo en el preview, en 1440×900 (sin overflow, sin scrollbar activa) el `leftGap`/`rightGap` de la tarjeta pasó de 496/496 (centrado exacto) a 488.5/503.5 — un desplazamiento de ~15px porque la propiedad reserva el espacio de la scrollbar de forma permanente, no solo cuando hay scroll real. Como el uso principal es en dispositivos grandes donde hoy no hay overflow, esto empeora el caso más importante para arreglar un caso ya deprioritizado. Se revierte.

2. **Padding responsivo (`pt-16 pb-16 md:pt-24 md:pb-24`) en vez de recalcular con JS o quitar el padding fijo por completo.**
   Es el cambio de menor riesgo: no altera el resultado ya verificado en `md:` (tablet) y superiores (breakpoint 768px, confirmado sin cambios en 768×1024), y en viewports angostos (< 768px de ancho) reduce en 64px el espacio reservado para header/footer (hoy livianos: solo `ThemeToggle` arriba y una línea de texto abajo), dando más margen antes de que la tarjeta desborde. Verificado: en 667×375 (celular apaisado angosto) el `scrollHeight` baja de ~676px a 613px con el padding reducido.

## Risks / Trade-offs

- [Riesgo] En una pantalla realmente muy baja (< ~450px de alto) la tarjeta seguirá desbordando incluso con el padding reducido, y viewports angostos-pero-anchos (≥768px de ancho, ej. tablet apaisada baja) no se benefician del padding reducido porque el breakpoint de Tailwind es por ancho, no por alto → Mitigación: aceptado explícitamente; el uso principal es en dispositivos grandes y el fallback (scroll) no rompe la funcionalidad, solo el centrado estético.

## Migration Plan

Sin migraciones. Cambio de CSS/frontend puro; rollback trivial revirtiendo el commit.

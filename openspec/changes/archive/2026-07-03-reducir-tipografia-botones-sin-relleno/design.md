## Context

El tema visual institucional (`resources/css/app.css`) define paleta de colores, radios y tipografía (`Manrope`) vía `@theme`, pero usa la escala tipográfica por defecto de Tailwind v4 (`text-xs` = 12px … `text-xl` = 20px). Todas las páginas — sidebar, listados densos, formularios, páginas de detalle, dashboard — heredan esa escala a través de las utilidades `text-*`. El botón compartido (`resources/js/components/ui/button.tsx`, shadcn/ui) define sus variantes con `cva`; `default`, `secondary` y `destructive` usan `bg-*` sólido.

El usuario pidió reducir la tipografía "en todas las páginas y en el sidenavbar, en especial en la página con contenido" (prioriza densidad de información sobre tamaño de letra) y que los botones "tengan sólo una línea de contorno, sin color de relleno".

## Goals / Non-Goals

**Goals:**
- Reducir el tamaño de texto de forma uniforme en toda la aplicación (contenido, sidebar, listados, formularios) con un cambio centralizado en el tema, sin editar cada página.
- Eliminar el relleno de color sólido de los botones con variante semántica (`default`, `secondary`, `destructive`), conservando su identidad de color mediante borde + texto.
- Mantener la convención de "listados tabulares densos" ya formalizada; esta convención se ajusta pero no se contradice.

**Non-Goals:**
- No se rediseña la paleta de colores, el radio de bordes ni la tipografía (`Manrope` se mantiene).
- No se cambia el layout, la información mostrada ni el comportamiento de ninguna página.
- No se toca la variante `outline` (ya cumple "solo borde"), ni `ghost`/`link` (no tienen relleno).

## Decisions

1. **Redefinir la escala `--text-*` en `@theme` en vez de escalar `font-size` en `html`/`:root`.**
   Tailwind v4 expone los tamaños de texto (`text-xs` … `text-2xl`) como variables de tema en el namespace `--text-*`, cada una con su variable de line-height acompañante `--text-{size}--line-height`. Sobrescribir estas variables en `resources/css/app.css` reduce el tamaño de cada utilidad `text-*` en toda la app (sidebar, tablas, formularios, botones) sin afectar la escala de espaciado (`--spacing-*`, usada por `px-*`/`py-*`/`gap-*`), que se mantiene intacta — así se reduce el texto sin comprimir excesivamente el padding de botones y celdas.
   Alternativa descartada: escalar `html { font-size: 87.5% }`. Funciona porque todas las utilidades de Tailwind son `rem`, pero también reduciría proporcionalmente el spacing (`--spacing` está en `rem`), lo que encogería paddings/gaps de forma no solicitada y es más difícil de ajustar de forma selectiva por tamaño.

   Valores nuevos (rem = valor actual, con line-height proporcional vía `calc`):
   ```
   --text-xs:  0.6875rem;  --text-xs--line-height: calc(1 / 0.6875);
   --text-sm:  0.75rem;    --text-sm--line-height: calc(1.25 / 0.75);
   --text-base: 0.8125rem; --text-base--line-height: calc(1.5 / 0.8125);
   --text-lg:  0.9375rem;  --text-lg--line-height: calc(1.75 / 0.9375);
   --text-xl:  1.0625rem;  --text-xl--line-height: calc(1.75 / 1.0625);
   --text-2xl: 1.25rem;    --text-2xl--line-height: calc(2 / 1.25);
   ```
   (equivalen aproximadamente a 11/12/13/15/17/20px con fuente raíz de 16px, frente a 12/14/16/18/20/24px por defecto).

2. **Botones sin relleno sólido: ajustar `buttonVariants` en vez de crear variantes nuevas.**
   Se modifican los estilos de `default`, `secondary` y `destructive` para usar `border` + `bg-transparent`/`bg-background` + texto del color semántico + `hover:bg-{color}/10` (fondo suave solo en hover), en vez de `bg-{color}` sólido. Se mantiene el nombre de las variantes (`default`, `secondary`, `destructive`) para no romper ningún uso existente en el código — solo cambia su apariencia visual.
   Alternativa descartada: eliminar las variantes `default`/`secondary`/`destructive` y forzar el uso de `outline` en todos los llamados. Requeriría editar cada uso de `<Button>` en la app (decenas de archivos) para pasar `variant="outline"` explícitamente, además de perder la distinción semántica de color; cambiar la definición central es más simple y de menor riesgo.

3. **No tocar tamaños de texto arbitrarios (`text-[10px]`, `text-[11px]`) ya usados en los listados densos.**
   Esos valores ya están fuera de la escala de tema y ya son pequeños; quedan como están. La reducción de `--text-xs`/`--text-sm` los acerca aún más en proporción visual sin necesidad de tocarlos.

## Risks / Trade-offs

- [Riesgo] Textos muy pequeños (`--text-xs` ≈ 11px) pueden afectar legibilidad/accesibilidad en pantallas de baja densidad → Mitigación: se mantiene el line-height proporcional (no se aprieta el interlineado) y no se reduce por debajo de ~11px; se verifica visualmente en el preview antes de cerrar el cambio.
- [Riesgo] Botones sin relleno sólido pueden reducir el contraste/la jerarquía visual de la acción primaria en una pantalla (ej. "Guardar") → Mitigación: se conserva el color semántico en borde y texto (`text-primary`, `border-primary`), y se agrega fondo suave en `hover` para mantener feedback interactivo.
- [Riesgo] Al ser un cambio de tema global, cualquier página no revisada visualmente podría verse afectada de forma inesperada (ej. componentes con `text-*` fijo que dependían del tamaño anterior para alinearse con iconos) → Mitigación: verificación manual en el preview de al menos sidebar, un listado denso, un formulario y el login antes de considerar terminado el cambio.

## Migration Plan

Cambio puramente de CSS/componente compartido, sin datos ni migraciones de base de datos. Se aplica editando `resources/css/app.css` y `resources/js/components/ui/button.tsx`, se reconstruye el frontend (`npm run build` o HMR de `npm run dev`), y se verifica visualmente. Rollback trivial: revertir el commit (no hay estado persistente involucrado).

## Why

Hoy el formato de números visibles al usuario (montos, indicadores económicos, KPIs, porcentajes) se resuelve de forma ad-hoc en cada página: algunas usan `Intl.NumberFormat('es-CL', ...)` in-line, otras `toLocaleString()` sin locale, y varias no formatean en absoluto — renderizan el valor crudo tal como llega del backend. Ejemplo concreto (imagen de referencia del usuario): en `resources/js/pages/indicadores-economicos/index.tsx:132`, `{indicador.valor}` se imprime directo, mostrando algo como `69542.0000` (el cast `decimal` de Laravel serializa como string con ceros de relleno) en vez de un valor legible. Lo mismo ocurre con distintos `monto`/`monto_total` en pago-proveedores (casos, egresos CGU) e informes razonados. Ninguna vista distingue visualmente los valores negativos.

Al ser una aplicación financiera institucional, esto no es solo estético: una cifra ambigua (dígitos que se confunden entre sí, ej. 0 con 8, o números que no se agrupan visualmente en miles) es un riesgo de error de lectura en montos de pago. Se necesita una convención única, legible (miles con punto, decimales con coma), tipográficamente nítida y sin ambigüedad entre dígitos, y con los negativos resaltados en rojo, aplicada mediante un helper/componente central en vez de lógica repetida o ausente.

## What Changes

- Se agrega un módulo central de formato numérico en `resources/js/lib/format.ts` con funciones puras: `formatNumero`, `formatMonto` (con símbolo `$`), `formatPorcentaje` — todas basadas en `Intl.NumberFormat('es-CL', ...)` (miles con punto, decimales con coma es nativo del locale `es-CL`).
- Se agrega un componente `<Monto>` (o `<NumeroFormateado>`) en `resources/js/components/ui/` que envuelve esas funciones, renderiza el número en la tipografía monoespaciada del tema (`font-mono` / `JetBrains Mono`, ya definida en `resources/css/app.css` y usada hoy para códigos/identificadores) para evitar ambigüedad entre dígitos (0/8/1/l) y alinear cifras en columnas, y pinta el valor en rojo (token semántico de color existente, ej. `text-destructive`) cuando el valor es negativo, dejando el color por defecto (heredado) cuando es cero o positivo.
- Se reemplazan los usos ad-hoc (o la ausencia de formateo) detectados en: `dashboard.tsx`, `lib/indicadores.ts`, `indicadores-economicos/index.tsx`, `pago-proveedores/casos/{index,show}.tsx`, `pago-proveedores/egresos-cgu/{index,show}.tsx`, `informes-razonados/ejecuciones/show.tsx`, `adquisiciones/procesos/show.tsx`, `sgf/importaciones/{index,show}.tsx`, `auditoria/index.tsx`, `seguridad/users-table.tsx` (solo la parte numérica; el formateo de fechas con `toLocaleString()` no cambia, es un tema aparte) por el nuevo helper/componente.
- Se documenta la convención como un nuevo Requirement en el spec `tema-visual-layout` (igual que la convención existente de tipografía/botones), para que todo listado o vista nueva la siga por defecto sin tener que redescubrirla.

## Capabilities

### New Capabilities
(ninguna — no se introduce un dominio nuevo)

### Modified Capabilities
- `tema-visual-layout`: se agrega el Requirement "Formato numérico global" (miles con punto, decimales con coma, negativos en rojo) como convención de tema, análoga a la de tipografía/botones ya existente en este spec.

## Impact

- Código afectado: nuevo `resources/js/lib/format.ts` y nuevo componente en `resources/js/components/ui/`; ediciones puntuales en las páginas listadas arriba para consumir el helper en vez de formatear inline.
- Sin cambios de backend, migraciones ni API — es una convención de presentación en el frontend.
- Sin cambios de permisos ni de workflow.

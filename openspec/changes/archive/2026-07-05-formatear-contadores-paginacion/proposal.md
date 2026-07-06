## Why

Los números en la app siguen la convención institucional de formato (miles con punto, decimales con coma, negativos en rojo — locale `es-CL`) casi en todas partes gracias a `lib/format.ts`/`<Monto>`, pero esa convención nunca quedó documentada en ninguna spec archivada, y quedó un hueco puntual sin corregir: los contadores de paginación ("Mostrando X–Y de Z") se muestran como números crudos, sin separador de miles, en 11 páginas de listado y en el componente de paginación compartido.

## What Changes

- Envolver `pagina.meta.from`, `pagina.meta.to` y `pagina.meta.total` con `formatNumero(...)` (de `@/lib/format`) en los 11 listados que arman su paginación inline: `pago-proveedores/casos`, `pago-proveedores/egresos-cgu`, `adquisiciones/procesos`, `indicadores-economicos`, `sgf/importaciones`, `maestros/items`, `maestros/proveedores`, `maestros/cfinancieros`, `maestros/ccostos`, `maestros/clientes-medidores`, `auditoria`.
- Mismo ajuste en `resources/js/components/shared/pagination.tsx` (usado hoy por `seguridad/usuarios/index.tsx`).
- Documentar retroactivamente en la spec `tema-visual-layout` la convención de formato numérico ya construida en código, para que quede como requisito verificable y no solo como práctica implícita.

## Capabilities

### New Capabilities

(ninguna)

### Modified Capabilities

- `tema-visual-layout`: nuevo requirement "Formato numérico institucional" que documenta la convención ya vigente (`es-CL`: miles con punto, decimales con coma, negativos en rojo) y exige que los contadores de paginación también la sigan.

## Impact

- Afecta: 11 páginas de listado + `resources/js/components/shared/pagination.tsx`. Ningún backend, tabla ni permiso involucrado.
- No afecta: los lugares que ya usan `<Monto>`/`formatMonto`/`formatPorcentaje`/`formatNumero` (dashboard, casos, egresos CGU, procesos de adquisición, indicadores económicos, SGF, informes razonados) — quedan igual, solo se documentan.

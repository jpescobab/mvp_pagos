## 1. Componente compartido

- [x] 1.1 `resources/js/components/shared/pagination.tsx`: importar `formatNumero` de `@/lib/format` y envolver `pagina.meta.from`, `pagina.meta.to` y `pagina.meta.total`.

## 2. Páginas con paginación inline

- [x] 2.1 `resources/js/pages/pago-proveedores/casos/index.tsx`
- [x] 2.2 `resources/js/pages/pago-proveedores/egresos-cgu/index.tsx`
- [x] 2.3 `resources/js/pages/adquisiciones/procesos/index.tsx`
- [x] 2.4 `resources/js/pages/indicadores-economicos/index.tsx`
- [x] 2.5 `resources/js/pages/sgf/importaciones/index.tsx`
- [x] 2.6 `resources/js/pages/maestros/items/index.tsx`
- [x] 2.7 `resources/js/pages/maestros/proveedores/index.tsx`
- [x] 2.8 `resources/js/pages/maestros/cfinancieros/index.tsx`
- [x] 2.9 `resources/js/pages/maestros/ccostos/index.tsx`
- [x] 2.10 `resources/js/pages/maestros/clientes-medidores/index.tsx`
- [x] 2.11 `resources/js/pages/auditoria/index.tsx`

Para cada una: importar `formatNumero` de `@/lib/format` (si no está ya importado) y envolver `pagina.meta.from`, `pagina.meta.to` y `pagina.meta.total` en el texto "Mostrando X–Y de Z".

## 3. Validación

- [x] 3.1 `npm run types:check` y `npm run lint:check`. Sin errores.
- [x] 3.2 Verificado en el navegador (build de producción): `maestros/proveedores` con 977 registros muestra "Mostrando 1–20 de 977" sin errores de consola; `Intl.NumberFormat('es-CL').format(12345)` (la misma llamada que usa `formatNumero`) confirmado como `"12.345"` para números ≥1000. Los indicadores del dashboard (`$ 40.844,79`, `$ 834.504`) ya muestran el formato correcto vía `<Monto>`, confirmando que la infraestructura compartida funciona end-to-end en este entorno.

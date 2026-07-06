## 1. Medir el estado actual

- [x] 1.1 Correr `npm run build` y anotar el tamaño (raw y gzip) del chunk que contiene el runtime de Wayfinder, como línea base para comparar después del cambio. (Línea base: `wayfinder-DT4hJ1Ck.js` = 316.12 kB / gzip 99.69 kB)

## 2. Cambiar los imports en app-sidebar.tsx

- [x] 2.1 Confirmar, con grep sobre `app-sidebar.tsx`, que las 18 variables de módulos de ruta (`procesosAdquisicion`, `auditoria`, `indicadoresEconomicos`, `definicionesInformeRazonado`, `ejecucionesInformeRazonado`, `conectores`, `sistemasExternos`, `ccostos`, `cfinancieros`, `clientesMedidores`, `proveedores`, `casos`, `egresosCgu`, `periodosReportabilidad`, `roles`, `importacionesSgf`, `usuarios`, `definicionesWorkflow`) solo se usan para `.index()` en todo el archivo.
- [x] 2.2 Cambiar cada import de `import X from '@/routes/...'` a `import { index as X } from '@/routes/...'`.
- [x] 2.3 Cambiar cada llamada `X.index()` por `X()`.
- [x] 2.4 No tocar ningún archivo bajo `resources/js/routes/**` ni `resources/js/actions/**`.

## 3. Validar

- [x] 3.1 `npm run types:check` para confirmar que el nuevo tipado de las funciones importadas con nombre es correcto.
- [x] 3.2 `npm run lint:check` sobre el archivo modificado.
- [x] 3.3 Levantar el dev server y verificar en el navegador que los 18 ítems del sidebar siguen apuntando a la misma URL que antes (comparar hrefs). Verificado: los 18 `href` coinciden exactamente con las rutas esperadas.
- [x] 3.4 Correr `npm run build` de nuevo y comparar el tamaño del chunk de Wayfinder contra la línea base de la tarea 1.1. **Resultado: sin cambio medible** (`wayfinder-DT4hJ1Ck.js` sigue en 316.12 kB / gzip 99.69 kB, mismo hash de contenido). Ver hallazgo en el mensaje al usuario: la hipótesis original era incorrecta — ese chunk resultó ser el runtime de Inertia/React, no los módulos de rutas de Wayfinder, y estos últimos (ubicados realmente en `app-*.js`) tampoco se redujeron de forma significativa (228.40 → 228.28 kB) porque el código generado por Wayfinder ata cada método al objeto exportado vía asignaciones a nivel de módulo, lo que impide a Rollup eliminarlos aunque solo se importe `index` por nombre.

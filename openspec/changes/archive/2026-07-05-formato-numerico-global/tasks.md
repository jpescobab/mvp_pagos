## 1. Helper y componente central

- [x] 1.1 Crear `resources/js/lib/format.ts` con `formatNumero(valor, opciones?)`, `formatMonto(valor, opciones?)` y `formatPorcentaje(valor, decimales?)` basados en `Intl.NumberFormat('es-CL', ...)`.
- [x] 1.2 Crear `resources/js/components/ui/monto.tsx` con el componente `<Monto>` que usa `format.ts`, aplica `font-mono` (JetBrains Mono, tipografía monoespaciada del tema) siempre para evitar ambigüedad entre dígitos (0/8/1/l), y aplica `text-destructive` cuando el valor es negativo.

## 2. Migración de vistas existentes

- [x] 2.1 Migrar `resources/js/pages/dashboard.tsx` (KPIs y montos) para usar `format.ts`/`<Monto>` en vez de `Intl.NumberFormat` in-line.
- [x] 2.2 Migrar `resources/js/lib/indicadores.ts` para reutilizar `format.ts` en vez de duplicar `Intl.NumberFormat`.
- [x] 2.3 Migrar `resources/js/pages/indicadores-economicos/index.tsx` (`{indicador.valor}` en la tabla, hoy sin formatear — ej. `69542.0000`; el decimal de Laravel llega como string) a `<Monto>`, manejando `valor` como `number | string`.
- [x] 2.4 Migrar `resources/js/pages/pago-proveedores/casos/show.tsx` (todos los renders de `caso.monto`, `registro.monto`, `factura.monto`, `egreso.monto`) a `<Monto>`.
- [x] 2.5 Migrar `resources/js/pages/pago-proveedores/casos/index.tsx` (`caso.monto` en el listado) a `<Monto>`.
- [x] 2.6 Migrar `resources/js/pages/pago-proveedores/egresos-cgu/index.tsx` y `show.tsx` (`egreso.monto_total`, `item.monto`) a `<Monto>`.
- [x] 2.7 Migrar `resources/js/pages/informes-razonados/ejecuciones/show.tsx` (`metrica.valor` y demás montos/valores numéricos) a `<Monto>`/`format.ts`.
- [x] 2.8 Migrar `resources/js/pages/adquisiciones/procesos/show.tsx` y `index.tsx` (montos/valores numéricos) a `<Monto>`.
- [x] 2.9 Migrar `resources/js/pages/sgf/importaciones/index.tsx` y `show.tsx` (cantidades/conteos numéricos) a `format.ts`.
- [x] 2.10 Revisar `resources/js/pages/auditoria/index.tsx`: no tiene valores numéricos de negocio (solo `auditable_id`, identificador fuera de alcance) — sin cambios necesarios.
- [x] 2.11 Revisar `resources/js/components/seguridad/users-table.tsx`: no tiene valores numéricos de negocio (RUT es identificador, resto es texto/fecha) — sin cambios necesarios.
- [x] 2.12 Revisar `pagina.meta.total` (conteos de paginación en todos los índices) y dejarlo explícitamente fuera de alcance: es un conteo simple, no una magnitud de negocio que pueda ser negativa; no requiere `<Monto>`.
- [x] 2.13 **Corrección post-verificación (el usuario reportó inconsistencia visual con una captura)**: los chips de indicadores en `dashboard.tsx` y las filas del desplegable en `topbar-indicadores.tsx` ya usaban `formatearValorIndicador` + `font-mono`, pero con `font-semibold` (negrita) — visualmente distinto a la columna "Valor" de `indicadores-economicos/index.tsx` (`font-mono tabular-nums`, sin negrita). Se alinearon ambos a `font-mono tabular-nums` para que los tres lugares se vean idénticos. Confirmado con HTML servido a una sesión autenticada real (`curl`, sin depender del navegador automatizado que se puso inestable): `font-mono text-sm tabular-nums">$ 40.844,79` etc. en el dashboard.

## 3. Spec y validación

- [x] 3.1 Confirmar que la spec delta de `tema-visual-layout` (Requirements "Formato numérico global", "Valores negativos en rojo" y "Legibilidad tipográfica de cifras") queda correcta antes de archivar.
- [x] 3.2 Verificado en el preview: dashboard (KPIs en `font-mono`) e `indicadores-economicos/index.tsx` (`69542.0000` ahora se ve `$ 69.542` en JetBrains Mono, confirmado con `preview_inspect`). No había datos con monto negativo sembrados en este entorno para confirmar visualmente el rojo; la lógica (`esNegativo` → `text-destructive`) es una comparación directa ya cubierta por `types:check`/`lint:check`.
- [x] 3.3 Ejecutar `npm run lint:check` y `npm run types:check`.

## Why

La página de detalle de un Egreso CGU muestra hoy muy poca información: la cabecera trae número, fecha, monto total y una glosa (habitualmente "varios"), y cada fila de "Casos cubiertos" pinta únicamente el `sgf_id` del caso y el monto de la línea. Con eso es imposible reconocer "a primera vista" de qué trata el egreso ni qué representa cada caso importado: no se ve el proveedor, el número de factura ni el estado del proceso. Toda esa información ya está modelada y disponible; solo falta exponerla y presentarla.

## What Changes

- La cabecera del detalle del egreso SHALL mostrar, además de lo actual, su periodo, centro financiero, cantidad de casos cubiertos, quién lo registró y si fue generado automáticamente.
- Cada caso cubierto SHALL identificarse por su proveedor (nombre + RUT), número de factura (`numero`/DTE), estado actual del `Proceso` (badge de workflow) y fecha SII, además del `sgf_id` y el monto de la línea que ya se muestran.
- El listado de casos cubiertos SHALL seguir el patrón de listado tabular denso del proyecto (avatar+iniciales del proveedor, badge de estado con tokens semánticos, columnas secundarias truncadas y ocultas progresivamente, fallback "—" en valores nulos) y cada fila SHALL enlazar al detalle del caso.
- Sin cambios de esquema: todos los campos provienen de datos ya modelados (`egresos_cgu`, `egresos_cgu_items`, `casos_pago_proveedor`, `proveedores`, `procesos`, `estados_workflow`). El estado del workflow solo se **lee**; ninguna transición se ejecuta desde esta vista.

## Capabilities

### New Capabilities

<!-- Ninguna. -->

### Modified Capabilities

- `paginas-pago-proveedores`: se amplía el requirement "Página de detalle de egreso CGU" para que, tanto la cabecera como cada caso cubierto, expongan la información de identificación disponible (proveedor, N° DTE, estado del workflow, fecha SII, periodo, centro financiero, registrado por), presentada con el patrón de listado denso.

## Impact

- **Backend (solo lectura, sin lógica nueva en el controlador):** `EgresoCguController::show` amplía el eager-load; `EgresoCguResource` amplía su `toArray()` reutilizando la forma de proveedor/estado ya usada por `CasoPagoProveedorResource` y `EstadoWorkflowResource`.
- **Frontend:** `resources/js/pages/pago-proveedores/egresos-cgu/show.tsx` se reconstruye con el patrón denso, reutilizando `EstadoBadge`, `useInitials`, `Avatar`, `Monto` y `formatFecha`; se extienden los tipos en `resources/js/types/pago-proveedores.ts`.
- **Tests:** se amplía `tests/Feature/PagoProveedores/DetalleEgresoCguTest.php`.
- Sin migraciones, sin permisos nuevos, sin cambios en `TransicionWorkflowService`.

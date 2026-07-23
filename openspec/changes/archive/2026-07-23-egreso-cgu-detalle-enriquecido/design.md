## Context

La vista de detalle del Egreso CGU (`/pago-proveedores/egresos-cgu/{id}`) es hoy mínima: `EgresoCguController::show` hace `->load('items.caso')` y devuelve un `EgresoCguResource` que aplana cada item a `{ caso: { sgf_id }, monto }`. La página React (`egresos-cgu/show.tsx`) renderiza un `<ul>` con `sgf_id` + monto. Toda la información que permitiría reconocer el egreso y sus casos (proveedor, N° DTE, estado del workflow, fecha SII, periodo, centro financiero, registrado por) ya está modelada y accesible por relaciones Eloquent existentes; el cuello de botella es puramente de exposición (Resource) y presentación (React).

Restricciones del harness: controladores livianos (solo eager-load, sin lógica); SGF es origen, no gobierno (el `sgf_status` crudo NO gobierna la vista — se muestra el estado del workflow interno); el estado del `Proceso` solo se lee, ninguna transición pasa por aquí; sin cambios de esquema; nombres en español; los listados densos siguen el patrón canónico de `tema-visual-layout`.

## Goals / Non-Goals

**Goals:**
- Que la cabecera y cada caso cubierto muestren la información de identificación ya disponible, sin migraciones ni permisos nuevos.
- Reutilizar los componentes y patrones existentes (`EstadoBadge`, `useInitials`, `Avatar`, `Monto`, `formatFecha`, `EstadoWorkflowResource`, forma de proveedor de `CasoPagoProveedorResource`).
- Mantener el controlador liviano (solo `->load(...)`) y la resolución de datos en el Resource.

**Non-Goals:**
- No se agregan columnas, tablas ni permisos.
- No se ejecuta ninguna transición de workflow ni se toca `TransicionWorkflowService`.
- No se muestra el volcado crudo de SGF (`payload_crudo`/`payload_normalizado`) ni el `sgf_status` crudo como estado gobernante.
- No se modifica la creación ni el listado de egresos, solo el detalle.

## Decisions

**1. Eager-load en el controlador, mapeo en el Resource.**
`EgresoCguController::show` amplía el load a `['items.caso.proveedor', 'items.caso.proceso.estadoActual', 'registradoPor', 'cfinanciero']` para evitar N+1. Toda la resolución de campos vive en `EgresoCguResource::toArray()`. Alternativa descartada: armar el payload en el controlador — viola la regla de controladores livianos.

**2. Reutilizar la forma "rica" ya establecida, no un Resource nuevo.**
Por item se mapean los mismos campos y con la misma forma que `CasoPagoProveedorResource` ya expone (`proveedor: { nombre, rutproveedor }`, `numero`, `periodo`, `fecha_sii`, `folio_egreso`, `observacion`, `sgf_id`, más `caso.id` para enlazar) y el estado vía `new EstadoWorkflowResource($item->caso->proceso?->estadoActual)`. Se mantiene `monto` como el monto de la línea del egreso (distinto de `caso.monto`). Alternativa descartada: incrustar `CasoPagoProveedorResource` completo por item — arrastra `proceso` completo, checklist, snapshots y demás payload pesado innecesario para esta vista.

**3. Guardas de nulos.**
Un caso podría no tener `Proceso` (o proveedor). Se usa `?->` y se expone `estado_actual: null` cuando no haya proceso; React hace guard antes de renderizar `<EstadoBadge>`. Los campos opcionales usan fallback "—".

**4. Frontend con el patrón denso.**
`show.tsx` se reconstruye como tabla `table-fixed text-xs` siguiendo `casos/index.tsx`: celda de proveedor (Avatar+iniciales + nombre truncado con `title` + RUT mono), columnas `sgf_id`, N° DTE, estado (`<EstadoBadge compact />`), fecha SII y monto; columnas secundarias con `hidden … lg:table-cell`; fila enlazada a `casos.show(item.caso.id)`. Cabecera con grilla compacta de metadatos. Alternativa descartada: cards por caso — inconsistente con el patrón denso obligatorio del proyecto.

## Risks / Trade-offs

- [Doble consulta / N+1 al resolver estado y proveedor por item] → cubierto por el eager-load explícito en el controlador; el estado se lee de `proceso.estadoActual` ya cargado.
- [Caso sin `Proceso` o sin proveedor rompe el render] → guardas con `?->` en el Resource (`estado_actual: null`) y guard en React antes del badge; tests cubren el caso.
- [El monto de la línea (`item.monto`) se confunde con el monto total del caso (`caso.monto`)] → se etiqueta la columna simplemente "Monto" (el monto que este egreso cubre para ese caso), consistente con la vista actual; no se muestra `caso.monto` para no duplicar semántica.
- [PHPStan: covarianza en el `map()` sobre Collection] → devolver arrays planos y `->values()` (como ya hace el Resource), con `?->` en relaciones opcionales.

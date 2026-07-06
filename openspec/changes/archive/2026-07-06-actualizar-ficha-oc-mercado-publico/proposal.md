## Why

La ficha de detalle de una Orden de Compra de Mercado Público (`show.tsx` / `buscar.tsx`) ya respeta el orden de secciones definido en la spec (cronograma como segunda sección), pero el cronograma se renderiza como una simple lista de texto sin iconos ni indicación visual de qué etapa está completada, y el backend trunca la fecha de cada hito a solo el día (`substr(..., 0, 10)`), perdiendo la hora real que entrega Mercado Público. El usuario pidió que la ficha calce con un formato de referencia (captura de una ficha de OC con línea de tiempo iconográfica, fecha y hora reales por etapa, y secciones en tarjetas más densas) sin perder la configuración de iconos ya existente en la ficha (badges de estado, acciones del encabezado).

## What Changes

- El backend deja de truncar las fechas del cronograma a solo el día: `cronogramaDesdeFechas` y `fecha_emision` conservan el datetime completo que entrega Mercado Público (fecha y hora), cuando la API lo incluye.
- El componente `CronogramaTimeline` se rediseña como una línea de tiempo horizontal con un ícono circular por etapa (check relleno en verde cuando la etapa está completada, círculo vacío cuando no), conectados por una línea, mostrando debajo de cada ícono el nombre de la etapa, la fecha y hora reales, y la palabra "Completado" cuando corresponde.
- El encabezado de la ficha (`FichaConsultaMercadoPublico`) gana una zona de monto destacado (monto total) y un grupo de acciones secundarias junto a las ya existentes: "Ver JSON" (abre el payload crudo del snapshot vinculado, ya capturado por la capability `ordenes-compra-mercado-publico`) queda funcional; "Ver PDF" y "Ver en Mercado Público" quedan visibles pero deshabilitados con "Disponible próximamente" (mismo patrón ya usado en menús de acciones de otros listados), porque no existe hoy un enlace externo confiable ni una fuente de PDF para una OC individual.
- Se agrega una sección "Desglose financiero" (neto, impuesto calculado como total − neto, total) antes de las condiciones del contrato, usando datos ya disponibles en el modelo — no se agregan campos nuevos que Mercado Público no entregue hoy (comuna, región, dirección, giro, tipo de despacho, categoría de ítem, título de licitación quedan fuera de alcance porque el payload normalizado actual no los captura).
- Las secciones existentes (organismo comprador, condiciones, adjudicación/proveedor, ítems) se reorganizan visualmente en tarjetas más compactas, sin cambiar los datos que muestran.

## Capabilities

### Modified Capabilities
- `ordenes-compra-mercado-publico`: el cronograma y `fecha_emision` conservan fecha y hora completas del payload de Mercado Público, en vez de truncarse a la fecha.
- `paginas-ordenes-compra-mercado-publico`: la ficha de la OC muestra el cronograma como línea de tiempo con iconos de estado (completado/pendiente) y hora real por etapa, agrega una sección de desglose financiero, y expone las acciones "Ver JSON" (funcional), "Ver PDF" y "Ver en Mercado Público" (deshabilitadas, "Disponible próximamente") junto al encabezado.

## Impact

- Backend: `app/Services/Adquisiciones/OrdenCompraMercadoPublicoService.php` (normalización de fechas/cronograma), `app/Http/Resources/Adquisiciones/OrdenCompraMercadoPublicoResource.php` (exponer snapshot para "Ver JSON").
- Frontend: `resources/js/components/mercado-publico/ficha-consulta.tsx` (rediseño de `CronogramaTimeline` y encabezado), `resources/js/pages/adquisiciones/ordenes-compra-mercado-publico/show.tsx` y `.../buscar.tsx` (nueva sección de desglose financiero, botones de acción).
- Tests: `tests/Feature/Adquisiciones/OrdenCompraMercadoPublicoServiceTest.php` y `ApiOrdenesCompraMercadoPublicoTest.php` (fixtures con horas reales en `Fechas`), posible test de Inertia/Pest para la ficha.

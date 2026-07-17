## Context

El detalle de un caso (`casos/show.tsx`) ya renderiza `<PreparacionEgresoCard caso={caso} />`, que calcula localmente (`calcularPreparacionEgreso`) si los 4 criterios de preparación están completos y muestra "X / 4 completo". El caso ya expone `caso.egresos_cgu` (lista de egresos asociados, vacía si ninguno). El formulario de creación de egreso (`egresos-cgu/crear.tsx`) ya soporta preseleccionar casos "listos" cuando se llega desde una corrida de importación SGF (`trabajo_integracion_id`), pero no tiene forma de preseleccionar un caso puntual llegado desde su propio detalle.

## Goals / Non-Goals

**Goals:**
- Dar al usuario, desde el detalle del caso, un camino de un clic hacia el formulario de creación de egreso cuando el caso está listo y sin egreso asignado.
- Reutilizar el cálculo de "listo" que ya existe en `PreparacionEgresoCard`, sin triplicar la lógica (ya está duplicada una vez respecto a `ListoParaEgresoResolver`, según su propio comentario).
- Preseleccionar ese caso puntual en el formulario de creación sin restringir la lista a solo ese caso.

**Non-Goals:**
- No se agrega ninguna transición de workflow nueva ni se cambia el criterio de "listo para egreso".
- No se toca `RevisionEgresoService::iniciarRevision()` ni `TransicionWorkflowService`.
- No se resuelve la creación del egreso en un solo paso desde el detalle del caso (sigue siendo necesario completar número de egreso y fecha en el formulario existente) — eso sería un salto de alcance mayor y el formulario ya cubre validación y errores.

## Decisions

**1. El botón vive dentro de `PreparacionEgresoCard`, no en `show.tsx`.**
`PreparacionEgresoCard` ya calcula `completados === criterios.length` para el badge "X/4 completo"; agregar el CTA ahí evita exponer ese booleano a un tercer lugar (show.tsx) y mantiene la lógica de "listo" en un solo componente. El componente ya recibe `caso` completo, así que también puede leer `caso.egresos_cgu` directamente para decidir si mostrar el botón (sin egreso asignado todavía).

**2. Preselección por `caso_pago_proveedor_id`, no por restricción de la lista.**
A diferencia de `trabajo_integracion_id` (que sí filtra la lista a los casos de esa corrida), el nuevo parámetro solo afecta la selección inicial: la lista completa de casos sin egreso sigue visible, para que el usuario pueda agregar más casos al mismo egreso si quiere. Esto es más simple que introducir un segundo modo de "lista restringida" y coincide con el caso de uso real (alguien creando un egreso a partir de un caso puntual normalmente sigue queriendo ver qué otros casos podría agrupar).

**3. El backend no necesita tocar el `WHERE` de la query.**
La query base de `EgresoCguController::create()` ya es `whereDoesntHave('egresoCguItems')`; un caso llegado por `caso_pago_proveedor_id` ya cumple esa condición (si no la cumpliera, significa que ya tiene un egreso y el botón no debería haberse mostrado). El controlador solo necesita leer el parámetro y pasarlo como prop para que el frontend preseleccione.

**4. (Enmienda previa a archivar) Todo el cuerpo de `EgresoCguController::create()` —no solo el passthrough nuevo de este change— se mueve a un Service, `CasosElegiblesEgresoCguService::paraFormulario()`.**
Una auditoría posterior del módulo (ver change `extraer-logica-negocio-controllers-pago-proveedores`) encontró que `create()` ya tenía, antes de este change, una query de elegibilidad combinando `CasoPagoProveedor`+`TrabajoIntegracion` y un armado manual del array de presentación con `app(ListoParaEgresoResolver::class)` resuelto vía service locator dentro de un `map()` — viola la directriz de controladores livianos. Como este change es el que sigue abierto tocando `create()`, se aprovecha para corregirlo aquí (inyectando `ResolutorChecklistDocumentalProceso` y `ListoParaEgresoResolver` por constructor) en vez de dejarlo para otro change que tendría que volver a tocar el mismo método. Sin cambio de comportamiento: misma query de elegibilidad, mismo filtro por `trabajo_integracion_id`, mismo criterio `listo`, y el nuevo passthrough de `caso_pago_proveedor_id` de este change se mantiene igual (solo pasa a través del Service).

## Risks / Trade-offs

- **[Riesgo] Si el usuario llega con un `caso_pago_proveedor_id` de un caso que mientras tanto ya obtuvo un egreso (carrera entre pestañas), ese id no aparecerá en la lista y la preselección será un no-op silencioso.** → Mitigación: comportamiento aceptable — el formulario ya excluye casos con egreso asignado por diseño; no hay corrupción de datos posible, solo que la preselección no tiene efecto visible. No amerita validación adicional para un caso límite de doble pestaña.
- **[Riesgo] Duplicar aún más el criterio "listo" si en el futuro se necesita en un cuarto lugar.** → Mitigación: fuera de alcance de este change: no se introduce una tercera copia, solo se reutiliza la ya existente en `PreparacionEgresoCard`. Si esto se vuelve un problema recurrente, la solución futura es exponer `listo_para_egreso` como campo calculado del backend (mismo patrón que se usó para `listo_para_aprobar` en el listado de casos), pero eso es una decisión aparte, no parte de este change puramente de navegación.

## Migration Plan

No aplica migración de base de datos. Cambio de código (controller, dos páginas React) desplegable directamente.

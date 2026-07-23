## Why

La ficha de una Licitación de Mercado Público muestra "Ver PDF" deshabilitado con la indicación "Disponible próximamente". El requirement vigente en `paginas-licitaciones-mercado-publico` justifica esa decisión textualmente: "porque no existe todavía un patrón de extracción verificado del PDF de una Licitación". **Ahora existe y está verificado contra el sitio real.**

Las Órdenes de Compra sí tienen el botón operativo, así que hoy dos fichas hermanas se comportan distinto sin ninguna razón funcional: quien revisa una licitación tiene que salir a `mercadopublico.cl` y descargar la ficha a mano. Es la última acción diferida que queda en el módulo de Adquisiciones.

## What Changes

- El sistema SHALL exponer `GET adquisiciones/licitaciones-mercado-publico/pdf?codigo=<codigo>` (`adquisiciones.licitaciones_mp.pdf`) que entrega el PDF de la ficha de una Licitación, sin exigir que la Licitación exista localmente, análoga a la ruta ya existente de Órdenes de Compra.
- El sistema SHALL obtener ese PDF ejecutando el postback de la página pública de Mercado Público (`DetailsAcquisition.aspx`): un `GET` para cosechar el estado del formulario y un `POST` que responde `application/pdf`. **No** se usa Playwright: el flujo completo funciona con `Illuminate\Http\Client`.
- **A diferencia de la Orden de Compra, acá Laravel recibe los bytes del documento**, no una URL. Por lo tanto el sistema SHALL persistir el PDF en almacenamiento privado y SHALL registrar un `snapshot_datos_externo` con fuente, fecha, hash, método de captura y usuario responsable, vinculado a la Licitación cuando exista localmente.
- El sistema SHALL servir desde el snapshot ya capturado las descargas posteriores del mismo código, sin repetir los dos viajes a Mercado Público.
- El sistema SHALL registrar cada obtención como `trabajo_integracion` + `solicitud_api_externa` sobre el `sistema_externo` `MERCADO_PUBLICO`, reutilizando `IntegracionExternaService`.
- La acción "Ver PDF" de la ficha de Licitación SHALL dejar de estar deshabilitada y SHALL descargar el PDF. Con eso, el módulo de Adquisiciones queda **sin ninguna acción "Disponible próximamente"**.
- Sin permisos nuevos: la autorización reutiliza la misma que ya protege la consulta de Licitaciones.

## Capabilities

### New Capabilities

Ninguna. El comportamiento nuevo se suma a las dos capabilities que ya cubren este dominio, en simetría exacta con cómo la Orden de Compra ubicó su requirement de PDF (`Resolver el enlace directo de descarga del PDF de una Orden de Compra`, dentro de `ordenes-compra-mercado-publico`).

### Modified Capabilities

- `licitaciones-mercado-publico`: se agrega el requirement de obtener, conservar y reutilizar el PDF de la ficha de una Licitación desde la página pública de Mercado Público, con su evidencia trazable.
- `paginas-licitaciones-mercado-publico`: se reemplaza el requirement "Acciones de encabezado para ver el JSON y el enlace a Mercado Público", cuyo escenario "Ver PDF no implementado" deja de describir el comportamiento vigente.

## Impact

- **Rutas**: `routes/adquisiciones.php` agrega `GET pdf` dentro del grupo `licitaciones_mp.`, declarada **antes** de `{licitacion}` para que no la capture el parámetro — mismo orden que ya usa el grupo `ordenes_compra_mp.`.
- **Backend**: `LicitacionMercadoPublicoController::pdf` (liviano: autoriza, delega, responde). Toda la lógica —el postback de dos pasos, la persistencia del archivo, el snapshot y la reutilización del ya capturado— vive en `app/Services/Adquisiciones/`. El design decide si es un método de `LicitacionMercadoPublicoService` o una clase propia.
- **Almacenamiento**: el PDF se guarda en el disco privado, junto a los demás documentos externos del sistema. No se guarda el binario en la base de datos.
- **Frontend**: sin cambio de contrato. `AccionesEncabezadoFichaMercadoPublico` ya usa `urlPdf` como `href`; basta reemplazar los tres `urlPdf={null}` de `licitaciones-mercado-publico/{show,buscar}.tsx` por la URL del endpoint. La rama deshabilitada del componente queda sin llamadores y se retira. Rutas tipadas vía Wayfinder.
- **Trazabilidad**: solo escrituras de evidencia (`trabajos_integracion`, `solicitudes_api_externas`, `snapshots_datos_externos`). No toca el workflow, no cambia estados y no modifica datos de negocio de la Licitación.
- **Sin migraciones**: el índice `(sistema_externo_id, referencia_externa)` de `snapshots_datos_externos` ya existe y cubre la búsqueda del PDF previamente capturado.
- **Tests**: `tests/Feature/Adquisiciones/`, con `Http::fake` siguiendo el patrón de `OrdenCompraMercadoPublicoServiceTest`.

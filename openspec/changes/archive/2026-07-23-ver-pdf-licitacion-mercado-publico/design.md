## Context

El módulo de Adquisiciones consulta Mercado Público en dos dominios hermanos —Órdenes de Compra y Licitaciones— que comparten la capa transversal de integraciones y el componente de encabezado de ficha. La acción "Ver PDF" está operativa solo en OC. En Licitaciones se dejó deshabilitada por una razón explícita, escrita en el requirement vigente: no había un patrón de extracción verificado.

**Ese patrón ya se verificó contra el sitio real** (dos licitaciones, estados publicada y cerrada, tres corridas). El hallazgo central es que **las dos páginas no se parecen**:

| | Orden de Compra | Licitación |
| --- | --- | --- |
| Página pública | `DetailsPurchaseOrder.aspx?codigoOC=` | `DetailsAcquisition.aspx?idlicitacion=` |
| Botón de PDF | `<input id="imgPDF" onclick="open('PDFReport.aspx?qs=<token>')">` | `<input id="imgPDF">` sin `onclick`, más `<a id="descargar_pdf" href="javascript:__doPostBack(…)">` |
| ¿Existe `PDFReport.aspx`? | Sí | **No** |
| Cómo se obtiene | Regex sobre el HTML → URL `GET` estable | Postback de ASP.NET WebForms: `POST` con `__VIEWSTATE` |
| Quién descarga el archivo | El navegador del usuario (`redirect()->away()`) | **Laravel** — el PDF viene en el cuerpo de la respuesta |

Datos medidos del flujo de Licitación: el `GET` inicial redirige 302 a `DetailsAcquisition.aspx?qs=<token>` (token **estable** por licitación entre sesiones distintas); hay que conservar cookies y cosechar `__VIEWSTATE` (~66 KB) y `__VIEWSTATEGENERATOR` (no hay `__EVENTVALIDATION` en esta página); el `POST` con `__EVENTTARGET=descargar_pdf` responde `application/pdf` con `Content-Disposition: attachment; filename=PDF<codigo>.pdf`, entre 315 y 446 KB. Todo funciona con `Illuminate\Http\Client`.

La consecuencia arquitectónica es la que gobierna este diseño: **Laravel pasa a recibir el documento**, y la regla de snapshot obligatorio del harness aplica a todo documento recibido desde una API externa. Con la OC nunca aplicó, porque Laravel jamás veía el archivo.

## Goals / Non-Goals

**Goals:**

- Que "Ver PDF" funcione en la ficha de Licitación con el mismo gesto que en la de OC, sin cambiar el contrato del componente compartido.
- Dejar evidencia trazable del documento entregado: qué se descargó, de dónde, cuándo, con qué hash y quién lo pidió.
- No repetir dos viajes de red de ~700 KB combinados cada vez que alguien hace clic en el mismo PDF.
- Que el módulo de Adquisiciones quede sin ninguna acción "Disponible próximamente".

**Non-Goals:**

- No se usa Playwright. El flujo se resuelve con HTTP plano; introducir un navegador exigiría un `conector_automatizacion_navegador` autorizado y no compra nada.
- No se descarga el PDF de forma proactiva ni por job programado al guardar una Licitación. La captura ocurre solo cuando alguien la pide.
- No se cambia el flujo de PDF de la Orden de Compra. Funciona y su asimetría con este está justificada por la diferencia real entre las dos páginas.
- No se agrega el PDF al expediente documental (`documentos`/`vinculos_documento`) ni se lo hace parte de ningún checklist. Es evidencia de consulta, no un documento del expediente.
- No se versiona el PDF ni se implementa una política de refresco automático (ver decisión 5).

## Decisions

### 1. El PDF se obtiene con `Illuminate\Http\Client` conservando cookies, no con Playwright

El postback es una petición HTTP corriente: dos llamadas, cookies de sesión y tres campos de formulario. `Http::withOptions(['cookies' => new CookieJar])` cubre exactamente eso.

*Alternativa considerada*: el microservicio `sgf-playwright` o un conector nuevo. Descartada: el harness reserva Playwright para cuando **no hay API o HTTP suficiente**, y exige un conector autorizado (`autorizado_por` + `autorizado_en`) para cada operación. Levantar un navegador de 300 MB para un `POST` de formulario sería introducir una dependencia autorizada donde `Http::` alcanza, y agregaría a la operación un modo de falla —el timeout de proceso descrito en `HARNESS_IA.md` §13— que hoy no tiene.

### 2. La lógica vive en una clase propia, `DescargaPdfLicitacionMercadoPublicoService`, no en `LicitacionMercadoPublicoService`

`LicitacionMercadoPublicoService` gobierna el ciclo de vida del dato de negocio: buscar, consultar la API JSON, comparar, guardar, actualizar. Lo del PDF es otra cosa —otro protocolo (scraping de WebForms, no API JSON), otro artefacto (un archivo, no filas) y otro ciclo de vida (caché en disco, no upsert)— y arrastra el manejo de ViewState, cookies y binarios.

Decisión: clase propia en `app/Services/Adquisiciones/`, con el sufijo `Service` que la convención del repo reserva para orquestación multi-operación.

*Alternativa considerada*: un método `resolverUrlPdf()`-símil dentro de `LicitacionMercadoPublicoService`, por simetría con la OC. Descartada: la simetría es aparente. El método de la OC son ~40 líneas que devuelven un string; este orquesta dos peticiones, escritura en disco, snapshot y una rama de caché. Meterlo ahí convierte un service de dominio en un cajón de sastre.

### 3. La existencia previa del PDF se resuelve por `snapshots_datos_externos`, sin migración

La búsqueda es `sistema_externo_id = MERCADO_PUBLICO` + `referencia_externa = <codigo>` + `metodo_captura = 'scraping_pdf'`, tomando el más reciente. **El índice `(sistema_externo_id, referencia_externa)` ya existe** en la migración de la tabla, así que la consulta está cubierta y este cambio no lleva ninguna migración.

El `payload_crudo` del snapshot guarda los metadatos de la captura —código, URL de la ficha, nombre de archivo, content-type, tamaño, ruta relativa en el disco privado y hash SHA-256 **del PDF**—, no el binario. Ojo con un detalle de `IntegracionExternaService::registrarSnapshot()`: la columna `hash` la calcula él mismo sobre el JSON del `payload_crudo`, no sobre el archivo. Por eso el hash del PDF va **dentro** del payload, con nombre propio; confundir los dos haría que el snapshot no pruebe nada sobre el documento.

Antes de servir un PDF cacheado hay que confirmar que el archivo sigue en disco: un snapshot cuyo archivo fue borrado debe degradar a captura nueva, no a un 500.

*Alternativa considerada*: una columna `ruta_pdf` en `licitaciones_mercado_publico`. Descartada por dos motivos: ata el PDF a la Licitación guardada localmente, pero el endpoint debe funcionar también para códigos que solo se están consultando (igual que el de OC); y duplicaría en una tabla de negocio un dato que la tabla de evidencia ya modela.

### 4. El endpoint responde el PDF en el cuerpo, no un redirect

La OC hace `redirect()->away($url)` porque tiene una URL pública estable. Acá no existe tal URL: el PDF solo se materializa como respuesta a un `POST` con estado de sesión. El endpoint responde entonces con el archivo (`Content-Type: application/pdf`, `Content-Disposition` con el nombre por código).

Esto **no cambia el contrato del frontend**: `AccionesEncabezadoFichaMercadoPublico` usa `urlPdf` como `href` de un `<a target="_blank">`, y al navegador le da igual recibir un 302 o el archivo.

Cuando la captura falla, el endpoint vuelve atrás con un error de validación, igual que hoy hace el de OC — no entrega un PDF vacío ni un 500 crudo.

### 5. El PDF cacheado no expira: se conserva la primera captura

Una vez capturado, el mismo código se sirve siempre desde disco. No hay TTL ni recaptura automática.

El razonamiento es institucional, no de rendimiento: el PDF es **evidencia de lo que Mercado Público publicaba en el momento de la captura**. Recapturar en silencio cada N días reemplazaría esa evidencia por otra sin que nadie lo pidiera, que es justo lo que la regla de snapshot busca impedir. La ficha de una licitación además cambia con su estado (publicada → cerrada → adjudicada), así que "el PDF" no es un objeto único: son documentos distintos en el tiempo, y el snapshot fecha cuál se tiene.

Consecuencia asumida y explícita: quien necesite la versión actual de una licitación que avanzó de estado no la obtiene desde acá. Queda anotado como el punto natural de un cambio futuro —una acción explícita de "recapturar" que agregue un snapshot nuevo sin pisar el anterior—, no como algo que este cambio resuelve por su cuenta.

### 6. La autorización reutiliza la de consulta de Licitaciones, sin permiso nuevo

El endpoint no expone nada que quien puede ver la ficha no pueda ya ver: es el mismo documento público que el enlace "Mercado Público" abre en otra pestaña. Se autoriza con la misma habilidad que gobierna hoy la consulta de Licitaciones (`viewAny` sobre `LicitacionMercadoPublico`, como hace el `pdf` de la OC), sin partir el permiso existente.

## Risks / Trade-offs

- **El postback depende del HTML y del ViewState de un ASP.NET que no controlamos** — es más frágil que el regex de la OC, porque no depende de un patrón sino de un formulario completo. → Mitigación: el service falla de forma explícita y registrada en cada paso (ficha sin el botón `descargar_pdf`, respuesta que no es `application/pdf`, error de red), dejando `solicitud_api_externa` con estado y mensaje, y devuelve un error legible al usuario en vez de romperse. Los tests cubren los tres modos de falla con `Http::fake`.
- **Dos viajes de red por captura (~245 KB de bajada + ~66 KB de subida + hasta 446 KB de bajada)** — es una operación lenta para una petición web sincrónica. → Mitigación: la caché de la decisión 3 hace que el costo se pague una sola vez por código. Si aun así resulta molesto en la primera captura, el paso siguiente natural es moverlo a un Job — y en ese caso aplica la lección de `HARNESS_IA.md` §13 sobre `--timeout` explícito en `queue:listen`.
- **El PDF cacheado envejece** (decisión 5) → Aceptado a conciencia: envejecer es la propiedad que se busca en una evidencia fechada. El escape es la acción explícita de recaptura, anotada como trabajo futuro.
- **Se acumulan PDFs en el disco privado**, ~300-450 KB cada uno, uno por licitación consultada → A la escala real del sistema (11 snapshots en total hoy) esto es irrelevante. Se anota el criterio para revisarlo: si el directorio pasa el orden de los cientos de megabytes, corresponde una política de retención, que es una conversación de operación y no de este cambio.
- **La descarga la dispara un usuario, no un job** → El `trabajo_integracion` queda asociado al usuario autenticado y se cierra dentro de la misma petición, así que no puede quedar huérfano en `en_progreso` por el modo de falla de `queue:listen`. Si más adelante se mueve a un Job, ahí sí hay que revisar `expirarSiEsHuerfano()` y `WithoutOverlapping::expireAfter()`.

## Open Questions

Ninguna bloqueante. La política de recaptura (decisión 5) queda deliberadamente fuera de alcance y anotada como el siguiente cambio natural si el uso real la pide.

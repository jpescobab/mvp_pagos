## Context

`AccionesEncabezadoFichaMercadoPublico` (creado en el change `2026-07-06-actualizar-ficha-oc-mercado-publico`) renderiza hoy tres acciones en el encabezado de la ficha de OC: "Ver JSON" (funcional), y "Ver PDF"/"Mercado Público" (deshabilitadas con tooltip "Disponible próximamente"), porque en ese momento no había forma de verificar un enlace externo confiable sin encontrar el token opaco `qs=` del portal antiguo.

Se investigó ahora con tres fuentes cruzadas:
1. Se llamó a la API real de Mercado Público (`https://api.mercadopublico.cl/servicios/v1/publico/ordenesdecompra.json`, con el ticket ya presente en `.env`) — confirmó que el JSON de respuesta no incluye ningún campo de enlace/URL/PDF.
2. Se descargó y analizó el bundle JS del buscador público (`buscador.mercadopublico.cl`) — reveló que su propio botón "Ver" arma la URL `<origin>/ficha?code=<código>` (`n.searchParams.append("code",e)` sobre `Tp.summary === "/ficha"`), y que no existe ninguna función de exportación a PDF por OC individual en ese bundle (solo un PDF legal no relacionado y exportación a Excel de listados completos).
3. El usuario aportó `https://www.mercadopublico.cl/PurchaseOrder/Modules/PO/DetailsPurchaseOrder.aspx?codigoOC=<código>` — se verificó con `curl` que responde HTTP 200, el HTML contiene el código de la OC consultada y el texto "Orden de Compra", y que la página incluye un botón nativo `input#imgPDF` ("Descargar PDF").

Esta última URL cubre el caso "Mercado Público" (ver el detalle oficial). Para "Ver PDF", el usuario pidió explícitamente que descargue el PDF de forma directa (sin el paso intermedio de hacer clic en el botón nativo de esa página). Se investigó más a fondo:

4. El HTML de `DetailsPurchaseOrder.aspx?codigoOC=<código>` contiene, en el botón nativo `input#imgPDF`, un atributo `onclick="open(&#39;PDFReport.aspx?qs=<token>&#39;,...)"` — un token que, verificado con dos fetchs consecutivos para el mismo código, es **estable/determinístico por OC** (no depende de sesión ni de un timestamp).
5. Se verificó con `curl` que `https://www.mercadopublico.cl/PurchaseOrder/Modules/PO/PDFReport.aspx?qs=<token>` responde el PDF binario directamente (`Content-Type: image/pdf`, `Content-Disposition: attachment; filename=<código>.pdf`, HTTP 200), como una petición completamente nueva y sin cookies previas — es decir, no depende de sesión ni de un flujo de dos pasos en el navegador del usuario.

Como el navegador no puede leer el HTML de `mercadopublico.cl` desde nuestro frontend (CORS), la resolución del token debe hacerse desde el backend, reutilizando la capa de integraciones ya existente en este dominio (`IntegracionExternaService`, `SistemaExterno` `MERCADO_PUBLICO`), igual que la consulta de la API JSON de OC.

## Goals / Non-Goals

**Goals:**
- Reemplazar el estado deshabilitado de "Ver PDF" y "Mercado Público" por acciones reales.
- "Mercado Público" abre en una pestaña nueva el detalle oficial de la OC en `mercadopublico.cl`.
- "Ver PDF" descarga el PDF directamente: el backend resuelve el token real desde la página pública de Mercado Público y redirige al navegador al PDF binario.
- Mantener "Ver JSON" sin cambios.

**Non-Goals:**
- No se usa el buscador nuevo (`buscador.mercadopublico.cl/ficha?code=`) para "Mercado Público": se prefiere la misma URL ASPX ya verificada, por simplicidad.
- No se persiste ni se snapshotea el HTML scrapeado ni el PDF descargado: no es un dato de negocio que el sistema gobierne, es solo una redirección de conveniencia hacia el propio Mercado Público. Se registra la `solicitud_api_externa` (trazabilidad de que se consultó Mercado Público), pero no se crea un `snapshot_datos_externos`.
- No se implementa un proxy que descargue y reenvíe los bytes del PDF a través de nuestro servidor: se usa una redirección HTTP (`302`) hacia la URL de Mercado Público, más simple y sin costo de ancho de banda propio.

## Decisions

1. **"Mercado Público" sigue siendo un enlace externo directo** (`<a target="_blank">`) a `https://www.mercadopublico.cl/PurchaseOrder/Modules/PO/DetailsPurchaseOrder.aspx?codigoOC=<código>`, codificando el código con `encodeURIComponent`.
2. **"Ver PDF" apunta a un endpoint propio** `GET /adquisiciones/ordenes-compra-mercado-publico/pdf?codigo=<código>` (nueva ruta, requiere el mismo permiso `adquisiciones.consultar_orden_compra_mp` que el resto del dominio) que:
   - Llama a `OrdenCompraMercadoPublicoService::resolverUrlPdf(string $codigo): ?string`.
   - Ese método hace un `Http::get()` a `DetailsPurchaseOrder.aspx?codigoOC=<código>`, extrae el token del atributo `onclick` de `#imgPDF` con una expresión regular estricta (solo caracteres de base64: `[A-Za-z0-9+/=]+`), y arma la URL final concatenando con una constante base propia (`https://www.mercadopublico.cl/PurchaseOrder/Modules/PO/`) — nunca se redirige a una URL tomada literalmente de la respuesta externa, solo a una construida a partir de un token validado por regex, evitando cualquier riesgo de open-redirect.
   - Registra la solicitud como `solicitud_api_externa` (exitosa si se encontró el token, no encontrada si el HTML no trae el botón de PDF o la OC no existe en Mercado Público), reutilizando `IntegracionExternaService`, igual que las demás consultas del dominio.
   - El controlador redirige (`302`) a la URL resuelta; si no se pudo resolver, vuelve atrás con un mensaje de error explícito.
3. **Enlaces renderizados como `<a>`/redirección de servidor, no `window.open` imperativo**, para que el navegador maneje la apertura/descarga de forma nativa.
4. **`AccionesEncabezadoFichaMercadoPublico` recibe `codigo: string` como prop obligatoria nueva** junto al `payloadCrudo` existente, en vez de inferir el código desde `payloadCrudo` (que puede ser `null`/`undefined` cuando no hay snapshot, pero el código de la OC sí está siempre disponible en los tres lugares donde se usa el componente).

## Risks / Trade-offs

- [Riesgo] Mercado Público podría cambiar el HTML de `DetailsPurchaseOrder.aspx` o el patrón del botón `imgPDF` (es un portal ASP.NET WebForms antiguo, sin API oficial para esto) → Mitigación: `resolverUrlPdf` retorna `null` de forma explícita si el patrón no matchea (en vez de fallar silenciosamente o construir una URL inválida), y el usuario recibe un mensaje de error claro en vez de una descarga rota.
- [Riesgo] Esta resolución depende de scraping de una página HTML externa, no de la API oficial de Mercado Público → Mitigación: es exactamente el escenario que el harness contempla para scraping como respaldo cuando no hay API suficiente («API primero» + Playwright/scraping solo como respaldo autorizado); aquí basta un `Http::get()` simple, sin necesidad de automatización de navegador.
- [Trade-off] Se usa una redirección `302` en vez de proxear los bytes del PDF: más simple y sin costo de ancho de banda propio, a cambio de que la URL final visible en el navegador sea la de `mercadopublico.cl` en vez de la de nuestra app — aceptable porque el archivo es público y la app nunca reemplaza a Mercado Público como fuente.

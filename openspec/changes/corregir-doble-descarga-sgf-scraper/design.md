## Context

`services/sgf-playwright/sgf-scraper.js` es el microservicio Node que automatiza SGF vía Playwright. `descargarDocumentosDeFila()` (líneas ~611-735) descarga los PDF adjuntos de un proceso: por cada documento, hace clic en su ícono de descarga, lo cual abre un **popup** del navegador que ya descarga el PDF exitosamente (Chromium lo renderiza inline). El código actual, en vez de usar esa descarga ya exitosa, vuelve a pedir la misma URL con `page.context().request.get(pdfUrl)` — una segunda petición HTTP independiente de la navegación del popup.

Esa segunda petición devuelve `417 Expectation Failed`. La causa más probable: la URL trae un `access_token` de un solo uso, ya consumido por la navegación del popup. El código actual lanza un `Error` con ese fallo, sin `try/catch` alrededor — la excepción se propaga hacia arriba a través de tres funciones que lo llaman en cadena (`descargarDocumentosDeFila` → `procesarFilaProceso` → el `for` de filas de `importarPendientes()`/`importarGrupoPagoOperaciones()`), abortando toda la importación masiva. En la corrida real del 2026-07-29 esto costó 65 procesos completos por un solo documento.

El mensaje de error también interpola `pdfUrl` completa (con el `access_token`) en texto plano, quedando expuesto en `trabajos_integracion.error` (Laravel) y en los logs del microservicio.

## Goals / Non-Goals

**Goals:**
- Eliminar la segunda petición HTTP: usar la respuesta de red que la navegación del popup ya generó.
- Que un documento individual fallido no aborte el resto de la importación (ni el resto de documentos del mismo proceso, ni los demás procesos del lote).
- Que ningún mensaje de error/log de descarga contenga el `access_token` (o cualquier parámetro de credencial de un solo uso) en texto plano.
- Corregir la codificación de nombres de archivo (UTF-8 leído como Latin-1).

**Non-Goals:**
- No se cambia el contrato HTTP entre Laravel y el microservicio (`ConectorSgfPlaywrightService`, payload de `importarPendientes()`/`importarGrupoPagoOperaciones()`).
- No se agrega reintento automático de un documento fallido — queda como fallo puntual reportado, reintentable manualmente en una corrida posterior (ídem al patrón ya usado para huérfanos: no reinventar automatismos no pedidos).
- No se toca `verificarCaso()` (verificación puntual, síncrona) más allá de que reutiliza la misma función corregida `descargarDocumentosDeFila()` — se beneficia del fix sin cambio adicional.
- No se puede verificar este fix con una corrida real contra SGF real en esta sesión (regla de `CALIBRACION.md`: nunca credenciales reales del lado del asistente) — la verificación real la ejecuta el usuario.

## Decisions

### 1. Capturar la respuesta de red del popup en vez de refetchear
Se registra el listener de respuesta (`popup.on('response', ...)` o `popup.waitForEvent('response', ...)`) **inmediatamente después de que `page.waitForEvent('popup')` resuelve**, antes de `await popup.waitForLoadState('domcontentloaded')` — así se evita la carrera de tiempos donde la navegación del popup ya disparó su respuesta antes de que el código empiece a escucharla. Se usa esa respuesta ya obtenida (`response.body()`) en vez de `page.context().request.get(pdfUrl)`.

Alternativa descartada: mantener el refetch pero agregar headers/token manualmente — se descarta porque la causa raíz (token de un solo uso ya consumido) no se resuelve agregando headers; cualquier segunda petición seguiría fallando.

### 2. Resiliencia en dos niveles: por documento y por proceso
Se envuelve en `try/catch`:
- El cuerpo del `for` de documentos dentro de `descargarDocumentosDeFila()` (línea ~664): un documento que falla se registra como un paso (`pasos.push(paso(...))`) en estado `error` con el detalle, se omite del array `documentos` que se retorna, y el `for` continúa con el siguiente documento del mismo proceso.
- La llamada a `procesarFilaProceso()` dentro de los `for` de filas de `importarPendientes()` e `importarGrupoPagoOperaciones()`: si un proceso completo falla (no solo un documento), se registra el error en `pasos` y el lote continúa con el siguiente proceso, en vez de abortar toda la importación.

Esto deja el resultado de una importación masiva como éxito parcial cuando corresponda — el llamador Laravel (`ConectorSgfPlaywrightService`/`CasoPagoProveedorImporter`) ya trabaja fila por fila (upsert por `sgf_id`) y no asume atomicidad de todo el lote, así que no requiere cambios.

Alternativa descartada: abortar todo si cualquier documento falla, pero mostrar un resumen más claro del error — se descarta porque no resuelve el problema real (perder 65 procesos por 1 documento sigue siendo inaceptable, sin importar cuán claro sea el mensaje).

### 3. Enmascarar credenciales de un solo uso en cualquier mensaje de error
Se agrega un helper `enmascararUrl(url)` que reemplaza el valor de `access_token` (y cualquier otro parámetro de query que luzca como token/credencial) por `***` antes de interpolar la URL en cualquier `Error`, `console.error`, o campo que termine en `trabajos_integracion.error`. Se aplica en los dos sitios que hoy interpolan `pdfUrl` cruda (línea ~693 y ~707-712) y en cualquier log nuevo que se agregue como parte de este fix.

### 4. Codificación de nombres de archivo
Se corrige la lectura del nombre de archivo (columna `Nombre` de la tabla de documentos) para decodificar correctamente UTF-8 en vez de asumir Latin-1 — mismo patrón que cualquier otro punto del código que ya maneje texto con tildes/ñ desde el DOM de SGF (si existe un helper de normalización de texto ya usado en otra parte de `sgf-scraper.js` o `selectors.js`, reutilizarlo en vez de crear uno nuevo).

## Risks / Trade-offs

- [Riesgo] Registrar el listener de respuesta inmediatamente tras `page.waitForEvent('popup')` puede seguir teniendo una ventana de carrera si Chromium dispara la respuesta antes de que el listener se registre síncronamente → Mitigación: usar `popup.waitForEvent('response', predicate)` en vez de `.on()` con captura manual, que Playwright diseñó específicamente para este caso (engancha el listener antes de resolver la promesa de espera); si en la calibración real contra SGF esto sigue fallando, hay que instrumentar con logs de timing para decidir un enfoque distinto (ej. interceptar la request antes del clic con `page.route()`).
- [Riesgo] Sin poder correr contra SGF real en esta sesión, el fix se valida solo con la lectura del código y el razonamiento sobre la causa raíz (417 = token de un solo uso, no problema de sesión) → Mitigación: el usuario ejecuta la verificación real con sus credenciales una vez aplicado; si el 417 persiste con el nuevo enfoque, es señal de que la causa raíz es otra y hay que recalibrar (documentarlo en `CALIBRACION.md` como ya es la convención del proyecto).
- [Riesgo] Al no abortar más por un documento fallido, un proceso puede terminar importado con documentos incompletos sin que nadie lo note de inmediato → Mitigación: el paso de error queda registrado en `pasos` (visible en `ejecucion_automatizacion_navegador` del lado Laravel), consultable; no se silencia el fallo, solo se deja de propagar como abort total.

## Migration Plan

Cambio acotado a un archivo (`sgf-scraper.js`), sin migración de base de datos ni cambio de contrato. Desplegar reiniciando el microservicio `sgf-playwright` (`services/sgf-playwright/server.js`) tras el cambio. Sin plan de rollback especial más allá de revertir el commit si la calibración real revela un problema.

## Open Questions

- ¿El nombre de archivo con codificación rota afecta también a otros puntos del scraper (ej. `extraerDatosFila()`) o es exclusivo de la columna `Nombre` de la tabla de documentos? A confirmar durante `/opsx:apply` leyendo el resto del archivo por si hay un patrón repetido que convenga corregir en un solo lugar.

## Why

El conector Playwright de SGF (`services/sgf-playwright/sgf-scraper.js`) refetchea con una segunda petición HTTP un PDF que el popup del navegador ya descargó exitosamente, y esa segunda petición devuelve `417 Expectation Failed` (probablemente porque la URL trae un `access_token` de un solo uso ya consumido). Ese error no está protegido por `try/catch` dentro del `for` de documentos de una importación masiva, así que un solo documento con este problema aborta la corrida completa — en una prueba real del 2026-07-29 se perdieron 65 procesos completos por un único PDF fallido. El mismo mensaje de error expone además el `access_token` completo en texto plano, quedando en `trabajos_integracion.error` y en los logs.

## What Changes

- El scraper deja de refetchear el PDF por HTTP: captura la respuesta de la descarga original (la que ya dispara el popup) vía `page.context().waitForEvent('response', ...)` en el mismo `Promise.all` del clic, en vez de volver a pedir la URL.
- El procesamiento de cada documento dentro de una importación masiva queda envuelto en `try/catch`: un documento fallido se registra como error de ese documento puntual y la corrida continúa con los siguientes, en vez de abortar toda la importación.
- Cualquier mensaje de error que incluya la URL de descarga enmascara el `access_token` (o cualquier parámetro de credencial de un solo uso) antes de registrarse en `trabajos_integracion.error` o en logs.
- Se corrige la codificación de nombres de archivo (UTF-8 leído como Latin-1, ej. "Garantía" → "GarantÃ­a") antes de usarlos como ruta en disco.

## Capabilities

### New Capabilities
(ninguna)

### Modified Capabilities
- `conector-sgf-playwright`: la importación masiva de casos SGF deja de abortarse por completo cuando un documento individual falla al descargarse — continúa con el resto y reporta el fallo puntual; ningún mensaje de error de descarga expone el `access_token` en texto plano.

## Impact

- `services/sgf-playwright/sgf-scraper.js`: cambia el mecanismo de descarga del PDF (líneas ~670-700) y el manejo de errores del bucle de documentos de la importación masiva.
- No afecta el lado Laravel (`ConectorSgfPlaywrightService`, `IntegracionExternaService`, `CasoPagoProveedorImporter`) más allá de que un `trabajo_integracion` de importación masiva ahora puede terminar con éxito parcial en vez de error total.
- Sin cambios de esquema de base de datos ni de contrato HTTP entre Laravel y el microservicio.
- No se puede verificar con una corrida real contra SGF real dentro de esta sesión — el usuario debe ejecutarla con sus propias credenciales (regla de `CALIBRACION.md`: nunca ingresar credenciales reales del lado del asistente).

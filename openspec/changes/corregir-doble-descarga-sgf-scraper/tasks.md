## 1. Descarga sin refetch

- [x] 1.1 En `descargarDocumentosDeFila()` (`services/sgf-playwright/sgf-scraper.js`), reemplazado `page.context().request.get(pdfUrl)` por la captura de la respuesta de red que ya genera la navegación del popup (`popup.waitForEvent('response')` registrado antes de `waitForLoadState('domcontentloaded')`).
- [x] 1.2 La validación de firma `%PDF-` (`bytesParecenPdf`) y el guardado a disco (`writeFile`) siguen igual, ahora sobre `respuesta.body()` de la respuesta capturada en vez de un refetch.

## 2. Resiliencia por documento y por caso

- [x] 2.1 El cuerpo del `for` de documentos dentro de `descargarDocumentosDeFila()` queda envuelto en `try/catch`: un documento que falla registra un paso `descargar_documento_{sgfId}_{n}` en estado `error`, se omite del array `documentos`, y el `for` sigue con el siguiente documento.
- [x] 2.2 La llamada a `procesarFilaProceso()` dentro de los `for` de filas de `importarPendientes()` e `importarGrupoPagoOperaciones()` queda envuelta en `try/catch`: un caso completo que falla registra un paso `procesar_fila_{pagina}_{n}` en estado `error` y el lote sigue con el siguiente caso.
- [x] 2.3 Confirmado: `verificarCaso()` no necesita el try/catch de nivel-caso (es un solo caso); ya se beneficia del fix de descarga (1) y del try/catch por documento (2.1) porque ambos reutilizan `descargarDocumentosDeFila()`.

## 3. Enmascarar credenciales de un solo uso

- [x] 3.1 Agregado el helper `enmascararUrl(url)` en `sgf-scraper.js`, que reemplaza por `***` cualquier parámetro de query cuyo nombre contenga `token`/`auth`/`key`/`secret`.
- [x] 3.2 Aplicado `enmascararUrl()` en los dos mensajes de error que interpolan `pdfUrl` (descarga fallida y contenido no-PDF) — ya no interpolan la URL cruda.

## 4. Codificación de nombres de archivo

- [ ] 4.1 **Investigado, sin fix aplicado.** Revisé cómo se lee la columna `Nombre` (`celdas[VER_DOCUMENTOS.columnaNombre]`, vía `allTextContents()` de Playwright) y no encontré ningún paso de decodificación explícita en el código actual (ni Latin-1↔UTF-8, ni un charset declarado incorrecto en las cabeceras del microservicio que lo explique de forma directa). El helper `normalizarTexto()` existente es para comparación (minúsculas + quitar tildes), no para corregir codificación. La corrección clásica de este mojibake (`Buffer.from(texto, 'latin1').toString('utf-8')`) es *destructiva* si se aplica a un nombre que ya está correctamente decodificado (un carácter UTF-16 legítimo como "í" no sobrevive ese round-trip) — aplicarla a ciegas, sin poder reproducir el defecto real, arriesga corromper nombres que hoy están bien. No se encontró evidencia reproducible en esta sesión (la observación original viene de una sesión previa sin cita de archivo/línea) — no aplico un fix sin poder confirmarlo, por la misma razón que el proyecto exige calibrar contra evidencia real en vez de adivinar (`CALIBRACION.md`).
- [ ] 4.2 No aplica mientras 4.1 quede sin fix — nada que confirmar.

## 5. Validación final

- [x] 5.1 Revisado el archivo completo: no se encontró ningún otro punto con el mismo patrón de codificación rota (ver nota de la tarea 4.1).
- [x] 5.2 Confirmado: `registrarPasos()` (`ConectorSgfPlaywrightService.php`) itera el array `pasos` sin asumir una cantidad ni forma fija — los pasos `error` nuevos (por documento/por caso) son aditivos y no rompen nada. Ningún consumidor Laravel asume una cantidad fija de `documentos`/`payload_crudo`.
- [x] 5.3 Verificación manual del usuario contra SGF real (`SGF_MODO=real`), corrida el 2026-09 — el usuario confirmó "no vi nada extraño" (sin 417, sin importación abortada, sin token expuesto). No se capturó ningún nombre de archivo con codificación rota en esta corrida, así que la tarea 4.1 sigue sin evidencia reproducible y queda diferida.
- [x] 5.4 No aplica — la verificación real (5.3) no reveló que el 417 persistiera; no hay hallazgo que documentar en `CALIBRACION.md`.

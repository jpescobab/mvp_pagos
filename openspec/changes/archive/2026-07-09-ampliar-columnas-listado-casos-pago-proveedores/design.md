## Context

El listado `/pago-proveedores/casos` (`CasoPagoProveedorController::index()`) renderiza hoy 5 columnas (proveedor, RUT, monto, estado SGF, estado workflow) desde `casos_pago_proveedor`, tabla poblada por `CasoPagoProveedorImporter` a partir de `SnapshotDatosExterno::payload_normalizado`, que a su vez viene de `NormalizadorSgf` sobre el payload crudo que entrega el conector Playwright de SGF (`services/sgf-playwright/sgf-scraper.js`).

La bandeja real de SGF expone más columnas de las que el conector extrae hoy. `services/sgf-playwright/selectors.js` documenta en un comentario "VERIFICADO" el encabezado completo de la bandeja (incluye `folio egreso`, `numero`, `fecha sii`), pero `MAPEO_COLUMNAS_BANDEJA` solo mapea `sgf_id`, `grupo_actual`, `estado`, `observaciones` (con la clave SGF `observacion envio`), `grupo_remitente`, `periodo`, `rut`, `monto`. `NormalizadorSgf::normalizar()` reduce aún más ese conjunto: descarta `periodo` y `grupo_remitente` del payload normalizado. El resultado es que ninguno de los cinco campos que pide este change (`periodo`, `observacion`, `folio_egreso`, `numero`, `fecha_sii`) llega hoy al modelo `CasoPagoProveedor`, aunque tres de ellos (`periodo`, `observacion` bajo la clave `observaciones`) sí se capturan del DOM y se pierden después, y dos (`folio_egreso`, `numero`, `fecha_sii`) ni siquiera se leen del DOM.

## Goals / Non-Goals

**Goals:**
- Capturar `folio_egreso`, `numero` y `fecha_sii` desde la bandeja SGF (nuevas columnas del DOM).
- Propagar `periodo` y `observacion` (ya capturados) a través del normalizador sin perderlos.
- Persistir los cinco campos como columnas propias de `casos_pago_proveedor`, siguiendo el mismo patrón ya usado para `rut_proveedor`/`monto`/`sgf_status`.
- Exponer los campos nuevos en `CasoPagoProveedorResource` y renderizarlos en el listado con el patrón de "listado denso".

**Non-Goals:**
- No se cambia el modelo de workflow ni el significado de `sgf_status`/`sgf_current_group_raw` (siguen siendo solo referencia externa).
- No se agrega backfill retroactivo para casos ya importados: quedan `null` hasta la próxima sincronización SGF.
- No se agregan filtros ni búsqueda al listado (fuera de alcance de este change; la spec ya documenta explícitamente que el listado no tiene filtros no soportados por el backend).
- No se cambia el mecanismo de captura (sigue siendo Playwright, no hay API SGF disponible para esto).

## Decisions

**1. Persistir los campos nuevos como columnas propias de `casos_pago_proveedor`, no solo leerlos del snapshot en cada request.**
Alternativa considerada: cargar `snapshotsSgf` en el índice y leer `payload_normalizado` en el Resource. Se descarta porque obliga a cargar y parsear JSON por cada fila del listado paginado (costo N+1 evitable) y porque rompe el patrón ya establecido: `rut_proveedor`, `monto`, `sgf_status` y `sgf_current_group_raw` ya viven como columnas propias del caso precisamente para que el listado no dependa del snapshot. Mantener esa consistencia es más barato de razonar que mezclar ambas estrategias.

**2. Extender `MAPEO_COLUMNAS_BANDEJA` y `extraerDatosFila()` con las claves SGF exactas ya documentadas en el comentario "VERIFICADO" de `selectors.js`.**
Las claves de columna en SGF son `"folio egreso"`, `"numero"`, `"fecha sii"` (minúsculas, con espacio, sin tilde según el comentario existente) — se usan tal cual, sin adivinar variantes, porque ya fueron verificadas manualmente contra la bandeja real (ver `services/sgf-playwright/CALIBRACION.md`).

**3. `NormalizadorSgf::normalizar()` deja de descartar `periodo` y agrega `observacion` (alias de `observaciones`), `folio_egreso`, `numero`, `fecha_sii` al payload normalizado.**
El campo se sigue llamando `observaciones` en el payload crudo del scraper (ya así, no se renombra ahí para no tocar más superficie de la necesaria) pero se normaliza a la clave `observacion` (singular) en el payload normalizado y en la columna del modelo, consistente con el nombre pedido por el usuario y con la convención de nombrar en español desde el modelo hacia afuera.

**4. `fecha_sii` se parsea a `date` nullable en la migración; si el conector entrega un formato no parseable, `CasoPagoProveedorImporter` guarda `null` en vez de fallar la importación completa.**
Alternativa descartada: fallar el `trabajo_integracion` completo si una fila trae `fecha_sii` no parseable. Se descarta porque un dato secundario mal formado en una fila no debe bloquear la importación de todas las demás filas de la corrida; el caso igual se crea/actualiza con el resto de sus campos.

**5. En el frontend, `Observación` se trunca con `truncate` + `title` (tooltip nativo), no con un componente de tooltip enriquecido nuevo.**
Consistente con el patrón de "listado denso" ya usado en `resources/js/pages/maestros/cfinancieros/index.tsx` para columnas secundarias truncadas.

## Risks / Trade-offs

- [Riesgo] Los nombres exactos de columna en el DOM de SGF podrían diferir levemente de lo documentado en el comentario "VERIFICADO" (mayúsculas, acentos, espacios) → Mitigación: `sgf-scraper.js` ya normaliza encabezados (lower-case, trim) antes de hacer el match contra `MAPEO_COLUMNAS_BANDEJA`, igual que para las columnas existentes; se valida en pruebas manuales contra SGF real antes de dar por cerrada la tarea del conector (no hay entorno de staging de SGF para automatizar este chequeo).
- [Riesgo] `fecha_sii` puede venir en un formato de fecha distinto al esperado (`dd-mm-yyyy` vs `yyyy-mm-dd`) → Mitigación: parseo tolerante con `Carbon::createFromFormat` envuelto en try/catch, `null` si falla, sin excepción propagada.
- [Trade-off] Los casos importados antes de este change no tendrán estos campos hasta la próxima sincronización SGF → Aceptado explícitamente en el proposal; el frontend muestra `"—"`.

## Migration Plan

1. Migración Laravel: agregar columnas nullable `periodo` (string), `observacion` (text), `folio_egreso` (string), `numero` (string), `fecha_sii` (date) a `casos_pago_proveedor`.
2. Actualizar `$fillable` y (si aplica) `$casts` de `CasoPagoProveedor`.
3. Ampliar `MAPEO_COLUMNAS_BANDEJA` en `selectors.js` y el objeto de retorno de `extraerDatosFila()` en `sgf-scraper.js`.
4. Ampliar `NormalizadorSgf::normalizar()`.
5. Ampliar `CasoPagoProveedorImporter` para persistir los campos nuevos al crear/actualizar.
6. Ampliar `CasoPagoProveedorResource::toArray()`.
7. Actualizar `resources/js/types/pago-proveedores.ts` (`CasoPagoProveedor`).
8. Rediseñar `resources/js/pages/pago-proveedores/casos/index.tsx` con las columnas nuevas.
9. Actualizar fixtures/tests de `CasoPagoProveedorImporterTest` y agregar cobertura para los campos nuevos (incluyendo `fecha_sii` no parseable → `null`).
10. Actualizar `openspec/specs/pago-proveedores-sgf/spec.md` y `openspec/specs/paginas-pago-proveedores/spec.md` al archivar.

No requiere rollback especial: es aditivo (columnas nullable, campos nuevos en Resource/frontend); revertir es simplemente revertir la migración y los archivos tocados.

## Open Questions

- Ninguna bloqueante. Queda pendiente verificar contra SGF real (no automatizable en este entorno) que los encabezados `"folio egreso"`, `"numero"`, `"fecha sii"` coincidan exactamente antes de dar por buena la extracción — se deja como paso explícito en tasks.md.

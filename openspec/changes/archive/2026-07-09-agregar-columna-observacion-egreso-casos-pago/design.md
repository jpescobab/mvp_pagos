## Context

El listado `/pago-proveedores/casos` y `casos_pago_proveedor` ya conservan `observacion` (del encabezado SGF `"observacion envio"`, capturado como `observaciones` en el payload crudo del scraper) desde el change archivado `2026-07-09-ampliar-columnas-listado-casos-pago-proveedores`. Ese mismo cambio dejó documentado en `services/sgf-playwright/selectors.js` (comentario "VERIFICADO", encabezados reales completos de la Bandeja) que existe además una columna `"observacion"` (plana, sin sufijo) y otra `"observacion finalizado"`, ninguna de las dos capturada hoy. Una captura de pantalla real de la Bandeja confirma que la columna `"observacion"` trae valores cortos tipo `"EGRESO-115"`/`"EGRESO 115"` — distinto en forma y contenido de `"observacion envio"` (texto libre largo describiendo el trámite). El conector no extrae esta columna, `NormalizadorSgf` no la conoce, y no existe ninguna columna en `casos_pago_proveedor` para persistirla.

## Goals / Non-Goals

**Goals:**
- Capturar la columna SGF `"observacion"` (plana) desde la Bandeja, sin confundirla con `"observacion envio"` ni `"observacion finalizado"`.
- Persistirla como columna propia de `casos_pago_proveedor`, siguiendo el mismo patrón ya usado para `observacion`/`folio_egreso`/`numero`/`fecha_sii`.
- Exponerla en `CasoPagoProveedorResource` y mostrarla en el listado denso existente.

**Non-Goals:**
- No se toca la captura ni el significado de `observacion` (ya mapeada a `"observacion envio"`) ni se agrega `"observacion finalizado"` (fuera de alcance de este change, sin un pedido concreto para ese campo).
- No se agrega backfill retroactivo: casos ya importados quedan con el campo nuevo en `null` hasta la próxima sincronización SGF.
- No se cambia el mecanismo de captura (Playwright, sin API SGF disponible para esto).

## Decisions

**1. Nuevo campo `observacion_egreso`, no reutilizar ni renombrar `observacion`.**
La columna SGF `"observacion"` (plana) y `"observacion envio"` son dos columnas reales distintas de la misma tabla, con contenido de naturaleza distinta (una es un código corto tipo referencia de egreso, la otra es texto libre largo). Reutilizar el nombre `observacion` ya tomado causaría una colisión de significado; se usa `observacion_egreso` porque el contenido observado (`"EGRESO-115"`) sugiere que es la referencia al egreso asociado a esa fila, y evita ambigüedad con el campo ya existente.

**2. Mapeo exacto por el encabezado `"observacion"` (sin acento, minúscula, como ya lo documenta el comentario "VERIFICADO" de `selectors.js`), confiando en el match por igualdad exacta ya implementado en `indiceColumna()`.**
`indiceColumna()` (`sgf-scraper.js`) ya prioriza igualdad exacta de encabezado sobre coincidencia por substring — así es como hoy conviven sin confundirse `"rut"` vs `"rut pago"` y `"observacion envio"` vs `"observacion"` en la extracción de `grupo_actual`/`estado`. Agregar `observacion_egreso: ['observacion']` a `MAPEO_COLUMNAS_BANDEJA` reutiliza esa misma garantía sin tocar la lógica de matching.
Alternativa descartada: usar coincidencia por substring (`"observacion"` matchearía también `"observacion envio"` y `"observacion finalizado"`) — ya descartada por el diseño existente de `indiceColumna()`, no hace falta reintroducirla acá.

**3. `NormalizadorSgf::normalizar()` agrega `observacion_egreso` al payload normalizado, con el mismo tratamiento `trimONull()` que el resto de los campos de texto opcionales.**
Consistente con `observacion`/`folio_egreso`/`numero`: SGF no distingue "sin dato" de string vacío, así que ambos casos se normalizan a `null`.

**4. En el frontend, se agrega como columna secundaria truncada (`truncate` + `title`), igual patrón que la columna `Observación` ya existente.**
Consistente con "listado denso" (`openspec/specs/tema-visual-layout/spec.md`) y con la Decisión 5 del change de referencia.

## Risks / Trade-offs

- [Riesgo] El nombre exacto del encabezado `"observacion"` en el DOM real podría diferir levemente (acento, mayúscula, espacio extra) de lo documentado → Mitigación: `sgf-scraper.js` ya normaliza encabezados (`normalizarTexto()`: lower-case, NFD, trim) antes del match; se valida en una sesión supervisada contra SGF real antes de cerrar la tarea del conector (no automatizable en este entorno).
- [Riesgo] El significado real de `"EGRESO-115"` podría no ser "referencia al egreso" sino algo distinto no evidente solo por el nombre de columna → Mitigación: el campo se persiste y se muestra tal cual, como texto de referencia externa sin interpretación ni validación de formato — si el significado resulta ser otro, renombrar la columna es un cambio de bajo costo (migración + rename) en un change posterior.
- [Trade-off] Los casos importados antes de este change no tendrán `observacion_egreso` hasta la próxima sincronización SGF → Aceptado explícitamente; el frontend muestra `"—"`.

## Migration Plan

1. Migración Laravel: agregar columna nullable `observacion_egreso` (string) a `casos_pago_proveedor`.
2. Actualizar `$fillable` de `CasoPagoProveedor`.
3. Agregar `observacion_egreso: ['observacion']` a `MAPEO_COLUMNAS_BANDEJA` en `selectors.js`, y el campo correspondiente en el objeto de retorno de `extraerDatosFila()` en `sgf-scraper.js`.
4. Ampliar `NormalizadorSgf::normalizar()`.
5. Ampliar `CasoPagoProveedorImporter` para persistir `observacion_egreso` al crear/actualizar.
6. Ampliar `CasoPagoProveedorResource::toArray()`.
7. Actualizar `resources/js/types/pago-proveedores.ts` (`CasoPagoProveedor`).
8. Agregar la columna nueva a `resources/js/pages/pago-proveedores/casos/index.tsx`.
9. Actualizar fixtures/tests de `CasoPagoProveedorImporterTest` y `ConectorSgfPlaywrightServiceTest` para cubrir el campo nuevo.
10. Actualizar `openspec/specs/pago-proveedores-sgf/spec.md` y `openspec/specs/paginas-pago-proveedores/spec.md` al archivar.

No requiere rollback especial: es aditivo (columna nullable, campo nuevo en Resource/frontend); revertir es revertir la migración y los archivos tocados.

## Open Questions

- Ninguna bloqueante. Queda pendiente verificar contra SGF real (sesión supervisada, no automatizable en este entorno) que el encabezado `"observacion"` coincide exactamente y que el contenido observado se comporta como referencia de egreso — se deja como paso explícito en tasks.md.

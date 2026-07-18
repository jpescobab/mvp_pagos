## Context

El importador de casos de pago desde SGF ya normaliza y persiste varios campos de referencia de la Bandeja (`rut`, `monto`, `estado`, `periodo`, `folio_egreso`, `numero`, `fecha_sii`, `observacion_egreso`) en `casos_pago_proveedor`, dejando el payload crudo/normalizado en `snapshots_datos_externos` como evidencia. La cadena es: `sgf-scraper.js` (Playwright) → `NormalizadorSgf` → `IntegracionExternaService::registrarSnapshot()` → `CasoPagoProveedorImporter::importarDesdeSnapshot()` (upsert por `sgf_id`).

Paralelamente existe el "Traspaso" que se ve en la UI del caso: es un `RegistroContableCgu` (`registros_contables_cgu.numero_registro`), **ingreso manual** vía `RegistroContableCguController::store` (CRUD directo con `registrado_por` + auditoría, sin pasar por workflow). `ListoParaEgresoResolver` exige al menos un `RegistroContableCgu` para que el caso avance a egreso.

El número de traspaso ya existe en SGF (columna "N° traspaso" de la Bandeja), pero el scraper no lo captura hoy (el encabezado está documentado en un comentario de `selectors.js` pero no mapeado).

## Goals / Non-Goals

**Goals:**
- Capturar "N° traspaso" desde SGF y poblarlo automáticamente en el caso, siguiendo el mismo patrón que los demás campos de referencia SGF.
- Que el traspaso importado satisfaga el requisito de "listo para egreso" sin ingreso manual.
- Que la vista del caso muestre el traspaso de SGF como vigente y degrade el ingreso manual a corrección puntual.
- Preservar el valor de SGF como evidencia (snapshot) y no perder trazabilidad ante correcciones.

**Non-Goals:**
- Backfill retroactivo de casos ya importados / re-scraping masivo (el usuario eligió "solo de aquí en adelante").
- Avanzar automáticamente el estado del workflow al importar (la transición `registrar_en_cgu` sigue siendo humana).
- Eliminar la tabla/flujo `registros_contables_cgu` (se conserva para correcciones manuales).
- Capturar fecha del traspaso desde SGF (la grilla solo entrega número + monto).

## Decisions

### D1 — El número de SGF se guarda como campo de referencia del caso (`sgf_numero_traspaso`), no dentro del registro contable interno

Se agrega una columna escalar `sgf_numero_traspaso` (string, nullable) a `casos_pago_proveedor`, poblada por el importer igual que `folio_egreso`/`numero`. **Alternativa descartada:** auto-crear/actualizar un `RegistroContableCgu` desde el import. Se descarta porque ese registro es un acto interno con `registrado_por` y auditoría; poblarlo desde SGF mezclaría origen con gobierno interno (el harness lo prohíbe: "SGF es origen, no gobierno... nunca se mezclan") y obligaría a inventar `registrado_por = Sistema`, fecha y monto. La columna de referencia mantiene SGF como evidencia y el `RegistroContableCgu` como acto humano, separados.

### D2 — `ListoParaEgresoResolver` acepta el traspaso de SGF O el registro manual

El guard pasa de "exige `registrosContablesCgu` no vacío" a "exige `registrosContablesCgu` no vacío **o** `sgf_numero_traspaso` no nulo". Así el traspaso importado desbloquea el egreso sin ingreso manual, y los casos históricos con registro manual siguen válidos. **Alternativa descartada:** reemplazar por completo la condición por `sgf_numero_traspaso`, lo que rompería casos ya resueltos manualmente y quitaría el fallback de corrección.

### D3 — La UI muestra el traspaso de SGF como vigente; el manual pasa a corrección

`show.tsx` deriva el "traspaso vigente" así: si existe un `RegistroContableCgu` manual (corrección), ese manda; si no, se muestra `sgf_numero_traspaso` con etiqueta "desde SGF" (solo-lectura). El formulario "Registrar Traspaso" se re-encuadra como "Corregir traspaso", gateado por `pago_proveedores.registrar_cgu`. Una corrección crea un `RegistroContableCgu` manual que se muestra por encima del valor de SGF **sin borrarlo** (regla del harness: ninguna corrección borra evidencia previa).

### D4 — Nombre `sgf_numero_traspaso` con prefijo `sgf_`

Se elige el prefijo `sgf_` para desambiguar del `numero_registro` del registro contable manual (que la UI etiqueta "N.º de Traspaso"). Coherente con los campos de origen SGF (`sgf_id`, `sgf_status`, `sgf_current_group_raw`).

### D5 — Captura en el scraper por header de texto, reutilizando el mecanismo existente

Se agrega la entrada `numero_traspaso: ['n° traspaso']` a `MAPEO_COLUMNAS_BANDEJA` y se añade el campo al objeto que retorna `extraerDatosFila()`. El match exacto de `indiceColumna()` evita colisión con `monto`. No se introduce parseo especial: el normalizador usa `trimONull()` como con los demás strings.

## Risks / Trade-offs

- [El scraper real requiere credenciales SGF y corrida supervisada por humano] → La captura contra SGF real no es automatizable por el asistente; se agrega un valor de ejemplo al stub de `server.js` para validar la cadena PHP→UI en modo stub, y la verificación contra SGF real ocurre en la primera importación supervisada.
- [Reimport sobrescribe con valor fresco de SGF, incluso `null`] → Es el comportamiento consistente con los demás campos de referencia; una corrección manual (`RegistroContableCgu`) no se ve afectada porque vive en otra tabla y sigue satisfaciendo el resolver.
- [Colisión conceptual "Traspaso" SGF vs manual confunde a usuarios] → Se mitiga con etiqueta explícita "desde SGF" en la UI y el re-encuadre del formulario manual como "Corregir".
- [El encabezado real de SGF podría variar en tildes/formato] → `normalizarTexto()` ya normaliza acentos; se mapea la forma normalizada `n° traspaso` y la calibración real lo confirma en la primera corrida supervisada.

## Migration Plan

1. Migración aditiva: `sgf_numero_traspaso` nullable en `casos_pago_proveedor` (sin backfill; casos previos quedan en `null`).
2. Desplegar cambios de scraper, normalizador, importer, resolver, resource y UI juntos.
3. Rollback: la columna es nullable y aditiva; revertir código deja la columna huérfana sin efecto (o se elimina con una migración `down`). Ningún dato existente se altera.

## Open Questions

_(ninguna — decisiones de alcance ya resueltas con el usuario: reemplazar el manual + solo de aquí en adelante.)_

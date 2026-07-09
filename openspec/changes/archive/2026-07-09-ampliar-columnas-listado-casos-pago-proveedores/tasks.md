## 1. Conector Playwright SGF

- [x] 1.1 Ampliar `MAPEO_COLUMNAS_BANDEJA` en `services/sgf-playwright/selectors.js` con `folio_egreso: ['folio egreso']`, `numero: ['numero']`, `fecha_sii: ['fecha sii']`
- [x] 1.2 Ampliar el objeto devuelto por `extraerDatosFila()` en `services/sgf-playwright/sgf-scraper.js` para incluir `folio_egreso`, `numero`, `fecha_sii`
- [ ] 1.3 Verificar manualmente contra la bandeja SGF real (no automatizable en este entorno) que los encabezados `"folio egreso"`, `"numero"`, `"fecha sii"` coinciden exactamente antes de dar por cerrada la extracción; ajustar si difieren

## 2. Normalización

- [x] 2.1 Ampliar `NormalizadorSgf::normalizar()` (`app/Services/Sgf/NormalizadorSgf.php`) para incluir `periodo`, `observacion` (mapeado desde `observaciones`), `folio_egreso`, `numero`, `fecha_sii` en el payload normalizado
- [x] 2.2 Actualizar el test de `NormalizadorSgf` (si existe) o crear cobertura para los campos nuevos — no existe test unitario dedicado; cobertura cubierta end-to-end por los tests de `CasoPagoProveedorImporterTest` (4.5-4.7), que ejercitan el payload normalizado completo

## 3. Modelo y migración

- [x] 3.1 Crear migración que agregue a `casos_pago_proveedor`: `periodo` (string, nullable), `observacion` (text, nullable), `folio_egreso` (string, nullable), `numero` (string, nullable), `fecha_sii` (date, nullable)
- [x] 3.2 Actualizar `$fillable` de `CasoPagoProveedor` con los campos nuevos
- [x] 3.3 Agregar cast `fecha_sii` a `date` en `CasoPagoProveedor`

## 4. Importador

- [x] 4.1 Ampliar `CasoPagoProveedorImporter` para persistir `periodo`, `observacion`, `folio_egreso`, `numero`, `fecha_sii` al crear un caso nuevo
- [x] 4.2 Ampliar `CasoPagoProveedorImporter` para actualizar esos mismos campos al reimportar un `sgf_id` existente
- [x] 4.3 Parsear `fecha_sii` de forma tolerante (try/catch), guardando `null` si el formato no es parseable, sin fallar la importación completa
- [x] 4.4 Actualizar fixtures de `tests/Feature/PagoProveedores/CasoPagoProveedorImporterTest.php` (función `crearSnapshotSgf()`) con los campos nuevos
- [x] 4.5 Agregar test: reimportar un `sgf_id` actualiza `periodo`/`observacion`/`folio_egreso`/`numero`/`fecha_sii` sin alterar el estado del `Proceso`
- [x] 4.6 Agregar test: payload normalizado sin alguno de los campos nuevos deja `null` en el caso sin fallar la importación
- [x] 4.7 Agregar test: `fecha_sii` con formato no parseable se guarda como `null` sin fallar la importación

## 5. Resource y tipos TS

- [x] 5.1 Ampliar `CasoPagoProveedorResource::toArray()` (`app/Http/Resources/PagoProveedores/CasoPagoProveedorResource.php`) con `periodo`, `observacion`, `folio_egreso`, `numero`, `fecha_sii`
- [x] 5.2 Ampliar la interfaz `CasoPagoProveedor` en `resources/js/types/pago-proveedores.ts` con los campos nuevos (nullable)

## 6. Frontend

- [x] 6.1 Rediseñar la tabla de `resources/js/pages/pago-proveedores/casos/index.tsx` siguiendo el patrón de "listado denso" (`openspec/specs/tema-visual-layout/spec.md`, referencia `resources/js/pages/maestros/cfinancieros/index.tsx`) con columnas: ID (sgf_id), Periodo, Observación (truncada con tooltip), Folio egreso, RUT, Nombre proveedor (avatar+iniciales), Número, Fecha SII (formateada), Monto, Estado SGF, Estado workflow — RUT se muestra como sub-línea bajo el nombre del proveedor en la columna principal, siguiendo el mismo patrón ya usado para código/RUT en `cfinancieros`/`ccostos`
- [x] 6.2 Aplicar fallback `"—"` en las columnas nuevas cuando el valor es `null`
- [x] 6.3 Ocultar progresivamente columnas secundarias en breakpoints chicos, consistente con el patrón de listado denso

## 7. Specs y validación

- [x] 7.1 Correr `composer test` (incluye `config:clear`, `lint:check`, `types:check`, `php artisan test`)
- [x] 7.2 Correr `npm run lint:check` y `npm run types:check`
- [x] 7.3 Verificar el listado en el navegador (dev server) con casos que tengan y no tengan los campos nuevos poblados — verificado; se detectó y corrigió un desfase de un día en `formatFecha` (parseo UTC de fechas `date`-only) durante la verificación
- [ ] 7.4 `/opsx:archive` para fusionar las specs delta en `openspec/specs/pago-proveedores-sgf/spec.md` y `openspec/specs/paginas-pago-proveedores/spec.md`

## 1. Conector Playwright SGF

- [x] 1.1 Agregar `observacion_egreso: ['observacion']` a `MAPEO_COLUMNAS_BANDEJA` en `services/sgf-playwright/selectors.js`
- [x] 1.2 Ampliar el objeto devuelto por `extraerDatosFila()` en `services/sgf-playwright/sgf-scraper.js` para incluir `observacion_egreso`
- [x] 1.3 Verificar manualmente contra la Bandeja SGF real (sesión supervisada, no automatizable en este entorno) que el encabezado `"observacion"` coincide exactamente y que el contenido se comporta como referencia de egreso; ajustar `selectors.js` si difiere — VERIFICADO 2026-07-09: corrida real (`trabajo_integracion` #141/#143) trajo `observacion_egreso` = `"EGRESO-115"`/`"EGRESO 115"` para los 6 casos reales, coincidiendo con la captura original del usuario; sin ajustes necesarios

## 2. Normalización

- [x] 2.1 Ampliar `NormalizadorSgf::normalizar()` (`app/Services/Sgf/NormalizadorSgf.php`) para incluir `observacion_egreso` (con `trimONull()`) en el payload normalizado

## 3. Modelo y migración

- [x] 3.1 Crear migración que agregue a `casos_pago_proveedor`: `observacion_egreso` (string, nullable)
- [x] 3.2 Actualizar `$fillable` de `CasoPagoProveedor` con el campo nuevo

## 4. Importador

- [x] 4.1 Ampliar `CasoPagoProveedorImporter` para persistir `observacion_egreso` al crear un caso nuevo
- [x] 4.2 Ampliar `CasoPagoProveedorImporter` para actualizar `observacion_egreso` al reimportar un `sgf_id` existente
- [x] 4.3 Actualizar fixtures de `tests/Feature/PagoProveedores/CasoPagoProveedorImporterTest.php` (función `crearSnapshotSgf()`) con el campo nuevo
- [x] 4.4 Agregar test: reimportar un `sgf_id` actualiza `observacion_egreso` sin alterar el estado del `Proceso`
- [x] 4.5 Agregar test: payload normalizado sin `observacion_egreso` deja `null` en el caso sin fallar la importación

## 5. Resource y tipos TS

- [x] 5.1 Ampliar `CasoPagoProveedorResource::toArray()` (`app/Http/Resources/PagoProveedores/CasoPagoProveedorResource.php`) con `observacion_egreso`
- [x] 5.2 Ampliar la interfaz `CasoPagoProveedor` en `resources/js/types/pago-proveedores.ts` con `observacion_egreso` (nullable)

## 6. Frontend

- [x] 6.1 Agregar la columna "Obs. egreso" a la tabla de `resources/js/pages/pago-proveedores/casos/index.tsx`, truncada con tooltip (mismo patrón que la columna "Observación" existente)
- [x] 6.2 Aplicar fallback `"—"` cuando `observacion_egreso` es `null`
- [x] 6.3 Ocultar la columna progresivamente en breakpoints chicos, consistente con el patrón de listado denso ya aplicado a las demás columnas secundarias

## 7. Specs y validación

- [x] 7.1 Correr `composer test` (incluye `config:clear`, `lint:check`, `types:check`, `php artisan test`)
- [x] 7.2 Correr `npm run lint:check` y `npm run types:check`
- [x] 7.3 Verificar el listado en el navegador (dev server) con casos que tengan y no tengan `observacion_egreso` poblado
- [x] 7.4 `/opsx:archive` para fusionar las specs delta en `openspec/specs/pago-proveedores-sgf/spec.md` y `openspec/specs/paginas-pago-proveedores/spec.md`

## Why

El número de traspaso (comprobante del registro contable CGU) hoy se tipea a mano en cada caso de pago, aunque **ya existe en SGF** como la columna "N° traspaso" de la Bandeja (penúltima, junto a "Monto"). Es reingreso manual de un dato que el sistema de origen ya provee: propenso a error, lento, y desalineado con el resto de campos que sí se importan automáticamente desde SGF.

## What Changes

- El conector Playwright de SGF captura la columna **"N° traspaso"** de la Bandeja (hoy documentada en el scraper pero no extraída) y la propaga en el payload crudo de cada fila.
- Se agrega el campo de referencia **`sgf_numero_traspaso`** a `casos_pago_proveedor`, poblado por el importer al normalizar el payload SGF, igual que los demás campos de referencia (`folio_egreso`, `numero`, `fecha_sii`, `observacion_egreso`). El valor queda además en el snapshot crudo/normalizado como evidencia de origen.
- El caso se considera "con traspaso" (a efectos de `ListoParaEgresoResolver`) cuando tiene `sgf_numero_traspaso` **o** un registro contable manual — el traspaso importado satisface el requisito de avance a egreso sin intervención.
- En la vista del caso, el traspaso proveniente de SGF se muestra como el vigente (solo-lectura, "desde SGF"); el formulario manual "Registrar Traspaso" queda como **corrección puntual** gateada por permiso, no como ingreso primario.
- **Alcance:** solo importaciones de aquí en adelante. Sin backfill retroactivo ni re-scraping masivo de casos ya cargados.
- **No** se avanza el estado del workflow por la importación: poblar el traspaso es solo dato/evidencia; la transición `registrar_en_cgu` sigue siendo acción humana vía `TransicionWorkflowService`.
- **No** se inyecta el valor de SGF dentro del registro contable interno (`registros_contables_cgu`, que conserva `registrado_por`/auditoría para las correcciones humanas): SGF permanece como origen/evidencia, separado del acto interno.

## Capabilities

### New Capabilities

_(ninguna)_

### Modified Capabilities

- `conector-sgf-playwright`: el scraper de la Bandeja SGF SHALL capturar la columna "N° traspaso" y exponerla en el payload crudo de cada fila.
- `pago-proveedores-sgf`: el importer SHALL normalizar y persistir el número de traspaso de SGF en el caso (`sgf_numero_traspaso`), preservándolo en el snapshot; el resolver de "listo para egreso" SHALL aceptar el traspaso importado de SGF como equivalente a un registro contable manual.
- `paginas-pago-proveedores`: la vista del caso SHALL mostrar el traspaso importado de SGF como vigente (solo-lectura) y degradar el registro manual a corrección puntual gateada por permiso.

## Impact

- **Scraper (Node/Playwright)**: `services/sgf-playwright/selectors.js` (mapa de columnas), `services/sgf-playwright/sgf-scraper.js` (extracción de fila), `services/sgf-playwright/server.js` (stub de dev), `services/sgf-playwright/CALIBRACION.md`.
- **Backend (PHP)**: `app/Services/Sgf/NormalizadorSgf.php`, `app/Services/PagoProveedores/CasoPagoProveedorImporter.php`, `app/Services/PagoProveedores/ListoParaEgresoResolver.php`, `app/Models/CasoPagoProveedor.php`, `app/Http/Resources/PagoProveedores/CasoPagoProveedorResource.php`, nueva migración `add_sgf_numero_traspaso_to_casos_pago_proveedor_table`.
- **Frontend (React/Inertia)**: `resources/js/types/pago-proveedores.ts`, `resources/js/pages/pago-proveedores/casos/show.tsx`.
- **Tests (Pest)**: `tests/Unit/Sgf/NormalizadorSgfTest.php`, `tests/Feature/PagoProveedores/CasoPagoProveedorImporterTest.php`, y cobertura del resolver y de la serialización del caso.
- **Datos**: columna nueva nullable en `casos_pago_proveedor`; sin migración de datos retroactiva. Los casos ya importados quedan con `sgf_numero_traspaso = null` hasta que se reimporten por el flujo normal.
- **Sin cambios** en rutas, dependencias, ni en el motor de workflow.

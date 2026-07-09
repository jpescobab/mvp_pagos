## Why

La Bandeja de procesos de SGF expone una columna "Observación" (texto libre corto, ej. "EGRESO-115", "EGRESO 115") distinta de "Observación envío" (que ya se captura y se muestra hoy en el listado de casos de pago). El conector Playwright hoy ni siquiera extrae esa columna de la Bandeja, así que se pierde por completo antes de llegar al caso — sin corregir la captura, no hay dato que persistir ni mostrar.

## What Changes

- Ampliar el conector Playwright SGF (`services/sgf-playwright/selectors.js`, `sgf-scraper.js`) para extraer también la columna "Observación" (plana, distinta de "Observación envío") de la Bandeja de procesos.
- Ampliar `NormalizadorSgf` para propagar ese campo nuevo (`observacion_egreso`) al `payload_normalizado`.
- Agregar columna nullable `observacion_egreso` a `casos_pago_proveedor` (mismo patrón que `observacion`/`folio_egreso`/`numero`/`fecha_sii`).
- Ampliar `CasoPagoProveedorImporter` para persistir `observacion_egreso` al crear/actualizar un caso desde el payload normalizado.
- Ampliar `CasoPagoProveedorResource` para exponer `observacion_egreso` al frontend.
- Agregar la columna "Obs. egreso" (truncada con tooltip) al listado denso de `resources/js/pages/pago-proveedores/casos/index.tsx`, con fallback `"—"` cuando sea `null`.
- Actualizar el tipo TS `CasoPagoProveedor` (`resources/js/types/pago-proveedores.ts`) con el campo nuevo.
- Casos ya importados antes de este cambio quedan con `observacion_egreso` en `null` hasta el próximo ciclo de sincronización SGF.

## Capabilities

### New Capabilities

(ninguna — este change amplía capacidades existentes, no introduce un dominio nuevo)

### Modified Capabilities

- `pago-proveedores-sgf`: al crear o reimportar un `sgf_id`, además de los campos de referencia SGF ya conservados, se conserva también `observacion_egreso` cuando el payload normalizado del snapshot lo incluye.
- `paginas-pago-proveedores`: el requirement "Página de listado de casos de pago de proveedores" agrega la columna `observacion_egreso` a la lista de datos mostrados, con el mismo fallback `"—"` que el resto de los campos de referencia SGF opcionales.

## Impact

- **Backend**: `app/Services/Sgf/NormalizadorSgf.php`, `app/Services/PagoProveedores/CasoPagoProveedorImporter.php`, `app/Models/CasoPagoProveedor.php`, `app/Http/Resources/PagoProveedores/CasoPagoProveedorResource.php`, nueva migración sobre `casos_pago_proveedor`.
- **Conector Playwright**: `services/sgf-playwright/selectors.js`, `services/sgf-playwright/sgf-scraper.js`.
- **Frontend**: `resources/js/pages/pago-proveedores/casos/index.tsx`, `resources/js/types/pago-proveedores.ts`.
- **Specs**: `openspec/specs/pago-proveedores-sgf/spec.md`, `openspec/specs/paginas-pago-proveedores/spec.md`.
- **Datos existentes**: no requiere backfill; los casos importados antes de este cambio muestran `"—"` en la columna nueva hasta la próxima sincronización SGF.
- **Tests**: fixtures de `tests/Feature/PagoProveedores/CasoPagoProveedorImporterTest.php` y del conector Playwright deben actualizarse para cubrir el campo nuevo.

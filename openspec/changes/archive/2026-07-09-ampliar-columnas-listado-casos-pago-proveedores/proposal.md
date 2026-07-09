## Why

El listado de "Casos de pago de proveedores" (`/pago-proveedores/casos`) hoy solo muestra proveedor, RUT, monto, estado SGF y estado de workflow. Para triage y seguimiento diario, quien revisa la bandeja necesita ubicar un caso por los mismos datos que ve en la bandeja de procesos del SGF (id, periodo, folio de egreso, número, fecha SII, observación), sin tener que abrir cada caso o consultar SGF por separado. Además, el conector Playwright que importa procesos SGF hoy descarta parte de esos datos antes de que lleguen al caso: `folio_egreso`, `numero` y `fecha_sii` ni siquiera se extraen de la bandeja, y `periodo`/`observacion` se capturan pero se pierden en el normalizador. Sin corregir esa captura, no hay dato que mostrar en el listado.

## What Changes

- Ampliar el conector Playwright SGF (`services/sgf-playwright/selectors.js`, `sgf-scraper.js`) para extraer también `folio_egreso`, `numero` y `fecha_sii` de la bandeja de procesos, columnas que SGF ya expone pero que el scraper ignora hoy.
- Ampliar `NormalizadorSgf` para propagar `periodo`, `observacion`, `folio_egreso`, `numero` y `fecha_sii` al `payload_normalizado` (hoy descarta `periodo` y no contempla los tres campos nuevos).
- Agregar columnas nullable `periodo`, `observacion`, `folio_egreso`, `numero`, `fecha_sii` a `casos_pago_proveedor` (mismo patrón que `monto`/`rut_proveedor`/`sgf_status`: se guardan en el caso, no solo en el snapshot).
- Ampliar `CasoPagoProveedorImporter` para persistir esos campos nuevos al crear/actualizar un caso desde el payload normalizado.
- Ampliar `CasoPagoProveedorResource` para exponer los campos nuevos al frontend.
- Rediseñar la tabla de `resources/js/pages/pago-proveedores/casos/index.tsx` siguiendo el patrón de "listado denso" (`openspec/specs/tema-visual-layout/spec.md`) con columnas: ID (sgf_id), Periodo, Observación (truncada con tooltip), Folio egreso, RUT, Nombre proveedor, Número, Fecha SII, Monto, conservando Estado SGF y Estado workflow.
- Actualizar el tipo TS `CasoPagoProveedor` (`resources/js/types/pago-proveedores.ts`) con los campos nuevos.
- Casos ya importados antes de este cambio quedan con estos campos en `null` hasta el próximo ciclo de sincronización SGF; el frontend debe mostrar `"—"` como fallback.

## Capabilities

### New Capabilities

(ninguna — este change amplía capacidades existentes, no introduce un dominio nuevo)

### Modified Capabilities

- `pago-proveedores-sgf`: al reimportar un `sgf_id` existente, además de rut/monto/estado/grupo SGF, se actualizan `periodo`, `observacion`, `folio_egreso`, `numero` y `fecha_sii` del `caso_pago_proveedor` desde el payload normalizado del snapshot SGF.
- `paginas-pago-proveedores`: el requirement "Página de listado de casos de pago de proveedores" pasa de 4 columnas de datos a la lista completa (id, periodo, observación, folio de egreso, RUT, nombre proveedor, número, fecha SII, monto, estado SGF, estado workflow) siguiendo el patrón de listado denso.

Nota: la captura de campos adicionales en el conector Playwright de SGF (`services/sgf-playwright/`) y su normalización (`NormalizadorSgf`) son detalle de implementación de este mismo change (ver design.md y tasks.md) — no modifican ningún requirement de la capability transversal `integraciones-api-browser-automation`, que es agnóstica a los campos específicos de cada sistema externo.

## Impact

- **Backend**: `app/Services/Sgf/NormalizadorSgf.php`, `app/Services/PagoProveedores/CasoPagoProveedorImporter.php` (o equivalente que lo persiste), `app/Models/CasoPagoProveedor.php`, `app/Http/Resources/PagoProveedores/CasoPagoProveedorResource.php`, nueva migración sobre `casos_pago_proveedor`.
- **Conector Playwright**: `services/sgf-playwright/selectors.js`, `services/sgf-playwright/sgf-scraper.js`.
- **Frontend**: `resources/js/pages/pago-proveedores/casos/index.tsx`, `resources/js/types/pago-proveedores.ts`.
- **Specs**: `openspec/specs/pago-proveedores-sgf/spec.md`, `openspec/specs/integraciones-api-browser-automation/spec.md`, `openspec/specs/paginas-pago-proveedores/spec.md`.
- **Datos existentes**: no requiere backfill; los casos importados antes de este cambio muestran `"—"` en las columnas nuevas hasta la próxima sincronización SGF.
- **Tests**: fixtures de `tests/Feature/PagoProveedores/CasoPagoProveedorImporterTest.php` deben actualizarse para cubrir los campos nuevos.

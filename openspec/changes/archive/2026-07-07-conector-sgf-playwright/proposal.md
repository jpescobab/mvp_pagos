## Why

Hoy `ImportadorSgf`/`NormalizadorSgf` (`app/Services/Sgf/`) saben guardar una fila de SGF como snapshot inmutable, pero nadie les entrega esas filas: no existe ningún conector real que las obtenga desde SGF. SGF no expone API, así que la única vía es Playwright — y esta capa de evidencia bespoke (`importaciones_sgf`, `snapshots_sgf`, `snapshots_sgf_documentos`) se diseñó un día antes de que existiera la capa transversal de integraciones (`sistemas_externos`, `trabajos_integracion`, `snapshots_datos_externos`), que hoy ya está implementada, archivada y probada en producción por `OrdenCompraMercadoPublicoService`. Sin datos reales importados todavía, es el momento de cerrar esa brecha: construir el conector real (microservicio Playwright + contrato HTTP) y, de paso, alinear la evidencia de SGF a la misma capa transversal que ya usa Mercado Público, en vez de mantener dos sistemas de evidencia paralelos para siempre.

## What Changes

- Se agrega un `sistema_externo` `SGF` con mecanismo `playwright`, su `conector_automatizacion_navegador` autorizado explícitamente y su `perfil_autenticacion_navegador` (referencia a usuario/clave reales, sin guardar el secreto en la base de datos Laravel).
- Se define el contrato HTTP interno que Laravel espera del microservicio `services/sgf-playwright/` (autenticado con API key propia, nunca público) — el código de ese microservicio no se construye en este change.
- Se agrega **verificación puntual** de un caso SGF (por `sgf_id`), síncrona: el usuario con permiso dispara la consulta, el conector navega SGF vía el microservicio, y el resultado se muestra de inmediato.
- Se agrega **importación masiva bajo demanda**: el usuario con permiso dispara la importación de todos los casos pendientes en SGF, siempre vía Job en cola + polling en el frontend, sin importar cuántas filas resulten (no se conoce la cantidad hasta que el conector ya navegó).
- Cada corrida registra evidencia completa en la capa transversal: `trabajo_integracion` (mecanismo `playwright`), `ejecucion_automatizacion_navegador` con sus `pasos_automatizacion_navegador`, y un `snapshot_datos_externo` por fila obtenida.
- **BREAKING**: se retiran `ImportadorSgf`, `NormalizadorSgf`, y los modelos/tablas `ImportacionSgf`, `SnapshotSgf`, `SnapshotSgfDocumento` (sin datos reales que migrar, solo fixtures de test). La evidencia de SGF pasa a vivir en `snapshots_datos_externos`.
- Se agrega una tabla de unión genérica (`snapshots_datos_externos_documentos`) para vincular varios documentos del expediente a un mismo `snapshot_datos_externo` — hoy la tabla transversal solo soporta un `vinculable` polimórfico único, insuficiente porque SGF entrega varios documentos por fila.
- `CasoPagoProveedor::snapshotsSgf()` se reescribe para consultar `snapshots_datos_externos` (sistema externo SGF + `referencia_externa` = `sgf_id`) en vez de `SnapshotSgf`.
- El listado/detalle de importaciones (hoy `ImportacionSgfController` sobre `ImportacionSgf`) se reescribe sobre `trabajos_integracion`/`snapshots_datos_externos` filtrados por el sistema externo SGF.
- No incluye: disparo programado/scheduler (queda para una iteración futura; el alcance acordado es solo bajo demanda), ni el código interno del microservicio Node/Playwright.

## Capabilities

### New Capabilities
- `conector-sgf-playwright`: verificación puntual y importación masiva bajo demanda de casos SGF vía el conector Playwright, con su contrato HTTP hacia el microservicio, permisos, y registro de evidencia en la capa transversal de integraciones.

### Modified Capabilities
- `sgf-origen-snapshot`: la evidencia de SGF deja de guardarse en tablas propias (`importaciones_sgf`, `snapshots_sgf`, `snapshots_sgf_documentos`) y pasa a usar la capa transversal (`trabajos_integracion`, `snapshots_datos_externos`); se retiran `ImportadorSgf`/`NormalizadorSgf` en su forma actual.
- `pago-proveedores-sgf`: `CasoPagoProveedor::snapshotsSgf()` deja de apuntar a `SnapshotSgf` y pasa a consultar `snapshots_datos_externos` por sistema externo SGF y `referencia_externa`.
- `consulta-importaciones-sgf`: el listado/detalle de importaciones deja de basarse en `ImportacionSgf`/`snapshots_sgf` y pasa a mostrar `trabajos_integracion`/`snapshots_datos_externos` del sistema externo SGF.
- `integraciones-api-browser-automation`: se agrega el requisito de vincular varios documentos a un mismo `snapshot_datos_externo` (nueva tabla de unión), hoy limitado a un `vinculable` polimórfico único.

## Impact

- **Nuevos**: `App\Services\Sgf\ConectorSgfPlaywrightService` (reemplaza a `ImportadorSgf`, construido al estilo `OrdenCompraMercadoPublicoService` sobre `IntegracionExternaService`/`AutomatizacionNavegadorService`); Job de importación masiva + endpoint de polling; migraciones para `snapshots_datos_externos_documentos`; seeder del `sistema_externo` `SGF`; config `services.sgf_playwright.*` (+ `.env.example`); permisos `pago_proveedores.verificar_caso_sgf` y `pago_proveedores.importar_casos_sgf`.
- **Modificados**: `CasoPagoProveedor::snapshotsSgf()`, `routes/sgf.php`, `ImportacionSgfController` (reescrito sobre la capa transversal), páginas React `sgf/importaciones/*`.
- **Eliminados**: `app/Services/Sgf/ImportadorSgf.php`, `NormalizadorSgf.php`, modelos `ImportacionSgf`/`SnapshotSgf`/`SnapshotSgfDocumento` y sus migraciones, y los tests que ejercitan esas clases (se reemplazan por tests equivalentes sobre el nuevo servicio).
- **Fuera de este repo**: contrato HTTP que debe implementar `services/sgf-playwright/` (microservicio Node/Playwright), construido en una iteración aparte contra ese contrato.

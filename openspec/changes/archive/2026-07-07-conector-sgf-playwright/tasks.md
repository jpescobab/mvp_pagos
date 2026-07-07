## 1. Esquema: nueva tabla de unión y retiro de tablas bespoke

- [x] 1.1 Migración: crear `snapshots_datos_externos_documentos` (`snapshot_datos_externo_id` FK a `snapshots_datos_externos` cascadeOnDelete, `documento_id` FK a `documentos` restrictOnDelete, `created_at`).
- [x] 1.2 Migración: eliminar `snapshots_sgf_documentos` (con `down()` de reversión que recrea la tabla).
- [x] 1.3 Migración: eliminar `snapshots_sgf` (con `down()` de reversión).
- [x] 1.4 Migración: eliminar `importaciones_sgf` (con `down()` de reversión).
- [x] 1.5 Correr `php artisan migrate` en el entorno local y confirmar que no queden referencias huérfanas (foreign keys) hacia las tablas eliminadas.

## 2. Retiro de código bespoke SGF

- [x] 2.1 Eliminar `app/Models/ImportacionSgf.php`, `SnapshotSgf.php`, `SnapshotSgfDocumento.php`.
- [x] 2.2 Eliminar `app/Services/Sgf/ImportadorSgf.php`. **Ajuste sobre el plan**: `NormalizadorSgf.php` se conserva — su lógica (trim de campos, parseo de números chilenos) no depende de las tablas eliminadas y `ConectorSgfPlaywrightService` la reutiliza tal cual; eliminarla habría obligado a reescribir la misma lógica desde cero.
- [x] 2.3 Eliminar `app/Http/Controllers/Sgf/ImportacionSgfController.php` (se reemplaza en la sección 6).
- [x] 2.4 Eliminar los tests que ejercitan estas clases (`tests/Feature/Sgf/ImportadorSgfTest.php`, `ConsultarImportacionesSgfTest.php`), a reemplazar por los tests de las secciones 8-9. Los tests de `PagoProveedores` que construían fixtures `SnapshotSgf`/`ImportacionSgf` (`CasoPagoProveedorImporterTest`, `ApiPagoProveedoresTest`, `VinculoAdquisicionCasoPagoProveedorTest`, `DashboardTest`, `MostrarHistorialSnapshotsSgfTest`) se reescribieron in situ para construir `SnapshotDatosExterno` en su lugar.

## 3. Configuración y seeder

- [x] 3.1 Agregar bloque `sgf_playwright` a `config/services.php` (`base_url`, `api_key` desde `.env`), siguiendo el patrón exacto de `mercadopublico`.
- [x] 3.2 Agregar `SGF_PLAYWRIGHT_BASE_URL` y `SGF_PLAYWRIGHT_API_KEY` a `.env.example`.
- [x] 3.3 Actualizar `IntegracionesSeeder` para que el `sistema_externo` `SGF` ya sembrado use `updateOrCreate` por `codigo` y quede con `tipo_integracion: 'playwright'` (en vez de `manual`).
- [x] 3.4 Sembrar el `conector_automatizacion_navegador` de SGF (`SGF_PLAYWRIGHT`), inactivo/no autorizado por defecto, en `IntegracionesSeeder`.
- [x] 3.5 Agregar los permisos `pago_proveedores.verificar_caso_sgf` y `pago_proveedores.importar_casos_sgf` a `WorkflowPagoProveedoresSeeder`.

## 4. Servicio del conector SGF

- [x] 4.1 Crear `App\Services\Sgf\ConectorSgfPlaywrightService`, inyectando `IntegracionExternaService` y `AutomatizacionNavegadorService`, siguiendo el estilo de `OrdenCompraMercadoPublicoService`.
- [x] 4.2 Implementar `verificarCaso(string $sgfId): array` — valida que el conector de SGF esté autorizado (lanza si no), inicia `trabajo_integracion` (tipo `verificar_caso`, mecanismo `playwright`) y `ejecucion_automatizacion_navegador`, llama a `POST /casos/verificar` en `services.sgf_playwright.base_url` con header `X-Api-Key`, registra pasos, y si `encontrada` registra el `snapshot_datos_externo` (`metodo_captura: 'playwright'`, `referencia_externa: $sgfId`); finaliza trabajo/ejecución en ambos casos.
- [x] 4.3 Implementar `importarPendientes(TrabajoIntegracion $trabajo): void` — invocado desde el Job de la sección 6: llama a `POST /casos/importar-pendientes`, registra un `snapshot_datos_externo` por cada fila recibida vinculado al mismo `trabajo_integracion`, y registra los pasos de navegación.
- [x] 4.4 Manejar explícitamente errores HTTP/timeout del microservicio: no guardar snapshots parciales, finalizar el `trabajo_integracion`/`ejecucion_automatizacion_navegador` en estado `error` con el mensaje de la excepción (sin relanzar la excepción — el estado visible para el usuario vive en `trabajo_integracion`, no en el estado del Job de cola).
- [x] 4.5 Vincular documentos: si el payload de una fila incluye documentos, crear/resolver `Documento`/`VersionDocumento` y registrar el vínculo en `snapshots_datos_externos_documentos` (modelo `SnapshotDatosExternoDocumento`, nuevo) para cada snapshot creado.

## 5. Verificación puntual (síncrona)

- [x] 5.1 **Ajuste sobre el plan**: no se creó un Form Request separado. La verificación puntual toma un `CasoPagoProveedor` ya existente por route-model-binding (`sgf_id` sale del propio modelo, no del body), igual que `OrdenCompraMercadoPublicoController::verificar(OrdenCompraMercadoPublico $orden)` — no hay body que validar.
- [x] 5.2 `CasoPagoProveedorController::verificarSgf(CasoPagoProveedor $caso)` invoca `ConectorSgfPlaywrightService::verificarCaso()`, gateado por `Gate::authorize('verificarCasoSgf', CasoPagoProveedor::class)` (método agregado a `CasoPagoProveedorPolicy`).
- [x] 5.3 Ruta `POST pago-proveedores/casos/{caso}/verificar-sgf` en `routes/pago-proveedores.php` (no en `routes/sgf.php`, porque la acción está scoped a un caso existente de ese dominio).
- [x] 5.4 UI: botón "Verificar en SGF" en `pago-proveedores/casos/show.tsx`, muestra si se encontró o no el caso en SGF tras recargar la página con `verificacionSgf` como prop adicional.

## 6. Importación masiva (Job + polling)

- [x] 6.1 **Ajuste sobre el plan**: el `trabajo_integracion` lo crea el controlador (síncronamente, antes de encolar), no el Job — así el controlador puede devolverlo/redirigir a su detalle de inmediato sin esperar a que el worker de cola lo levante. `ImportarCasosPendientesSgfJob` (`ShouldQueue`, `WithoutOverlapping('sgf-importar-pendientes')`) recibe el `TrabajoIntegracion` ya creado y delega en `ConectorSgfPlaywrightService::importarPendientes()`.
- [x] 6.2 `ImportarCasosPendientesSgfController::store()` gateado por `Gate::authorize('importarCasosSgf', CasoPagoProveedor::class)`; si ya existe un `trabajo_integracion` de SGF tipo `importar_pendientes` en estado `en_progreso`, redirige a su detalle sin despachar un Job nuevo (con toast informativo); si no, crea el trabajo, despacha el Job y redirige a su detalle.
- [x] 6.3 **Ajuste sobre el plan**: no se construyó un endpoint JSON dedicado — el propio `sgf.importaciones.show` (Inertia) ya expone el estado del `trabajo_integracion`; el frontend lo recarga vía `usePoll` (Inertia v3), sin necesidad de una segunda ruta.
- [x] 6.4 Ruta `POST sgf/casos/importar-pendientes` en `routes/sgf.php`.
- [x] 6.5 UI: botón "Importar pendientes de SGF" en `sgf/importaciones/index.tsx`; `sgf/importaciones/show.tsx` usa `usePoll(2000, ..., {autoStart:false})` + `useEffect` para sondear mientras `estado === 'en_progreso'` y detenerse al llegar a `completado`/`error`, mostrando el mensaje de error si corresponde.

## 7. Reescritura de consumidores existentes

- [x] 7.1 Reescribir `CasoPagoProveedor::snapshotsSgf()` para consultar `SnapshotDatosExterno` filtrando por el `sistema_externo_id` de `SGF` (subquery por `codigo`) y `referencia_externa` = `sgf_id`, ordenado descendente.
- [x] 7.2 Reescribir `ImportacionSgfController` sobre `TrabajoIntegracion`/`SnapshotDatosExterno` filtrados por el sistema externo `SGF`, preservando las rutas Inertia (`sgf/importaciones/index`, `sgf/importaciones/show`) y su paginación. Ruta `show` ahora bindea por `{trabajoIntegracion}` en vez de `{importacionSgf}`.
- [x] 7.3 `App\Services\PagoProveedores\CasoPagoProveedorImporter::importarDesdeSnapshot()` reescrito para recibir `SnapshotDatosExterno` (usa `$snapshot->referencia_externa` en vez de `$snapshot->sgf_id`). `CasoPagoProveedorResource::mapSnapshotsSgf()` actualizado (campo `fuente` → `metodo_captura`, sin relación `importacion`). Corregido de paso un eager-load ya roto en `CasoPagoProveedorController::show()` (`snapshotsSgf.importacion.iniciadoPor`, relación que ya no existe) — ahora solo `snapshotsSgf` (sin relación adicional, `metodo_captura` es columna propia).

## 8. Tests

- [x] 8.1 `tests/Feature/Sgf/ConectorSgfPlaywrightServiceTest.php`: `verificarCaso()` encontrado, no encontrado, error del microservicio, conector no autorizado — con `Http::fake()`.
- [x] 8.2 Mismo archivo: `importarPendientes()` con varias filas, falla a mitad de camino sin snapshots parciales.
- [x] 8.3 `tests/Feature/PagoProveedores/VerificarCasoSgfTest.php` y `tests/Feature/Sgf/ImportarCasosPendientesSgfTest.php`: autorización (permiso requerido + `security_audit_logs` en denegación), respuesta síncrona vs. Job encolado (`Queue::fake()` + `Queue::assertPushed`/`assertNotPushed`, incluye el caso de "ya hay una importación en curso").
- [x] 8.4 `tests/Feature/Sgf/ConsultarImportacionesSgfTest.php` reescrito sobre `TrabajoIntegracion`/`SnapshotDatosExterno`.
- [x] 8.5 Cubierto por `tests/Feature/PagoProveedores/MostrarHistorialSnapshotsSgfTest.php` (reescrito en la sección 2) — ya ejercita `CasoPagoProveedor::snapshotsSgf()` contra `SnapshotDatosExterno`.
- [x] 8.6 Cubierto dentro de `ConectorSgfPlaywrightServiceTest.php` (`importarPendientes registra un snapshot por fila y vincula documentos entregados`): una fila con documentos y otra sin documentos en la misma corrida.

## 9. Validación final

- [x] 9.1 `vendor/bin/pint --dirty --format agent` — sin cambios pendientes.
- [x] 9.2 `composer test` — Pint, PHPStan/Larastan y la suite Pest completa (471 tests, 467 passed, 4 skipped preexistentes, 0 failed) en verde.
- [x] 9.3 `npm run lint:check` y `npm run types:check` — ambos sin errores.
- [x] 9.4 Verificado ANTES de correr las migraciones de eliminación (vía `database-query` de Laravel Boost): `importaciones_sgf`, `snapshots_sgf` y `snapshots_sgf_documentos` estaban vacías (0 filas) en la base de datos local — confirmado que no había datos reales que preservar.

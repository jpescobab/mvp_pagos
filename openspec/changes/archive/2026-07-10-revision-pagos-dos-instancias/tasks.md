## 1. Migraciones y modelos

- [x] 1.1 Migración: agregar `instancia` (string nullable) + índice a `validaciones_documento` (editada la migración original, sin ALTER separado); actualizar `ValidacionDocumento` (`fillable`, cast a `InstanciaRevision`).
- [x] 1.2 Migración: agregar `periodo`, `cfinanciero_id`, `generado_automaticamente` (nullable) + índice/foreign key a `egresos_cgu`; nueva tabla `revisiones_pago_instancia` + modelo `RevisionPagoInstancia`; enum `InstanciaRevision`; actualizar `EgresoCgu` (`fillable`, relación `cfinanciero()`, `jurisdiccionId()`).
- [x] 1.3 Accesores/relaciones derivadas: `CasoPagoProveedor` (`revisionesInstancia()`, `cfinancieroId()`); estado/instancia derivados del Egreso se resuelven en el `RevisionEgresoService`/Resource (grupo 3/5).
- [x] 1.4 Setup de datos para tests resuelto con helpers en `RevisionPagosTest` (`crearEscenarioRevision`, `usuarioConRol`) sobre los seeders reales; no se requieren factories nuevas.

## 2. Workflow (estados, transiciones, permisos, roles)

- [x] 2.1 `WorkflowPagoProveedoresSeeder`: estados `en_revision_finanzas` y `en_revision_zonal` (reemplazan `en_revision_documental`).
- [x] 2.2 `WorkflowPagoProveedoresSeeder`: transiciones `aprobar_finanzas`, `aprobar_zonal`, `devolver_a_finanzas`, `observar_finanzas`, `rechazar_finanzas`, `rechazar_zonal` con permisos, comentario obligatorio y documentos requeridos.
- [x] 2.3 Punto de entrada de revisión repunta a `en_revision_finanzas` (`iniciar_revision_documental`, `reenviar_revision`, `anular`). Tests afectados actualizados (Dashboard, ConsultarDefiniciones).
- [x] 2.4 `RolesAndPermissionsSeeder`: roles `jefe_finanzas`, `administrador_zonal` con sus permisos; `admin` recibe los `revisar_*` vía WorkflowPagoProveedoresSeeder.
- [x] 2.5 Sin datos productivos (proyecto en construcción): el reemplazo de `en_revision_documental` se resuelve con `migrate:fresh` + re-seed; no se requiere data migration.

## 3. Servicios de dominio

- [x] 3.1 `ValidacionDocumentoInstanciaService`: registra evento en `validaciones_documento` con `instancia`, motivo obligatorio en rechazo (vía Form Request); resuelve estado vigente por instancia y `todosAprobados`.
- [x] 3.2 Verificación de totales por instancia en `RevisionEgresoService` (tabla `revisiones_pago_instancia`): compara factura vs. recepción/OC vs. monto; expone coincidencia/diferencia.
- [x] 3.3 `RevisionEgresoService`: aprobar/rechazar/devolver pago individual y aprobar/devolver Egreso completo iterando casos vía `TransicionWorkflowService`, en transacción, validando precondiciones.
- [x] 3.4 `CasoPagoProveedorImporter`: agrupación automática en `EgresoCgu` por `folio_egreso` de SGF (refinado, ver design §6), acumulando monto y derivando cfinanciero cuando está vinculado, sin alterar `sgf_id`/snapshots/historial.

## 4. Autorización

- [x] 4.1 `EgresoCguPolicy`: métodos `revisar`/`revisarFinanzas`/`revisarZonal` con scope zonal; denegaciones auditadas por el `Gate::after` central en `security_audit_logs`.
- [x] 4.2 Asociación usuario↔jurisdicción resuelta vía `User → funcionario → cfinanciero → jurisdiccion_id` (documentado en design §5).

## 5. HTTP: controladores, rutas, requests, resources

- [x] 5.1 `RevisionPagosController` (`index`/`show`) + `RevisionEgresoPresenter` que entrega estado derivado, instancia activa, pagos, totales y documentos por instancia.
- [x] 5.2 `RevisionValidacionDocumentoController@store` + `ValidarDocumentoRevisionRequest` (motivo obligatorio en rechazo).
- [x] 5.3 `RevisionTransicionPagoController@store` y `RevisionTransicionEgresoController@store` + Form Requests (comentario obligatorio en devolución/rechazo).
- [x] 5.4 `RevisionTotalesController@store` (verificación de totales por instancia).
- [x] 5.5 Rutas registradas en `routes/pago-proveedores.php`.

## 6. Frontend (Inertia + React)

- [x] 6.1 `wayfinder:generate --with-form` ejecutado; helpers importados con nombre.
- [x] 6.2 Pantalla `revision/index.tsx` (listado de Egresos pendientes con estado/instancia) y `revision/show.tsx` (strip de pagos → documentos + panel de revisión).
- [x] 6.3 Panel de revisión: totales (coinciden/diferencia + verificar), aprobar/rechazar documento con motivo, aprobar/rechazar/devolver pago, aprobar/devolver Egreso.
- [x] 6.4 Acciones condicionadas por `egreso.puede_operar` (permiso + instancia activa); instancia no activa queda en modo lectura.
- [x] 6.5 Ítem de sidebar "Revisión de Pagos" gateado por `revisar_finanzas`/`revisar_zonal`.

## 7. Tests

- [x] 7.1 Flujo feliz: pago recorre `en_revision_finanzas` → `en_revision_zonal` → `lista_para_registro_cgu`.
- [x] 7.2 Devolución `en_revision_zonal` → `en_revision_finanzas` con comentario; rechazo/devolución sin comentario bloqueado (HTTP 422).
- [x] 7.3 Bloqueo de aprobación con documentos pendientes y/o totales sin verificar.
- [x] 7.4 Validación por instancia: aprobación de Finanzas no altera el estado para Zonal; historial conserva ambos eventos.
- [x] 7.5 Egreso avanza solo cuando todos sus pagos están aprobados; avance parcial rechazado.
- [x] 7.6 Scope zonal: `administrador_zonal` de otra jurisdicción recibe 403; denegación auditada en `security_audit_logs`.
- [x] 7.7 Agrupación automática al importar de SGF por `folio_egreso`; caso sin folio no crea Egreso.
- [x] 7.8 Autorización de transiciones por permiso (`revisar_finanzas`).

## 8. Validaciones y cierre

- [x] 8.1 Suite completa `php artisan test` en verde (509 passed, 4 skipped, 0 failed); PHPStan/Larastan (`composer types:check`) en verde.
- [x] 8.2 `npm run lint:check`, `format:check`, `types:check` en verde; `npm run build` compila el bundle de revisión.
- [x] 8.3 `openspec validate revision-pagos-dos-instancias --strict` en verde.
- [x] 8.4 Verificación funcional en navegador (tras `migrate:fresh --seed` + escenario demo): login OK, índice lista el egreso con instancia/estado, workbench renderiza pagos+documentos+totales+acciones, y el camino de escritura en vivo (aprobar documento → "Aprobado", verificar totales → "Totales verificados", gate recalcula → "Aprobar pago" habilitado y badge "Listo") funciona end-to-end.

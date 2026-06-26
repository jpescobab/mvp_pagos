## 1. Migraciones

- [x] 1.1 Crear migración `casos_pago_proveedor` (sgf_id string unique, proveedor_id FK nullable, rut_proveedor string, monto decimal nullable, sgf_status string nullable, sgf_current_group_raw string nullable)
- [x] 1.2 Crear migración `facturas` (caso_pago_proveedor_id FK, proveedor_id FK nullable, folio string, monto decimal, fecha_emision date)
- [x] 1.3 Crear migración `registros_contables_cgu` (caso_pago_proveedor_id FK, numero_registro string, fecha_registro date, monto decimal nullable, observaciones text nullable, registrado_por FK nullable)
- [x] 1.4 Crear migración `registros_pago_bancario` (caso_pago_proveedor_id FK, numero_operacion string, fecha_pago date, monto decimal nullable, banco string nullable, registrado_por FK nullable)
- [x] 1.5 Crear migración `egresos_cgu` (numero_egreso string unique, fecha date, monto_total decimal nullable, observaciones text nullable, registrado_por FK nullable)
- [x] 1.6 Crear migración `egresos_cgu_items` (egreso_cgu_id FK, caso_pago_proveedor_id FK, monto decimal nullable; unique(egreso_cgu_id, caso_pago_proveedor_id))

## 2. Modelos Eloquent

- [x] 2.1 Crear `CasoPagoProveedor` (belongsTo proveedor; `proceso()` como `MorphOne` vía `sujeto`; hasMany facturas/registrosContablesCgu/registrosPagoBancario)
- [x] 2.2 Crear `Factura` (belongsTo caso, belongsTo proveedor)
- [x] 2.3 Crear `RegistroContableCgu` y `RegistroPagoBancario` (belongsTo caso, belongsTo registradoPor)
- [x] 2.4 Crear `EgresoCgu` (hasMany items; `vinculosDocumento()` morphMany para respaldo documental) y `EgresoCguItem` (belongsTo egreso, belongsTo caso)

## 3. Workflow "pago_proveedores"

- [x] 3.1 Crear `WorkflowPagoProveedoresSeeder`: `DefinicionWorkflow` codigo `pago_proveedores` + 13 `EstadoWorkflow` (importada_desde_sgf es_inicial; cerrada/rechazada/anulada es_final) + 13 `TransicionWorkflow` según design.md decisión 7 (incluye `documentos_requeridos: ['FACTURA']` en `aprobar_documentacion` y `permiso_requerido` en transiciones sensibles)
- [x] 3.2 Registrar el seeder en `DatabaseSeeder`

## 4. Servicio de importación

- [x] 4.1 Crear `App\Services\PagoProveedores\CasoPagoProveedorImporter::importarDesdeSnapshot(SnapshotSgf $snapshot): CasoPagoProveedor` — crea caso + Proceso inicial si no existe (resolviendo `proveedor_id` por igualdad exacta de RUT); si ya existe, actualiza solo los campos de referencia SGF y el `monto` del Proceso, sin tocar su estado

## 5. Tests

- [x] 5.1 Test feature: importar un snapshot nuevo crea `caso_pago_proveedor` + `Proceso` en estado `importada_desde_sgf`
- [x] 5.2 Test feature: importar un snapshot de un `sgf_id` existente actualiza referencia SGF sin alterar el estado del proceso
- [x] 5.3 Test feature: el `proveedor_id` se resuelve cuando el RUT coincide con un proveedor existente, y queda `null` si no hay coincidencia
- [x] 5.4 Test feature: el seeder de workflow "pago_proveedores" permite ejecutar una transición real (`recibir_en_finanzas`) vía `TransicionWorkflowService`
- [x] 5.5 Test feature: `egresos_cgu_items` asocia un egreso a varios casos

## 6. Validación

- [x] 6.1 `composer lint:check`
- [x] 6.2 `composer types:check`
- [x] 6.3 `php artisan test --compact`

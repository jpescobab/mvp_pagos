## 1. Migraciones y modelo

- [x] 1.1 Migración `create_tipos_proceso_pago_table`: `id`, `codigo` (string(30), unique), `nombre` (string(150)), `activo` (boolean default true), timestamps
- [x] 1.2 Migración `add_tipo_proceso_pago_id_to_procesos_table`: `tipo_proceso_pago_id` (unsignedBigInteger nullable, FK real → `tipos_proceso_pago`, `restrictOnDelete`)
- [x] 1.3 Migración `add_tipo_proceso_pago_id_to_requisitos_documentales_table`: `tipo_proceso_pago_id` (unsignedBigInteger nullable, FK real → `tipos_proceso_pago`, `restrictOnDelete`)
- [x] 1.4 `app/Models/TipoProcesoPago.php`: `$fillable` (codigo, nombre, activo), cast `activo`, `hasMany(RequisitoDocumental)`
- [x] 1.5 `app/Models/Proceso.php`: agregar `tipo_proceso_pago_id` a `$fillable`, relación `tipoProcesoPago(): BelongsTo`
- [x] 1.6 `database/seeders/TiposProcesoPagoSeeder.php`: siembra `COMPRA`, `CONTRATO`, `CONVENIO`, `REEMBOLSO`, `ANTICIPO`, `OTRO`; registrar en `DatabaseSeeder.php` (antes de `RequisitosDocumentalesPagoProveedoresSeeder`)

## 2. Resolución del checklist por tipo de proceso

- [x] 2.1 `ResolutorChecklistDocumentalProceso::requisitosAplicables()` (`app/Services/Documentos/ResolutorChecklistDocumentalProceso.php:82-100`): agregar filtro `whereNull('tipo_proceso_pago_id')->orWhere('tipo_proceso_pago_id', $proceso->tipo_proceso_pago_id)`
- [x] 2.2 Test: un proceso con `tipo_proceso_pago_id` asignado ve solo los requisitos universales + los de su tipo
- [x] 2.3 Test: un proceso sin `tipo_proceso_pago_id` ve solo los requisitos universales
- [x] 2.4 Test: cambiar el tipo de un caso y volver a resolver el checklist refleja los nuevos requisitos sin duplicar ítems

## 3. Reescritura del seeder de requisitos con la matriz por tipo

- [x] 3.1 Reescribir `database/seeders/RequisitosDocumentalesPagoProveedoresSeeder.php`: `FACTURA` y `COMPROBANTE` quedan universales (`tipo_proceso_pago_id => null`, obligatorios); desactivar (`activo = false`, no borrar) las filas universales actuales de `ACTA_RECEP`, `CERT_VIGENCIA`, `RESOLUCION`, `ORDEN_COMPRA`, `CONTRATO`; crear las filas nuevas por tipo según la matriz confirmada en `design.md`
- [x] 3.2 Test: el seeder genera exactamente los requisitos esperados por tipo (spot-check COMPRA, CONTRATO, ANTICIPO, CONVENIO, OTRO) — `tests/Feature/PagoProveedores/RequisitosDocumentalesPagoProveedoresMatrizTest.php`
- [x] 3.3 Test: `FACTURA` sigue siendo obligatoria para `ANTICIPO` (universal, consistente con el gate de `aprobar_finanzas`/`aprobar_zonal`) — corrección respecto al plan original, ver `design.md`. También se corrigió el helper compartido `sembrarRequisitosDocumentalesPagoProveedores()` (`ChecklistDocumentalPagoProveedoresTest.php`), que no sembraba `TiposProcesoPagoSeeder` y ocultaba la matriz real; y se arregló la aserción de "no duplica items" que comparaba contra el total de filas en vez del checklist resuelto

## 4. Acción de clasificación (backend)

- [x] 4.1 `app/Http/Requests/PagoProveedores/ClasificarTipoProcesoPagoRequest.php`: `tipo_proceso_pago_id` required, `exists:tipos_proceso_pago,id` + `activo=true`
- [x] 4.2 `app/Policies/CasoPagoProveedorPolicy.php`: método `clasificarTipoProcesoPago(User $user, CasoPagoProveedor $caso): bool` → `$user->can('pago_proveedores.gestionar_caso')`
- [x] 4.3 `app/Http/Controllers/PagoProveedores/TipoProcesoPagoCasoPagoProveedorController.php::store()`: `Gate::authorize(...)`, `DB::transaction` con `$caso->proceso->update([...])` + `AuditLogger::log('caso_pago_proveedor.clasificar_tipo_proceso_pago', ...)`
- [x] 4.4 `routes/pago-proveedores.php`: `POST casos/{caso}/tipo-proceso-pago` → `casos.tipo-proceso-pago.store`
- [x] 4.5 Test: clasificar con el permiso requerido persiste el valor y registra auditoría con antes/después
- [x] 4.6 Test: usuario sin `pago_proveedores.gestionar_caso` recibe 403 y no se persiste el cambio
- [x] 4.7 Test: reclasificar un caso ya clasificado actualiza el valor y audita el cambio (+ test extra: rechaza tipo inactivo)

## 5. Exposición al frontend y UI de clasificación

- [x] 5.1 `app/Http/Resources/PagoProveedores/ProcesoResource.php`: exponer `tipo_proceso_pago_id` y `tipo_proceso_pago: {id, codigo, nombre}` (whenLoaded)
- [x] 5.2 `app/Http/Controllers/PagoProveedores/CasoPagoProveedorController.php::cargarDetalle()`: sumar `proceso.tipoProcesoPago` al `load()`; `show()`/`verificarSgf()` pasan prop `tiposProcesoPago` (lista activa)
- [x] 5.3 `resources/js/types/pago-proveedores.ts`: tipo `TipoProcesoPago`; `Proceso` gana `tipo_proceso_pago_id`/`tipo_proceso_pago`
- [x] 5.4 Correr `php artisan wayfinder:generate --with-form`
- [x] 5.5 `resources/js/pages/pago-proveedores/casos/show.tsx`: nueva sección "Tipo de proceso" (`<Select>`) antes del Checklist documental, `disabled` si `enRevision`, visible solo con permiso `pago_proveedores.gestionar_caso`
- [x] 5.6 Test Inertia: la página de detalle de caso incluye `tiposProcesoPago` y `caso.proceso.tipo_proceso_pago_id`

## 6. Renombrado "Traspaso" (Bloque B)

- [x] 6.1 `resources/js/pages/pago-proveedores/casos/show.tsx`: renombrar encabezado "Registro contable CGU" → "Registro contable CGU (Traspaso)", label "N.º de registro" → "N.º de Traspaso", botón "Registrar" → "Registrar Traspaso"

## 7. Verificación

- [x] 7.1 Correr `composer test` (lint:check, types:check, php artisan test) y `vendor/bin/pint --dirty --format agent` — 557 tests, 553 passed, 4 skipped preexistentes, Pint y PHPStan limpios
- [x] 7.2 `npm run build` + `npm run types:check` — ambos limpios
- [x] 7.3 Verificado en el navegador con `mmardoneso@pjud.cl` (administrativo_finanzas, caso 58/sgf_id 753): sin clasificar, el checklist mostraba solo Factura y Comprobante de Pago; al clasificar como "Contrato" el checklist se recalculó automáticamente agregando Contrato, Acta de Recepción y Certificado de Vigencia como obligatorios. Sin errores en consola

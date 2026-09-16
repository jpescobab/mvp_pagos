## 1. Base de datos

- [x] 1.1 Migración `create_tipos_compra_table`: `id`, `codigo` (string, unique), `nombre` (string), `activo` (boolean, default true), timestamps.
- [x] 1.2 Seeder `TipoCompraSeeder`: `firstOrCreate` por `codigo` para `BIENES`, `SERVICIOS_GENERALES`, `OBRAS` (idempotente, aditivo), registrado en `DatabaseSeeder.php` después de `RolesAndPermissionsSeeder`.
- [x] 1.3 Migración `create_detalles_factura_table`: `factura_id` (FK a `facturas`, `unique`, `cascadeOnDelete`), `tipo_compra_id` (FK a `tipos_compra`, `restrictOnDelete`), `ccosto_id` (FK a `ccostos`, `restrictOnDelete`), `cantidad` (decimal 14,2), `unidad_medida` (string, nullable), `monto` (decimal 14,2), timestamps.

## 2. Modelos

- [x] 2.1 Crear `app/Models/TipoCompra.php`: fillable `codigo`/`nombre`/`activo`, relación `detallesFactura(): HasMany`.
- [x] 2.2 Crear `app/Models/DetalleFactura.php`: fillable según migración, casts (`cantidad`/`monto` → `decimal:2`), relaciones `factura()` (BelongsTo), `tipoCompra()` (BelongsTo), `ccosto()` (BelongsTo).
- [x] 2.3 Agregar relación `detalle(): HasOne` en `app/Models/Factura.php`.

## 3. Catálogo Tipo de Compra (CRUD en Maestros)

- [x] 3.1 Crear `app/Http/Requests/Maestros/StoreTipoCompraRequest.php` y `UpdateTipoCompraRequest.php` (código único, nombre requerido), siguiendo `app/Http/Requests/Maestros/StoreTipoProcesoPagoRequest.php` como referencia.
- [x] 3.2 Crear `app/Http/Controllers/Maestros/TipoCompraController.php` (index/create/store/show/edit/update/destroy), siguiendo `TipoProcesoPagoController.php` como referencia — `destroy` rechazado si `detallesFactura()->exists()`.
- [x] 3.3 Crear `app/Http/Resources/Maestros/TipoCompraResource.php`.
- [x] 3.4 Rutas nuevas en `routes/maestros.php`, mismo bloque de rutas que `tipos-proceso-pago`.
- [x] 3.5 Crear `app/Policies/TipoCompraPolicy.php` (`viewAny`/`view`/`create`/`update`/`delete` mapeados a `pago_proveedores.administrar_tipos_compra`).
- [x] 3.6 Registrar `Gate::policy(TipoCompra::class, TipoCompraPolicy::class)` en `AppServiceProvider::configureAuthorization()`.
- [x] 3.7 Crear páginas `resources/js/pages/maestros/tipos-compra/{index,create,show,edit}.tsx` copiando la estructura de `maestros/tipos-proceso-pago/*.tsx`, y agregar la entrada correspondiente al menú de Maestros en el sidebar (condicionada al permiso `pago_proveedores.administrar_tipos_compra`).

## 4. Policy y permisos de Detalle de Factura

- [x] 4.1 Agregar método `registrarDetalleFactura(User $user, CasoPagoProveedor $caso): bool` a `app/Policies/CasoPagoProveedorPolicy.php` (mismo lugar que `registrarFactura`), chequeando únicamente `pago_proveedores.registrar_detalle_factura` — sin consultar `$caso->proceso`.
- [x] 4.2 Agregar `pago_proveedores.registrar_detalle_factura` y `pago_proveedores.administrar_tipos_compra` a `$permisos` en `database/seeders/WorkflowPagoProveedoresSeeder.php`; otorgar ambos a `admin` y a `administrativo_finanzas`.

## 5. Service

- [x] 5.1 Crear `app/Services/PagoProveedores/DetalleFacturaService.php`: método `registrar(Factura $factura, array $datos): DetalleFactura` dentro de `DB::transaction`, sin ninguna referencia a `TransicionWorkflowService` ni a `Proceso`.
- [x] 5.2 Método `actualizar(DetalleFactura $detalle, array $datos): DetalleFactura` en el mismo service.

## 6. Controlador y rutas

- [x] 6.1 ~~Agregar `show` a `FacturaController`~~ — decisión tomada en apply: se omite. Contradecía la 8.3 (sin página dedicada de Factura); nada del frontend la habría consultado, así que agregarla sería código muerto. `DetalleFacturaController::create/edit` cargan directamente lo que necesitan desde `Factura`.
- [x] 6.2 Crear `app/Http/Controllers/PagoProveedores/DetalleFacturaController.php` (`create`/`store`/`edit`/`update`) — controlador liviano, delega a `DetalleFacturaService`.
- [x] 6.3 Crear Form Requests `app/Http/Requests/PagoProveedores/StoreDetalleFacturaRequest.php` / `UpdateDetalleFacturaRequest.php`: `tipo_compra_id`/`ccosto_id` (`required`, `exists`), `cantidad`/`monto` (`required`, `numeric`, `min:0`), `unidad_medida` (`nullable`, `string`, `max:50`); en `Store...`, regla de closure sobre la `Factura` de la ruta para rechazar si ya tiene un `DetalleFactura` (unicidad 1:1).
- [x] 6.4 Rutas nuevas en `routes/pago-proveedores.php`: `GET facturas/{factura}/detalle-factura/crear` (`facturas.detalle-factura.create`), `POST facturas/{factura}/detalle-factura` (`facturas.detalle-factura.store`), `GET facturas/{factura}/detalle-factura/editar` (`facturas.detalle-factura.edit`), `PATCH facturas/{factura}/detalle-factura` (`facturas.detalle-factura.update`) — sin `facturas.show` (ver 6.1).

## 7. Resource

- [x] 7.1 Crear `app/Http/Resources/PagoProveedores/DetalleFacturaResource.php` (tipo de compra, centro de costo, cantidad, unidad de medida, monto).
- [x] 7.2 Extender `CasoPagoProveedorResource::mapFacturas()` con `tiene_detalle: bool` (basado en `$factura->detalle !== null`); cargar `facturas.detalle` en `CasoPagoProveedorController::cargarDetalle()`.

## 8. Frontend

- [x] 8.1 Agregar sección "Facturas" (nueva) a `resources/js/pages/pago-proveedores/casos/show.tsx`: tabla de `caso.facturas` (folio, monto, fecha de emisión), badge "Con detalle" / "Sin detalle" por fila, con link a crear (`detalle-factura.create`) o editar (`detalle-factura.edit`) el detalle según corresponda.
- [x] 8.2 Extender el tipo `Factura` en `resources/js/types/pago-proveedores.ts` con `tiene_detalle: boolean`.
- [x] 8.3 Crear `resources/js/pages/pago-proveedores/detalle-factura/create.tsx` y `edit.tsx`: `Select` de tipo de compra, `Select` de centro de costo, inputs de cantidad, unidad de medida (texto libre) y monto — mismo patrón visual/estado que `resources/js/pages/consumo-basico/create.tsx`.
- [x] 8.4 Regenerar Wayfinder (`php artisan wayfinder:generate --with-form`).

## 9. Tests

- [x] 9.1 `tests/Feature/PagoProveedores/RegistrarDetalleFacturaTest.php`: registro exitoso, validación de campos obligatorios, rechazo si la Factura ya tiene un `DetalleFactura` (unicidad 1:1), rechazo sin el permiso `pago_proveedores.registrar_detalle_factura` (con verificación de `security_audit_logs`).
- [x] 9.2 `tests/Feature/PagoProveedores/ActualizarDetalleFacturaTest.php`: edición exitosa de un `DetalleFactura` existente.
- [x] 9.3 `tests/Feature/PagoProveedores/DetalleFacturaSinEfectoWorkflowTest.php`: registrar/editar un `DetalleFactura` no dispara ninguna transición sobre el `Proceso` del `CasoPagoProveedor`; un caso con facturas sin detalle permite ejercer las transiciones normales del workflow sin bloqueo.
- [x] 9.4 `tests/Feature/Maestros/TipoCompraTest.php`: CRUD completo del catálogo, `destroy` rechazado cuando el tipo está en uso por algún `DetalleFactura`, rechazo de acciones sin el permiso `pago_proveedores.administrar_tipos_compra`.
- [x] 9.5 Confirmado que NO aplica (mismo precedente que Contratos/Consumo Básico): `RolesAndPermissionsSeederTest` no referencia ningún permiso `pago_proveedores.*`, solo permisos core.

## 10. Validación final

- [x] 10.1 `vendor/bin/pint --dirty --format agent` sobre los archivos PHP tocados.
- [x] 10.2 `composer test` (lint:check + types:check + suite Pest completa).
- [x] 10.3 `npm run lint:check` y `npm run types:check` sobre el frontend tocado.
- [ ] 10.4 Verificación manual en navegador: desde el detalle de un `CasoPagoProveedor` con al menos una `Factura`, registrar su detalle (tipo de compra, centro de costo, cantidad, monto), confirmar el badge "Con detalle", y confirmar que el estado del caso/workflow no cambió; crear/editar un `TipoCompra` desde Maestros y confirmar que aparece en el select del formulario de detalle.

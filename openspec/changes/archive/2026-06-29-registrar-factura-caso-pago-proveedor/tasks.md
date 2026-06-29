## 1. Permiso y autorización

- [x] 1.1 Agregar el permiso `pago_proveedores.registrar_factura` en `WorkflowPagoProveedoresSeeder` (mismo patrón que `pago_proveedores.vincular_adquisicion`) y asignarlo al rol `admin`.
- [x] 1.2 Agregar la ability `registrarFactura(User $user, CasoPagoProveedor $caso): bool` en `CasoPagoProveedorPolicy`, autorizando vía `$user->can('pago_proveedores.registrar_factura')`.

## 2. Backend: registrar factura

- [x] 2.1 Crear `App\Http\Requests\PagoProveedores\RegistrarFacturaRequest` con reglas: `folio` (required, string, max:255), `monto` (required, numeric), `fecha_emision` (required, date).
- [x] 2.2 Crear `App\Http\Controllers\PagoProveedores\FacturaController::store(CasoPagoProveedor $caso, RegistrarFacturaRequest $request)`: autoriza con `Gate::authorize('registrarFactura', $caso)`, crea la `Factura` dentro de `DB::transaction` (`caso_pago_proveedor_id`, `proveedor_id` tomado de `$caso->proveedor_id`, `folio`, `monto`, `fecha_emision`), registra `AuditLogger::log('caso_pago_proveedor.registrar_factura', ...)`, retorna `back()`.
- [x] 2.3 Registrar la ruta `POST pago-proveedores/casos/{caso}/facturas` en `routes/pago-proveedores.php` (`casos.facturas.store`), junto a las rutas de `registros-contables-cgu`/`registros-pago-bancario`.

## 3. Exponer facturas en el detalle del caso

- [x] 3.1 Agregar `'facturas'` a los eager loads de `CasoPagoProveedorController::show()`.
- [x] 3.2 Agregar `mapFacturas()` y la clave `facturas` (`whenLoaded('facturas', ...)`) en `CasoPagoProveedorResource`, serializando `id`, `folio`, `monto`, `fecha_emision`, mismo estilo que `mapRegistrosContablesCgu`/`mapEgresosCgu`.

## 4. Frontend

- [x] 4.1 Agregar el tipo `Factura` (folio, monto, fecha_emision) en los tipos TypeScript del caso de pago (junto a los tipos existentes de `RegistroContableCgu`/`RegistroPagoBancario`) y agregar `facturas` al tipo del caso.
- [x] 4.2 En `resources/js/pages/pago-proveedores/casos/show.tsx`, agregar sección con formulario (folio, monto, fecha de emisión) que postea a `casos.facturas.store`, mostrado siempre (sin precálculo de permiso, igual que la acción de vincular adquisición) y mostrando el error 403 si el backend rechaza.
- [x] 4.3 En la misma sección, listar las facturas ya registradas del caso (folio, monto, fecha de emisión), igual estilo que los listados de registros CGU/pago bancario.

## 5. Tests

- [x] 5.1 Feature test: usuario con permiso `pago_proveedores.registrar_factura` registra una factura → se persiste con `caso_pago_proveedor_id` y `proveedor_id` correctos, y se registra el `AuditLog` con la acción `caso_pago_proveedor.registrar_factura`.
- [x] 5.2 Feature test: usuario sin el permiso intenta registrar una factura → `assertForbidden()` y se registra el evento de autorización denegada en `security_audit_logs`.
- [x] 5.3 Feature test: el detalle del caso (`CasoPagoProveedorResource` vía `show()`) incluye todas las facturas asociadas, no solo la más reciente.

## 6. Validación

- [x] 6.1 Ejecutar `vendor/bin/pint --dirty --format agent`, `npm run lint:check`, `npm run types:check` y `php artisan test --filter=Factura --compact`.

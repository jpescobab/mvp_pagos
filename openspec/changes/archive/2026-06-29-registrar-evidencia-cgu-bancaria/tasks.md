## 1. Backend: registro contable CGU

- [x] 1.1 Agregar `registrarCgu(User $user, CasoPagoProveedor $caso): bool` a `CasoPagoProveedorPolicy`, retornando `$user->can('pago_proveedores.registrar_cgu')`.
- [x] 1.2 Crear `App\Http\Requests\PagoProveedores\RegistrarRegistroContableCguRequest` (`numero_registro` required string, `fecha_registro` required date, `monto` required numeric, `observaciones` nullable string).
- [x] 1.3 Crear `App\Http\Controllers\PagoProveedores\RegistroContableCguController::store()`: `Gate::authorize('registrarCgu', $caso)`, crea el `RegistroContableCgu` dentro de una transacción con `registrado_por = $request->user()->id`, audita con `AuditLogger::log('caso_pago_proveedor.registrar_contable_cgu', $caso, after: [...])`, `return back()`.
- [x] 1.4 Agregar ruta `POST pago-proveedores/casos/{caso}/registros-contables-cgu` en `routes/pago-proveedores.php`.

## 2. Backend: registro de pago bancario

- [x] 2.1 Agregar `registrarPagoBancario(User $user, CasoPagoProveedor $caso): bool` a `CasoPagoProveedorPolicy`, retornando `$user->can('pago_proveedores.pagar')`.
- [x] 2.2 Crear `App\Http\Requests\PagoProveedores\RegistrarRegistroPagoBancarioRequest` (`numero_operacion` required string, `fecha_pago` required date, `monto` required numeric, `banco` nullable string).
- [x] 2.3 Crear `App\Http\Controllers\PagoProveedores\RegistroPagoBancarioController::store()`: mismo patrón que 1.3 con `AuditLogger::log('caso_pago_proveedor.registrar_pago_bancario', ...)`.
- [x] 2.4 Agregar ruta `POST pago-proveedores/casos/{caso}/registros-pago-bancario` en `routes/pago-proveedores.php`.

## 3. Exponer en el detalle del caso

- [x] 3.1 En `CasoPagoProveedorController::show()`, eager-load `registrosContablesCgu.registradoPor` y `registrosPagoBancario.registradoPor`.
- [x] 3.2 En `CasoPagoProveedorResource`, agregar `registros_contables_cgu` y `registros_pago_bancario` (`whenLoaded`, cada item con id, numero, fecha, monto, observaciones/banco, registrado_por.nombre).

## 4. Frontend

- [x] 4.1 Agregar tipos `RegistroContableCgu` y `RegistroPagoBancario` en `resources/js/types/pago-proveedores.ts`; extender `CasoPagoProveedor` con `registros_contables_cgu?` y `registros_pago_bancario?`.
- [x] 4.2 En `resources/js/pages/pago-proveedores/casos/show.tsx`, agregar dos secciones nuevas ("Registro contable CGU", "Registro de pago bancario") listando el historial existente y un formulario para `router.post` a las rutas nuevas, mismo patrón visual que la sección "Documentos".

## 5. Tests y spec

- [x] 5.1 Tests Feature para ambos `store()`: con permiso crea el registro y el `AuditLog`; sin permiso `assertForbidden()` + evento `acceso_denegado` en `security_audit_logs`.
- [x] 5.2 Test Feature: el detalle del caso (`casos.show`) incluye `registros_contables_cgu` y `registros_pago_bancario` con más de un registro cada uno (verifica que se muestre el historial completo, no solo el último).
- [x] 5.3 Confirmar que los tests ya archivados de `ApiPagoProveedoresTest` (transición `registrar_en_cgu` sin datos de registro) siguen pasando sin modificación.
- [x] 5.4 `vendor/bin/pint --dirty --format agent`, `npm run lint:check`, `npm run types:check`, `php artisan test --compact`.

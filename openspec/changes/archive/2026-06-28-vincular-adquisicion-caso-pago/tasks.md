## 1. Esquema de datos

- [x] 1.1 Crear migración que agrega `proceso_adquisicion_id` (nullable, FK a `procesos_adquisicion`, con índice) en `casos_pago_proveedor`, con `down()` que la elimina.
- [x] 1.2 Agregar relación `procesoAdquisicion(): BelongsTo` en `CasoPagoProveedor`.
- [x] 1.3 Agregar relación `casosPagoProveedor(): HasMany` en `ProcesoAdquisicion`.

## 2. Permisos y auditoría

- [x] 2.1 Agregar el permiso `pago_proveedores.vincular_adquisicion` en `WorkflowPagoProveedoresSeeder` (mismo patrón que los permisos existentes) y asignarlo al rol `admin`.
- [x] 2.2 Definir las acciones de auditoría `caso_pago_proveedor.vincular_adquisicion` y `caso_pago_proveedor.desvincular_adquisicion` para usar con `AuditLogger::log()`.

## 3. Backend: vincular/desvincular

- [x] 3.1 Crear `App\Http\Controllers\PagoProveedores\VinculoAdquisicionCasoPagoProveedorController` con métodos `store` (vincular) y `destroy` (desvincular).
- [x] 3.2 Crear Form Request de validación para `store` (requiere `proceso_adquisicion_id` existente).
- [x] 3.3 Aplicar autorización con el permiso `pago_proveedores.vincular_adquisicion` (Policy o `Gate::authorize`) en ambos métodos.
- [x] 3.4 En `store`/`destroy`, actualizar `proceso_adquisicion_id` del caso dentro de una transacción y registrar el evento correspondiente vía `AuditLogger::log()` con el estado antes/después. No invocar `TransicionWorkflowService`.
- [x] 3.5 Agregar las rutas `POST /pago-proveedores/casos/{caso}/vincular-adquisicion` y `DELETE /pago-proveedores/casos/{caso}/vincular-adquisicion` en `routes/pago-proveedores.php`.

## 4. Backend: búsqueda asistida

- [x] 4.1 Crear endpoint `GET /pago-proveedores/casos/{caso}/buscar-adquisiciones` que busca `ProcesoAdquisicion` por código, objeto, proveedor o monto, limitado a un máximo de resultados (ej. 10), protegido por el mismo permiso de vinculación.
- [x] 4.2 Devolver en cada resultado al menos: `id`, `codigo`, `objeto`, `proveedor` (nombre), `monto`.

## 5. Frontend

- [x] 5.1 Regenerar rutas Wayfinder para los nuevos endpoints (`php artisan wayfinder:generate` o build de Vite).
- [x] 5.2 En `resources/js/pages/pago-proveedores/casos/show.tsx`, agregar UI de búsqueda asistida y acción "Vincular a proceso de adquisición" (y "Desvincular" cuando ya existe un vínculo). Nota de implementación: en vez de precalcular un flag de permiso (lo que dispararía `Gate::after` y ensuciaría `security_audit_logs` en cada carga de página), se sigue el patrón ya usado por `transiciones_disponibles`: la acción siempre se muestra y el error de autorización se surface solo si el intento real es rechazado por el backend.
- [x] 5.3 En `resources/js/pages/adquisiciones/procesos/show.tsx`, agregar un bloque que liste los `casos_pago_proveedor` vinculados (o estado vacío si no hay ninguno).
- [x] 5.4 Actualizar `resources/js/types/adquisiciones.ts` y `resources/js/types/pago-proveedores.ts` con los campos nuevos.

## 6. Tests

- [x] 6.1 Feature test: usuario con permiso vincula un caso a una adquisición → se persiste la FK y se registra el `AuditLog`.
- [x] 6.2 Feature test: usuario con permiso desvincula un caso → la FK queda en `null` y se registra el `AuditLog` correspondiente.
- [x] 6.3 Feature test: usuario sin permiso intenta vincular/desvincular → 403 y se registra en `security_audit_logs`.
- [x] 6.4 Feature test: vincular/desvincular no crea ni modifica ningún `Proceso` ni `HistorialTransicionWorkflow`.
- [x] 6.5 Feature test: la búsqueda asistida devuelve coincidencias por código, objeto, proveedor y monto, respetando el límite de resultados.
- [x] 6.6 Feature test: el detalle de un `ProcesoAdquisicion` muestra los casos vinculados (y la lista vacía cuando no hay ninguno).

## 7. Validación

- [x] 7.1 Ejecutar `composer test` (incluye `lint:check`, `types:check` y la suite Pest).
- [x] 7.2 Ejecutar `npm run lint:check` y `npm run types:check`.
- [x] 7.3 Probar manualmente en el navegador: vincular un caso existente a `ADQ-DEMO-001`, verificar que aparece en ambas vistas, y desvincular.

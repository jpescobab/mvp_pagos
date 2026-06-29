## Context

`facturas` (migración `2026_06_26_210002_create_facturas_table`, modelo `App\Models\Factura`) existe desde la tarea 08 con la relación `CasoPagoProveedor::facturas(): HasMany` ya declarada, pero ningún código la escribe ni la lee: no hay controlador, no hay `FormRequest`, no se carga en `CasoPagoProveedorController::show()` ni se serializa en `CasoPagoProveedorResource`. El expediente documental ya exige el tipo `FACTURA` como archivo para la transición `aprobar_documentacion`; este cambio agrega el dato estructurado equivalente (folio, monto, fecha de emisión) que el diseño original de la tarea 08 dejó pendiente, reutilizando exactamente el patrón ya construido para `registros_contables_cgu` y `registros_pago_bancario`.

## Goals / Non-Goals

**Goals:**
- Permitir registrar una factura (folio, monto, fecha de emisión, proveedor opcional) asociada a un caso de pago de proveedor, con autorización, transacción y auditoría.
- Exponer las facturas ya registradas en el detalle del caso (`CasoPagoProveedorResource`), igual que el resto de la evidencia estructurada.
- Reutilizar el componente de formulario/listado en `show.tsx` con el mismo estilo visual que CGU/pago bancario.

**Non-Goals:**
- No se crea ningún flujo de generación o validación automática de facturas (XML SII, folio CAF, etc.) — solo el registro manual del dato estructurado, igual que CGU/pago bancario son registro manual hoy.
- No se cambia el esquema de `facturas` ni se agregan columnas nuevas.
- No se vincula la factura a una transición de workflow específica (la transición `aprobar_documentacion` sigue exigiendo el documento `FACTURA` como archivo, sin depender de que exista un registro estructurado).
- No se modifica `documentos`/expediente documental.

## Decisions

1. **Reusar el patrón `RegistroContableCgu`/`RegistroPagoBancario` exactamente**: `FacturaController::store(CasoPagoProveedor $caso, RegistrarFacturaRequest $request)`, autorización vía `CasoPagoProveedorPolicy::registrarFactura`, `DB::transaction`, `AuditLogger::log('caso_pago_proveedor.registrar_factura', ...)`. Mantiene consistencia con el código ya revisado y evita inventar una convención nueva.
2. **Nuevo permiso `pago_proveedores.registrar_factura`** agregado al arreglo `$permisos` de `WorkflowPagoProveedoresSeeder` (mismo lugar que `pago_proveedores.vincular_adquisicion`, que tampoco es un permiso de transición de workflow sino de una acción de Policy) y asignado al rol `admin`. Se reutiliza el seeder existente (idempotente vía `firstOrCreate`) en vez de crear uno nuevo solo para un permiso.
3. **UI sin precálculo de permiso**: siguiendo la decisión ya tomada en `vincular-adquisicion-caso-pago`, el formulario de registrar factura se muestra siempre en `show.tsx`; si el usuario no tiene permiso, el backend responde 403 y el error se muestra en el formulario. Evita disparar `Gate::after` (y por tanto entradas de `security_audit_logs`) en cada carga de página solo para decidir si mostrar el botón.
4. **`proveedor_id` se completa automáticamente desde `$caso->proveedor_id`** en el controlador (no se pide en el formulario): toda factura de un caso es del mismo proveedor del caso, así que pedirlo de nuevo sería redundante y abre la puerta a inconsistencias entre el proveedor del caso y el de la factura.
5. **Listado de facturas sin paginación propia**: igual que `registros_contables_cgu`/`registros_pago_bancario`, se listan todas las facturas del caso (volumen bajo, una decena como máximo por caso) directamente en el `CasoPagoProveedorResource`, sin endpoint de listado independiente.

## Risks / Trade-offs

- [Riesgo] Nada impide registrar folios duplicados para el mismo caso por error de tipeo. → Mitigación: fuera de alcance — el dato es evidencia consultable, no un libro contable con folios únicos garantizados; si se detecta necesidad real de unicidad se aborda en un cambio posterior con datos reales de uso.
- [Riesgo] El monto de la factura puede no coincidir con `CasoPagoProveedor.monto` (parcialidad, múltiples facturas por caso). → Mitigación: no se valida cruce de montos en este cambio; es responsabilidad de quien revisa el caso, igual que ocurre hoy con CGU/pago bancario.

## Migration Plan

- Sin migraciones nuevas (tabla ya existe). Se ejecuta `php artisan db:seed --class=WorkflowPagoProveedoresSeeder` (o el seeder completo) para que el nuevo permiso quede creado y asignado a `admin` en cualquier entorno ya sembrado.

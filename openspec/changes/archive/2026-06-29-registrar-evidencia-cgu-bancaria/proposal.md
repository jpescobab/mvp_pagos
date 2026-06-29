## Why

El spec `pago-proveedores-sgf` ya declara que el sistema SHALL registrar evidencia de "registro contable CGU, pago BancoEstado y egreso CGU" (Requirement "Registrar CGU, BancoEstado y egreso CGU como evidencia"), y solo la parte de egreso CGU está implementada. Las tablas `registros_contables_cgu` y `registros_pago_bancario` (modelos `RegistroContableCgu`, `RegistroPagoBancario`) existen desde la tarea 8 con sus relaciones desde `CasoPagoProveedor`, pero no tienen ningún controlador, ruta, policy ni página: no hay forma de registrar esta evidencia hoy. Los permisos `pago_proveedores.registrar_cgu` y `pago_proveedores.pagar` ya existen (sembrados para gatear las transiciones `registrar_en_cgu` y `marcar_pagada_bancoestado`), pero nunca se usan para autorizar el registro de la evidencia misma.

## What Changes

- Permitir registrar un `registro_contable_cgu` (número de registro, fecha, monto, observaciones) asociado a un `caso_pago_proveedor`, autorizado por el permiso ya existente `pago_proveedores.registrar_cgu`.
- Permitir registrar un `registro_pago_bancario` (número de operación, fecha de pago, monto, banco) asociado a un `caso_pago_proveedor`, autorizado por el permiso ya existente `pago_proveedores.pagar`.
- Ambas acciones son evidencia de registro manual (append-only, sin edición ni eliminación), igual que `validaciones_documento` e `historial_transiciones_workflow`. No pasan por `TransicionWorkflowService` (no son un cambio de estado) y se auditan vía `AuditLogger`, mismo patrón que `vincular_adquisicion`.
- Mostrar ambos registros (historial completo, no solo el último) en el detalle del caso de pago (`pago-proveedores/casos/show`), con un formulario para agregar uno nuevo.

## Capabilities

### Modified Capabilities
- `pago-proveedores-sgf`: el Requirement "Registrar CGU, BancoEstado y egreso CGU como evidencia" gana dos escenarios nuevos (registrar evidencia de registro contable CGU, registrar evidencia de pago bancario), antes sin ninguna implementación.

## Impact

- Nuevos: `App\Http\Controllers\PagoProveedores\RegistroContableCguController`, `RegistroPagoBancarioController`, sus Form Requests, dos métodos nuevos en `CasoPagoProveedorPolicy`.
- Modificados: `CasoPagoProveedorController::show()` (eager load), `CasoPagoProveedorResource`, `routes/pago-proveedores.php`, `resources/js/pages/pago-proveedores/casos/show.tsx`, `resources/js/types/pago-proveedores.ts`.
- Sin cambios de esquema (las tablas y modelos ya existen desde la tarea 8).

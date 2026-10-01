## Why

Un CDP firmado compromete presupuesto (`MovimientoPresupuestario` tipo `compromiso`), y `CalculadorSaldoPresupuestoService` ya resta ejecuciones y liberaciones, pero nada genera movimientos `ejecucion` ni `liberacion_compromiso`: aunque el pago al proveedor se concrete, el presupuesto sigue figurando como comprometido y nunca como ejecutado. Es el último cabo del módulo Presupuesto (changes 1 y 2 ya archivados).

## What Changes

- `TransicionWorkflowService::execute()` emite un evento `TransicionWorkflowEjecutada` después de confirmar la transacción. El core de workflow no conoce Presupuesto; un listener del dominio reacciona.
- Cuando un `CasoPagoProveedor` pasa por `marcar_pagada_bancoestado` y su adquisición tiene exactamente un CDP firmado vigente, se registran un movimiento `ejecucion` y otro `liberacion_compromiso` por `caso.monto`, con el caso como origen.
- 0 CDP vigentes o más de 1: no se registra nada y queda constancia auditable para resolución humana (no se adivina).
- Idempotencia: a lo más un par `ejecucion`/`liberacion_compromiso` por caso.
- Alerta de sobre-ejecución (ejecutado acumulado > monto del CDP): marca, audita y notifica; nunca bloquea el pago (CGU gobierna el presupuesto, este sistema solo lo refleja).
- Una falla del listener no revierte ni impide el pago ya confirmado; queda en auditoría y log.
- La vista de detalle del CDP muestra una tarjeta de ejecución (pagos ejecutados, monto ejecutado, compromiso remanente, badge de sobre-ejecución).
- Nueva columna nullable en `movimientos_presupuestarios` para vincular cada ejecución/liberación con su CDP, e índice único por origen para garantizar la idempotencia.
- Fuera de alcance: reversar ejecuciones (el workflow no tiene esa transición), integración con CGU, rediseño del proceso de compras, `WorkflowAdquisicionesSeeder`, y cambios a `CalculadorSaldoPresupuestoService`.

## Capabilities

### New Capabilities

- `presupuesto-ejecucion-desde-pago`: registro automático de ejecución y liberación de compromiso contra el CDP al pagar un caso, resolución del CDP vigente, idempotencia, alerta de sobre-ejecución y visibilidad en el detalle del CDP.

### Modified Capabilities

- `workflow-core`: se agrega el requisito de que el servicio central emita un evento tras cada transición confirmada, sin acoplar el core a ningún dominio.

## Impact

- `app/Services/Workflow/TransicionWorkflowService.php`, nuevo `app/Events/TransicionWorkflowEjecutada.php`.
- Nuevo listener y `app/Services/Presupuesto/RegistrarEjecucionDesdePagoService.php`; registro del listener en `AppServiceProvider`.
- Migración sobre `movimientos_presupuestarios`; modelos `MovimientoPresupuestario` y `CertificadoDisponibilidadPresupuestaria` (relación de movimientos).
- `CertificadoDisponibilidadPresupuestariaResource`, `resources/js/pages/presupuesto/cdp/show.tsx` y tipos TS.
- Tests Pest en `tests/Feature/Presupuesto/` y `tests/Feature/Workflow/`. Sin permisos nuevos.

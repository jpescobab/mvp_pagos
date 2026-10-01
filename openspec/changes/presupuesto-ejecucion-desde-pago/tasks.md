## 1. Evento de workflow

- [x] 1.1 Crear `app/Events/TransicionWorkflowEjecutada.php` (proceso, código de transición, usuario).
- [x] 1.2 En `TransicionWorkflowService::execute()` despachar el evento con `DB::afterCommit()`; sin tocar el comportamiento existente.
- [x] 1.3 Tests en `tests/Feature/Workflow/`: se emite tras una transición exitosa, no se emite si es rechazada, un listener que lanza excepción no revierte la transición.

## 2. Modelo de datos

- [x] 2.1 Migración sobre `movimientos_presupuestarios`: columna nullable `certificado_disponibilidad_presupuestaria_id` (FK, `nullOnDelete`) e índice único `(tipo, origen_type, origen_id)`; verificar contra los datos existentes.
- [x] 2.2 Ajustar `MovimientoPresupuestario` (fillable, relación al CDP) y agregar a `CertificadoDisponibilidadPresupuestaria` la relación `movimientosEjecucion`.

## 3. Registro de ejecución

- [x] 3.1 Crear `app/Services/Presupuesto/RegistrarEjecucionDesdePagoService.php`: valida módulo `presupuesto_cdp` activo, resuelve el CDP vigente (firmado, monto > 0, sin anulación firmada que lo referencie), crea `ejecucion` + `liberacion_compromiso` por `caso.monto` en transacción con `lockForUpdate`, idempotente por caso.
- [x] 3.2 Constancia auditable `presupuesto.ejecucion_no_resuelta` para 0 o más de 1 CDP vigentes; sin constancia si el caso no tiene adquisición.
- [x] 3.3 Detección de sobre-ejecución: auditoría `presupuesto.sobre_ejecucion` y notificación `database` a usuarios con `presupuesto.firmar_cdp` (nueva `Notification` en `app/Notifications/`).
- [x] 3.4 Crear el listener de `TransicionWorkflowEjecutada` que filtra `marcar_pagada_bancoestado` de un `CasoPagoProveedor`, llama al service dentro de try/catch y registra la falla en auditoría y log (se registra solo por auto-descubrimiento de `app/Listeners`; registrarlo a mano además lo ejecutaría dos veces).
- [x] 3.5 Tests en `tests/Feature/Presupuesto/`: un CDP vigente (movimientos, saldo disponible intacto, remanente), pagos parciales, 0 CDP y 2+ CDP (sin movimientos + constancia), CDP anulado excluido, caso sin adquisición, módulo desactivado, idempotencia (doble disparo y índice único), sobre-ejecución (audita, notifica, no bloquea), falla del listener no revierte el pago.

## 4. Visibilidad en el detalle del CDP

- [x] 4.1 Service/consulta que calcula `ejecucion` (pagos, `monto_ejecutado`, `compromiso_remanente`, `sobre_ejecutado`) y exponerlo en `CertificadoDisponibilidadPresupuestariaResource` solo para CDP firmados de monto positivo.
- [x] 4.2 Tipos TS en `resources/js/types/presupuesto.ts` y tarjeta de ejecución en `resources/js/pages/presupuesto/cdp/show.tsx` (montos con `Monto`, badge de sobre-ejecución con tokens semánticos).
- [x] 4.3 Test del recurso/endpoint de detalle (CDP con pagos y CDP sin ejecuciones, requiere `presupuesto.consultar`).

## 5. Validación final

- [x] 5.1 `vendor/bin/pint --dirty --format agent` sobre los PHP tocados.
- [x] 5.2 `composer types:check` (PHPStan) y `php artisan test --compact` sobre `tests/Feature/Presupuesto`, `tests/Feature/Workflow` y `tests/Feature/PagoProveedores`; correr el directorio completo por el gotcha de helpers cross-archivo.
- [x] 5.3 `npm run lint:check` y `npm run types:check` sobre el frontend tocado, regenerando Wayfinder si hace falta.
- [ ] 5.4 Verificación manual en navegador: pagar un caso de prueba con CDP firmado vigente y confirmar la tarjeta de ejecución en el detalle del CDP y que el estado del caso es `pagada_bancoestado`.

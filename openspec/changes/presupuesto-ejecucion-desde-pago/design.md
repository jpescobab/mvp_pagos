## Context

`FirmarCertificadoDisponibilidadService` crea un `MovimientoPresupuestario` `compromiso` con origen el CDP. Una anulación es un CDP nuevo con monto negativo (`cdp_original_id` apunta al original) que firma otro compromiso negativo. `CalculadorSaldoPresupuestoService::disponible()` ya calcula `monto_asignado − (compromisos − liberaciones) − ejecutado`, de modo que ejecutar y liberar por el mismo monto deja el disponible neto sin cambio y mueve el monto de "comprometido" a "ejecutado".

`TransicionWorkflowService::execute()` hoy no emite eventos: audita, completa tareas y notifica responsables (`TransicionWorkflowNotification`, canal `database`) dentro de su transacción. La regla dura del proyecto: todo cambio de estado pasa por ese servicio; este change no cambia estados.

El módulo Presupuesto se considera activo si `definiciones_workflow` con código `presupuesto_cdp` tiene `activo = true` (misma noción de módulo activable del core).

## Goals / Non-Goals

**Goals:**
- Reflejar el pago concretado como ejecución presupuestaria contra el CDP correcto, automáticamente y sin duplicar.
- No acoplar el core de workflow a Presupuesto.
- Que el pago nunca falle ni se bloquee por el presupuesto.

**Non-Goals:**
- Reversar ejecuciones, integrar con CGU, redefinir el proceso de compras, tocar `WorkflowAdquisicionesSeeder` o `CalculadorSaldoPresupuestoService`.
- Gobernar el presupuesto: CGU es el origen, aquí solo se refleja y se alerta.

## Decisions

**1. Evento `TransicionWorkflowEjecutada`, no llamada directa.** `execute()` lo despacha con `DB::afterCommit()` (proceso, código de transición, usuario). Alternativa descartada: que el core llame a un service de Presupuesto (acopla el core a un dominio funcional activable, contra "core no desactivable vs módulos activables").

**2. Listener síncrono, aislado por try/catch.** Corre justo después del commit y dentro del mismo request, para que el movimiento exista cuando el usuario vea el resultado. Cualquier excepción se captura, se audita (`AuditLogger`) y se registra en log; la transición ya confirmada no se toca. Alternativa descartada: cola (agrega latencia y un estado intermedio "pagado pero sin ejecución" sin beneficio real a este volumen).

**3. Resolución del CDP vigente.** Se parte de `caso.proceso_adquisicion_id`. CDP vigente = firmado, con `monto > 0`, sin un CDP de anulación firmado que lo referencie por `cdp_original_id`. Exactamente 1 → se usa. 0 o más de 1 → no se registra y se deja constancia auditable (acción `presupuesto.ejecucion_no_resuelta` con el motivo y los CDP candidatos). Alternativa descartada: elegir "el más reciente" o repartir el monto (adivinar presupuesto es peor que pedir resolución humana).

**4. Monto.** `ejecucion` y `liberacion_compromiso` por `caso.monto` (CLP), ambos con origen el `CasoPagoProveedor` y observación con el folio del CDP. Pagos parciales (varios casos por adquisición) ejecutan cada uno su monto; compromiso remanente = `cdp.monto − suma ejecutada`. Es una simplificación aceptada: no se distingue IVA/exento ni moneda; el `caso.monto` es el monto pagado.

**5. Vínculo explícito movimiento→CDP.** Como el origen del movimiento es el caso, se agrega la columna nullable `certificado_disponibilidad_presupuestaria_id` (FK, `nullOnDelete`) a `movimientos_presupuestarios`, poblada solo para `ejecucion`/`liberacion_compromiso`. Permite sumar lo ejecutado por CDP y mostrarlo sin inferir. Alternativa descartada: guardar el id en `observacion` (no consultable ni íntegro).

**6. Idempotencia en dos capas.** El service verifica en transacción (con `lockForUpdate` sobre el presupuesto) si ya existe un movimiento del mismo `tipo` con ese origen; además un índice único `(tipo, origen_type, origen_id)` en BD impide la duplicación bajo concurrencia. Es compatible con los datos existentes (cada CDP aparece una sola vez como origen de su `compromiso`).

**7. Alerta de sobre-ejecución.** Tras registrar, si la suma de `ejecucion` del CDP supera `cdp.monto`: se audita (`presupuesto.sobre_ejecucion`) y se notifica por `database` a los usuarios con el permiso `presupuesto.firmar_cdp` (reutiliza el canal ya existente, sin permisos nuevos). No bloquea nada. El detalle del CDP expone el flag calculado.

**8. Visibilidad.** `CertificadoDisponibilidadPresupuestariaResource` entrega `ejecucion` (lista de pagos, `monto_ejecutado`, `compromiso_remanente`, `sobre_ejecutado`) calculado por un service/consulta, no en React. `show.tsx` solo la renderiza en una tarjeta; se muestra únicamente para CDP con monto positivo.

## Risks / Trade-offs

- [El listener falla y el pago queda sin ejecución] → se audita y se loguea; la constancia permite reprocesar a mano y el service es idempotente, así que re-ejecutarlo es seguro.
- [Adquisición con 0 o 2+ CDP vigentes] → no se adivina; queda en auditoría para resolución humana. Puede generar casos pendientes recurrentes hasta que se ordenen los CDP.
- [Dos transiciones concurrentes sobre la misma adquisición] → `lockForUpdate` sobre el presupuesto más el índice único.
- [Índice único `(tipo, origen_type, origen_id)` choca con datos existentes] → se verifica en la migración con los datos actuales; hoy no hay duplicados.
- [`caso.monto` ≠ monto presupuestario real (IVA, moneda)] → se documenta como simplificación; se puede refinar después sin romper el modelo.

## Open Questions

- ¿El destinatario de la alerta de sobre-ejecución debe ser también quien registró el pago? Por ahora solo quienes pueden firmar CDP.
- ¿Los pagos de casos sin adquisición (sin `proceso_adquisicion_id`) deben ejecutar contra algún presupuesto? Se deja fuera: no hay CDP al cual imputar.

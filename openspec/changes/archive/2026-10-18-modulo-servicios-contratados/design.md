## Context

`Contrato` ya existe (workflow `borrador → pendiente → aprobado/rechazado`, proveedor, vigencia, `ContratoCuota` con vínculo opcional a `CasoPagoProveedor`). `CasoPagoProveedor` trae `proveedor_id`, `periodo` (texto `YYYY-MM` desde SGF) y su `Proceso` de workflow (estado `pagada_bancoestado` y posteriores = pagado). Consumo Básico es el patrón más cercano de módulo ligero sin workflow propio (service + policy + controller + páginas Inertia). Los modelos viven planos en `app/Models/`.

## Goals / Non-Goals

**Goals:**
- Ver qué servicios recurrentes están vigentes, cuáles vencen pronto y qué meses están pagados.
- Reutilizar `Contrato` como fuente de proveedor y vigencia; no duplicar datos.

**Non-Goals:**
- Workflow o estados persistidos propios, recepción conforme, generación de casos de pago, bloquear o validar pagos, conciliar montos contra el pago real, Mantenimiento y RR.HH.

## Decisions

1. **Sin workflow.** El estado se deriva de `fecha_fin` y `terminado_en` al consultar (mismo criterio que `ContratoCuota::vencida`): sin job programado que lo mantenga. Alternativa descartada: definición de workflow; no hay acciones sensibles ni revisión humana que gobernar. Consecuencia: no hay `DefinicionWorkflow.activo` que apague el módulo; el acceso se controla por permisos.
2. **Proveedor heredado del contrato.** `servicios_contratados` guarda `contrato_id` obligatorio y no `proveedor_id`; evita inconsistencias. La vigencia del servicio SHALL quedar dentro de la del contrato.
3. **Solo contratos `aprobado`** pueden recibir servicios nuevos (el contrato manda; el servicio no lo altera).
4. **Períodos derivados, no persistidos.** `PeriodosServicioContratadoService` genera un período por mes entre `fecha_inicio` y min(`fecha_fin`, mes actual + 1) y busca casos del proveedor con `periodo` igual a `YYYY-MM`. Estado por período: `pagado` si algún caso está en `pagada_bancoestado` o posterior; `en_tramite` si hay caso pero no pagado; `sin_caso` si no hay. Alternativa descartada: tabla de períodos con FK a caso (duplica a `ContratoCuota`, exige mantenimiento).
5. **Ambigüedad explícita.** Si el proveedor tiene varios servicios vigentes el mismo mes, un caso no se puede atribuir con certeza: el período se marca `ambiguo` en vez de adivinar (mismo principio que "resolver CDP sin adivinar"). El parseo de `periodo` tolera formato no `YYYY-MM` ignorando el caso.
6. **Alerta de vencimiento** vía config `servicios_contratados.dias_alerta_vencimiento` (default 60), calculada en el Resource.
7. **Categoría** como string validado contra constantes del modelo (`aseo`, `vigilancia`, `mantencion`, `arriendo`, `otro`); no se crea tabla de catálogo hasta que se necesite administrarla.
8. **Permisos** `servicios_contratados.{ver,crear,editar,terminar}` sembrados en `ServiciosContratadosSeeder` (admin y administrativo_finanzas: ver; admin: todos); policy registrada a mano en `AppServiceProvider`. Auditoría de crear/editar/terminar vía `AuditLogger::log`.

## Risks / Trade-offs

- [`periodo` de SGF puede venir en otro formato o no corresponder al mes del servicio] → solo se cruzan casos con `YYYY-MM` válido; el resto queda `sin_caso`; el cruce es informativo.
- [Sin tabla de períodos no hay historial de lo que se vio] → aceptable; el estado real vive en los casos de pago.
- [Pagar mal clasificado por proveedor con varios servicios] → período `ambiguo`, nunca atribución silenciosa.

## Migration Plan

Migración aditiva (`servicios_contratados`), seeder idempotente de permisos; rollback con `migrate:rollback`. Sin datos preexistentes que migrar.

## Open Questions

Ninguna bloqueante.

## Context

`CasoPagoProveedor` nace siempre de SGF (`sgf_id`, ver `pago-proveedores-sgf`) y `ProcesoAdquisicion` nace siempre del módulo de Adquisiciones (ver `adquisiciones`). No comparten ninguna FK ni clave de correlación hoy. El harness exige que el sistema sea la capa de evidencia y trazabilidad institucional, pero hoy no hay forma de reconstruir, desde los datos, qué adquisición originó un pago que SGF terminó procesando.

Se evaluaron tres patrones de vínculo ya presentes en el código:
- `EgresoCguItem.caso_pago_proveedor_id`: FK directa (`belongsTo`), no polimórfica.
- `CorteReportabilidadItem.vinculable` (`morphTo`): polimórfico, genérico sobre cualquier modelo.
- `VinculoDocumento`: polimórfico, para adjuntar documentos a cualquier entidad.

Este vínculo es entre dos entidades de dominio concretas y conocidas de antemano (no "cualquier modelo"), así que se sigue el primer patrón (FK directa), no el polimórfico — evita indirección innecesaria y es más fácil de indexar/consultar.

## Goals / Non-Goals

**Goals:**
- Permitir que una persona autorizada vincule manualmente un `CasoPagoProveedor` a un `ProcesoAdquisicion` existente, con búsqueda asistida.
- Dejar trazabilidad auditable (quién, cuándo, qué vínculo) sin pasar por el motor de workflow (no es un cambio de estado).
- Permitir desvincular (corregir un vínculo mal hecho), también auditado.
- Mostrar el vínculo en ambas vistas de detalle (Caso de Pago y Proceso de Adquisición).

**Non-Goals:**
- No se construye matching automático por texto libre de SGF (`observaciones`/`payload_crudo`).
- No se agrega `numero_orden_compra` ni ningún campo de Mercado Público — eso depende de una integración futura no construida.
- No se modifica `TransicionWorkflowService` ni los estados de ningún workflow.
- No se permite vincular un caso a más de un proceso de adquisición a la vez (relación muchos-casos-a-un-proceso, no muchos-a-muchos).

## Decisions

**D1. FK directa nullable en `casos_pago_proveedor`, no tabla pivote ni polimórfica.**
Un caso se vincula a a lo sumo una adquisición; una adquisición puede tener varios casos (pagos por avance). Esa es exactamente la forma `belongsTo`/`hasMany`, sin necesidad de pivote. Alternativa descartada: polimórfico `vinculable` — innecesario porque el otro extremo de la relación siempre es `ProcesoAdquisicion`, nunca otro modelo.

**D2. Acción dedicada (controller + ruta), no edición genérica del caso.**
Vincular/desvincular se modela como una acción explícita (`POST .../vincular-adquisicion`, `DELETE .../vincular-adquisicion`), no como parte de un `update` genérico del caso. Esto permite aplicar un permiso específico (`pago_proveedores.vincular_adquisicion`) y un log de auditoría con `action` semántico (`caso_pago_proveedor.vincular_adquisicion` / `.desvincular_adquisicion`), igual al patrón ya usado por `TransicionWorkflowService` para sus propias acciones (acción nombrada + antes/después).

**D3. No pasa por `TransicionWorkflowService`.**
El servicio gobierna exclusivamente `estado_actual_id` de un `Proceso`. Vincular una adquisición no toca ese campo en ningún `Proceso` (ni el del caso ni el de la adquisición), así que no es una transición de workflow. Se reutiliza `AuditLogger::log()` directamente desde el controlador/servicio nuevo, igual que ya hace `TransicionWorkflowService` internamente para sus propios registros — mismo mecanismo de auditoría, sin acoplarse al motor de estados.

**D4. Búsqueda asistida vía endpoint de búsqueda liviano, no carga completa de `procesos_adquisicion`.**
La UI de búsqueda (código/objeto/proveedor/monto) pega a un endpoint `GET /pago-proveedores/casos/{caso}/buscar-adquisiciones?q=...` que devuelve coincidencias paginadas/limitadas (ej. máx. 10), evitando traer todas las adquisiciones al cliente.

**D5. Permiso nuevo en vez de reusar uno existente.**
`pago_proveedores.vincular_adquisicion` se agrega al seeder de roles/permisos de Pago de Proveedores (no al de Adquisiciones), porque la acción se ejecuta desde el caso de pago, y porque vincular información financiera a un contrato es una decisión distinta de gestionar el ciclo de vida de la adquisición misma.

## Risks / Trade-offs

- **[Riesgo] Un vínculo incorrecto pasa desapercibido** → Mitigación: la acción de desvincular siempre está disponible para quien tiene el permiso, y queda su propio registro de auditoría (no se sobrescribe el anterior).
- **[Riesgo] Búsqueda por proveedor/monto puede traer falsos positivos cuando hay muchos contratos similares** → Mitigación: el código `ADQ-XXXX` siempre se muestra en los resultados de búsqueda para que la persona confirme visualmente antes de vincular; no hay auto-selección por mejor coincidencia.
- **[Trade-off] No resolver Mercado Público ahora significa que el vínculo seguirá siendo 100% manual por un tiempo indefinido** → Aceptado explícitamente: es preferible un proceso manual auditable hoy que construir un campo de OC que no se puede poblar de forma confiable todavía.

## Migration Plan

1. Migración: agregar `proceso_adquisicion_id` (nullable, FK a `procesos_adquisicion`, índice) en `casos_pago_proveedor`. Sin backfill (no hay datos previos que correlacionar automáticamente).
2. Seeder de permisos: agregar `pago_proveedores.vincular_adquisicion` y asignarlo al rol `admin` (mismo patrón que `adquisiciones.publicar`, etc.).
3. Rollback: la migración es aditiva y reversible (`down()` elimina la columna); no afecta datos existentes si se revierte.

## Open Questions

- ¿Qué roles además de `admin` deberían tener `pago_proveedores.vincular_adquisicion` en producción? (Definir junto al dueño funcional antes de archivar el change, no bloquea la implementación porque el seeder ya sigue el patrón existente.)

## Context

`AuditLogger::log()` (tarea 03, ya archivada) escribe en `audit_logs` desde múltiples dominios (`TransicionWorkflowService`, vínculo/desvínculo de adquisición a caso de pago, y presumiblemente más a futuro), pero su spec original (`seguridad-auditoria`) solo cubre la escritura, nunca la lectura. A diferencia de `IndicadorEconomico` (datos de referencia públicos), `audit_logs` expone quién hizo qué sobre qué entidad — información operativa sensible que no debe ser visible para cualquier usuario autenticado.

## Goals / Non-Goals

**Goals:**
- Página de solo lectura para consultar `audit_logs`, paginada, con el detalle (`before`/`after`/`metadata`) visible por fila.
- Protegida por un permiso dedicado (`auditoria.ver`), no por autenticación simple.

**Non-Goals:**
- Visualizar `security_audit_logs` (eventos de seguridad) — se deja para una iteración posterior, igual de incremental que como se hizo con documentos.
- Filtros por usuario, tipo de entidad o rango de fecha — sin volumen real que lo justifique todavía; agregarlos sin soporte de backend real sería simulación.
- Exportar o eliminar registros de auditoría — un registro de auditoría que se puede borrar deja de ser confiable como evidencia.

## Decisions

**Permiso dedicado `auditoria.ver`, vía `AuditLogPolicy::viewAny()`, no `Gate::authorize('permission.string')` plano.** Mismo patrón ya establecido en este proyecto (`ProcesoPolicy`, `EgresoCguPolicy`): solo un método de Policy real que retorna `false` explícito dispara `Gate::after` de forma confiable para registrar `acceso_denegado` en `security_audit_logs`. Justo por ser una página que expone quién-hizo-qué, es la más importante de todas las que se han construido para que su propio acceso quede auditado si es denegado.

**`auditoria.ver` se asigna a `superadmin` y `admin` por defecto, igual que `documentos.gestionar`/`documentos.validar`.** Es infraestructura core, no de módulo funcional — no se crea un permiso por dominio (`workflow.ver_auditoria`, `documentos.ver_auditoria`, etc.) porque `audit_logs` ya es transversal por diseño (polimórfico vía `auditable_type`/`auditable_id`).

**El detalle (`before`/`after`/`metadata`) se expone siempre en la misma respuesta del listado, no en un endpoint de detalle separado.** El volumen de cada fila es pequeño (arrays JSON ya parseados por el modelo) y no justifica una segunda ida al backend solo para expandir una fila — la página React simplemente muestra/oculta el bloque JSON con estado local.

## Risks / Trade-offs

- **[Riesgo] Mostrar `before`/`after` crudo como JSON sin formatear puede ser difícil de leer para according** → **Mitigación**: aceptado para esta primera versión; un render más amigable (diff visual) es una mejora incremental posterior sin romper el contrato actual.
- **[Riesgo] Sin paginación eficiente si el volumen de `audit_logs` crece mucho** → **Mitigación**: aceptado; se usa `paginate()` estándar igual que el resto del sistema.

## Migration Plan

Sin cambios de esquema ni datos — solo un controlador, una ruta, un Resource, una Policy y una página nuevos, más un permiso nuevo en el seeder.

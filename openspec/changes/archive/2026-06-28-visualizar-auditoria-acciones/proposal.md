## Why

`AuditLogger` ya registra fielmente cada acción sensible del sistema (`audit_logs` ya tiene filas reales generadas en esta misma sesión: transiciones de workflow, vínculo/desvínculo de adquisición a un caso de pago) — pero no existe ningún controlador, ruta ni página que permita consultarlas. La trazabilidad es uno de los pilares explícitos de este proyecto (`HARNESS_IA.md`, `CLAUDE.md`): un registro de auditoría que nadie puede ver solo cumple la mitad de su propósito.

## What Changes

- Página de solo lectura para consultar `audit_logs`: lista paginada con usuario, acción, entidad afectada y fecha; cada fila puede expandirse para ver `before`/`after`/`metadata`.
- Endpoint HTTP autenticado, protegido por un permiso nuevo (`auditoria.ver`) — a diferencia de los indicadores económicos, el historial de auditoría expone datos potencialmente sensibles (quién hizo qué, sobre qué entidad) y no debe ser visible para cualquier usuario autenticado.
- Permiso `auditoria.ver` (core, no de módulo), asignado por defecto a `superadmin` y `admin`.
- Enlace en el sidebar.

No incluye en este change: visualizar `security_audit_logs` (eventos de seguridad como accesos denegados) — mismo patrón incremental ya usado para documentos (subir → validar → versionar → historial, en changes separados); se deja para una iteración posterior si se necesita. Tampoco incluye filtros por usuario/entidad/rango de fecha (se puede agregar después si el volumen lo justifica).

## Capabilities

### New Capabilities

(ninguna)

### Modified Capabilities

- `seguridad-auditoria`: agrega "Visualizar el historial de auditoría" y el permiso `auditoria.ver`.

## Impact

- Backend: nuevo `App\Http\Controllers\Seguridad\AuditoriaController`, nuevo `App\Http\Resources\Seguridad\AuditLogResource`, nueva `App\Policies\AuditLogPolicy`, nueva ruta `routes/seguridad.php`, permiso nuevo en `RolesAndPermissionsSeeder`.
- Frontend: nueva página `resources/js/pages/auditoria/index.tsx`, nuevo tipo en `resources/js/types/`, entrada nueva en `resources/js/components/app-sidebar.tsx`.
- Tests: feature test para el listado, el detalle expandido y la restricción por permiso.

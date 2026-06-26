## Context

Hoy no hay roles, permisos ni auditoría. Cualquier usuario autenticado (vía Fortify) tiene el mismo acceso a todo. `Features::registration()` ya está deshabilitado en `config/fortify.php`, así que "no auto-registro público" ya se cumple — falta la capa de autorización y auditoría en sí.

## Goals / Non-Goals

**Goals:**
- Roles y permisos funcionando con Spatie Permission, con un bypass simple para `superadmin`.
- Infraestructura de auditoría genérica y reutilizable (`AuditLogger`), no acoplada a workflow.
- Conectar ya el escenario de "acción no permitida" (no depende de nada futuro).
- Permisos solo para lo que ya existe (administración de usuarios, roles, core institucional, tablas maestras).

**Non-Goals:**
- No se construye UI de gestión de usuarios/roles en React — sigue el mismo patrón backend-only de las tareas 1 y 2.
- No se inventan permisos para módulos que no existen todavía (workflow, documentos, pago de proveedores, etc.) — se definen cuando se construyan, siguiendo la convención `modulo.accion`.
- No se conecta el escenario de auditoría de transición de workflow — depende de `WorkflowTransitionService` (tarea 5).

## Decisions

- **`Gate::before` para bypass de `superadmin`**, en vez de asignarle todos los permisos uno por uno. Patrón estándar recomendado por Spatie Permission; evita que la lista de permisos de superadmin quede desincronizada cada vez que se agregue un permiso nuevo en una tarea futura.
- **`Gate::after` para auditoría de seguridad**, en vez de loguear manualmente en cada policy. Captura toda denegación (Policy o `Gate::define`) en un solo lugar, sin tocar cada controlador futuro.
- **Convención de permisos `modulo.accion`** (ej. `usuarios.administrar`, `tablas_maestras.administrar`), documentada aquí para que las tareas futuras (workflow-core, documentos, pago-proveedores) la sigan sin tener que rediseñarla.
- **`admin` no tiene `roles.administrar`**: solo `superadmin` puede crear/modificar roles y permisos. `admin` administra usuarios y datos institucionales, pero no el sistema de autorización en sí.
- **`audit_logs`/`security_audit_logs` son append-only**: solo `created_at`, sin `updated_at` ni soft deletes — un log de auditoría que se puede editar deja de ser confiable como evidencia.
- **El usuario de prueba sembrado recibe `superadmin`**, para que el entorno de desarrollo tenga un usuario funcional desde el primer `db:seed`.

## Risks / Trade-offs

- **[Riesgo] `Gate::after` podría generar ruido de logs en operaciones de autorización muy frecuentes** → Mitigación: solo se registra cuando el resultado es denegado (`false`/`null`), nunca en autorizaciones exitosas.
- **[Riesgo] Los permisos `modulo.accion` definidos ahora podrían no anticipar la granularidad real que necesiten las tareas futuras** → Mitigación: aceptado conscientemente — es preferible ajustar la granularidad cuando el módulo real se construya que inventar permisos especulativos ahora (mismo criterio aplicado a `funcionarios` en la tarea 2).

## MODIFIED Requirements

### Requirement: Auditar las mutaciones del catálogo de tablas maestras institucionales
El sistema SHALL registrar en `audit_logs`, mediante `AuditLogger`, la creación, edición y eliminación de cada tabla maestra institucional —instituciones, jurisdicciones, centros financieros, centros de costo, proveedores, clientes medidores, ítems presupuestarios, tipos de documento, tipos de proceso de pago, asignaciones y catálogos—. Cada registro SHALL identificar la entidad afectada, el usuario responsable y el estado anterior y nuevo de los atributos que cambiaron. La eliminación SHALL auditarse tanto si es lógica (soft delete) como física. El sistema SHALL registrar la auditoría únicamente cuando la mutación ocurre en el contexto de un usuario autenticado; las mutaciones sin usuario —siembra, migración o sincronización automática— SHALL NOT generar registros de auditoría.

#### Scenario: Crear una tabla maestra deja registro
- **WHEN** un usuario autenticado crea una tabla maestra institucional
- **THEN** se registra en `audit_logs` una acción de creación con la entidad afectada, el usuario y el estado nuevo

#### Scenario: Editar una tabla maestra registra qué cambió
- **WHEN** un usuario autenticado edita una tabla maestra institucional
- **THEN** se registra en `audit_logs` una acción de edición con la entidad afectada, el usuario y los estados antes y después de los atributos que cambiaron

#### Scenario: Eliminar una tabla maestra deja registro
- **WHEN** un usuario autenticado elimina una tabla maestra institucional, sea la eliminación lógica o física
- **THEN** se registra en `audit_logs` una acción de eliminación con la entidad afectada, el usuario y el estado anterior

#### Scenario: La siembra no genera auditoría
- **WHEN** un seeder, una migración o un job sin usuario autenticado crea o modifica tablas maestras
- **THEN** no se genera ningún registro en `audit_logs` por esas mutaciones

#### Scenario: Mutar los niveles superiores de la jerarquía deja registro
- **WHEN** un usuario autenticado crea, edita o elimina una institución o una jurisdicción
- **THEN** se registra en `audit_logs` la acción correspondiente con la entidad afectada, el usuario y los estados anterior y nuevo, igual que para el resto de las tablas maestras

## MODIFIED Requirements

### Requirement: Versionar documentos con trazabilidad de validación
Todo documento cargado al expediente SHALL quedar asociado a un tipo documental, conservar todas sus versiones (`versiones_documento`) y registrar un historial inmutable de eventos de validación (`validaciones_documento`). Cada evento de validación SHALL registrar la instancia de revisión que lo emitió (`validaciones_documento.instancia`, p. ej. `finanzas` o `zonal`) cuando la revisión ocurre por instancias. El estado vigente de un documento para una instancia dada es el del evento de validación más reciente de esa instancia; una validación emitida por una instancia no altera el estado del documento para otra instancia.

#### Scenario: Subir una nueva versión de un documento
- **WHEN** se carga un archivo para un documento existente
- **THEN** se crea una nueva fila en `versiones_documento`
- **AND** las versiones anteriores no se eliminan

#### Scenario: Validar un documento
- **WHEN** un usuario autorizado valida o rechaza un documento
- **THEN** se registra un nuevo evento en `validaciones_documento` con el resultado, la observación y la instancia
- **AND** el estado vigente del documento para esa instancia pasa a ser el de ese evento

#### Scenario: Una validación por instancia no altera el estado en otra instancia
- **WHEN** la instancia de Finanzas aprueba un documento y luego el mismo documento es revisado por la instancia Zonal
- **THEN** el estado vigente del documento para la instancia Zonal parte como pendiente (sin evento propio)
- **AND** el evento de aprobación de Finanzas permanece registrado y consultable

#### Scenario: Validar o rechazar un documento vía HTTP requiere el permiso dedicado
- **WHEN** un usuario con el permiso `documentos.validar` envía una validación (`valido` o `rechazado`) para un documento vinculado a un proceso
- **THEN** se crea el evento en `validaciones_documento` con su usuario y fecha
- **AND** el checklist documental del proceso refleja ese estado en su siguiente resolución

#### Scenario: Rechazar un documento exige una observación
- **WHEN** se envía una validación con `estado: 'rechazado'` sin `observacion`
- **THEN** el sistema rechaza la operación sin crear ningún evento

#### Scenario: Usuario sin permiso intenta validar
- **WHEN** un usuario sin el permiso `documentos.validar` intenta validar o rechazar un documento
- **THEN** el sistema bloquea la operación
- **AND** registra el evento de autorización denegada en `security_audit_logs`

#### Scenario: Subir una nueva versión vía HTTP no crea un documento nuevo
- **WHEN** un usuario con el permiso `documentos.gestionar` sube un archivo válido como nueva versión de un `Documento` ya existente
- **THEN** se crea una `VersionDocumento` con el siguiente `numero_version` consecutivo
- **AND** no se crea ningún `Documento` ni `VinculoDocumento` nuevo
- **AND** el historial de `validaciones_documento` del documento permanece intacto

#### Scenario: Descargar un documento siempre sirve su última versión
- **WHEN** se descarga un documento que tiene más de una versión
- **THEN** el sistema sirve el archivo de la versión con el `numero_version` más alto

#### Scenario: El historial de validaciones de un documento es consultable, no solo su estado vigente
- **WHEN** un usuario consulta el detalle de un proceso que tiene un documento con más de un evento de validación
- **THEN** la respuesta incluye el historial completo de `validaciones_documento` de ese documento, ordenado del más reciente al más antiguo, con su resultado, observación, instancia, usuario y fecha
- **AND** la observación de un evento de rechazo pasado sigue siendo visible aunque el documento haya sido validado posteriormente

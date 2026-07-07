## REMOVED Requirements

### Requirement: Registrar cada corrida de importación SGF
**Reason**: Reemplazado por `trabajos_integracion` de la capa transversal de integraciones (`integraciones-api-browser-automation`), que ya registra fuente/mecanismo, quién inició la corrida y su resultado para cualquier sistema externo, no solo SGF.
**Migration**: Consultar `trabajos_integracion` filtrando por el `sistema_externo` de código `SGF` en vez de `importaciones_sgf`. La tabla `importaciones_sgf` se elimina.

### Requirement: Conservar snapshot inmutable de cada fila SGF
**Reason**: Reemplazado por `snapshots_datos_externos` de la capa transversal, que ya conserva payload crudo, normalizado y hash sin sobrescribir snapshots anteriores, para cualquier sistema externo.
**Migration**: Consultar `snapshots_datos_externos` filtrando por `sistema_externo_id` del sistema `SGF` y `referencia_externa` = `sgf_id`. La tabla `snapshots_sgf` se elimina.

### Requirement: Conservar documentos SGF en el expediente documental
**Reason**: Reemplazado por `snapshots_datos_externos_documentos`, la tabla de unión genérica que vincula documentos del expediente a cualquier `snapshot_datos_externo`, no solo a snapshots de SGF.
**Migration**: Usar `snapshots_datos_externos_documentos` en vez de `snapshots_sgf_documentos`. Esta última se elimina.

## MODIFIED Requirements

### Requirement: Mostrar el historial de snapshots SGF en el detalle del caso de pago
El sistema SHALL exponer, en el detalle de un `caso_pago_proveedor`, el historial completo de `snapshots_datos_externos` del sistema externo `SGF` cuya `referencia_externa` coincide con el `sgf_id` del caso, ordenado del más reciente al más antiguo, sin permiso adicional al ya requerido para ver el detalle del caso.

#### Scenario: Ver el historial de snapshots de un caso con varias importaciones
- **WHEN** un usuario autorizado abre el detalle de un `caso_pago_proveedor` cuyo `sgf_id` tiene más de un `snapshot_datos_externo` asociado
- **THEN** la respuesta incluye todos los snapshots de ese `sgf_id` en el sistema externo `SGF`, ordenados del más reciente al más antiguo
- **AND** cada snapshot incluye su fecha de captura (`capturado_en`), hash y el `trabajo_integracion` que lo produjo

#### Scenario: Ver el detalle de un snapshot
- **WHEN** un usuario expande un snapshot del historial
- **THEN** la página muestra su `payload_crudo` y `payload_normalizado` completos

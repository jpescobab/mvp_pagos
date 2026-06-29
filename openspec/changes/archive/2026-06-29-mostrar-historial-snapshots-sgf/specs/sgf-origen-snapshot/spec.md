## ADDED Requirements

### Requirement: Mostrar el historial de snapshots SGF en el detalle del caso de pago
El sistema SHALL exponer, en el detalle de un `caso_pago_proveedor`, el historial completo de `snapshots_sgf` cuyo `sgf_id` coincide con el del caso, ordenado del más reciente al más antiguo, sin permiso adicional al ya requerido para ver el detalle del caso.

#### Scenario: Ver el historial de snapshots de un caso con varias importaciones
- **WHEN** un usuario autorizado abre el detalle de un `caso_pago_proveedor` cuyo `sgf_id` tiene más de un `snapshot_sgf`
- **THEN** la respuesta incluye todos los snapshots de ese `sgf_id`, ordenados del más reciente al más antiguo
- **AND** cada snapshot incluye su fecha de captura, hash y la fuente de su `importacion_sgf`

#### Scenario: Ver el detalle de un snapshot
- **WHEN** un usuario expande un snapshot del historial
- **THEN** la página muestra su `payload_crudo` y `payload_normalizado` completos

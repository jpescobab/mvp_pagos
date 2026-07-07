# Spec: sgf-origen-snapshot

## Purpose

Capa de evidencia para datos provenientes de SGF: expone, en el detalle del caso de pago, el historial de `snapshots_datos_externos` (capturados por la capa transversal de integraciones) correspondientes al `sgf_id` del caso, sin gobernar workflow ni crear casos de pago. SGF es origen, no gobierno.

## Requirements

### Requirement: Mostrar el historial de snapshots SGF en el detalle del caso de pago
El sistema SHALL exponer, en el detalle de un `caso_pago_proveedor`, el historial completo de `snapshots_datos_externos` del sistema externo `SGF` cuya `referencia_externa` coincide con el `sgf_id` del caso, ordenado del más reciente al más antiguo, sin permiso adicional al ya requerido para ver el detalle del caso.

#### Scenario: Ver el historial de snapshots de un caso con varias importaciones
- **WHEN** un usuario autorizado abre el detalle de un `caso_pago_proveedor` cuyo `sgf_id` tiene más de un `snapshot_datos_externo` asociado
- **THEN** la respuesta incluye todos los snapshots de ese `sgf_id` en el sistema externo `SGF`, ordenados del más reciente al más antiguo
- **AND** cada snapshot incluye su fecha de captura (`capturado_en`), hash y el `trabajo_integracion` que lo produjo

#### Scenario: Ver el detalle de un snapshot
- **WHEN** un usuario expande un snapshot del historial
- **THEN** la página muestra su `payload_crudo` y `payload_normalizado` completos

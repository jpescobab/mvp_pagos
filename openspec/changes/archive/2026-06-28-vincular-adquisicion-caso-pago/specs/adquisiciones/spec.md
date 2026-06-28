## ADDED Requirements

### Requirement: Un proceso de adquisición expone sus casos de pago vinculados
El sistema SHALL permitir que un `proceso_adquisicion` consulte todos los `caso_pago_proveedor` que se hayan vinculado manualmente a él, sin que esto implique gobernar el workflow de esos casos.

#### Scenario: Ver casos de pago vinculados desde el detalle de una adquisición
- **WHEN** un usuario consulta el detalle de un `proceso_adquisicion` que tiene uno o más `caso_pago_proveedor` vinculados
- **THEN** el detalle incluye la lista de esos casos, identificados por su `sgf_id`
- **AND** la lista queda vacía si ningún caso ha sido vinculado todavía

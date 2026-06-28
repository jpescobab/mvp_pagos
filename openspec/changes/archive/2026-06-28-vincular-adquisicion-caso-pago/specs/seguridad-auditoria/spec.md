## ADDED Requirements

### Requirement: Permiso dedicado para vincular adquisiciones a casos de pago
El sistema SHALL definir el permiso `pago_proveedores.vincular_adquisicion`, distinto de los permisos de gestión del ciclo de vida de Adquisiciones (`adquisiciones.publicar`, `adquisiciones.adjudicar`, `adquisiciones.anular`), para gobernar quién puede crear o quitar el vínculo entre un `caso_pago_proveedor` y un `proceso_adquisicion`.

#### Scenario: Asignación inicial del permiso
- **WHEN** se ejecuta el seeder de roles y permisos del módulo Pago de Proveedores
- **THEN** el permiso `pago_proveedores.vincular_adquisicion` existe
- **AND** el rol `admin` lo tiene asignado

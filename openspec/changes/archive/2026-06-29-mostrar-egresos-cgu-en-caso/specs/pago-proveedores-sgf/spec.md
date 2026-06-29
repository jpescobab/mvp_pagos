## MODIFIED Requirements

### Requirement: Registrar CGU, BancoEstado y egreso CGU como evidencia

#### Scenario: Mostrar los egresos CGU asociados en el detalle de un caso de pago
- **WHEN** un usuario abre el detalle de un `caso_pago_proveedor` que ya tiene uno o más `egresos_cgu_items` asociados
- **THEN** la respuesta incluye cada `egreso_cgu` asociado, con su número, fecha y el monto del item correspondiente a ese caso
- **AND** cada egreso mostrado permite navegar a su propio detalle (`pago-proveedores.egresos-cgu.show`)

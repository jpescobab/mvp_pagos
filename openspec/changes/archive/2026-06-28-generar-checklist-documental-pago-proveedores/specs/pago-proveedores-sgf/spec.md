## ADDED Requirements

### Requirement: El checklist documental de un caso de pago se resuelve con una matriz real
El sistema SHALL mantener una matriz de `requisitos_documentales` concreta para el workflow "pago_proveedores", asociada a un `conjunto_requisitos_documentales` propio, reutilizando el catálogo de `tipos_documento` ya existente. El `tipo_documento` con código `FACTURA` SHALL existir y aplicarse, dado que ya es referenciado por la transición `aprobar_documentacion` del workflow de Pago de Proveedores.

#### Scenario: Seeder de requisitos documentales disponible
- **WHEN** se ejecuta el seeder de requisitos documentales de Pago de Proveedores
- **THEN** existe un `conjunto_requisitos_documentales` para el workflow "pago_proveedores"
- **AND** existen `requisitos_documentales` que incluyen `FACTURA` como obligatorio

### Requirement: El detalle de un caso de pago resuelve y muestra su checklist documental real
El sistema SHALL invocar la resolución del checklist documental al abrir el detalle de un `caso_pago_proveedor`, usando el `conjunto_requisitos_documentales` de Pago de Proveedores.

#### Scenario: Abrir el detalle de un caso de pago genera un checklist no vacío
- **WHEN** un usuario abre el detalle de un `caso_pago_proveedor`
- **THEN** el backend resuelve o actualiza su `checklist_documental_proceso` usando las reglas de Pago de Proveedores
- **AND** la respuesta incluye al menos el item correspondiente a `FACTURA`

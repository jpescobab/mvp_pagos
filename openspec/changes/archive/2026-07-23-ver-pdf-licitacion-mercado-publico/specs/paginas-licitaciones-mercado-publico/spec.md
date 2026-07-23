## MODIFIED Requirements

### Requirement: Acciones de encabezado para ver el JSON y el enlace a Mercado Público
El sistema SHALL ofrecer, junto al encabezado de la ficha de una Licitación, una acción "Ver JSON" que muestra el payload crudo del snapshot vinculado a esa Licitación, una acción "Mercado Público" que abre en una pestaña nueva el detalle oficial de esa Licitación en `mercadopublico.cl`, y una acción "Ver PDF" que descarga el PDF de la ficha de esa Licitación. Las tres acciones SHALL ofrecerse por igual sobre una Licitación guardada, una local o una en vista previa. Ninguna acción del encabezado SHALL mostrarse deshabilitada con la indicación "Disponible próximamente".

#### Scenario: Ver JSON con snapshot disponible
- **WHEN** un usuario hace clic en "Ver JSON" sobre una Licitación que tiene un snapshot de Mercado Público vinculado
- **THEN** el sistema muestra el payload crudo de ese snapshot

#### Scenario: Ver JSON sin snapshot disponible
- **WHEN** una Licitación no tiene ningún snapshot de Mercado Público vinculado
- **THEN** la acción "Ver JSON" queda deshabilitada

#### Scenario: Mercado Público abre el detalle oficial de la Licitación
- **WHEN** un usuario hace clic en "Mercado Público" sobre cualquier Licitación (guardada, local o en vista previa)
- **THEN** el sistema abre en una pestaña nueva el detalle oficial de esa Licitación en `mercadopublico.cl`, identificado por su código

#### Scenario: Ver PDF descarga la ficha
- **WHEN** un usuario hace clic en "Ver PDF" sobre cualquier Licitación (guardada, local o en vista previa)
- **THEN** el sistema entrega el PDF de la ficha de esa Licitación, identificada por su código

#### Scenario: No se pudo obtener el PDF
- **WHEN** el sistema no logra obtener el PDF de la Licitación desde Mercado Público
- **THEN** la página informa el error al usuario
- **AND** SHALL NOT entregar una descarga vacía ni dejar la acción sin respuesta

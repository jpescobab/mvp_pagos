## MODIFIED Requirements

### Requirement: Acciones de encabezado para ver el JSON, el PDF y el enlace a Mercado Público
El sistema SHALL ofrecer, junto al encabezado de la ficha de una OC, una acción "Ver JSON" que muestra el payload crudo del snapshot vinculado a esa OC, una acción "Mercado Público" que abre en una pestaña nueva el detalle oficial de esa OC en `mercadopublico.cl`, y una acción "Ver PDF" que descarga directamente el PDF de esa OC resolviendo el enlace real a través de un endpoint propio del backend.

#### Scenario: Ver JSON con snapshot disponible
- **WHEN** un usuario hace clic en "Ver JSON" sobre una OC que tiene un snapshot de Mercado Público vinculado
- **THEN** el sistema muestra el payload crudo de ese snapshot

#### Scenario: Ver JSON sin snapshot disponible
- **WHEN** una OC no tiene ningún snapshot de Mercado Público vinculado
- **THEN** la acción "Ver JSON" queda deshabilitada

#### Scenario: Mercado Público abre el detalle oficial de la OC
- **WHEN** un usuario hace clic en "Mercado Público" sobre cualquier OC (guardada, local o en vista previa)
- **THEN** el sistema abre en una pestaña nueva el detalle oficial de esa OC en `mercadopublico.cl`, identificado por su código

#### Scenario: Ver PDF descarga el archivo directamente
- **WHEN** un usuario hace clic en "Ver PDF" sobre cualquier OC (guardada, local o en vista previa) y el backend logra resolver el enlace de descarga desde la página pública de Mercado Público
- **THEN** el sistema redirige al navegador directamente al PDF de esa OC, descargándolo sin pasos intermedios

#### Scenario: Ver PDF cuando Mercado Público no expone el botón de descarga
- **WHEN** un usuario hace clic en "Ver PDF" y el backend no logra resolver el enlace de descarga (la OC ya no existe en Mercado Público o esa página no incluye el botón de PDF)
- **THEN** el sistema informa un error explícito en vez de intentar una descarga rota

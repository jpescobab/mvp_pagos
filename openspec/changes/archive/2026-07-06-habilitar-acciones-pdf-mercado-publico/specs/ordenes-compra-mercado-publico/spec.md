## ADDED Requirements

### Requirement: Resolver el enlace directo de descarga del PDF de una Orden de Compra
El sistema SHALL resolver, a partir del código de una Orden de Compra, el enlace real de descarga del PDF que Mercado Público expone en su página pública de detalle (`DetailsPurchaseOrder.aspx`), sin exigir que la OC exista localmente, y SHALL registrar la consulta como una `solicitud_api_externa` asociada al `sistema_externo` `MERCADO_PUBLICO`, reutilizando `IntegracionExternaService`.

#### Scenario: El botón de descarga existe en la página pública
- **WHEN** el sistema consulta la página pública de detalle de una OC en Mercado Público y esta incluye el botón nativo de descarga de PDF
- **THEN** el sistema extrae el enlace real de descarga y lo retorna
- **AND** registra la solicitud como exitosa

#### Scenario: La OC no existe o la página no incluye el botón de PDF
- **WHEN** la página pública de Mercado Público no incluye el botón de descarga de PDF para el código consultado
- **THEN** el sistema retorna que no fue posible resolver el enlace, sin construir una URL inválida
- **AND** registra la solicitud como no encontrada

#### Scenario: No se persiste el HTML consultado
- **WHEN** el sistema resuelve el enlace de descarga de PDF
- **THEN** no se crea ningún `snapshot_datos_externos` a partir de esa consulta, porque no es un dato de negocio que el sistema gobierne

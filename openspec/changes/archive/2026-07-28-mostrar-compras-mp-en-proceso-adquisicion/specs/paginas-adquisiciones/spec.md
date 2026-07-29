## ADDED Requirements

### Requirement: El detalle del proceso muestra sus compras de Mercado Público vinculadas

La página de detalle de un proceso de adquisición SHALL mostrar dos secciones: las órdenes de compra de Mercado Público vinculadas y las licitaciones de Mercado Público vinculadas. Cada ítem SHALL mostrar sus datos clave (código, organismo comprador, estado en Mercado Público) y SHALL enlazar a su página de detalle. Cuando el proceso no tiene compras vinculadas de un tipo, la sección correspondiente SHALL mostrar un vacío explícito.

#### Scenario: Se muestran las órdenes de compra vinculadas con enlace a su detalle

- **WHEN** un usuario abre el detalle de un proceso con una orden de compra de Mercado Público vinculada
- **THEN** ve la sección de órdenes de compra con esa orden y sus datos clave
- **AND** puede navegar a su detalle desde ahí

#### Scenario: Secciones vacías cuando no hay compras vinculadas

- **WHEN** un usuario abre el detalle de un proceso sin órdenes de compra ni licitaciones vinculadas
- **THEN** ambas secciones muestran un mensaje de vacío explícito en lugar de una lista

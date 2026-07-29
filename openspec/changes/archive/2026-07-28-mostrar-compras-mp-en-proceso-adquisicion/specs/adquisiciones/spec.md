## ADDED Requirements

### Requirement: Un proceso de adquisición expone sus compras de Mercado Público vinculadas

Un proceso de adquisición SHALL exponer las órdenes de compra y las licitaciones de Mercado Público que tienen su `proceso_adquisicion_id`. Para cada una, la exposición SHALL incluir al menos su identificador interno, su código, su estado en Mercado Público y su organismo comprador, de modo que puedan mostrarse y enlazarse a su detalle. El modelo `ProcesoAdquisicion` SHALL ofrecer relaciones para consultar tanto sus órdenes de compra como sus licitaciones de Mercado Público.

#### Scenario: El detalle expone las órdenes de compra vinculadas

- **WHEN** se consulta un proceso de adquisición que tiene órdenes de compra de Mercado Público vinculadas
- **THEN** la respuesta incluye esas órdenes de compra con su código, estado en Mercado Público y organismo comprador

#### Scenario: El detalle expone las licitaciones vinculadas

- **WHEN** se consulta un proceso de adquisición que tiene licitaciones de Mercado Público vinculadas
- **THEN** la respuesta incluye esas licitaciones con su código, estado en Mercado Público y organismo comprador

#### Scenario: Un proceso sin compras vinculadas expone colecciones vacías

- **WHEN** se consulta un proceso de adquisición sin órdenes de compra ni licitaciones vinculadas
- **THEN** ambas colecciones se exponen vacías, sin error

# Spec: consultar-catalogo-clientes-medidores

## Purpose

Exponer, de solo lectura, el catálogo institucional de `clientes_medidores` ya sembrado (número de cliente, proveedor, centro de costo, tipo de suministro), para que sea consultable antes de que el módulo funcional "Consumo eléctrico" exista.

## Requirements

### Requirement: Listar el catálogo de clientes medidores
El sistema SHALL exponer, a cualquier usuario autenticado, un listado de los `clientes_medidores` registrados con su número de cliente, proveedor, centro de costo, tipo de suministro, dirección y si está activo.

#### Scenario: Listar el catálogo de clientes medidores
- **WHEN** un usuario autenticado visita el catálogo de clientes medidores
- **THEN** la respuesta incluye todos los `clientes_medidores` con su número de cliente, proveedor, centro de costo, tipo de suministro, dirección y si está activo

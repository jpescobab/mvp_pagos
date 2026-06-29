## ADDED Requirements

### Requirement: Listar las definiciones de workflow existentes
El sistema SHALL exponer, a cualquier usuario autenticado, un listado de las `DefinicionWorkflow` existentes con su código, nombre, si está activa y la cantidad de estados y transiciones que define.

#### Scenario: Listar definiciones de workflow
- **WHEN** un usuario autenticado visita el listado de definiciones de workflow
- **THEN** la respuesta incluye cada `DefinicionWorkflow` con su código, nombre, estado activo/inactivo y la cantidad de estados y transiciones

### Requirement: Ver el detalle de una definición de workflow
El sistema SHALL exponer, a cualquier usuario autenticado, el detalle completo de una `DefinicionWorkflow`: todos sus estados (incluyendo si son iniciales o finales) y todas sus transiciones (estado origen, estado destino, permiso requerido, documentos requeridos y si exige comentario).

#### Scenario: Ver el detalle de una definición con sus estados y transiciones
- **WHEN** un usuario autenticado abre el detalle de una `DefinicionWorkflow`
- **THEN** la respuesta incluye todos sus `estados_workflow`, marcando cuáles son `es_inicial` o `es_final`
- **AND** incluye todas sus `transiciones_workflow`, cada una con su estado origen, estado destino, `permiso_requerido`, `documentos_requeridos` y `requiere_comentario`

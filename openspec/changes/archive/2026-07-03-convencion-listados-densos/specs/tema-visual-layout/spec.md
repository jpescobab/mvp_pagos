## ADDED Requirements

### Requirement: Listados tabulares densos
El sistema SHALL presentar cualquier página de listado/índice tabular (catálogos de consulta, tablas maestras u otro listado paginado) con: columnas de ancho fijo que no se reordenan según el contenido más largo, una identidad visual (avatar con iniciales u otro indicador equivalente) junto al campo principal de cada fila, un badge de estado con los tokens semánticos del tema cuando la entidad tenga un estado, columnas secundarias con una sola línea de texto truncada y su valor completo disponible al pasar el cursor, ocultamiento progresivo de columnas secundarias en viewports angostos, y un menú de acciones desplegable al final de la fila (en vez de íconos sueltos) donde las acciones aún no implementadas se muestran deshabilitadas con la indicación "Disponible próximamente".

#### Scenario: Columnas de ancho fijo
- **WHEN** un usuario autenticado visualiza un listado tabular con el sidebar expandido
- **THEN** todas las columnas permanecen visibles dentro del viewport, sin que el contenido más largo de una columna empuje a otra columna fuera de la vista

#### Scenario: Texto truncado con valor completo accesible
- **WHEN** el valor de una columna secundaria (por ejemplo dirección o correo) excede el ancho disponible de su columna
- **THEN** el texto se trunca en una sola línea y su valor completo queda disponible al pasar el cursor sobre la celda

#### Scenario: Columnas secundarias ocultas en viewports angostos
- **WHEN** un usuario autenticado visualiza un listado tabular en un viewport angosto
- **THEN** las columnas secundarias se ocultan progresivamente, dejando visibles el campo principal, el estado y las acciones

#### Scenario: Acciones agrupadas en un menú desplegable
- **WHEN** un usuario autenticado abre el menú de acciones de una fila de un listado tabular
- **THEN** las acciones se muestran agrupadas en un desplegable en vez de íconos sueltos en la fila
- **AND** las acciones que aún no están implementadas se muestran deshabilitadas con la indicación "Disponible próximamente"

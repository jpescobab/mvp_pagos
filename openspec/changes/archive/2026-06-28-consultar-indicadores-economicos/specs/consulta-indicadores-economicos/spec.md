## ADDED Requirements

### Requirement: Listar indicadores económicos importados vía HTTP
El sistema SHALL exponer una ruta autenticada que liste los `indicadores_economicos` ya importados, paginados y ordenados del más reciente al más antiguo, opcionalmente filtrados por `tipo` (`UF`, `USD`, `UTM`, `UTA`, `IPC`).

#### Scenario: Listar todos los indicadores
- **WHEN** un usuario autenticado visita la página de indicadores económicos sin filtro
- **THEN** la respuesta incluye los `indicadores_economicos` paginados de todos los tipos, ordenados del más reciente al más antiguo

#### Scenario: Filtrar por tipo
- **WHEN** un usuario autenticado solicita el listado con `tipo=UF`
- **THEN** la respuesta incluye solo los indicadores con `tipo = 'UF'`

#### Scenario: Usuario no autenticado no puede acceder
- **WHEN** un usuario no autenticado intenta acceder al listado de indicadores económicos
- **THEN** el sistema redirige al login

### Requirement: Página de consulta de indicadores económicos
El sistema SHALL renderizar una página que muestre los indicadores económicos paginados (tipo, fecha de valor o periodo, valor, fuente) con un control para filtrar por tipo, sin permitir edición ni eliminación desde la UI.

#### Scenario: Ver el listado de indicadores
- **WHEN** un usuario autenticado visita la página de indicadores económicos
- **THEN** la página muestra una fila por cada indicador recibido, con su tipo, fecha de valor o periodo, valor y fuente

#### Scenario: Cambiar el filtro de tipo
- **WHEN** un usuario selecciona un tipo distinto en el control de filtro
- **THEN** la página solicita el listado filtrado por ese tipo al backend

#### Scenario: Sin indicadores importados todavía
- **WHEN** no existe ningún `indicador_economico` para el filtro seleccionado
- **THEN** la página muestra un estado vacío explícito en lugar de una tabla vacía sin contexto

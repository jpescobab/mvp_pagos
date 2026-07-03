# Spec: consulta-catalogo-centros-financieros-costos

## Purpose

Permite a los usuarios con permisos administrativos institucionales (`core_institucional.administrar`) consultar los catálogos de centros financieros y centros de costo de la jerarquía institucional CAPJ (`instituciones -> jurisdicciones -> cfinancieros -> ccostos`), con búsqueda y paginación, mostrando la relación jerárquica inmediata de cada registro.

## Requirements

### Requirement: Consultar catálogo de centros financieros
El sistema SHALL permitir a los usuarios con el permiso `core_institucional.administrar` consultar un listado paginado de centros financieros (`cfinancieros`), con búsqueda por código o nombre, mostrando la jurisdicción asociada a cada uno.

#### Scenario: Listar centros financieros
- **WHEN** un usuario con el permiso `core_institucional.administrar` visita el listado de centros financieros
- **THEN** el sistema muestra un listado paginado con código, nombre, jurisdicción asociada y estado activo/inactivo de cada centro financiero

#### Scenario: Buscar por código o nombre
- **WHEN** el usuario ingresa un término de búsqueda en el listado de centros financieros
- **THEN** el sistema filtra los resultados por coincidencia parcial en el código o el nombre del centro financiero

#### Scenario: Acceso denegado sin el permiso
- **WHEN** un usuario autenticado sin el permiso `core_institucional.administrar` intenta acceder al listado de centros financieros
- **THEN** el sistema deniega el acceso

### Requirement: Consultar catálogo de centros de costo
El sistema SHALL permitir a los usuarios con el permiso `core_institucional.administrar` consultar un listado paginado de centros de costo (`ccostos`), con búsqueda por código o nombre, mostrando el centro financiero asociado a cada uno.

#### Scenario: Listar centros de costo
- **WHEN** un usuario con el permiso `core_institucional.administrar` visita el listado de centros de costo
- **THEN** el sistema muestra un listado paginado con código, nombre, centro financiero asociado, código de edificio (cuando exista) y estado activo/inactivo de cada centro de costo

#### Scenario: Buscar por código o nombre
- **WHEN** el usuario ingresa un término de búsqueda en el listado de centros de costo
- **THEN** el sistema filtra los resultados por coincidencia parcial en el código o el nombre del centro de costo

#### Scenario: Centro de costo sin código de edificio
- **WHEN** un centro de costo no tiene `cod_edificio` registrado
- **THEN** el listado muestra un indicador de valor vacío en esa columna en vez de omitir la fila o mostrar un error

#### Scenario: Acceso denegado sin el permiso
- **WHEN** un usuario autenticado sin el permiso `core_institucional.administrar` intenta acceder al listado de centros de costo
- **THEN** el sistema deniega el acceso

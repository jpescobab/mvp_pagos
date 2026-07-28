# Spec: administracion-instituciones-jurisdicciones

## Purpose

Permite a los usuarios con permisos administrativos institucionales (`core_institucional.administrar`) administrar —listar, ver, crear, editar y eliminar— los dos niveles superiores de la jerarquía institucional CAPJ (`instituciones -> jurisdicciones -> cfinancieros -> ccostos`) desde la aplicación, cerrando la asimetría con los dos niveles inferiores, que ya eran administrables. Cubre búsqueda, unicidad por código institucional, navegación jerárquica en ambos sentidos, protección ante eliminación con dependencias y el acceso a estos catálogos desde la navegación.

## Requirements

### Requirement: Consultar el catálogo de instituciones
El sistema SHALL permitir a los usuarios con el permiso `core_institucional.administrar` consultar un listado paginado de instituciones, con búsqueda por coincidencia parcial en el código o el nombre, mostrando el código institucional, el nombre, el estado activo/inactivo y la cantidad de jurisdicciones que dependen de cada institución.

#### Scenario: Listar instituciones
- **WHEN** un usuario con el permiso `core_institucional.administrar` visita el listado de instituciones
- **THEN** el sistema muestra un listado paginado con el código, el nombre, el estado activo/inactivo y la cantidad de jurisdicciones de cada institución

#### Scenario: Buscar por código o nombre
- **WHEN** el usuario ingresa un término de búsqueda en el listado de instituciones
- **THEN** el sistema filtra los resultados por coincidencia parcial en el código o en el nombre de la institución

#### Scenario: Acceso denegado sin el permiso
- **WHEN** un usuario autenticado sin el permiso `core_institucional.administrar` intenta acceder al listado de instituciones
- **THEN** el sistema deniega el acceso

### Requirement: Ver el detalle de una institución
El sistema SHALL permitir a los usuarios con el permiso `core_institucional.administrar` ver el detalle de una institución, incluyendo sus atributos y el listado de las jurisdicciones que dependen de ella, cada una enlazada a su propio detalle.

#### Scenario: Ver una institución con jurisdicciones
- **WHEN** un usuario con el permiso `core_institucional.administrar` abre el detalle de una institución que tiene jurisdicciones asociadas
- **THEN** el sistema muestra los atributos de la institución y el listado de sus jurisdicciones, cada una enlazada a su detalle

#### Scenario: Ver una institución sin jurisdicciones
- **WHEN** un usuario con el permiso `core_institucional.administrar` abre el detalle de una institución que no tiene jurisdicciones asociadas
- **THEN** el sistema muestra los atributos de la institución y un mensaje de listado vacío, sin error

#### Scenario: Acceso denegado sin el permiso
- **WHEN** un usuario autenticado sin el permiso `core_institucional.administrar` intenta ver el detalle de una institución
- **THEN** el sistema deniega el acceso

### Requirement: Registrar una institución
El sistema SHALL permitir a los usuarios con el permiso `core_institucional.administrar` registrar una institución con código y nombre obligatorios y estado activo opcional. El código SHALL ser único entre todas las instituciones. Una institución registrada sin estado explícito SHALL quedar activa.

#### Scenario: Registrar una institución válida
- **WHEN** un usuario con el permiso `core_institucional.administrar` envía código y nombre válidos
- **THEN** el sistema crea la institución, la deja activa y redirige al listado con un mensaje de confirmación

#### Scenario: Código duplicado
- **WHEN** el usuario envía un código que ya pertenece a otra institución
- **THEN** el sistema rechaza la solicitud con un error de validación sobre el campo del código y no crea la institución

#### Scenario: Registro denegado sin el permiso
- **WHEN** un usuario autenticado sin el permiso `core_institucional.administrar` intenta registrar una institución
- **THEN** el sistema rechaza la solicitud y no crea la institución

### Requirement: Editar una institución
El sistema SHALL permitir a los usuarios con el permiso `core_institucional.administrar` editar el código, el nombre y el estado activo/inactivo de una institución. El código SHALL seguir siendo único entre las instituciones, ignorando la propia institución editada.

#### Scenario: Editar una institución
- **WHEN** un usuario con el permiso `core_institucional.administrar` envía cambios válidos sobre una institución
- **THEN** el sistema actualiza la institución y redirige a su detalle con un mensaje de confirmación

#### Scenario: Conservar el propio código al editar
- **WHEN** el usuario guarda una institución sin modificar su código
- **THEN** el sistema acepta la edición y no reporta el código como duplicado consigo mismo

#### Scenario: Código de otra institución
- **WHEN** el usuario envía un código que ya pertenece a otra institución
- **THEN** el sistema rechaza la solicitud con un error de validación y no modifica la institución

#### Scenario: Edición denegada sin el permiso
- **WHEN** un usuario autenticado sin el permiso `core_institucional.administrar` intenta editar una institución
- **THEN** el sistema rechaza la solicitud y no modifica la institución

### Requirement: Eliminar una institución sin dependencias
El sistema SHALL permitir a los usuarios con el permiso `core_institucional.administrar` eliminar una institución que no tenga jurisdicciones asociadas. Si la institución tiene jurisdicciones, el sistema SHALL rechazar la eliminación con un mensaje que indique la dependencia, sin eliminar nada y sin exponer un error de restricción de clave foránea.

#### Scenario: Eliminar una institución sin jurisdicciones
- **WHEN** un usuario con el permiso `core_institucional.administrar` elimina una institución que no tiene jurisdicciones
- **THEN** el sistema elimina la institución y redirige al listado con un mensaje de confirmación

#### Scenario: Eliminación bloqueada por jurisdicciones asociadas
- **WHEN** el usuario intenta eliminar una institución que tiene al menos una jurisdicción asociada
- **THEN** el sistema rechaza la eliminación, informa que la institución tiene jurisdicciones asociadas y conserva la institución y sus jurisdicciones

#### Scenario: Eliminación denegada sin el permiso
- **WHEN** un usuario autenticado sin el permiso `core_institucional.administrar` intenta eliminar una institución
- **THEN** el sistema rechaza la solicitud y no elimina la institución

### Requirement: Consultar el catálogo de jurisdicciones
El sistema SHALL permitir a los usuarios con el permiso `core_institucional.administrar` consultar un listado paginado de jurisdicciones, con búsqueda por coincidencia parcial en el código o el nombre, mostrando el código, el nombre, la institución a la que pertenece cada una, su descripción cuando exista y el estado activo/inactivo.

#### Scenario: Listar jurisdicciones
- **WHEN** un usuario con el permiso `core_institucional.administrar` visita el listado de jurisdicciones
- **THEN** el sistema muestra un listado paginado con el código, el nombre, la institución asociada, la descripción y el estado activo/inactivo de cada jurisdicción

#### Scenario: Buscar por código o nombre
- **WHEN** el usuario ingresa un término de búsqueda en el listado de jurisdicciones
- **THEN** el sistema filtra los resultados por coincidencia parcial en el código o en el nombre de la jurisdicción

#### Scenario: Jurisdicción sin descripción
- **WHEN** una jurisdicción no tiene descripción registrada
- **THEN** el listado muestra un indicador de valor vacío en esa columna, sin omitir la fila ni mostrar un error

#### Scenario: Acceso denegado sin el permiso
- **WHEN** un usuario autenticado sin el permiso `core_institucional.administrar` intenta acceder al listado de jurisdicciones
- **THEN** el sistema deniega el acceso

### Requirement: Ver el detalle de una jurisdicción
El sistema SHALL permitir a los usuarios con el permiso `core_institucional.administrar` ver el detalle de una jurisdicción, incluyendo sus atributos, la institución a la que pertenece —enlazada a su propio detalle— y el listado de los centros financieros que dependen de ella.

#### Scenario: Ver una jurisdicción con centros financieros
- **WHEN** un usuario con el permiso `core_institucional.administrar` abre el detalle de una jurisdicción que tiene centros financieros asociados
- **THEN** el sistema muestra los atributos de la jurisdicción, su institución enlazada y el listado de sus centros financieros, cada uno enlazado a su detalle

#### Scenario: Ver una jurisdicción sin centros financieros
- **WHEN** un usuario con el permiso `core_institucional.administrar` abre el detalle de una jurisdicción que no tiene centros financieros asociados
- **THEN** el sistema muestra los atributos de la jurisdicción y un mensaje de listado vacío, sin error

#### Scenario: Acceso denegado sin el permiso
- **WHEN** un usuario autenticado sin el permiso `core_institucional.administrar` intenta ver el detalle de una jurisdicción
- **THEN** el sistema deniega el acceso

### Requirement: Registrar una jurisdicción
El sistema SHALL permitir a los usuarios con el permiso `core_institucional.administrar` registrar una jurisdicción indicando la institución a la que pertenece, su código y su nombre como campos obligatorios, y la descripción y el estado activo como opcionales. El código SHALL ser único entre todas las jurisdicciones. La institución indicada SHALL existir. Una jurisdicción registrada sin estado explícito SHALL quedar activa.

#### Scenario: Registrar una jurisdicción válida
- **WHEN** un usuario con el permiso `core_institucional.administrar` envía una institución existente junto con código y nombre válidos
- **THEN** el sistema crea la jurisdicción bajo esa institución, la deja activa y redirige al listado con un mensaje de confirmación

#### Scenario: Código duplicado
- **WHEN** el usuario envía un código que ya pertenece a otra jurisdicción
- **THEN** el sistema rechaza la solicitud con un error de validación sobre el campo del código y no crea la jurisdicción

#### Scenario: Institución inexistente
- **WHEN** el usuario envía una institución que no existe
- **THEN** el sistema rechaza la solicitud con un error de validación y no crea la jurisdicción

#### Scenario: Registro denegado sin el permiso
- **WHEN** un usuario autenticado sin el permiso `core_institucional.administrar` intenta registrar una jurisdicción
- **THEN** el sistema rechaza la solicitud y no crea la jurisdicción

### Requirement: Editar una jurisdicción
El sistema SHALL permitir a los usuarios con el permiso `core_institucional.administrar` editar la institución, el código, el nombre, la descripción y el estado activo/inactivo de una jurisdicción. El código SHALL seguir siendo único entre las jurisdicciones, ignorando la propia jurisdicción editada.

#### Scenario: Editar una jurisdicción
- **WHEN** un usuario con el permiso `core_institucional.administrar` envía cambios válidos sobre una jurisdicción
- **THEN** el sistema actualiza la jurisdicción y redirige a su detalle con un mensaje de confirmación

#### Scenario: Conservar el propio código al editar
- **WHEN** el usuario guarda una jurisdicción sin modificar su código
- **THEN** el sistema acepta la edición y no reporta el código como duplicado consigo mismo

#### Scenario: Reasignar la jurisdicción a otra institución
- **WHEN** el usuario cambia la institución de una jurisdicción por otra institución existente
- **THEN** el sistema actualiza la relación y la jurisdicción pasa a depender de la nueva institución

#### Scenario: Edición denegada sin el permiso
- **WHEN** un usuario autenticado sin el permiso `core_institucional.administrar` intenta editar una jurisdicción
- **THEN** el sistema rechaza la solicitud y no modifica la jurisdicción

### Requirement: Eliminar una jurisdicción sin dependencias
El sistema SHALL permitir a los usuarios con el permiso `core_institucional.administrar` eliminar una jurisdicción que no tenga centros financieros asociados. Si la jurisdicción tiene centros financieros, el sistema SHALL rechazar la eliminación con un mensaje que indique la dependencia, sin eliminar nada y sin exponer un error de restricción de clave foránea.

#### Scenario: Eliminar una jurisdicción sin centros financieros
- **WHEN** un usuario con el permiso `core_institucional.administrar` elimina una jurisdicción que no tiene centros financieros
- **THEN** el sistema elimina la jurisdicción y redirige al listado con un mensaje de confirmación

#### Scenario: Eliminación bloqueada por centros financieros asociados
- **WHEN** el usuario intenta eliminar una jurisdicción que tiene al menos un centro financiero asociado
- **THEN** el sistema rechaza la eliminación, informa que la jurisdicción tiene centros financieros asociados y conserva la jurisdicción y sus centros financieros

#### Scenario: Eliminación denegada sin el permiso
- **WHEN** un usuario autenticado sin el permiso `core_institucional.administrar` intenta eliminar una jurisdicción
- **THEN** el sistema rechaza la solicitud y no elimina la jurisdicción

### Requirement: Navegar la estructura institucional completa desde el menú
El sistema SHALL ofrecer, en el grupo de navegación de estructura institucional, accesos a instituciones y jurisdicciones además de los existentes a centros financieros y centros de costo, ordenados de mayor a menor nivel jerárquico. Cada acceso SHALL estar condicionado al permiso `core_institucional.administrar`.

#### Scenario: Usuario con el permiso ve los cuatro niveles
- **WHEN** un usuario con el permiso `core_institucional.administrar` abre la navegación
- **THEN** el grupo de estructura institucional ofrece instituciones, jurisdicciones, centros financieros y centros de costo, en ese orden

#### Scenario: Usuario sin el permiso no ve los accesos
- **WHEN** un usuario autenticado sin el permiso `core_institucional.administrar` abre la navegación
- **THEN** el grupo de estructura institucional no ofrece los accesos a instituciones ni a jurisdicciones

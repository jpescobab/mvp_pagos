## MODIFIED Requirements

### Requirement: Crear una definición de informe razonado
El sistema SHALL permitir crear una `definicion_informe_razonado` con código, nombre y descripción únicamente a los usuarios con el permiso `informes.administrar`. El `codigo` SHALL ser único entre todas las definiciones. Una definición creada sin estado explícito SHALL quedar activa.

#### Scenario: Crear una definición con permiso
- **WHEN** un usuario con el permiso `informes.administrar` crea una definición de informe razonado con código y nombre
- **THEN** se crea una `definicion_informe_razonado` activa

#### Scenario: Crear sin el permiso es rechazado
- **WHEN** un usuario autenticado sin el permiso `informes.administrar` intenta crear una definición de informe razonado
- **THEN** el sistema rechaza la solicitud y no crea ninguna `definicion_informe_razonado`

#### Scenario: Código duplicado
- **WHEN** un usuario con el permiso `informes.administrar` envía un código que ya pertenece a otra definición
- **THEN** el sistema rechaza la solicitud con un error de validación sobre el campo del código y no crea la definición

## ADDED Requirements

### Requirement: Ver el detalle de una definición de informe razonado
El sistema SHALL permitir a los usuarios con el permiso `informes.ver` ver el detalle de una `definicion_informe_razonado`, incluyendo sus atributos y el listado de las ejecuciones que se generaron a partir de ella, cada una enlazada a su propio detalle.

#### Scenario: Ver una definición con ejecuciones
- **WHEN** un usuario con el permiso `informes.ver` abre el detalle de una definición que tiene ejecuciones asociadas
- **THEN** el sistema muestra los atributos de la definición y el listado de sus ejecuciones, cada una enlazada a su detalle

#### Scenario: Ver una definición sin ejecuciones
- **WHEN** un usuario con el permiso `informes.ver` abre el detalle de una definición que no tiene ejecuciones asociadas
- **THEN** el sistema muestra los atributos de la definición y un mensaje de listado vacío, sin error

#### Scenario: Acceso denegado sin el permiso
- **WHEN** un usuario autenticado sin el permiso `informes.ver` intenta ver el detalle de una definición
- **THEN** el sistema deniega el acceso

### Requirement: Editar una definición de informe razonado
El sistema SHALL permitir a los usuarios con el permiso `informes.administrar` editar el código, el nombre, la descripción y el estado activo/inactivo de una `definicion_informe_razonado`. El `codigo` SHALL seguir siendo único entre las definiciones, ignorando la propia definición editada.

#### Scenario: Editar una definición con permiso
- **WHEN** un usuario con el permiso `informes.administrar` envía cambios válidos sobre una definición
- **THEN** el sistema actualiza la definición y redirige a su detalle con un mensaje de confirmación

#### Scenario: Conservar el propio código al editar
- **WHEN** el usuario guarda una definición sin modificar su código
- **THEN** el sistema acepta la edición y no reporta el código como duplicado consigo mismo

#### Scenario: Edición denegada sin el permiso
- **WHEN** un usuario autenticado sin el permiso `informes.administrar` intenta editar una definición
- **THEN** el sistema rechaza la solicitud y no modifica la definición

### Requirement: Eliminar una definición de informe razonado sin ejecuciones
El sistema SHALL permitir a los usuarios con el permiso `informes.administrar` eliminar una `definicion_informe_razonado` que no tenga ejecuciones asociadas. Si la definición tiene ejecuciones, el sistema SHALL rechazar la eliminación con un mensaje que indique la dependencia, sin eliminar nada, para preservar la trazabilidad de los informes ya generados.

#### Scenario: Eliminar una definición sin ejecuciones
- **WHEN** un usuario con el permiso `informes.administrar` elimina una definición que no tiene ejecuciones
- **THEN** el sistema elimina la definición y redirige al listado con un mensaje de confirmación

#### Scenario: Eliminación bloqueada por ejecuciones asociadas
- **WHEN** el usuario intenta eliminar una definición que tiene al menos una ejecución asociada
- **THEN** el sistema rechaza la eliminación, informa que la definición tiene ejecuciones asociadas y conserva la definición y sus ejecuciones

#### Scenario: Eliminación denegada sin el permiso
- **WHEN** un usuario autenticado sin el permiso `informes.administrar` intenta eliminar una definición
- **THEN** el sistema rechaza la solicitud y no elimina la definición

### Requirement: Consultar el catálogo de definiciones de informes razonados con búsqueda
El sistema SHALL permitir a los usuarios con el permiso `informes.ver` consultar un listado paginado de definiciones de informe razonado, con búsqueda por coincidencia parcial en el código o el nombre, mostrando el código, el nombre, la descripción, el estado activo/inactivo y la cantidad de ejecuciones generadas a partir de cada definición.

#### Scenario: Listar definiciones
- **WHEN** un usuario con el permiso `informes.ver` visita el listado de definiciones
- **THEN** el sistema muestra un listado paginado con el código, el nombre, la descripción, el estado y la cantidad de ejecuciones de cada definición

#### Scenario: Buscar por código o nombre
- **WHEN** el usuario ingresa un término de búsqueda en el listado de definiciones
- **THEN** el sistema filtra los resultados por coincidencia parcial en el código o en el nombre de la definición

#### Scenario: Acceso denegado sin el permiso
- **WHEN** un usuario autenticado sin el permiso `informes.ver` intenta acceder al listado de definiciones
- **THEN** el sistema deniega el acceso

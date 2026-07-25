## ADDED Requirements

### Requirement: El acceso a los procesos de adquisición vía HTTP se controla por permiso

El sistema SHALL restringir el listado, la visualización y la creación de `procesos_adquisicion` vía HTTP a usuarios que posean el permiso correspondiente, resuelto por `ProcesoAdquisicionPolicy`. Listar y ver el detalle SHALL exigir el permiso `adquisiciones.consultar_proceso`; crear (formulario y guardado) SHALL exigir el permiso `adquisiciones.crear_proceso`. Un usuario autenticado sin el permiso requerido SHALL recibir una respuesta HTTP 403. El rol `superadmin` conserva acceso total vía `Gate::before`, sin necesidad de estos permisos.

#### Scenario: Listar procesos con el permiso de consulta

- **WHEN** un usuario autenticado con el permiso `adquisiciones.consultar_proceso` solicita la lista de procesos de adquisición
- **THEN** el sistema responde con la página Inertia del listado paginado

#### Scenario: Ver el detalle de un proceso con el permiso de consulta

- **WHEN** un usuario autenticado con el permiso `adquisiciones.consultar_proceso` solicita el detalle de un proceso de adquisición
- **THEN** el sistema responde con la página Inertia del detalle

#### Scenario: Rechazar el listado o el detalle sin el permiso de consulta

- **WHEN** un usuario autenticado sin el permiso `adquisiciones.consultar_proceso` solicita el listado o el detalle de un proceso de adquisición
- **THEN** el sistema rechaza la petición con una respuesta HTTP 403

#### Scenario: Crear un proceso con el permiso de creación

- **WHEN** un usuario autenticado con el permiso `adquisiciones.crear_proceso` solicita el formulario de creación o envía datos válidos para crear un proceso de adquisición
- **THEN** el sistema muestra el formulario o crea el `proceso_adquisicion` según corresponda

#### Scenario: Rechazar la creación sin el permiso de creación

- **WHEN** un usuario autenticado sin el permiso `adquisiciones.crear_proceso` solicita el formulario de creación o intenta guardar un proceso de adquisición
- **THEN** el sistema rechaza la petición con una respuesta HTTP 403
- **AND** no se crea ningún `proceso_adquisicion`

#### Scenario: superadmin accede sin los permisos explícitos

- **WHEN** un usuario con el rol `superadmin` lista, ve o crea un proceso de adquisición sin tener asignados `adquisiciones.consultar_proceso` ni `adquisiciones.crear_proceso`
- **THEN** el sistema permite la operación por la regla `Gate::before`

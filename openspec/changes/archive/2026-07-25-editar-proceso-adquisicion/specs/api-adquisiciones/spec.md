## ADDED Requirements

### Requirement: Editar y actualizar un proceso de adquisición vía HTTP

El sistema SHALL exponer rutas autenticadas para editar y actualizar un `proceso_adquisicion`, gobernadas por el permiso `adquisiciones.editar_proceso` resuelto por `ProcesoAdquisicionPolicy::update`. La ruta de edición (`GET`) SHALL entregar el proceso con sus valores actuales más las modalidades activas, los centros de costo y los proveedores disponibles. La ruta de actualización SHALL delegar íntegramente en `ProcesoAdquisicionService::actualizar()`, sin duplicar la validación de modalidad, la sincronización del `Proceso` ni la regla de editabilidad por estado. Un usuario autenticado sin el permiso SHALL recibir una respuesta HTTP 403. `superadmin` conserva acceso vía `Gate::before`.

#### Scenario: Solicitar el formulario de edición con permiso

- **WHEN** un usuario con el permiso `adquisiciones.editar_proceso` solicita la edición de un proceso de adquisición
- **THEN** el sistema responde con una página Inertia que incluye los valores actuales del proceso, las modalidades activas, los centros de costo y los proveedores

#### Scenario: Actualizar un proceso en borrador con datos válidos

- **WHEN** un usuario con el permiso `adquisiciones.editar_proceso` envía datos válidos para actualizar un proceso de adquisición que está en estado `borrador`
- **THEN** el sistema actualiza el proceso y su `Proceso` asociado
- **AND** redirige al detalle del proceso

#### Scenario: Rechazar la edición o actualización sin permiso

- **WHEN** un usuario autenticado sin el permiso `adquisiciones.editar_proceso` solicita editar o actualizar un proceso de adquisición
- **THEN** el sistema rechaza la petición con una respuesta HTTP 403
- **AND** no modifica el proceso

#### Scenario: Rechazar la actualización de un proceso fuera de borrador

- **WHEN** un usuario con el permiso intenta actualizar un proceso de adquisición cuyo estado ya no es `borrador`
- **THEN** el sistema traduce la excepción de dominio a una respuesta HTTP de error apropiada
- **AND** no modifica el proceso

#### Scenario: El código permanece único ignorando el propio registro

- **WHEN** un usuario actualiza un proceso de adquisición sin cambiar su `codigo`
- **THEN** la validación de unicidad de `codigo` no se dispara contra el propio registro y la actualización se acepta

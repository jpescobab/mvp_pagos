## ADDED Requirements

### Requirement: Restringir el listado de tablas maestras al mismo permiso de administración
El sistema SHALL exigir el permiso `core_institucional.administrar` para listar (no solo para ver el detalle, crear, editar o eliminar) proveedores, ítems presupuestarios y clientes medidores.

#### Scenario: Listar con permiso
- **WHEN** un usuario con el permiso `core_institucional.administrar` visita el listado de proveedores, ítems presupuestarios o clientes medidores
- **THEN** el sistema muestra el listado correspondiente

#### Scenario: Usuario sin permiso no puede listar
- **WHEN** un usuario autenticado sin el permiso `core_institucional.administrar` intenta visitar el listado de proveedores, ítems presupuestarios o clientes medidores
- **THEN** el sistema rechaza la solicitud

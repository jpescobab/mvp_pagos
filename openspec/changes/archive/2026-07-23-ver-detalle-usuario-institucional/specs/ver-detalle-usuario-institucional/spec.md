## ADDED Requirements

### Requirement: Ver el detalle de un usuario institucional
El sistema SHALL exponer una página autorizada por la policy `view` de `User` (permiso `usuarios.ver`) que muestre, en modo consulta, el detalle de un usuario institucional: su identidad y estado de cuenta (nombre, email, RUT, cargo, unidad, si la cuenta está activa o inactiva, último acceso y fecha de creación) y su ámbito institucional (jurisdicción, centro financiero y centro de costo derivados del `Funcionario` vinculado). Los campos opcionales que estén en `null` SHALL mostrarse con un fallback explícito en vez de un espacio vacío. La página SHALL ser de solo lectura respecto de los datos que presenta: SHALL NOT escribir en `audit_logs` ni en `security_audit_logs`, y SHALL NOT modificar roles ni permisos.

#### Scenario: Ver el detalle con permiso
- **WHEN** un usuario con el permiso `usuarios.ver` visita la página de detalle de un usuario institucional
- **THEN** la página muestra el nombre, email, RUT, cargo, unidad, estado de la cuenta, último acceso y fecha de creación del usuario
- **AND** muestra su jurisdicción, centro financiero y centro de costo

#### Scenario: Usuario sin permiso intenta ver el detalle
- **WHEN** un usuario sin el permiso `usuarios.ver` intenta acceder a la página de detalle de un usuario
- **THEN** el sistema bloquea la operación
- **AND** registra el evento de autorización denegada en `security_audit_logs`

#### Scenario: Usuario sin funcionario vinculado
- **WHEN** se visita el detalle de un usuario que no tiene un `Funcionario` asociado
- **THEN** la página muestra igualmente su identidad y estado de cuenta
- **AND** muestra un fallback explícito en RUT, cargo, unidad, jurisdicción, centro financiero y centro de costo en vez de omitir esos campos

### Requirement: Mostrar roles y permisos efectivos del usuario
La página de detalle SHALL mostrar los roles asignados al usuario y los permisos efectivos que de ellos se derivan. Cuando el usuario tiene el rol `superadmin`, la página SHALL indicar que posee acceso total —porque ese acceso proviene del `Gate::before` y no de una asignación explícita de permisos— en vez de enumerar la lista completa de permisos del sistema. La página SHALL leer los permisos sin usar la caché de permisos del usuario autenticado, de modo que un cambio de roles recién aplicado se refleje de inmediato.

#### Scenario: Usuario con roles y permisos concretos
- **WHEN** se visita el detalle de un usuario que tiene roles asignados distintos de `superadmin`
- **THEN** la página muestra sus roles y la lista de permisos efectivos derivados de ellos

#### Scenario: Usuario superadmin
- **WHEN** se visita el detalle de un usuario con el rol `superadmin`
- **THEN** la página indica que el usuario tiene acceso total
- **AND** no enumera la lista completa de permisos del sistema

#### Scenario: Usuario sin roles
- **WHEN** se visita el detalle de un usuario que no tiene ningún rol asignado
- **THEN** la página muestra un estado vacío en la sección de roles y permisos

### Requirement: Mostrar la actividad reciente del usuario
La página de detalle SHALL mostrar la actividad reciente del usuario en dos secciones separadas: las acciones de negocio registradas en `audit_logs` y los eventos de seguridad registrados en `security_audit_logs`, cada una acotada a sus últimos registros en orden descendente por fecha. Las dos secciones SHALL presentarse siguiendo el patrón de listado tabular denso del proyecto. La página SHALL NOT fusionar ambas fuentes en una sola línea de tiempo, porque cada una describe hechos de naturaleza distinta y con campos propios.

#### Scenario: Usuario con actividad registrada
- **WHEN** se visita el detalle de un usuario que tiene registros en `audit_logs` y en `security_audit_logs`
- **THEN** la página muestra sus últimas acciones de negocio con la acción, la entidad afectada y la fecha
- **AND** muestra sus últimos eventos de seguridad con el evento, la descripción, la dirección IP y la fecha

#### Scenario: Usuario sin actividad registrada
- **WHEN** se visita el detalle de un usuario que no tiene registros en ninguna de las dos tablas de auditoría
- **THEN** cada sección muestra un estado vacío en vez de una tabla vacía

#### Scenario: La actividad de otros usuarios no se mezcla
- **WHEN** se visita el detalle de un usuario
- **THEN** las dos secciones de actividad muestran únicamente registros cuyo `user_id` corresponde a ese usuario

### Requirement: Acciones de cuenta desde el detalle
La página de detalle SHALL ofrecer las acciones de cuenta ya implementadas —editar, activar, desactivar y resetear contraseña— condicionadas cada una al permiso correspondiente del usuario autenticado (`usuarios.editar`, `usuarios.activar`, `usuarios.desactivar`, `usuarios.resetear_password`), reutilizando los mismos endpoints que usa el listado. Las acciones que el usuario autenticado no tenga permitidas SHALL NOT mostrarse. Activar y desactivar SHALL ser mutuamente excluyentes según el estado actual de la cuenta.

#### Scenario: Acciones visibles según permiso
- **WHEN** un usuario con permiso de edición visita el detalle de otro usuario
- **THEN** la página ofrece la acción de editar ese usuario

#### Scenario: Acción no permitida
- **WHEN** un usuario sin el permiso `usuarios.resetear_password` visita el detalle de otro usuario
- **THEN** la página no muestra la acción de resetear contraseña

#### Scenario: Activar y desactivar según el estado de la cuenta
- **WHEN** se visita el detalle de un usuario con la cuenta activa
- **THEN** la página ofrece la acción de desactivar y no la de activar

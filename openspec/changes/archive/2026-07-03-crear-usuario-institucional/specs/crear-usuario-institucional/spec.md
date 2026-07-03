## ADDED Requirements

### Requirement: Autorización para crear usuarios
El sistema SHALL restringir el acceso al formulario de alta (`GET /usuarios/create`) y a la creación (`POST /usuarios`) a usuarios con el permiso `usuarios.crear`.

#### Scenario: Usuario sin permiso intenta acceder al formulario
- **WHEN** un usuario autenticado sin `usuarios.crear` visita `GET /usuarios/create`
- **THEN** el sistema responde con error de autorización (403) y no renderiza el formulario

#### Scenario: Usuario sin permiso intenta enviar el formulario
- **WHEN** un usuario autenticado sin `usuarios.crear` envía `POST /usuarios`
- **THEN** el sistema responde con error de autorización (403) y no crea ningún registro

### Requirement: Alta de usuario institucional con funcionario asociado
El sistema SHALL crear, en una única transacción, un `User` y su `Funcionario` asociado (nombre, email, rut, cargo, unidad, centro financiero y centro de costo opcionales) junto con los roles iniciales indicados en el formulario.

#### Scenario: Alta exitosa con todos los datos
- **WHEN** un usuario con `usuarios.crear` envía el formulario con nombre, email, rut, cargo, unidad, al menos un rol, y opcionalmente centro financiero/centro de costo
- **THEN** el sistema crea el `User` con `active = true`, crea el `Funcionario` vinculado (`user_id`) con los datos institucionales indicados, y asigna los roles seleccionados

#### Scenario: Falla la creación del funcionario
- **WHEN** la creación del `Funcionario` falla después de haberse creado el `User` en la misma operación
- **THEN** el sistema revierte la transacción completa y no deja un `User` sin `Funcionario` asociado

### Requirement: Contraseña temporal de un solo uso al crear
El sistema SHALL generar una contraseña temporal aleatoria para todo usuario recién creado, marcar `must_change_password = true`, y mostrarla en texto plano exactamente una vez tras el alta.

#### Scenario: Contraseña mostrada tras el alta
- **WHEN** el alta de usuario se completa exitosamente
- **THEN** el sistema redirige a la bandeja de usuarios con la contraseña temporal en texto plano en la respuesta (flash), y el usuario creado queda con `must_change_password = true`

#### Scenario: La contraseña no se recupera después
- **WHEN** el usuario recarga o navega fuera de la bandeja tras el alta
- **THEN** el sistema ya no expone la contraseña temporal en ninguna respuesta posterior

### Requirement: Validación de unicidad de email y RUT
El sistema SHALL rechazar el alta si el email ya existe en `users` o si el RUT ya existe en `funcionarios`, informando el error de validación sin crear ningún registro.

#### Scenario: Email duplicado
- **WHEN** se envía el formulario con un email que ya pertenece a otro `User`
- **THEN** el sistema rechaza la solicitud con un error de validación en el campo email y no crea ningún registro

#### Scenario: RUT duplicado
- **WHEN** se envía el formulario con un RUT que ya pertenece a otro `Funcionario`
- **THEN** el sistema rechaza la solicitud con un error de validación en el campo rut y no crea ningún registro

### Requirement: Auditoría del alta de usuario
El sistema SHALL registrar toda alta de usuario institucional como evento auditable, vinculado al usuario creado y al usuario que ejecutó la acción.

#### Scenario: Alta registrada en auditoría
- **WHEN** el alta de usuario se completa exitosamente
- **THEN** el sistema registra un evento de auditoría con acción `crear_usuario`, el usuario actor, y el usuario creado como entidad auditada

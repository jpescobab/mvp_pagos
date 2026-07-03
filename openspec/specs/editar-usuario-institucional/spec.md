# editar-usuario-institucional Specification

## Purpose
TBD - created by archiving change editar-usuario-institucional. Update Purpose after archive.
## Requirements
### Requirement: Autorización para editar usuarios
El sistema SHALL restringir el acceso al formulario de edición (`GET /usuarios/{usuario}/editar`) y a la actualización (`PATCH /usuarios/{usuario}`) a usuarios con el permiso `usuarios.editar`.

#### Scenario: Usuario sin permiso intenta acceder al formulario
- **WHEN** un usuario autenticado sin `usuarios.editar` visita `GET /usuarios/{usuario}/editar`
- **THEN** el sistema responde con error de autorización (403) y no renderiza el formulario

#### Scenario: Usuario sin permiso intenta actualizar
- **WHEN** un usuario autenticado sin `usuarios.editar` envía `PATCH /usuarios/{usuario}`
- **THEN** el sistema responde con error de autorización (403) y no modifica ningún registro

### Requirement: Formulario de edición precargado
El sistema SHALL renderizar el formulario de edición con los datos actuales del usuario y de su funcionario (nombre, email, rut, cargo, unidad, centro financiero, centro de costo) y los catálogos institucionales activos.

#### Scenario: Usuario con funcionario completo
- **WHEN** un usuario con `usuarios.editar` visita el formulario de edición de un usuario con funcionario
- **THEN** el formulario muestra precargados nombre, email, rut, cargo, unidad y los centros seleccionados

#### Scenario: Usuario sin funcionario (caso legado)
- **WHEN** se visita el formulario de edición de un usuario sin `Funcionario` asociado
- **THEN** el formulario muestra precargados nombre y email, y los campos institucionales vacíos

### Requirement: Actualización transaccional de usuario y funcionario
El sistema SHALL actualizar, en una única transacción, el `User` (name, email) y su `Funcionario` asociado (rut, nombre, cargo, unidad, centro financiero, centro de costo); si el usuario no tiene `Funcionario`, SHALL crearlo con los datos del formulario.

#### Scenario: Edición exitosa
- **WHEN** un usuario con `usuarios.editar` envía el formulario con datos válidos
- **THEN** el sistema actualiza el `User` y el `Funcionario` con los nuevos valores y redirige a la bandeja de usuarios

#### Scenario: Usuario legado sin funcionario
- **WHEN** se edita un usuario que no tiene `Funcionario` asociado
- **THEN** el sistema crea el `Funcionario` vinculado (`user_id`) con los datos institucionales del formulario

#### Scenario: La edición no altera roles, contraseña ni estado
- **WHEN** se edita un usuario con roles asignados
- **THEN** sus roles, su contraseña y su estado `active` permanecen sin cambios

### Requirement: Unicidad de email y RUT ignorando al propio usuario
El sistema SHALL validar la unicidad del email (en `users`) y del RUT (en `funcionarios`) excluyendo al usuario editado y a su funcionario, de modo que pueda guardar sin cambiar esos campos.

#### Scenario: Guardar conservando email y RUT propios
- **WHEN** se envía el formulario de edición sin cambiar email ni rut
- **THEN** el sistema acepta la actualización sin error de validación

#### Scenario: Email de otro usuario
- **WHEN** se envía el formulario con un email que pertenece a otro `User`
- **THEN** el sistema rechaza la solicitud con error de validación en el campo email y no modifica ningún registro

#### Scenario: RUT de otro funcionario
- **WHEN** se envía el formulario con un RUT que pertenece al funcionario de otro usuario
- **THEN** el sistema rechaza la solicitud con error de validación en el campo rut y no modifica ningún registro

### Requirement: Auditoría de la edición
El sistema SHALL registrar toda edición de usuario como evento auditable `editar_usuario` con los valores anteriores y posteriores de los campos editables, vinculado al usuario editado y al actor.

#### Scenario: Edición registrada con before/after
- **WHEN** la edición se completa exitosamente
- **THEN** el sistema registra un evento de auditoría con acción `editar_usuario`, el usuario editado como entidad auditada, y los valores before/after de los campos modificados

### Requirement: Acción de edición habilitada en la bandeja
El sistema SHALL mostrar "Editar usuario" como acción habilitada en el menú por fila de la bandeja de usuarios para quienes tienen `usuarios.editar`, navegando al formulario de edición.

#### Scenario: Acción visible y funcional
- **WHEN** un usuario con `usuarios.editar` abre el menú de acciones de una fila
- **THEN** "Editar usuario" aparece habilitada y navega al formulario de edición de ese usuario


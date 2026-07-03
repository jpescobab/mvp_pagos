## ADDED Requirements

### Requirement: Listar usuarios institucionales
El sistema SHALL exponer una página autorizada (`usuarios.ver`) que liste los `users` paginados con sus datos institucionales (rut, cargo, unidad, jurisdicción, centro financiero, centro de costo vía `Funcionario`), roles, estado (`active`) y último acceso (`last_login_at`).

#### Scenario: Listar usuarios con permiso
- **WHEN** un usuario con el permiso `usuarios.ver` visita la página de usuarios
- **THEN** la respuesta incluye los usuarios paginados con sus datos institucionales, roles, estado y último acceso

#### Scenario: Usuario sin permiso intenta ver el listado
- **WHEN** un usuario sin el permiso `usuarios.ver` intenta acceder al listado de usuarios
- **THEN** el sistema bloquea la operación
- **AND** registra el evento de autorización denegada en `security_audit_logs`

#### Scenario: Sin usuarios registrados
- **WHEN** no existen usuarios registrados en el sistema
- **THEN** la página muestra el mensaje "No existen usuarios registrados."

### Requirement: Buscar y filtrar usuarios institucionales
El sistema SHALL permitir buscar usuarios por nombre, email y rut (del `Funcionario` vinculado), y filtrar por estado, rol, jurisdicción, centro financiero y centro de costo, conservando los filtros aplicados tras cualquier acción sobre el listado.

#### Scenario: Búsqueda general
- **WHEN** se envía un término de búsqueda
- **THEN** el sistema retorna solo los usuarios cuyo nombre, email o rut coincidan parcialmente con el término

#### Scenario: Filtrar por atributos institucionales
- **WHEN** se envían filtros de estado, rol, jurisdicción, centro financiero o centro de costo
- **THEN** el sistema retorna solo los usuarios que cumplen todos los filtros aplicados

#### Scenario: Sin resultados por filtros
- **WHEN** los filtros aplicados no coinciden con ningún usuario
- **THEN** la página muestra el mensaje "No se encontraron usuarios con los filtros aplicados."
- **AND** ofrece un botón para limpiar los filtros

### Requirement: Paginar y ordenar el listado de usuarios
El sistema SHALL paginar el listado con 15 registros por defecto (15/25/50/100 configurables) y SHALL ordenarlo por nombre, email, estado, último acceso o fecha de creación, con orden inicial de usuarios activos primero y luego alfabético por nombre.

#### Scenario: Orden inicial
- **WHEN** se visita el listado sin especificar orden
- **THEN** los usuarios activos aparecen antes que los inactivos
- **AND** dentro de cada grupo aparecen ordenados alfabéticamente por nombre

#### Scenario: Cambiar tamaño de página
- **WHEN** se solicita un tamaño de página de 25, 50 o 100
- **THEN** el sistema retorna esa cantidad de registros por página

### Requirement: Activar cuenta de usuario
El sistema SHALL permitir activar la cuenta de un usuario inactivo, exclusivamente a quien tenga el permiso `usuarios.activar`, permitiéndole volver a iniciar sesión.

#### Scenario: Activar usuario inactivo
- **WHEN** un usuario con el permiso `usuarios.activar` activa a un usuario cuya cuenta está inactiva
- **THEN** el sistema marca `active = true` en ese usuario
- **AND** el usuario puede volver a iniciar sesión
- **AND** se registra el evento en `AuditLogger`

#### Scenario: Sin permiso para activar
- **WHEN** un usuario sin el permiso `usuarios.activar` intenta activar a otro usuario
- **THEN** el sistema bloquea la operación

### Requirement: Desactivar cuenta de usuario
El sistema SHALL permitir desactivar la cuenta de un usuario activo, exclusivamente a quien tenga el permiso `usuarios.desactivar`, impidiéndole iniciar sesión sin eliminar su historial ni trazabilidad. El sistema SHALL impedir la auto-desactivación y la desactivación del último usuario activo con rol `admin` o `superadmin`.

#### Scenario: Desactivar usuario activo
- **WHEN** un usuario con el permiso `usuarios.desactivar` desactiva a un usuario distinto de sí mismo cuya cuenta está activa
- **THEN** el sistema marca `active = false` en ese usuario
- **AND** el usuario no puede iniciar sesión
- **AND** el usuario conserva su historial y trazabilidad
- **AND** se registra el evento en `AuditLogger`

#### Scenario: Intento de auto-desactivación
- **WHEN** un usuario intenta desactivar su propia cuenta
- **THEN** el sistema rechaza la operación
- **AND** no cambia el estado de la cuenta

#### Scenario: Intento de desactivar al último administrador activo
- **WHEN** se intenta desactivar al último usuario activo con rol `admin` o `superadmin`
- **THEN** el sistema rechaza la operación
- **AND** no cambia el estado de la cuenta

### Requirement: Resetear contraseña de usuario
El sistema SHALL permitir generar una contraseña temporal segura para un usuario, exclusivamente a quien tenga el permiso `usuarios.resetear_password`, mostrándola una única vez y exigiendo su cambio en el siguiente inicio de sesión.

#### Scenario: Resetear contraseña
- **WHEN** un usuario con el permiso `usuarios.resetear_password` resetea la contraseña de otro usuario
- **THEN** el sistema genera una contraseña temporal segura
- **AND** la retorna en texto plano una única vez en la respuesta de la acción
- **AND** persiste la contraseña solo con hash
- **AND** marca `must_change_password = true` en ese usuario
- **AND** se registra el evento en `AuditLogger` sin incluir la contraseña en claro

#### Scenario: Sin permiso para resetear contraseña
- **WHEN** un usuario sin el permiso `usuarios.resetear_password` intenta resetear la contraseña de otro usuario
- **THEN** el sistema bloquea la operación

### Requirement: Acciones diferidas visibles pero deshabilitadas
El sistema SHALL mostrar las acciones "Ver detalle", "Editar usuario" y "Asignar roles" en el menú de acciones por usuario cuando el usuario autenticado tenga el permiso correspondiente (`usuarios.ver`, `usuarios.editar`, `usuarios.asignar_roles`), pero SHALL mantenerlas deshabilitadas con una indicación de "Disponible próximamente", ya que su implementación completa (rutas, páginas) pertenece a tareas separadas.

#### Scenario: Acción diferida visible pero inactiva
- **WHEN** un usuario con el permiso `usuarios.editar` abre el menú de acciones de un usuario
- **THEN** la opción "Editar usuario" aparece deshabilitada con la indicación "Disponible próximamente"
- **AND** no navega a ninguna ruta al intentar seleccionarla

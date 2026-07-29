## ADDED Requirements

### Requirement: Los roles tienen un nombre legible y una descripción

Cada rol SHALL poder tener una `etiqueta` (nombre legible para humanos) y una `descripcion` (una línea sobre qué habilita), ambas opcionales. La UI de gestión de roles y de asignación de roles a usuarios SHALL mostrar la `etiqueta` como identificador principal y el `name` técnico como dato secundario. Cuando un rol no tiene `etiqueta`, la UI SHALL usar su `name` como respaldo, de modo que ningún rol quede sin mostrar. Al crear o editar un rol, el sistema SHALL permitir capturar y persistir `etiqueta` y `descripcion`. La incorporación de estos campos SHALL NOT alterar los permisos de ningún rol ni el conjunto de roles existentes.

#### Scenario: Crear un rol con etiqueta y descripción

- **WHEN** un usuario con permiso para administrar roles crea un rol con `name`, `etiqueta` y `descripcion`
- **THEN** el rol se persiste con su `etiqueta` y `descripcion`
- **AND** el índice de roles muestra la `etiqueta` como texto principal

#### Scenario: Un rol sin etiqueta usa su name como respaldo

- **WHEN** se muestra en la UI un rol cuya `etiqueta` es nula
- **THEN** se muestra su `name` técnico en lugar de la etiqueta
- **AND** no se muestra un valor vacío

#### Scenario: El selector de roles al asignar a un usuario muestra la etiqueta

- **WHEN** un usuario con permiso `usuarios.asignar_roles` abre el editor de un usuario
- **THEN** el selector de roles muestra la `etiqueta` de cada rol (o su `name` como respaldo)

## MODIFIED Requirements

### Requirement: Administrar el catálogo de tipos de proceso de pago
El sistema SHALL permitir, a un usuario con el permiso `pago_proveedores.administrar_requisitos_documentales`, crear, editar y activar/desactivar registros de `TipoProcesoPago` (código único, nombre, activo, y si requiere Traspaso (CGU)). El sistema SHALL rechazar la eliminación de un `TipoProcesoPago` que tenga `RequisitoDocumental` asociados. Un `TipoProcesoPago` nuevo SHALL crearse con `requiere_traspaso_cgu = true` por defecto, salvo que el usuario lo desmarque explícitamente.

#### Scenario: Crear un tipo de proceso de pago
- **WHEN** un usuario con el permiso requerido crea un `TipoProcesoPago` con un código único y un nombre
- **THEN** el sistema lo persiste con `activo = true` por defecto y queda disponible para clasificar casos y para la matriz de requisitos documentales

#### Scenario: Código duplicado es rechazado
- **WHEN** un usuario intenta crear un `TipoProcesoPago` con un código que ya existe (sin distinguir mayúsculas/minúsculas)
- **THEN** el sistema rechaza la operación con un error de validación

#### Scenario: Desactivar un tipo de proceso de pago en uso
- **WHEN** un usuario desactiva un `TipoProcesoPago` que ya está asignado a casos existentes
- **THEN** el sistema lo marca `activo = false` sin afectar los casos que ya lo tienen asignado, y deja de ofrecerlo como opción para casos nuevos

#### Scenario: Eliminar un tipo de proceso de pago con requisitos asociados es rechazado
- **WHEN** un usuario intenta eliminar un `TipoProcesoPago` que tiene `RequisitoDocumental` asociados
- **THEN** el sistema rechaza la eliminación e informa que existen requisitos documentales relacionados

#### Scenario: Usuario sin permiso no puede administrar tipos de proceso de pago
- **WHEN** un usuario sin el permiso `pago_proveedores.administrar_requisitos_documentales` intenta crear, editar o eliminar un `TipoProcesoPago`
- **THEN** el sistema bloquea la operación

#### Scenario: Un tipo de proceso de pago nuevo requiere Traspaso (CGU) por defecto
- **WHEN** un usuario con el permiso requerido crea un `TipoProcesoPago` sin especificar `requiere_traspaso_cgu`
- **THEN** el sistema lo persiste con `requiere_traspaso_cgu = true`

#### Scenario: Marcar un tipo de proceso de pago como que no requiere Traspaso (CGU)
- **WHEN** un usuario con el permiso requerido crea o edita un `TipoProcesoPago` marcando `requiere_traspaso_cgu` en `false`
- **THEN** el sistema lo persiste así

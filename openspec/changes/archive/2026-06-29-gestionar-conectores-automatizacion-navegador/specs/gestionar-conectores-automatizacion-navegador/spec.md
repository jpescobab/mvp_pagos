## ADDED Requirements

### Requirement: Registrar un conector de automatización Playwright
El sistema SHALL permitir registrar un `conector_automatizacion_navegador` asociado a un `sistema_externo`, con su código y nombre, en estado inactivo (`activo=false`) por defecto, únicamente a usuarios con el permiso `integraciones.gestionar_conectores`.

#### Scenario: Registrar un conector con permiso
- **WHEN** un usuario con el permiso `integraciones.gestionar_conectores` registra un conector para un sistema externo
- **THEN** se crea un `conector_automatizacion_navegador` con `activo=false` y sin autorización

#### Scenario: Registrar un conector sin permiso
- **WHEN** un usuario sin el permiso `integraciones.gestionar_conectores` intenta registrar un conector
- **THEN** el sistema bloquea la operación

### Requirement: Autorizar explícitamente un conector
El sistema SHALL permitir autorizar un conector existente, registrando el usuario y la fecha de autorización y activándolo, únicamente a usuarios con el permiso `integraciones.gestionar_conectores`. Un conector no autorizado o inactivo no SHALL considerarse disponible para ninguna ejecución.

#### Scenario: Autorizar un conector con permiso
- **WHEN** un usuario con el permiso `integraciones.gestionar_conectores` autoriza un conector
- **THEN** el conector queda `activo=true` con `autorizado_por` y `autorizado_en` registrados
- **AND** `ConectorAutomatizacionNavegador::estaAutorizado()` retorna verdadero

#### Scenario: Autorizar un conector sin permiso
- **WHEN** un usuario sin el permiso `integraciones.gestionar_conectores` intenta autorizar un conector
- **THEN** el sistema bloquea la operación
- **AND** el conector permanece sin autorizar

### Requirement: Registrar un perfil de autenticación sin guardar el secreto real
El sistema SHALL permitir registrar un `perfil_autenticacion_navegador` para un conector con su nombre, almacén de secretos y referencia a la clave del secreto, únicamente a usuarios con el permiso `integraciones.gestionar_conectores`, sin aceptar ni almacenar el valor real de ninguna credencial.

#### Scenario: Registrar un perfil de autenticación
- **WHEN** un usuario con el permiso `integraciones.gestionar_conectores` registra un perfil de autenticación para un conector con su almacén y referencia de secreto
- **THEN** se crea un `perfil_autenticacion_navegador` asociado al conector
- **AND** ningún campo del registro contiene el valor real de la credencial

### Requirement: Listar conectores con su estado de autorización
El sistema SHALL exponer, a cualquier usuario autenticado, el listado de conectores con su sistema externo, código, estado activo, autorización y perfiles de autenticación asociados.

#### Scenario: Listar el catálogo de conectores
- **WHEN** un usuario autenticado visita el listado de conectores
- **THEN** la respuesta incluye cada conector con su sistema externo, código, estado activo, quién lo autorizó (si corresponde) y sus perfiles de autenticación

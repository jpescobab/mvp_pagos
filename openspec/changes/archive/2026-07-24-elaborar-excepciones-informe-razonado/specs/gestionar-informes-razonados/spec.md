## ADDED Requirements

### Requirement: Elaborar las excepciones de una ejecución de informe razonado
El sistema SHALL permitir a los usuarios con el permiso `informes.elaborar` crear, editar y eliminar `excepcion_informe_razonado` (`codigo`, `descripcion`, `severidad`) de una `ejecucion_informe_razonado`, **únicamente mientras la ejecución está en el estado `en_elaboracion`**. La `severidad` SHALL ser uno de `info`, `advertencia` o `critico` (por defecto `info`). Toda la escritura SHALL pasar por `InformeRazonadoService` (`agregarExcepcion`, `editarExcepcion`, `eliminarExcepcion`); el controlador es liviano. Crear, editar o eliminar una excepción **no** es una transición de workflow: es contenido, por lo que no pasa por `TransicionWorkflowService` y no altera el estado del `Proceso`.

#### Scenario: Agregar una excepción con permiso durante la elaboración
- **WHEN** un usuario con el permiso `informes.elaborar` agrega una excepción con código, descripción y severidad a una ejecución en estado `en_elaboracion`
- **THEN** el sistema crea la `excepcion_informe_razonado` asociada a la ejecución
- **AND** el estado del `Proceso` de la ejecución no cambia

#### Scenario: Editar una excepción durante la elaboración
- **WHEN** un usuario con el permiso `informes.elaborar` edita la descripción o la severidad de una excepción de una ejecución en estado `en_elaboracion`
- **THEN** el sistema actualiza esa excepción

#### Scenario: Eliminar una excepción durante la elaboración
- **WHEN** un usuario con el permiso `informes.elaborar` elimina una excepción de una ejecución en estado `en_elaboracion`
- **THEN** el sistema elimina esa `excepcion_informe_razonado`

#### Scenario: Rechazar una severidad inválida
- **WHEN** un usuario con el permiso `informes.elaborar` intenta crear o editar una excepción con una severidad distinta de `info`, `advertencia` o `critico`
- **THEN** el sistema rechaza la solicitud por validación
- **AND** la excepción no se crea ni modifica

#### Scenario: No se pueden elaborar excepciones fuera del estado de elaboración
- **WHEN** un usuario intenta crear, editar o eliminar una excepción de una ejecución que no está en estado `en_elaboracion`
- **THEN** el sistema bloquea la operación
- **AND** la excepción no se crea, modifica ni elimina

#### Scenario: Elaborar excepciones sin el permiso requerido
- **WHEN** un usuario autenticado sin el permiso `informes.elaborar` intenta crear, editar o eliminar una excepción de una ejecución en estado `en_elaboracion`
- **THEN** el sistema rechaza la solicitud con un error de autorización
- **AND** la excepción no se crea, modifica ni elimina

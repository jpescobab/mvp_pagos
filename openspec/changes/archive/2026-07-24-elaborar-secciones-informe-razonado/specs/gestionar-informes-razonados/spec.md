## ADDED Requirements

### Requirement: Elaborar las secciones de una ejecución de informe razonado
El sistema SHALL permitir a los usuarios con el permiso `informes.elaborar` crear, editar y eliminar `seccion_informe_razonado` (`codigo`, `titulo`, `orden`) de una `ejecucion_informe_razonado`, **únicamente mientras la ejecución está en el estado `en_elaboracion`**. Toda la escritura SHALL pasar por `InformeRazonadoService` (`agregarSeccion`, `editarSeccion`, `eliminarSeccion`); el controlador es liviano. Crear, editar o eliminar una sección **no** es una transición de workflow: es contenido, por lo que no pasa por `TransicionWorkflowService` y no altera el estado del `Proceso`. Al eliminar una sección, el sistema SHALL eliminar en cascada las métricas, gráficos y narrativas asignadas a esa sección (comportamiento del esquema: la FK `seccion_informe_razonado_id` es `cascadeOnDelete`); el contenido sin sección (`seccion_informe_razonado_id` nulo) no se ve afectado.

#### Scenario: Agregar una sección con permiso durante la elaboración
- **WHEN** un usuario con el permiso `informes.elaborar` agrega una sección con código y título a una ejecución en estado `en_elaboracion`
- **THEN** el sistema crea la `seccion_informe_razonado` asociada a la ejecución
- **AND** el estado del `Proceso` de la ejecución no cambia

#### Scenario: Editar una sección durante la elaboración
- **WHEN** un usuario con el permiso `informes.elaborar` edita el título o el orden de una sección de una ejecución en estado `en_elaboracion`
- **THEN** el sistema actualiza esa sección

#### Scenario: Eliminar una sección elimina en cascada su contenido asignado
- **WHEN** un usuario con el permiso `informes.elaborar` elimina una sección que tiene narrativas, métricas o gráficos asignados, en una ejecución en estado `en_elaboracion`
- **THEN** el sistema elimina esa `seccion_informe_razonado`
- **AND** elimina también las narrativas, métricas y gráficos que estaban asignados a esa sección
- **AND** el contenido de la ejecución sin sección asignada permanece intacto

#### Scenario: No se pueden elaborar secciones fuera del estado de elaboración
- **WHEN** un usuario intenta crear, editar o eliminar una sección de una ejecución que no está en estado `en_elaboracion`
- **THEN** el sistema bloquea la operación
- **AND** la sección no se crea, modifica ni elimina

#### Scenario: Elaborar secciones sin el permiso requerido
- **WHEN** un usuario autenticado sin el permiso `informes.elaborar` intenta crear, editar o eliminar una sección de una ejecución en estado `en_elaboracion`
- **THEN** el sistema rechaza la solicitud con un error de autorización
- **AND** la sección no se crea, modifica ni elimina

## MODIFIED Requirements

### Requirement: Elaborar las narrativas de una ejecución de informe razonado
El sistema SHALL permitir a los usuarios con el permiso `informes.elaborar` crear, editar y eliminar `narrativa_informe_razonado` (`contenido`, `generado_por_ia`, y opcionalmente `seccion_informe_razonado_id`) de una `ejecucion_informe_razonado`, **únicamente mientras la ejecución está en el estado `en_elaboracion`**. Al crear una narrativa, se le SHALL poder asignar opcionalmente una sección de la **misma** ejecución (el sistema valida que la sección pertenezca a esa ejecución); si no se indica sección, la narrativa queda sin sección. Toda la escritura SHALL pasar por `InformeRazonadoService` (`agregarNarrativa`, `editarNarrativa`, `eliminarNarrativa`); el controlador es liviano. Crear, editar o eliminar una narrativa **no** es una transición de workflow: es contenido, por lo que no pasa por `TransicionWorkflowService` y no altera el estado del `Proceso`.

#### Scenario: Agregar una narrativa con permiso durante la elaboración
- **WHEN** un usuario con el permiso `informes.elaborar` agrega una narrativa con contenido a una ejecución en estado `en_elaboracion`
- **THEN** el sistema crea la `narrativa_informe_razonado` asociada a la ejecución
- **AND** el estado del `Proceso` de la ejecución no cambia

#### Scenario: Agregar una narrativa dentro de una sección
- **WHEN** un usuario con el permiso `informes.elaborar` agrega una narrativa indicando una sección de la misma ejecución
- **THEN** el sistema crea la narrativa asociada a esa sección
- **AND** rechaza la operación si la sección indicada pertenece a otra ejecución

#### Scenario: Editar el contenido de una narrativa durante la elaboración
- **WHEN** un usuario con el permiso `informes.elaborar` edita el contenido de una narrativa de una ejecución en estado `en_elaboracion`
- **THEN** el sistema actualiza el `contenido` de esa narrativa

#### Scenario: Eliminar una narrativa durante la elaboración
- **WHEN** un usuario con el permiso `informes.elaborar` elimina una narrativa de una ejecución en estado `en_elaboracion`
- **THEN** el sistema elimina esa `narrativa_informe_razonado`

#### Scenario: No se puede elaborar narrativas fuera del estado de elaboración
- **WHEN** un usuario intenta crear, editar o eliminar una narrativa de una ejecución que no está en estado `en_elaboracion` (por ejemplo `en_revision`, `aprobado` o `publicado`)
- **THEN** el sistema bloquea la operación
- **AND** la narrativa no se crea, modifica ni elimina

#### Scenario: Elaborar narrativas sin el permiso requerido
- **WHEN** un usuario autenticado sin el permiso `informes.elaborar` intenta crear, editar o eliminar una narrativa de una ejecución en estado `en_elaboracion`
- **THEN** el sistema rechaza la solicitud con un error de autorización
- **AND** la narrativa no se crea, modifica ni elimina

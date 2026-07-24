## ADDED Requirements

### Requirement: Elaborar las narrativas de una ejecución de informe razonado
El sistema SHALL permitir a los usuarios con el permiso `informes.elaborar` crear, editar y eliminar `narrativa_informe_razonado` (`contenido`, `generado_por_ia`, y opcionalmente `seccion_informe_razonado_id`) de una `ejecucion_informe_razonado`, **únicamente mientras la ejecución está en el estado `en_elaboracion`**. Toda la escritura SHALL pasar por `InformeRazonadoService` (`agregarNarrativa`, `editarNarrativa`, `eliminarNarrativa`); el controlador es liviano. Crear, editar o eliminar una narrativa **no** es una transición de workflow: es contenido, por lo que no pasa por `TransicionWorkflowService` y no altera el estado del `Proceso`.

#### Scenario: Agregar una narrativa con permiso durante la elaboración
- **WHEN** un usuario con el permiso `informes.elaborar` agrega una narrativa con contenido a una ejecución en estado `en_elaboracion`
- **THEN** el sistema crea la `narrativa_informe_razonado` asociada a la ejecución
- **AND** el estado del `Proceso` de la ejecución no cambia

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

### Requirement: Marcar como revisada una narrativa de una ejecución de informe razonado
El sistema SHALL permitir a los usuarios con el permiso `informes.aprobar` marcar una `narrativa_informe_razonado` como revisada, registrando `revisado_por` (el usuario) y `revisado_en` (la fecha), a través de `InformeRazonadoService::revisarNarrativa`. Esta acción SHALL ser independiente de la autoría del contenido y materializa la revisión humana que el módulo exige antes de publicar. Marcar una narrativa como revisada **no** es una transición de workflow y no altera el estado del `Proceso`.

#### Scenario: Marcar una narrativa como revisada con permiso
- **WHEN** un usuario con el permiso `informes.aprobar` marca como revisada una narrativa de una ejecución
- **THEN** el sistema registra `revisado_por` con ese usuario y `revisado_en` con la fecha actual en esa narrativa

#### Scenario: Marcar una narrativa como revisada sin el permiso requerido
- **WHEN** un usuario autenticado sin el permiso `informes.aprobar` intenta marcar como revisada una narrativa
- **THEN** el sistema rechaza la solicitud con un error de autorización
- **AND** la narrativa permanece sin revisar

## MODIFIED Requirements

### Requirement: Mostrar el detalle completo de una ejecución de informe razonado
El sistema SHALL exponer el detalle de una `ejecucion_informe_razonado` con su definición, corte, estado de workflow, transiciones disponibles, secciones, métricas, gráficos, narrativas, excepciones, snapshots, aprobaciones y exportaciones asociadas. El detalle SHALL indicar si la ejecución es editable (es decir, si su `Proceso` está en estado `en_elaboracion`) y, por cada narrativa, si ya fue revisada y por quién (`revisado_en`, `revisado_por`), de modo que el frontend pueda condicionar los controles de autoría y de revisión.

#### Scenario: Ver el detalle de una ejecución
- **WHEN** un usuario autenticado abre el detalle de una ejecución
- **THEN** la respuesta incluye su definición, corte, estado de workflow y transiciones disponibles
- **AND** incluye todas sus secciones, métricas, gráficos, narrativas, excepciones, snapshots, aprobaciones y exportaciones, aunque estén vacías

#### Scenario: El detalle indica si la ejecución es editable y el estado de revisión de cada narrativa
- **WHEN** un usuario autenticado abre el detalle de una ejecución en estado `en_elaboracion` que tiene narrativas
- **THEN** la respuesta indica que la ejecución es editable
- **AND** cada narrativa indica si fue revisada (`revisado_en`) y por quién (`revisado_por`)

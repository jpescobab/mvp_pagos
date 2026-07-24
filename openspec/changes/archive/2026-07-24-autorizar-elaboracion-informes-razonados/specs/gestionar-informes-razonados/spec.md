## MODIFIED Requirements

### Requirement: Iniciar una ejecución de informe razonado sobre un corte publicado
El sistema SHALL permitir iniciar una `ejecucion_informe_razonado` para una definición y un corte de reportabilidad únicamente a los usuarios con el permiso `informes.elaborar`, y únicamente si el corte está publicado, creando su `Proceso` de workflow en el estado inicial `en_elaboracion`. Este permiso es distinto de `informes.administrar` (que gobierna el CRUD de las definiciones) y de `informes.ver` (que gobierna el listado): iniciar una ejecución es una acción operacional propia del elaborador.

#### Scenario: Iniciar una ejecución con permiso sobre un corte publicado
- **WHEN** un usuario con el permiso `informes.elaborar` inicia una ejecución para una definición y un corte publicado
- **THEN** se crea una `ejecucion_informe_razonado` vinculada a la definición y al corte
- **AND** se crea su `Proceso` de workflow en el estado inicial `en_elaboracion`

#### Scenario: Iniciar sin el permiso es rechazado
- **WHEN** un usuario autenticado sin el permiso `informes.elaborar` intenta iniciar una ejecución sobre un corte publicado
- **THEN** el sistema rechaza la operación con un error de autorización
- **AND** no se crea ninguna `ejecucion_informe_razonado`

#### Scenario: Rechazar iniciar una ejecución sobre un corte no publicado
- **WHEN** un usuario con el permiso `informes.elaborar` intenta iniciar una ejecución sobre un corte en estado `borrador`
- **THEN** el sistema rechaza la operación
- **AND** no se crea ninguna `ejecucion_informe_razonado`

### Requirement: Mover una ejecución de informe razonado por su workflow
El sistema SHALL permitir ejecutar las transiciones `enviar_a_revision`, `aprobar`, `rechazar` y `publicar` sobre una ejecución, exclusivamente a través de `InformeRazonadoService`, registrando una `aprobacion_informe_razonado` al aprobar o rechazar y un `snapshot_informe_razonado` inmutable al publicar. Cada transición SHALL exigir un permiso: `enviar_a_revision` requiere `informes.elaborar` (el elaborador envía su propio borrador a revisión), `aprobar` y `rechazar` requieren `informes.aprobar` (ambas son el veredicto de la revisión), y `publicar` requiere `informes.publicar`.

#### Scenario: Enviar una ejecución a revisión con permiso
- **WHEN** un usuario con el permiso `informes.elaborar` envía a revisión una ejecución en estado `en_elaboracion`
- **THEN** el `Proceso` de la ejecución pasa al estado `en_revision`

#### Scenario: Aprobar una ejecución con permiso
- **WHEN** un usuario con el permiso `informes.aprobar` aprueba una ejecución en estado `en_revision`
- **THEN** el `Proceso` pasa al estado `aprobado`
- **AND** se crea una `aprobacion_informe_razonado` con decisión `aprobado`

#### Scenario: Rechazar una ejecución con permiso
- **WHEN** un usuario con el permiso `informes.aprobar` rechaza una ejecución en estado `en_revision` con un comentario
- **THEN** el `Proceso` pasa al estado `rechazado`
- **AND** se crea una `aprobacion_informe_razonado` con decisión `rechazado`

#### Scenario: Publicar una ejecución aprobada con permiso
- **WHEN** un usuario con el permiso `informes.publicar` publica una ejecución en estado `aprobado`
- **THEN** el `Proceso` pasa al estado `publicado`
- **AND** se crea un `snapshot_informe_razonado` inmutable con el contenido ensamblado de la ejecución

#### Scenario: Transición sin el permiso requerido
- **WHEN** un usuario sin `informes.elaborar` intenta enviar a revisión, o sin `informes.aprobar` intenta aprobar o rechazar, o sin `informes.publicar` intenta publicar una ejecución
- **THEN** el sistema bloquea la operación
- **AND** el estado de la ejecución no cambia

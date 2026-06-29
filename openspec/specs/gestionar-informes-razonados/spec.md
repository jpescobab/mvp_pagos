# Spec: gestionar-informes-razonados

## Purpose

Permitir crear definiciones de informe razonado, iniciar ejecuciones sobre un corte de reportabilidad publicado y moverlas por su workflow de revisión, aprobación y publicación, con evidencia (aprobaciones y snapshot inmutable) en cada paso.

## Requirements

### Requirement: Crear una definición de informe razonado
El sistema SHALL permitir a cualquier usuario autenticado crear una `definicion_informe_razonado` con código, nombre y descripción.

#### Scenario: Crear una definición
- **WHEN** un usuario autenticado crea una definición de informe razonado con código y nombre
- **THEN** se crea una `definicion_informe_razonado`

### Requirement: Iniciar una ejecución de informe razonado sobre un corte publicado
El sistema SHALL permitir iniciar una `ejecucion_informe_razonado` para una definición y un corte de reportabilidad, únicamente si el corte está publicado, creando su `Proceso` de workflow en el estado inicial `en_elaboracion`.

#### Scenario: Iniciar una ejecución sobre un corte publicado
- **WHEN** un usuario autenticado inicia una ejecución para una definición y un corte publicado
- **THEN** se crea una `ejecucion_informe_razonado` vinculada a la definición y al corte
- **AND** se crea su `Proceso` de workflow en el estado inicial `en_elaboracion`

#### Scenario: Rechazar iniciar una ejecución sobre un corte no publicado
- **WHEN** un usuario autenticado intenta iniciar una ejecución sobre un corte en estado `borrador`
- **THEN** el sistema rechaza la operación
- **AND** no se crea ninguna `ejecucion_informe_razonado`

### Requirement: Mover una ejecución de informe razonado por su workflow
El sistema SHALL permitir ejecutar las transiciones `enviar_a_revision`, `aprobar`, `rechazar` y `publicar` sobre una ejecución, exclusivamente a través de `InformeRazonadoService`, registrando una `aprobacion_informe_razonado` al aprobar o rechazar y un `snapshot_informe_razonado` inmutable al publicar. Las transiciones `aprobar` y `publicar` SHALL exigir los permisos `informes.aprobar` e `informes.publicar` respectivamente.

#### Scenario: Enviar una ejecución a revisión
- **WHEN** un usuario autenticado envía a revisión una ejecución en estado `en_elaboracion`
- **THEN** el `Proceso` de la ejecución pasa al estado `en_revision`

#### Scenario: Aprobar una ejecución con permiso
- **WHEN** un usuario con el permiso `informes.aprobar` aprueba una ejecución en estado `en_revision`
- **THEN** el `Proceso` pasa al estado `aprobado`
- **AND** se crea una `aprobacion_informe_razonado` con decisión `aprobado`

#### Scenario: Rechazar una ejecución
- **WHEN** un usuario autenticado rechaza una ejecución en estado `en_revision` con un comentario
- **THEN** el `Proceso` pasa al estado `rechazado`
- **AND** se crea una `aprobacion_informe_razonado` con decisión `rechazado`

#### Scenario: Publicar una ejecución aprobada con permiso
- **WHEN** un usuario con el permiso `informes.publicar` publica una ejecución en estado `aprobado`
- **THEN** el `Proceso` pasa al estado `publicado`
- **AND** se crea un `snapshot_informe_razonado` inmutable con el contenido ensamblado de la ejecución

#### Scenario: Transición sin el permiso requerido
- **WHEN** un usuario sin el permiso `informes.aprobar` intenta aprobar una ejecución, o sin `informes.publicar` intenta publicarla
- **THEN** el sistema bloquea la operación
- **AND** el estado de la ejecución no cambia

### Requirement: Mostrar el detalle completo de una ejecución de informe razonado
El sistema SHALL exponer el detalle de una `ejecucion_informe_razonado` con su definición, corte, estado de workflow, transiciones disponibles, secciones, métricas, gráficos, narrativas, excepciones, snapshots, aprobaciones y exportaciones asociadas.

#### Scenario: Ver el detalle de una ejecución
- **WHEN** un usuario autenticado abre el detalle de una ejecución
- **THEN** la respuesta incluye su definición, corte, estado de workflow y transiciones disponibles
- **AND** incluye todas sus secciones, métricas, gráficos, narrativas, excepciones, snapshots, aprobaciones y exportaciones, aunque estén vacías

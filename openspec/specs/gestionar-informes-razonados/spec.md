# Spec: gestionar-informes-razonados

## Purpose

Permitir crear definiciones de informe razonado, iniciar ejecuciones sobre un corte de reportabilidad publicado y moverlas por su workflow de revisión, aprobación y publicación, con evidencia (aprobaciones y snapshot inmutable) en cada paso.

## Requirements

### Requirement: Crear una definición de informe razonado
El sistema SHALL permitir crear una `definicion_informe_razonado` con código, nombre y descripción únicamente a los usuarios con el permiso `informes.administrar`. El `codigo` SHALL ser único entre todas las definiciones. Una definición creada sin estado explícito SHALL quedar activa.

#### Scenario: Crear una definición con permiso
- **WHEN** un usuario con el permiso `informes.administrar` crea una definición de informe razonado con código y nombre
- **THEN** se crea una `definicion_informe_razonado` activa

#### Scenario: Crear sin el permiso es rechazado
- **WHEN** un usuario autenticado sin el permiso `informes.administrar` intenta crear una definición de informe razonado
- **THEN** el sistema rechaza la solicitud y no crea ninguna `definicion_informe_razonado`

#### Scenario: Código duplicado
- **WHEN** un usuario con el permiso `informes.administrar` envía un código que ya pertenece a otra definición
- **THEN** el sistema rechaza la solicitud con un error de validación sobre el campo del código y no crea la definición

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

### Requirement: Restringir el listado de definiciones y ejecuciones de informes razonados
El sistema SHALL exigir el permiso `informes.ver` para listar las `definicion_informe_razonado` y `ejecucion_informe_razonado` existentes. Este permiso es distinto de los ya existentes `informes.aprobar` e `informes.publicar`, que siguen gobernando exclusivamente esas transiciones de workflow.

#### Scenario: Listar con permiso
- **WHEN** un usuario con el permiso `informes.ver` visita el listado de definiciones o de ejecuciones de informes razonados
- **THEN** el sistema muestra el listado correspondiente

#### Scenario: Usuario sin permiso no puede listar
- **WHEN** un usuario autenticado sin el permiso `informes.ver` intenta visitar el listado de definiciones o de ejecuciones de informes razonados
- **THEN** el sistema rechaza la solicitud

### Requirement: Ver el detalle de una definición de informe razonado
El sistema SHALL permitir a los usuarios con el permiso `informes.ver` ver el detalle de una `definicion_informe_razonado`, incluyendo sus atributos y el listado de las ejecuciones que se generaron a partir de ella, cada una enlazada a su propio detalle.

#### Scenario: Ver una definición con ejecuciones
- **WHEN** un usuario con el permiso `informes.ver` abre el detalle de una definición que tiene ejecuciones asociadas
- **THEN** el sistema muestra los atributos de la definición y el listado de sus ejecuciones, cada una enlazada a su detalle

#### Scenario: Ver una definición sin ejecuciones
- **WHEN** un usuario con el permiso `informes.ver` abre el detalle de una definición que no tiene ejecuciones asociadas
- **THEN** el sistema muestra los atributos de la definición y un mensaje de listado vacío, sin error

#### Scenario: Acceso denegado sin el permiso
- **WHEN** un usuario autenticado sin el permiso `informes.ver` intenta ver el detalle de una definición
- **THEN** el sistema deniega el acceso

### Requirement: Editar una definición de informe razonado
El sistema SHALL permitir a los usuarios con el permiso `informes.administrar` editar el código, el nombre, la descripción y el estado activo/inactivo de una `definicion_informe_razonado`. El `codigo` SHALL seguir siendo único entre las definiciones, ignorando la propia definición editada.

#### Scenario: Editar una definición con permiso
- **WHEN** un usuario con el permiso `informes.administrar` envía cambios válidos sobre una definición
- **THEN** el sistema actualiza la definición y redirige a su detalle con un mensaje de confirmación

#### Scenario: Conservar el propio código al editar
- **WHEN** el usuario guarda una definición sin modificar su código
- **THEN** el sistema acepta la edición y no reporta el código como duplicado consigo mismo

#### Scenario: Edición denegada sin el permiso
- **WHEN** un usuario autenticado sin el permiso `informes.administrar` intenta editar una definición
- **THEN** el sistema rechaza la solicitud y no modifica la definición

### Requirement: Eliminar una definición de informe razonado sin ejecuciones
El sistema SHALL permitir a los usuarios con el permiso `informes.administrar` eliminar una `definicion_informe_razonado` que no tenga ejecuciones asociadas. Si la definición tiene ejecuciones, el sistema SHALL rechazar la eliminación con un mensaje que indique la dependencia, sin eliminar nada, para preservar la trazabilidad de los informes ya generados.

#### Scenario: Eliminar una definición sin ejecuciones
- **WHEN** un usuario con el permiso `informes.administrar` elimina una definición que no tiene ejecuciones
- **THEN** el sistema elimina la definición y redirige al listado con un mensaje de confirmación

#### Scenario: Eliminación bloqueada por ejecuciones asociadas
- **WHEN** el usuario intenta eliminar una definición que tiene al menos una ejecución asociada
- **THEN** el sistema rechaza la eliminación, informa que la definición tiene ejecuciones asociadas y conserva la definición y sus ejecuciones

#### Scenario: Eliminación denegada sin el permiso
- **WHEN** un usuario autenticado sin el permiso `informes.administrar` intenta eliminar una definición
- **THEN** el sistema rechaza la solicitud y no elimina la definición

### Requirement: Consultar el catálogo de definiciones de informes razonados con búsqueda
El sistema SHALL permitir a los usuarios con el permiso `informes.ver` consultar un listado paginado de definiciones de informe razonado, con búsqueda por coincidencia parcial en el código o el nombre, mostrando el código, el nombre, la descripción, el estado activo/inactivo y la cantidad de ejecuciones generadas a partir de cada definición.

#### Scenario: Listar definiciones
- **WHEN** un usuario con el permiso `informes.ver` visita el listado de definiciones
- **THEN** el sistema muestra un listado paginado con el código, el nombre, la descripción, el estado y la cantidad de ejecuciones de cada definición

#### Scenario: Buscar por código o nombre
- **WHEN** el usuario ingresa un término de búsqueda en el listado de definiciones
- **THEN** el sistema filtra los resultados por coincidencia parcial en el código o en el nombre de la definición

#### Scenario: Acceso denegado sin el permiso
- **WHEN** un usuario autenticado sin el permiso `informes.ver` intenta acceder al listado de definiciones
- **THEN** el sistema deniega el acceso

## ADDED Requirements

### Requirement: Elaborar las métricas de una ejecución de informe razonado
El sistema SHALL permitir a los usuarios con el permiso `informes.elaborar` crear, editar y eliminar `metrica_informe_razonado` (`codigo`, `etiqueta`, `valor` opcional, `unidad` opcional, `orden`, y opcionalmente `seccion_informe_razonado_id`) de una `ejecucion_informe_razonado`, **únicamente mientras la ejecución está en el estado `en_elaboracion`**. Al crear o editar una métrica se le SHALL poder asignar opcionalmente una sección de la **misma** ejecución (el sistema valida que la sección pertenezca a esa ejecución); si no se indica sección, la métrica queda sin sección. Toda la escritura SHALL pasar por `InformeRazonadoService` (`agregarMetrica`, `editarMetrica`, `eliminarMetrica`); el controlador es liviano. Crear, editar o eliminar una métrica **no** es una transición de workflow: es contenido, por lo que no pasa por `TransicionWorkflowService` y no altera el estado del `Proceso`.

#### Scenario: Agregar una métrica con permiso durante la elaboración
- **WHEN** un usuario con el permiso `informes.elaborar` agrega una métrica con código y etiqueta a una ejecución en estado `en_elaboracion`
- **THEN** el sistema crea la `metrica_informe_razonado` asociada a la ejecución
- **AND** el estado del `Proceso` de la ejecución no cambia

#### Scenario: Agregar una métrica dentro de una sección
- **WHEN** un usuario con el permiso `informes.elaborar` agrega una métrica indicando una sección de la misma ejecución
- **THEN** el sistema crea la métrica asociada a esa sección
- **AND** rechaza la operación si la sección indicada pertenece a otra ejecución

#### Scenario: Editar una métrica durante la elaboración
- **WHEN** un usuario con el permiso `informes.elaborar` edita la etiqueta, el valor, la unidad o el orden de una métrica de una ejecución en estado `en_elaboracion`
- **THEN** el sistema actualiza esa métrica

#### Scenario: Eliminar una métrica durante la elaboración
- **WHEN** un usuario con el permiso `informes.elaborar` elimina una métrica de una ejecución en estado `en_elaboracion`
- **THEN** el sistema elimina esa `metrica_informe_razonado`

#### Scenario: No se pueden elaborar métricas fuera del estado de elaboración
- **WHEN** un usuario intenta crear, editar o eliminar una métrica de una ejecución que no está en estado `en_elaboracion`
- **THEN** el sistema bloquea la operación
- **AND** la métrica no se crea, modifica ni elimina

#### Scenario: Elaborar métricas sin el permiso requerido
- **WHEN** un usuario autenticado sin el permiso `informes.elaborar` intenta crear, editar o eliminar una métrica de una ejecución en estado `en_elaboracion`
- **THEN** el sistema rechaza la solicitud con un error de autorización
- **AND** la métrica no se crea, modifica ni elimina

### Requirement: Elaborar los gráficos de una ejecución de informe razonado
El sistema SHALL permitir a los usuarios con el permiso `informes.elaborar` crear, editar y eliminar `grafico_informe_razonado` (`codigo`, `titulo`, `tipo`, `datos`, `orden`, y opcionalmente `seccion_informe_razonado_id`) de una `ejecucion_informe_razonado`, **únicamente mientras la ejecución está en el estado `en_elaboracion`**. El `tipo` SHALL ser uno de un conjunto acotado definido por el backend (`barra`, `linea`, `torta`, `area`) y `datos` SHALL ser una estructura JSON válida. Al crear o editar un gráfico se le SHALL poder asignar opcionalmente una sección de la **misma** ejecución (el sistema valida que la sección pertenezca a esa ejecución); si no se indica sección, el gráfico queda sin sección. Toda la escritura SHALL pasar por `InformeRazonadoService` (`agregarGrafico`, `editarGrafico`, `eliminarGrafico`); el controlador es liviano. Crear, editar o eliminar un gráfico **no** es una transición de workflow: es contenido, por lo que no pasa por `TransicionWorkflowService` y no altera el estado del `Proceso`.

#### Scenario: Agregar un gráfico con permiso durante la elaboración
- **WHEN** un usuario con el permiso `informes.elaborar` agrega un gráfico con código, título, tipo y datos a una ejecución en estado `en_elaboracion`
- **THEN** el sistema crea el `grafico_informe_razonado` asociado a la ejecución
- **AND** el estado del `Proceso` de la ejecución no cambia

#### Scenario: Agregar un gráfico dentro de una sección
- **WHEN** un usuario con el permiso `informes.elaborar` agrega un gráfico indicando una sección de la misma ejecución
- **THEN** el sistema crea el gráfico asociado a esa sección
- **AND** rechaza la operación si la sección indicada pertenece a otra ejecución

#### Scenario: Editar un gráfico durante la elaboración
- **WHEN** un usuario con el permiso `informes.elaborar` edita el título, el tipo, los datos o el orden de un gráfico de una ejecución en estado `en_elaboracion`
- **THEN** el sistema actualiza ese gráfico

#### Scenario: Eliminar un gráfico durante la elaboración
- **WHEN** un usuario con el permiso `informes.elaborar` elimina un gráfico de una ejecución en estado `en_elaboracion`
- **THEN** el sistema elimina ese `grafico_informe_razonado`

#### Scenario: Rechazar un tipo de gráfico inválido
- **WHEN** un usuario con el permiso `informes.elaborar` intenta crear o editar un gráfico con un tipo distinto de `barra`, `linea`, `torta` o `area`
- **THEN** el sistema rechaza la solicitud por validación
- **AND** el gráfico no se crea ni modifica

#### Scenario: No se pueden elaborar gráficos fuera del estado de elaboración
- **WHEN** un usuario intenta crear, editar o eliminar un gráfico de una ejecución que no está en estado `en_elaboracion`
- **THEN** el sistema bloquea la operación
- **AND** el gráfico no se crea, modifica ni elimina

#### Scenario: Elaborar gráficos sin el permiso requerido
- **WHEN** un usuario autenticado sin el permiso `informes.elaborar` intenta crear, editar o eliminar un gráfico de una ejecución en estado `en_elaboracion`
- **THEN** el sistema rechaza la solicitud con un error de autorización
- **AND** el gráfico no se crea, modifica ni elimina

### Requirement: Generar y registrar una exportación de una ejecución de informe razonado
El sistema SHALL permitir a los usuarios con el permiso `informes.exportar` generar una exportación de una `ejecucion_informe_razonado` en formato **HTML**, a partir del contenido ya ensamblado de la ejecución (secciones, métricas, gráficos, narrativas y excepciones). La generación del archivo SHALL delegarse a un `ExportadorInformeRazonadoService` con una interfaz extensible a otros formatos; en este alcance solo el formato `html` está soportado y cualquier otro formato solicitado SHALL rechazarse por validación. El archivo generado SHALL guardarse en almacenamiento privado (no en la base de datos) y el sistema SHALL registrar la evidencia en `exportaciones_informe_razonado` a través de `InformeRazonadoService::exportar()`, incluyendo `formato`, `ruta_archivo` y el responsable (`generado_por`, `generado_en`). Una exportación registrada es **evidencia inmutable**: el sistema SHALL NOT permitir editarla ni eliminarla. Generar una exportación **no** es una transición de workflow y no altera el estado del `Proceso`; se permite en cualquier estado de la ejecución.

#### Scenario: Generar una exportación HTML con permiso
- **WHEN** un usuario con el permiso `informes.exportar` solicita exportar una ejecución en formato `html`
- **THEN** el sistema genera un archivo HTML con el contenido ensamblado de la ejecución en almacenamiento privado
- **AND** registra una `exportacion_informe_razonado` con formato `html`, la ruta del archivo, el usuario responsable y la fecha
- **AND** el estado del `Proceso` de la ejecución no cambia

#### Scenario: Rechazar un formato de exportación no soportado
- **WHEN** un usuario con el permiso `informes.exportar` solicita exportar una ejecución en un formato distinto de `html`
- **THEN** el sistema rechaza la solicitud por validación
- **AND** no se genera ningún archivo ni se registra ninguna exportación

#### Scenario: Exportar sin el permiso requerido
- **WHEN** un usuario autenticado sin el permiso `informes.exportar` intenta exportar una ejecución
- **THEN** el sistema rechaza la solicitud con un error de autorización
- **AND** no se genera ningún archivo ni se registra ninguna exportación

#### Scenario: Una exportación registrada es evidencia inmutable
- **WHEN** existe una `exportacion_informe_razonado` registrada para una ejecución
- **THEN** el sistema no expone ninguna operación para editarla ni eliminarla

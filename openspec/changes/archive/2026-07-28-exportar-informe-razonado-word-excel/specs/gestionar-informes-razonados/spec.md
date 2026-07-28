## MODIFIED Requirements

### Requirement: Generar y registrar una exportación de una ejecución de informe razonado
El sistema SHALL permitir a los usuarios con el permiso `informes.exportar` generar una exportación de una `ejecucion_informe_razonado` en formato **HTML**, **PDF**, **Word (docx)** o **Excel (xlsx)**, a partir del contenido ya ensamblado de la ejecución (secciones, métricas, gráficos, narrativas y excepciones). La generación del archivo SHALL delegarse a un `ExportadorInformeRazonadoService` con una interfaz extensible a otros formatos; en este alcance los formatos `html`, `pdf`, `docx` y `xlsx` están soportados y cualquier otro formato solicitado SHALL rechazarse por validación. El PDF y el Word SHALL generarse a partir de la misma vista renderizada del formato HTML, de modo que reflejen idéntico contenido; el Excel SHALL generarse en forma tabular desde el modelo (una hoja de métricas y una hoja de excepciones, más la metadata de la ejecución), sin volcar la narrativa libre a celdas. El archivo generado SHALL guardarse en almacenamiento privado (no en la base de datos) y el sistema SHALL registrar la evidencia en `exportaciones_informe_razonado` a través de `InformeRazonadoService::exportar()`, incluyendo `formato`, `ruta_archivo` y el responsable (`generado_por`, `generado_en`). Una exportación registrada es **evidencia inmutable**: el sistema SHALL NOT permitir editarla ni eliminarla. Generar una exportación **no** es una transición de workflow y no altera el estado del `Proceso`; se permite en cualquier estado de la ejecución.

#### Scenario: Generar una exportación HTML con permiso
- **WHEN** un usuario con el permiso `informes.exportar` solicita exportar una ejecución en formato `html`
- **THEN** el sistema genera un archivo HTML con el contenido ensamblado de la ejecución en almacenamiento privado
- **AND** registra una `exportacion_informe_razonado` con formato `html`, la ruta del archivo, el usuario responsable y la fecha
- **AND** el estado del `Proceso` de la ejecución no cambia

#### Scenario: Generar una exportación PDF con permiso
- **WHEN** un usuario con el permiso `informes.exportar` solicita exportar una ejecución en formato `pdf`
- **THEN** el sistema genera un archivo PDF con el contenido ensamblado de la ejecución en almacenamiento privado
- **AND** registra una `exportacion_informe_razonado` con formato `pdf`, la ruta del archivo, el usuario responsable y la fecha
- **AND** el estado del `Proceso` de la ejecución no cambia

#### Scenario: Generar una exportación Word con permiso
- **WHEN** un usuario con el permiso `informes.exportar` solicita exportar una ejecución en formato `docx`
- **THEN** el sistema genera un archivo Word con el contenido ensamblado de la ejecución en almacenamiento privado
- **AND** registra una `exportacion_informe_razonado` con formato `docx`, la ruta del archivo, el usuario responsable y la fecha
- **AND** el estado del `Proceso` de la ejecución no cambia

#### Scenario: Generar una exportación Excel con permiso
- **WHEN** un usuario con el permiso `informes.exportar` solicita exportar una ejecución en formato `xlsx`
- **THEN** el sistema genera un archivo Excel con las métricas y excepciones de la ejecución en almacenamiento privado
- **AND** registra una `exportacion_informe_razonado` con formato `xlsx`, la ruta del archivo, el usuario responsable y la fecha
- **AND** el estado del `Proceso` de la ejecución no cambia

#### Scenario: Descargar una exportación con el content-type correcto según su formato
- **WHEN** un usuario con el permiso `informes.exportar` descarga una exportación registrada
- **THEN** el sistema responde con el archivo y el `Content-Type` correspondiente a su formato (`application/pdf` para pdf, `application/vnd.openxmlformats-officedocument.wordprocessingml.document` para docx, `application/vnd.openxmlformats-officedocument.spreadsheetml.sheet` para xlsx, `text/html` para html)

#### Scenario: Rechazar un formato de exportación no soportado
- **WHEN** un usuario con el permiso `informes.exportar` solicita exportar una ejecución en un formato distinto de `html`, `pdf`, `docx` o `xlsx`
- **THEN** el sistema rechaza la solicitud por validación
- **AND** no se genera ningún archivo ni se registra ninguna exportación

#### Scenario: Exportar sin el permiso requerido
- **WHEN** un usuario autenticado sin el permiso `informes.exportar` intenta exportar una ejecución
- **THEN** el sistema rechaza la solicitud con un error de autorización
- **AND** no se genera ningún archivo ni se registra ninguna exportación

#### Scenario: Una exportación registrada es evidencia inmutable
- **WHEN** existe una `exportacion_informe_razonado` registrada para una ejecución
- **THEN** el sistema no expone ninguna operación para editarla ni eliminarla

## MODIFIED Requirements

### Requirement: Generar y registrar una exportación de una ejecución de informe razonado
El sistema SHALL permitir a los usuarios con el permiso `informes.exportar` generar una exportación de una `ejecucion_informe_razonado` en formato **HTML** o **PDF**, a partir del contenido ya ensamblado de la ejecución (secciones, métricas, gráficos, narrativas y excepciones). La generación del archivo SHALL delegarse a un `ExportadorInformeRazonadoService` con una interfaz extensible a otros formatos; en este alcance los formatos `html` y `pdf` están soportados y cualquier otro formato solicitado SHALL rechazarse por validación. El PDF SHALL generarse a partir de la misma vista renderizada del formato HTML, de modo que ambos formatos reflejen idéntico contenido. El archivo generado SHALL guardarse en almacenamiento privado (no en la base de datos) y el sistema SHALL registrar la evidencia en `exportaciones_informe_razonado` a través de `InformeRazonadoService::exportar()`, incluyendo `formato`, `ruta_archivo` y el responsable (`generado_por`, `generado_en`). Una exportación registrada es **evidencia inmutable**: el sistema SHALL NOT permitir editarla ni eliminarla. Generar una exportación **no** es una transición de workflow y no altera el estado del `Proceso`; se permite en cualquier estado de la ejecución.

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

#### Scenario: Descargar una exportación PDF con el content-type correcto
- **WHEN** un usuario con el permiso `informes.exportar` descarga una exportación registrada en formato `pdf`
- **THEN** el sistema responde con el archivo PDF y el encabezado `Content-Type: application/pdf`

#### Scenario: Rechazar un formato de exportación no soportado
- **WHEN** un usuario con el permiso `informes.exportar` solicita exportar una ejecución en un formato distinto de `html` o `pdf`
- **THEN** el sistema rechaza la solicitud por validación
- **AND** no se genera ningún archivo ni se registra ninguna exportación

#### Scenario: Exportar sin el permiso requerido
- **WHEN** un usuario autenticado sin el permiso `informes.exportar` intenta exportar una ejecución
- **THEN** el sistema rechaza la solicitud con un error de autorización
- **AND** no se genera ningún archivo ni se registra ninguna exportación

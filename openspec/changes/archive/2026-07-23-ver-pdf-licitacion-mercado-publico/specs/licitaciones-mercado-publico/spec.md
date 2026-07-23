## ADDED Requirements

### Requirement: Obtener el PDF de la ficha de una Licitación desde la página pública de Mercado Público
El sistema SHALL obtener, a partir del código de una Licitación y sin exigir que esa Licitación exista localmente, el PDF de su ficha desde la página pública de Mercado Público (`DetailsAcquisition.aspx`), ejecutando el envío del formulario que esa página expone para descargar la ficha. El sistema SHALL registrar la operación como un `trabajo_integracion` y sus peticiones como `solicitudes_api_externas` asociadas al `sistema_externo` `MERCADO_PUBLICO`, reutilizando `IntegracionExternaService`. El sistema SHALL NOT usar automatización de navegador para esta operación.

#### Scenario: La ficha pública permite descargar el PDF
- **WHEN** el sistema solicita el PDF de una Licitación cuya ficha pública ofrece la descarga
- **THEN** el sistema obtiene el contenido del PDF
- **AND** registra el trabajo de integración como completado

#### Scenario: La ficha pública no ofrece la descarga
- **WHEN** la ficha pública de Mercado Público no ofrece la acción de descarga para el código consultado
- **THEN** el sistema informa que no fue posible obtener el PDF
- **AND** registra la solicitud como no encontrada y el trabajo de integración como fallido

#### Scenario: La respuesta no es un PDF
- **WHEN** Mercado Público responde a la solicitud de descarga con un contenido que no es un PDF
- **THEN** el sistema descarta la respuesta y informa que no fue posible obtener el PDF
- **AND** SHALL NOT entregar al usuario un archivo con contenido distinto del solicitado

#### Scenario: Error de red contra Mercado Público
- **WHEN** la comunicación con Mercado Público falla durante la obtención del PDF
- **THEN** el sistema informa que no fue posible obtener el PDF
- **AND** registra el error en la solicitud y cierra el trabajo de integración como fallido

### Requirement: Conservar como evidencia el PDF obtenido de una Licitación
El sistema SHALL persistir el PDF obtenido en almacenamiento privado —nunca en la base de datos ni en almacenamiento público— y SHALL registrar un `snapshot_datos_externo` que deje constancia de la fuente, la fecha de captura, el método de captura, el usuario responsable, la referencia externa (el código de la Licitación) y el hash del archivo obtenido. Cuando la Licitación exista localmente, el snapshot SHALL quedar vinculado a ella. El snapshot SHALL conservar los metadatos de la captura, no el binario.

#### Scenario: Se registra la evidencia de la captura
- **WHEN** el sistema obtiene con éxito el PDF de una Licitación
- **THEN** el archivo queda persistido en almacenamiento privado
- **AND** se crea un snapshot con la fuente, la fecha, el método de captura, el usuario responsable, el código de la Licitación y el hash del archivo

#### Scenario: La Licitación existe localmente
- **WHEN** el sistema obtiene el PDF de una Licitación que ya está guardada en el sistema
- **THEN** el snapshot de esa captura queda vinculado a esa Licitación

#### Scenario: La Licitación no existe localmente
- **WHEN** el sistema obtiene el PDF de una Licitación que no está guardada en el sistema
- **THEN** el sistema entrega igualmente el PDF y registra el snapshot con el código como referencia externa, sin crear la Licitación

### Requirement: Reutilizar el PDF ya capturado de una Licitación
El sistema SHALL entregar, para las solicitudes posteriores del PDF de un mismo código de Licitación, el archivo ya capturado, sin volver a consultar Mercado Público. El sistema SHALL NOT reemplazar automáticamente un PDF ya capturado por una versión más reciente: la captura conservada es la evidencia fechada de lo que Mercado Público publicaba en ese momento. Si el archivo de una captura previa ya no está disponible en el almacenamiento, el sistema SHALL volver a obtenerlo desde Mercado Público en vez de fallar.

#### Scenario: Segunda solicitud del mismo PDF
- **WHEN** un usuario solicita el PDF de una Licitación que el sistema ya capturó antes
- **THEN** el sistema entrega el archivo conservado
- **AND** no realiza ninguna petición a Mercado Público

#### Scenario: El archivo conservado ya no está disponible
- **WHEN** existe una captura previa registrada pero su archivo ya no está en el almacenamiento
- **THEN** el sistema obtiene nuevamente el PDF desde Mercado Público y registra la captura nueva

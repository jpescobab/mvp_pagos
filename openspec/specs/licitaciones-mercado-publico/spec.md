## Purpose

Esta capacidad cubre el dominio y el servicio de integración que trae Licitaciones desde la API de Mercado Público hacia el sistema propio: búsqueda local primero, consulta a la API solo cuando se pide explícitamente, comparación campo a campo sin sobrescribir datos automáticamente, y guardado únicamente tras confirmación explícita del usuario. A diferencia de una Orden de Compra, una Licitación no tiene un único proveedor emisor: la adjudicación (si existe) se conserva como dato informativo a nivel de licitación y por ítem, sin crear ni tocar registros del catálogo de `Proveedor`. Toda consulta a la API deja evidencia trazable (snapshot + solicitud) reutilizando la capa transversal de integraciones (`sistemas_externos`, `solicitudes_api_externas`, `snapshots_datos_externos`). Mercado Público es origen de evidencia, nunca gobierno del workflow interno.

## Requirements

### Requirement: Buscar una Licitación por código, primero localmente
El sistema SHALL buscar una Licitación de Mercado Público (`licitacion_mercado_publico`) por su código primero en la base de datos local, antes de considerar cualquier consulta a la API externa.

#### Scenario: La licitación ya existe localmente
- **WHEN** un usuario con el permiso `adquisiciones.consultar_licitacion_mp` busca un código de licitación que ya existe en `licitaciones_mercado_publico`
- **THEN** el sistema devuelve el registro local (con sus ítems y su proceso de adquisición vinculado) sin consultar la API externa

#### Scenario: La licitación no existe localmente
- **WHEN** un usuario busca un código de licitación que no existe en `licitaciones_mercado_publico`
- **THEN** el sistema procede a consultarla en la API de Mercado Público

### Requirement: Verificar una Licitación local contra la API de Mercado Público solo si el usuario lo solicita
El sistema SHALL ofrecer, cuando la licitación ya existe localmente, una acción explícita para consultar la API de Mercado Público y comparar el resultado contra el registro local, sin ejecutar esa consulta automáticamente.

#### Scenario: Usuario acepta verificar contra la API
- **WHEN** un usuario con el permiso requerido solicita verificar una Licitación local contra Mercado Público
- **THEN** el sistema consulta la API, registra la solicitud y el snapshot de la respuesta, y calcula las diferencias campo a campo entre el dato local y el dato recibido

#### Scenario: Usuario no solicita verificación
- **WHEN** un usuario visualiza una Licitación local sin solicitar la verificación contra Mercado Público
- **THEN** el sistema no realiza ninguna llamada a la API externa

#### Scenario: Sin diferencias
- **WHEN** la verificación contra la API no encuentra diferencias respecto al registro local
- **THEN** el sistema informa que el registro local está actualizado, sin ofrecer ninguna acción de actualización

#### Scenario: Con diferencias, el usuario decide
- **WHEN** la verificación contra la API encuentra diferencias respecto al registro local
- **THEN** el sistema muestra el detalle de las diferencias campo a campo
- **AND** el usuario decide explícitamente si aplica la actualización o mantiene el dato local
- **AND** el sistema no modifica el registro local hasta que el usuario confirma aplicar la actualización

### Requirement: Consultar una Licitación inexistente localmente en la API de Mercado Público
El sistema SHALL consultar la API de Mercado Público cuando el código de licitación buscado no existe localmente, y SHALL registrar la solicitud y su resultado (encontrada o no) como evidencia trazable.

#### Scenario: La API no encuentra la licitación
- **WHEN** el sistema consulta la API de Mercado Público por un código de licitación no encontrado localmente y la API no la encuentra
- **THEN** el sistema informa al usuario que la licitación no fue encontrada
- **AND** registra el intento como una `solicitud_api_externa` con su estado de error/no encontrado
- **AND** no crea ningún registro de licitación ni snapshot de datos

#### Scenario: La API encuentra la licitación
- **WHEN** el sistema consulta la API de Mercado Público por un código de licitación no encontrado localmente y la API la encuentra
- **THEN** el sistema registra la solicitud y el snapshot del payload recibido
- **AND** presenta al usuario una vista previa de la licitación y sus ítems sin guardarla todavía

### Requirement: Toda consulta a la API de Mercado Público deja evidencia trazable
El sistema SHALL registrar, para toda consulta realizada a la API de Mercado Público (encuentre o no la licitación, se guarde o no el resultado), una `solicitud_api_externa` asociada al `sistema_externo` `MERCADO_PUBLICO`, y un `snapshot_datos_externos` con el payload crudo cuando la API devuelva datos, reutilizando `App\Services\Integraciones\IntegracionExternaService`.

#### Scenario: Snapshot del payload crudo
- **WHEN** la API de Mercado Público devuelve datos de una licitación
- **THEN** el sistema guarda el payload crudo recibido, su hash, la fecha de captura y el usuario que inició la consulta en un `snapshot_datos_externos` vinculado a la licitación

### Requirement: Una Licitación no tiene un único proveedor emisor
El sistema SHALL conservar el o los proveedores adjudicados de una Licitación únicamente como dato informativo por ítem (`rut_proveedor`, `nombre_proveedor`, `cantidad`, `monto_unitario` dentro de `licitacion_mercado_publico_items.adjudicacion`), y SHALL NOT crear, completar ni vincular automáticamente ningún registro del catálogo de `Proveedor` a partir de una Licitación.

#### Scenario: Ítem con proveedor adjudicado
- **WHEN** el payload de una Licitación incluye la adjudicación de un ítem con RUT y nombre de proveedor
- **THEN** el sistema guarda ese RUT y nombre como parte del ítem, sin buscar, crear ni actualizar ningún registro de `Proveedor`

#### Scenario: Licitación sin adjudicar
- **WHEN** el payload de una Licitación no incluye adjudicación (ni a nivel de licitación ni de ítems)
- **THEN** el sistema guarda la licitación y sus ítems con sus campos de adjudicación en `null`, sin error

### Requirement: Guardar una Licitación nueva solo tras confirmación explícita del usuario
El sistema SHALL requerir una confirmación explícita del usuario antes de persistir una Licitación obtenida de la API de Mercado Público, y SHALL guardar en la misma operación transaccional: la licitación, sus ítems, y dejarla vinculada al snapshot y la solicitud que la originaron.

#### Scenario: Confirmación de guardado
- **WHEN** un usuario confirma guardar una Licitación previamente mostrada como vista previa
- **THEN** el sistema crea el registro `licitacion_mercado_publico`, sus `licitacion_mercado_publico_items`, y lo asocia al `snapshot_datos_externos` y la `solicitud_api_externa` que lo originaron, todo en una sola transacción

#### Scenario: Usuario no confirma
- **WHEN** un usuario descarta la vista previa de una Licitación sin confirmar el guardado
- **THEN** el sistema no persiste ningún registro de Licitación ni de sus ítems

#### Scenario: Código de licitación duplicado
- **WHEN** un usuario confirma guardar una Licitación cuyo código ya existe en `licitaciones_mercado_publico`
- **THEN** el sistema no crea un segundo registro para el mismo código

### Requirement: Vínculo opcional entre una Licitación y un proceso de adquisición
El sistema SHALL permitir vincular y desvincular manualmente una `licitacion_mercado_publico` a un `proceso_adquisicion` existente, sin que ese vínculo dispare ninguna transición de workflow.

#### Scenario: Vincular una Licitación a un proceso de adquisición
- **WHEN** un usuario con el permiso requerido vincula una `licitacion_mercado_publico` a un `proceso_adquisicion` existente
- **THEN** el sistema guarda la referencia y registra la acción en auditoría
- **AND** el estado del `Proceso` del `proceso_adquisicion` no cambia como consecuencia de este vínculo

#### Scenario: Desvincular una Licitación
- **WHEN** un usuario desvincula una `licitacion_mercado_publico` de su `proceso_adquisicion`
- **THEN** el sistema quita la referencia y registra la acción en auditoría

#### Scenario: Licitación sin vínculo
- **WHEN** se consulta una `licitacion_mercado_publico` que nunca fue vinculada a un `proceso_adquisicion`
- **THEN** el sistema la muestra sin proceso de adquisición asociado, sin error

### Requirement: El cronograma de la Licitación conserva fecha y hora reales de cada etapa
El sistema SHALL conservar la fecha y hora completas que entrega Mercado Público para cada hito del cronograma de una Licitación (creación, publicación, inicio y cierre de preguntas, publicación de respuestas, cierre de recepción de ofertas, apertura técnica, apertura económica, adjudicación), sin truncarlas a solo el día, y SHALL omitir del cronograma cualquier hito que la API no informe.

#### Scenario: La API entrega fecha y hora de un hito
- **WHEN** el payload de Mercado Público incluye un hito de `Fechas` con fecha y hora
- **THEN** el sistema guarda ese hito en el cronograma con su fecha y hora completas, sin recortar la hora

#### Scenario: La API no informa un hito
- **WHEN** el payload de Mercado Público no incluye valor para uno de los hitos del cronograma de una Licitación
- **THEN** ese hito no aparece en el arreglo `cronograma` guardado, en vez de guardarse con un valor `null` o inventado

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

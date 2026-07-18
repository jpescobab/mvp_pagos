## Purpose

Esta capacidad cubre el dominio y el servicio de integración que trae Órdenes de Compra (OC) desde la API de Mercado Público hacia el sistema propio: búsqueda local primero, consulta a la API solo cuando se pide explícitamente, comparación campo a campo sin sobrescribir datos automáticamente, verificación del proveedor emisor contra el catálogo, y guardado únicamente tras confirmación explícita del usuario. Toda consulta a la API deja evidencia trazable (snapshot + solicitud) reutilizando la capa transversal de integraciones (`sistemas_externos`, `solicitudes_api_externas`, `snapshots_datos_externos`). Mercado Público es origen de evidencia, nunca gobierno del workflow interno.
## Requirements
### Requirement: Buscar una Orden de Compra por código, primero localmente
El sistema SHALL buscar una Orden de Compra de Mercado Público (`orden_compra_mercado_publico`) por su código primero en la base de datos local, antes de considerar cualquier consulta a la API externa.

#### Scenario: La OC ya existe localmente
- **WHEN** un usuario con el permiso `adquisiciones.consultar_orden_compra_mp` busca un código de OC que ya existe en `ordenes_compra_mercado_publico`
- **THEN** el sistema devuelve el registro local (con sus ítems y el proveedor vinculado) sin consultar la API externa

#### Scenario: La OC no existe localmente
- **WHEN** un usuario busca un código de OC que no existe en `ordenes_compra_mercado_publico`
- **THEN** el sistema procede a consultarla en la API de Mercado Público

### Requirement: Verificar una OC local contra la API de Mercado Público solo si el usuario lo solicita
El sistema SHALL ofrecer, cuando la OC ya existe localmente, una acción explícita para consultar la API de Mercado Público y comparar el resultado contra el registro local, sin ejecutar esa consulta automáticamente.

#### Scenario: Usuario acepta verificar contra la API
- **WHEN** un usuario con el permiso requerido solicita verificar una OC local contra Mercado Público
- **THEN** el sistema consulta la API, registra la solicitud y el snapshot de la respuesta, y calcula las diferencias campo a campo entre el dato local y el dato recibido

#### Scenario: Usuario no solicita verificación
- **WHEN** un usuario visualiza una OC local sin solicitar la verificación contra Mercado Público
- **THEN** el sistema no realiza ninguna llamada a la API externa

#### Scenario: Sin diferencias
- **WHEN** la verificación contra la API no encuentra diferencias respecto al registro local
- **THEN** el sistema informa que el registro local está actualizado, sin ofrecer ninguna acción de actualización

#### Scenario: Con diferencias, el usuario decide
- **WHEN** la verificación contra la API encuentra diferencias respecto al registro local
- **THEN** el sistema muestra el detalle de las diferencias campo a campo
- **AND** el usuario decide explícitamente si aplica la actualización o mantiene el dato local
- **AND** el sistema no modifica el registro local hasta que el usuario confirma aplicar la actualización

### Requirement: Consultar una OC inexistente localmente en la API de Mercado Público
El sistema SHALL consultar la API de Mercado Público cuando el código de OC buscado no existe localmente, y SHALL registrar la solicitud y su resultado (encontrada o no) como evidencia trazable.

#### Scenario: La API no encuentra la OC
- **WHEN** el sistema consulta la API de Mercado Público por un código de OC no encontrado localmente y la API no la encuentra
- **THEN** el sistema informa al usuario que la OC no fue encontrada
- **AND** registra el intento como una `solicitud_api_externa` con su estado de error/no encontrado
- **AND** no crea ningún registro de OC ni snapshot de datos

#### Scenario: La API encuentra la OC
- **WHEN** el sistema consulta la API de Mercado Público por un código de OC no encontrado localmente y la API la encuentra
- **THEN** el sistema registra la solicitud y el snapshot del payload recibido
- **AND** presenta al usuario una vista previa de la OC y sus ítems sin guardarla todavía

### Requirement: Toda consulta a la API de Mercado Público deja evidencia trazable
El sistema SHALL registrar, para toda consulta realizada a la API de Mercado Público (encuentre o no la OC, se guarde o no el resultado), una `solicitud_api_externa` asociada al `sistema_externo` `MERCADO_PUBLICO`, y un `snapshot_datos_externos` con el payload crudo cuando la API devuelva datos, reutilizando `App\Services\Integraciones\IntegracionExternaService`.

#### Scenario: Snapshot del payload crudo
- **WHEN** la API de Mercado Público devuelve datos de una OC
- **THEN** el sistema guarda el payload crudo recibido, su hash, la fecha de captura y el usuario que inició la consulta en un `snapshot_datos_externos` vinculado a la OC

### Requirement: Verificar y vincular al proveedor emisor de la OC
El sistema SHALL resolver automáticamente al proveedor emisor de una OC nueva como parte de la misma operación transaccional de guardado, sin bloquear ni exigir un paso manual previo del usuario. La resolución SHALL poblar y completar el proveedor con todos los campos que el payload de Mercado Público aporta y para los que el catálogo de proveedores tiene columna (RUT, nombre, dirección, comuna, región, giro, correo y datos de contacto), normalizando a nulo los valores que Mercado Público entrega vacíos o con solo espacios. Cuando el payload no aporta un RUT de proveedor identificable y el usuario no indica un override, el sistema SHALL rechazar el guardado de esa OC con un mensaje claro, sin crear un proveedor con RUT vacío ni abortar la operación por una violación de unicidad.

#### Scenario: El proveedor ya existe y está completo
- **WHEN** el proveedor emisor de una OC obtenida de la API ya existe en el catálogo de proveedores (identificado por su RUT normalizado) y sus datos ya están completos
- **THEN** el sistema vincula ese proveedor a la OC sin modificar ninguno de sus campos

#### Scenario: El proveedor ya existe pero tiene campos vacíos
- **WHEN** el proveedor emisor ya existe en el catálogo pero tiene campos vacíos que el payload de Mercado Público sí aporta (p. ej. `nombre`, `direccion`, `comuna`, `region`, `giro`, `correo` o datos de contacto)
- **THEN** el sistema completa únicamente esos campos vacíos con los datos del payload, sin sobreescribir ningún campo que ya tenga un valor cargado

#### Scenario: El proveedor no existe
- **WHEN** el proveedor emisor de una OC obtenida de la API no existe en el catálogo de proveedores
- **THEN** el sistema crea el proveedor con todos los datos disponibles del payload (RUT normalizado, nombre, dirección, comuna, región, giro, correo y datos de contacto), como parte de la misma transacción de guardado de la OC
- **AND** los campos que el payload entrega vacíos o con solo espacios se guardan como nulos, no como cadenas vacías

#### Scenario: El payload no trae un RUT de proveedor identificable
- **WHEN** se intenta guardar una OC cuyo payload no aporta un RUT de proveedor identificable y el usuario no indicó un `proveedor_id` de override
- **THEN** el sistema rechaza el guardado con un mensaje claro
- **AND** no crea ningún proveedor con RUT vacío ni persiste la OC

#### Scenario: Falla el guardado de la OC tras resolver el proveedor
- **WHEN** la creación o actualización del registro `orden_compra_mercado_publico` falla después de haberse creado o actualizado el proveedor dentro de la misma operación
- **THEN** la transacción completa se revierte, incluyendo la creación o actualización del proveedor

#### Scenario: Override manual del proveedor
- **WHEN** el usuario indica explícitamente un `proveedor_id` distinto al detectado por RUT del payload
- **THEN** el sistema vincula la OC a ese proveedor indicado sin ejecutar la lógica de creación o completado automático

### Requirement: Guardar una OC nueva solo tras confirmación explícita del usuario
El sistema SHALL requerir una confirmación explícita del usuario antes de persistir una OC obtenida de la API de Mercado Público, y SHALL guardar en la misma operación transaccional: la resolución del proveedor emisor (creación o completado de campos vacíos, salvo override manual), la OC, sus ítems, y dejar vinculados el snapshot y la solicitud que la originaron.

#### Scenario: Confirmación de guardado
- **WHEN** un usuario confirma guardar una OC previamente mostrada como vista previa
- **THEN** el sistema resuelve el proveedor emisor (crea, completa campos vacíos, o usa el override manual indicado), crea el registro `orden_compra_mercado_publico`, sus `orden_compra_mercado_publico_items`, y lo asocia al `snapshot_datos_externos` y la `solicitud_api_externa` que la originaron, todo en una sola transacción
- **AND** el sistema informa el resultado de la operación sobre el proveedor (creado, actualizado, o sin cambios)

#### Scenario: Usuario no confirma
- **WHEN** un usuario descarta la vista previa de una OC sin confirmar el guardado
- **THEN** el sistema no persiste ningún registro de OC, de sus ítems, ni realiza ninguna operación sobre el catálogo de proveedores

### Requirement: Vínculo opcional entre una OC y un proceso de adquisición
El sistema SHALL permitir vincular y desvincular manualmente una `orden_compra_mercado_publico` a un `proceso_adquisicion` existente, sin que ese vínculo dispare ninguna transición de workflow.

#### Scenario: Vincular una OC a un proceso de adquisición
- **WHEN** un usuario con el permiso requerido vincula una `orden_compra_mercado_publico` a un `proceso_adquisicion` existente
- **THEN** el sistema guarda la referencia y registra la acción en auditoría
- **AND** el estado del `Proceso` del `proceso_adquisicion` no cambia como consecuencia de este vínculo

#### Scenario: Desvincular una OC
- **WHEN** un usuario desvincula una `orden_compra_mercado_publico` de su `proceso_adquisicion`
- **THEN** el sistema quita la referencia y registra la acción en auditoría

#### Scenario: OC sin vínculo
- **WHEN** se consulta una `orden_compra_mercado_publico` que nunca fue vinculada a un `proceso_adquisicion`
- **THEN** el sistema la muestra sin proceso de adquisición asociado, sin error

### Requirement: El cronograma conserva fecha y hora reales de cada etapa
El sistema SHALL conservar la fecha y hora completas que entrega Mercado Público para cada hito del cronograma (`FechaCreacion`, `FechaEnvio`, `FechaAceptacion`, `FechaCancelacion`), sin truncarlas a solo el día. `fecha_emision` queda fuera de este requisito porque su columna es de tipo fecha (sin hora) por diseño.

#### Scenario: La API entrega fecha y hora de un hito
- **WHEN** el payload de Mercado Público incluye un hito de `Fechas` con fecha y hora (por ejemplo `FechaAceptacion`)
- **THEN** el sistema guarda ese hito en el cronograma con su fecha y hora completas, sin recortar la hora

#### Scenario: La API entrega solo fecha sin hora
- **WHEN** el payload de Mercado Público incluye un hito de `Fechas` sin componente de hora
- **THEN** el sistema guarda el valor tal como lo entrega la API, sin inventar ni completar una hora que no fue informada

### Requirement: Resolver el enlace directo de descarga del PDF de una Orden de Compra
El sistema SHALL resolver, a partir del código de una Orden de Compra, el enlace real de descarga del PDF que Mercado Público expone en su página pública de detalle (`DetailsPurchaseOrder.aspx`), sin exigir que la OC exista localmente, y SHALL registrar la consulta como una `solicitud_api_externa` asociada al `sistema_externo` `MERCADO_PUBLICO`, reutilizando `IntegracionExternaService`.

#### Scenario: El botón de descarga existe en la página pública
- **WHEN** el sistema consulta la página pública de detalle de una OC en Mercado Público y esta incluye el botón nativo de descarga de PDF
- **THEN** el sistema extrae el enlace real de descarga y lo retorna
- **AND** registra la solicitud como exitosa

#### Scenario: La OC no existe o la página no incluye el botón de PDF
- **WHEN** la página pública de Mercado Público no incluye el botón de descarga de PDF para el código consultado
- **THEN** el sistema retorna que no fue posible resolver el enlace, sin construir una URL inválida
- **AND** registra la solicitud como no encontrada

#### Scenario: No se persiste el HTML consultado
- **WHEN** el sistema resuelve el enlace de descarga de PDF
- **THEN** no se crea ningún `snapshot_datos_externos` a partir de esa consulta, porque no es un dato de negocio que el sistema gobierne


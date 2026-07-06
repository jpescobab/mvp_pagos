## ADDED Requirements

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
El sistema SHALL verificar si el proveedor emisor indicado en el payload de la OC ya existe en el catálogo de proveedores antes de permitir guardar la OC.

#### Scenario: El proveedor ya existe
- **WHEN** el proveedor emisor de una OC obtenida de la API ya existe en el catálogo de proveedores (identificado por su RUT/código)
- **THEN** el sistema vincula automáticamente ese proveedor a la vista previa de la OC, sin pedir confirmación adicional para el vínculo

#### Scenario: El proveedor no existe
- **WHEN** el proveedor emisor de una OC obtenida de la API no existe en el catálogo de proveedores
- **THEN** el sistema le pregunta al usuario si desea crear o actualizar el proveedor antes de continuar
- **AND** no guarda la OC hasta que el proveedor exista en el catálogo

### Requirement: Guardar una OC nueva solo tras confirmación explícita del usuario
El sistema SHALL requerir una confirmación explícita del usuario antes de persistir una OC obtenida de la API de Mercado Público, y SHALL guardar en la misma operación la OC, sus ítems, el proveedor si corresponde, y dejar vinculados el snapshot y la solicitud que la originaron.

#### Scenario: Confirmación de guardado
- **WHEN** un usuario confirma guardar una OC previamente mostrada como vista previa
- **THEN** el sistema crea el registro `orden_compra_mercado_publico`, sus `orden_compra_mercado_publico_items`, y lo asocia al `snapshot_datos_externos` y la `solicitud_api_externa` que la originaron

#### Scenario: Usuario no confirma
- **WHEN** un usuario descarta la vista previa de una OC sin confirmar el guardado
- **THEN** el sistema no persiste ningún registro de OC ni de sus ítems

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

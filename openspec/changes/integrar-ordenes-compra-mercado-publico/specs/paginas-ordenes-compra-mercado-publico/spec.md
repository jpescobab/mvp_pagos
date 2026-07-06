## ADDED Requirements

### Requirement: Página de búsqueda de Orden de Compra por código
El sistema SHALL renderizar una página donde un usuario autenticado con el permiso `adquisiciones.consultar_orden_compra_mp` ingresa un código de OC y recibe el resultado de la búsqueda local, delegando toda la lógica en la capability `ordenes-compra-mercado-publico`. Los controles de búsqueda (código de OC y el botón de consultar) SHALL ubicarse en una única fila horizontal por sobre la ficha de resultado, nunca en una barra lateral.

#### Scenario: Buscar un código existente localmente
- **WHEN** un usuario ingresa un código de OC que existe localmente
- **THEN** la página muestra los datos de la OC local, sus ítems y el proveedor vinculado
- **AND** ofrece la acción de verificar contra Mercado Público

#### Scenario: Buscar un código inexistente localmente
- **WHEN** un usuario ingresa un código de OC que no existe localmente
- **THEN** la página consulta la API y muestra el resultado (vista previa si se encontró, o el aviso de no encontrada)

#### Scenario: Los filtros de búsqueda quedan en una sola fila sobre la ficha
- **WHEN** se renderiza la página de búsqueda de OC, con o sin resultado cargado
- **THEN** el código de OC y el botón de consultar aparecen alineados en una única fila horizontal ubicada por sobre la ficha de resultado
- **AND** ningún control de búsqueda se ubica en una columna o barra lateral

### Requirement: Ficha de la Orden de Compra en secciones separadas
El sistema SHALL renderizar el resultado de una OC (local o desde la API) usando un componente de ficha genérico y reutilizable (no acoplado a los campos específicos de OC), compuesto por secciones separadas visualmente unas de otras, en este orden: (1) encabezado (código, tipo/estado, organismo comprador), (2) cronograma de estados de la OC en Mercado Público (si el payload lo incluye, como línea de tiempo puramente informativa), (3) datos del organismo comprador, (4) condiciones (moneda, forma de pago, plazo de entrega), (5) datos de adjudicación/proveedor, (6) tabla de ítems.

#### Scenario: Secciones separadas visualmente y en orden fijo
- **WHEN** la página muestra la ficha de una OC (local, verificada o en vista previa)
- **THEN** cada grupo de datos se muestra en su propia tarjeta o bloque diferenciado del resto
- **AND** el cronograma de estados aparece como la segunda sección, inmediatamente después del encabezado y antes de los datos del organismo comprador
- **AND** ningún grupo de datos se mezcla dentro de otro

#### Scenario: Cronograma de estados es solo informativo
- **WHEN** el payload de la OC incluye un historial de estados de Mercado Público
- **THEN** la ficha lo muestra como línea de tiempo de solo lectura
- **AND** ninguna interacción sobre ese cronograma modifica el workflow interno de un `proceso_adquisicion`

#### Scenario: Sección sin datos disponibles
- **WHEN** alguna sección (p. ej. cronograma o adjudicación) no viene informada en el payload de la OC
- **THEN** la ficha muestra esa sección con un estado vacío explícito en lugar de omitirla u ocultarla sin indicación

### Requirement: Página de comparación de diferencias entre la OC local y la API
El sistema SHALL renderizar la comparación devuelta por el backend entre el dato local y el dato de la API, permitiendo al usuario elegir explícitamente aplicar la actualización o mantener el dato local.

#### Scenario: Mostrar diferencias
- **WHEN** el backend devuelve diferencias entre la OC local y la API
- **THEN** la página muestra cada campo distinto con su valor local y su valor de la API
- **AND** presenta las acciones "Actualizar" y "Mantener" sin preseleccionar ninguna

#### Scenario: Confirmar actualización
- **WHEN** el usuario selecciona "Actualizar" tras ver las diferencias
- **THEN** la página envía la confirmación al backend y refleja el registro local ya actualizado

#### Scenario: Mantener el dato local
- **WHEN** el usuario selecciona "Mantener" tras ver las diferencias
- **THEN** la página cierra la comparación sin enviar ninguna actualización al backend

### Requirement: Página de vista previa de una OC nueva antes de guardar
El sistema SHALL renderizar una vista previa de la OC y sus ítems obtenidos de la API, mostrando si el proveedor emisor ya existe o requiere creación/actualización, antes de permitir confirmar el guardado.

#### Scenario: Proveedor existente
- **WHEN** la vista previa de una OC nueva indica que el proveedor emisor ya existe
- **THEN** la página muestra el proveedor vinculado y habilita directamente la confirmación de guardado

#### Scenario: Proveedor inexistente
- **WHEN** la vista previa de una OC nueva indica que el proveedor emisor no existe
- **THEN** la página ofrece crear/actualizar el proveedor (reutilizando el formulario existente de alta de proveedores) antes de habilitar la confirmación de guardado

#### Scenario: Confirmar guardado de una OC nueva
- **WHEN** el usuario confirma guardar la vista previa de una OC nueva
- **THEN** la página envía la confirmación al backend y navega al detalle de la OC recién guardada

### Requirement: OC no encontrada en Mercado Público
El sistema SHALL informar de forma explícita cuando la API de Mercado Público no encuentra una OC, sin ofrecer ninguna acción de guardado.

#### Scenario: Código no encontrado en ningún lado
- **WHEN** un código de OC no existe localmente ni la API de Mercado Público lo encuentra
- **THEN** la página muestra un mensaje de "OC no encontrada" sin ofrecer acciones de guardado

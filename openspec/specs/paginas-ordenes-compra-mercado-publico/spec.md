## Purpose

Esta capacidad cubre la capa HTTP/Inertia que expone al usuario autenticado el flujo de la capability `ordenes-compra-mercado-publico`: búsqueda por código, ficha de resultado en secciones separadas, comparación de diferencias, vista previa de OC nueva, aviso de no encontrada, y vínculo/desvínculo con un proceso de adquisición. No implementa lógica de dominio propia — delega toda decisión (búsqueda local/remota, comparación, verificación de proveedor, guardado) en el servicio de la capability `ordenes-compra-mercado-publico`.

## Requirements

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
El sistema SHALL renderizar el resultado de una OC (local o desde la API) usando un componente de ficha genérico y reutilizable (no acoplado a los campos específicos de OC), compuesto por secciones separadas visualmente unas de otras, en este orden: (1) encabezado (código, tipo/estado, organismo comprador, monto total destacado y acciones), (2) cronograma de estados de la OC en Mercado Público (si el payload lo incluye, como línea de tiempo iconográfica puramente informativa), (3) desglose financiero (monto neto, impuesto, monto total), (4) datos del organismo comprador, (5) condiciones (moneda, forma de pago, plazo de entrega), (6) datos de adjudicación/proveedor, (7) tabla de ítems.

#### Scenario: Secciones separadas visualmente y en orden fijo
- **WHEN** la página muestra la ficha de una OC (local, verificada o en vista previa)
- **THEN** cada grupo de datos se muestra en su propia tarjeta o bloque diferenciado del resto
- **AND** el cronograma de estados aparece como la segunda sección, inmediatamente después del encabezado y antes del desglose financiero y de los datos del organismo comprador
- **AND** ningún grupo de datos se mezcla dentro de otro

#### Scenario: Cronograma de estados es solo informativo, con iconos de estado y hora real
- **WHEN** el payload de la OC incluye un historial de estados de Mercado Público
- **THEN** la ficha lo muestra como línea de tiempo de solo lectura, con un ícono circular por etapa (relleno con un check cuando la etapa está completada, vacío cuando no) conectados por una línea
- **AND** cada etapa muestra su nombre, su fecha y hora reales tal como las entrega Mercado Público, y la palabra "Completado" cuando corresponde
- **AND** ninguna interacción sobre ese cronograma modifica el workflow interno de un `proceso_adquisicion`

#### Scenario: Sección sin datos disponibles
- **WHEN** alguna sección (p. ej. cronograma o adjudicación) no viene informada en el payload de la OC
- **THEN** la ficha muestra esa sección con un estado vacío explícito en lugar de omitirla u ocultarla sin indicación

#### Scenario: Desglose financiero calculado a partir de los montos disponibles
- **WHEN** la ficha muestra una OC con monto neto y monto total informados
- **THEN** la sección de desglose financiero muestra el monto neto, el impuesto (monto total menos monto neto) y el monto total
- **AND** si el monto neto o el monto total no están informados, la sección muestra "—" en el campo faltante en vez de calcular un impuesto incorrecto

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
El sistema SHALL renderizar una vista previa de la OC y sus ítems obtenidos de la API, mostrando si el proveedor emisor ya existe o será creado/completado automáticamente, con la acción de guardado siempre habilitada.

#### Scenario: Proveedor existente
- **WHEN** la vista previa de una OC nueva indica que el proveedor emisor ya existe
- **THEN** la página muestra el proveedor vinculado y la acción de guardado está habilitada

#### Scenario: Proveedor inexistente
- **WHEN** la vista previa de una OC nueva indica que el proveedor emisor no existe en el catálogo
- **THEN** la página indica que el proveedor se creará automáticamente al confirmar el guardado
- **AND** la acción de guardado está habilitada, sin ofrecer ni exigir un enlace a un formulario de alta manual de proveedor

#### Scenario: Confirmar guardado de una OC nueva
- **WHEN** el usuario confirma guardar la vista previa de una OC nueva
- **THEN** la página envía la confirmación al backend, muestra el resultado de la operación sobre el proveedor (creado, actualizado, o sin cambios), y navega al listado de Órdenes de Compra

### Requirement: OC no encontrada en Mercado Público
El sistema SHALL informar de forma explícita cuando la API de Mercado Público no encuentra una OC, sin ofrecer ninguna acción de guardado.

#### Scenario: Código no encontrado en ningún lado
- **WHEN** un código de OC no existe localmente ni la API de Mercado Público lo encuentra
- **THEN** la página muestra un mensaje de "OC no encontrada" sin ofrecer acciones de guardado

### Requirement: Página de listado de Órdenes de Compra guardadas localmente
El sistema SHALL renderizar, en `GET /adquisiciones/ordenes-compra-mercado-publico` sin un código de búsqueda, un listado paginado de las `orden_compra_mercado_publico` guardadas localmente para un usuario con el permiso `adquisiciones.consultar_orden_compra_mp`, siguiendo el patrón de listado tabular denso ya definido en la capability `tema-visual-layout`. El listado SHALL ofrecer un acceso explícito hacia la página de búsqueda por código para consultar una OC que todavía no esté guardada localmente.

#### Scenario: Listado de OC guardadas
- **WHEN** un usuario con el permiso requerido visita `/adquisiciones/ordenes-compra-mercado-publico` sin indicar un código
- **THEN** la página muestra un listado paginado de las Órdenes de Compra ya guardadas localmente, con su proveedor y proceso de adquisición vinculado (o "—" si no tiene)

#### Scenario: Filtrar el listado por código
- **WHEN** un usuario escribe un texto en el buscador del listado
- **THEN** el listado se filtra por coincidencia de código de OC tras un debounce, sin navegar a la página de búsqueda por código

#### Scenario: Acceso al flujo de búsqueda por código desde el listado
- **WHEN** un usuario visualiza el listado de OC guardadas
- **THEN** la página ofrece una acción explícita para ir a la búsqueda por código (para traer una OC que no está en el listado), sin fusionar ambas páginas

#### Scenario: Listado vacío
- **WHEN** no existe ninguna `orden_compra_mercado_publico` guardada localmente
- **THEN** el listado muestra un estado vacío en vez de una tabla sin filas ni indicación

#### Scenario: Fila navega al detalle
- **WHEN** un usuario hace clic en una fila del listado
- **THEN** el sistema navega al detalle (`show`) de esa Orden de Compra

### Requirement: Acciones de encabezado para ver el JSON, el PDF y el enlace a Mercado Público
El sistema SHALL ofrecer, junto al encabezado de la ficha de una OC, una acción "Ver JSON" que muestra el payload crudo del snapshot vinculado a esa OC, una acción "Mercado Público" que abre en una pestaña nueva el detalle oficial de esa OC en `mercadopublico.cl`, y una acción "Ver PDF" que descarga directamente el PDF de esa OC resolviendo el enlace real a través de un endpoint propio del backend.

#### Scenario: Ver JSON con snapshot disponible
- **WHEN** un usuario hace clic en "Ver JSON" sobre una OC que tiene un snapshot de Mercado Público vinculado
- **THEN** el sistema muestra el payload crudo de ese snapshot

#### Scenario: Ver JSON sin snapshot disponible
- **WHEN** una OC no tiene ningún snapshot de Mercado Público vinculado
- **THEN** la acción "Ver JSON" queda deshabilitada

#### Scenario: Mercado Público abre el detalle oficial de la OC
- **WHEN** un usuario hace clic en "Mercado Público" sobre cualquier OC (guardada, local o en vista previa)
- **THEN** el sistema abre en una pestaña nueva el detalle oficial de esa OC en `mercadopublico.cl`, identificado por su código

#### Scenario: Ver PDF descarga el archivo directamente
- **WHEN** un usuario hace clic en "Ver PDF" sobre cualquier OC (guardada, local o en vista previa) y el backend logra resolver el enlace de descarga desde la página pública de Mercado Público
- **THEN** el sistema redirige al navegador directamente al PDF de esa OC, descargándolo sin pasos intermedios

#### Scenario: Ver PDF cuando Mercado Público no expone el botón de descarga
- **WHEN** un usuario hace clic en "Ver PDF" y el backend no logra resolver el enlace de descarga (la OC ya no existe en Mercado Público o esa página no incluye el botón de PDF)
- **THEN** el sistema informa un error explícito en vez de intentar una descarga rota

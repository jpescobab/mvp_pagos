## Purpose

Esta capacidad cubre la capa HTTP/Inertia que expone al usuario autenticado el flujo de la capability `licitaciones-mercado-publico`: búsqueda por código, ficha de resultado en secciones separadas, comparación de diferencias, vista previa de Licitación nueva, aviso de no encontrada, y vínculo/desvínculo con un proceso de adquisición. No implementa lógica de dominio propia — delega toda decisión (búsqueda local/remota, comparación, guardado) en el servicio de la capability `licitaciones-mercado-publico`.

## Requirements

### Requirement: Página de búsqueda de Licitación por código
El sistema SHALL renderizar una página donde un usuario autenticado con el permiso `adquisiciones.consultar_licitacion_mp` ingresa un código de licitación y recibe el resultado de la búsqueda local, delegando toda la lógica en la capability `licitaciones-mercado-publico`. Los controles de búsqueda (código de licitación y el botón de consultar) SHALL ubicarse en una única fila horizontal por sobre la ficha de resultado, nunca en una barra lateral.

#### Scenario: Buscar un código existente localmente
- **WHEN** un usuario ingresa un código de licitación que existe localmente
- **THEN** la página muestra los datos de la licitación local, sus ítems y su proceso de adquisición vinculado
- **AND** ofrece la acción de verificar contra Mercado Público

#### Scenario: Buscar un código inexistente localmente
- **WHEN** un usuario ingresa un código de licitación que no existe localmente
- **THEN** la página consulta la API y muestra el resultado (vista previa si se encontró, o el aviso de no encontrada)

#### Scenario: Los filtros de búsqueda quedan en una sola fila sobre la ficha
- **WHEN** se renderiza la página de búsqueda de Licitación, con o sin resultado cargado
- **THEN** el código de licitación y el botón de consultar aparecen alineados en una única fila horizontal ubicada por sobre la ficha de resultado
- **AND** ningún control de búsqueda se ubica en una columna o barra lateral

### Requirement: Ficha de la Licitación en secciones separadas
El sistema SHALL renderizar el resultado de una Licitación (local o desde la API) reutilizando el mismo componente genérico `FichaConsultaMercadoPublico` ya usado por Órdenes de Compra, compuesto por secciones separadas visualmente unas de otras, en este orden: (1) encabezado (código, nombre, estado, organismo comprador y acciones), (2) cronograma de la licitación en Mercado Público (línea de tiempo iconográfica puramente informativa), (3) datos del organismo comprador, (4) condiciones (moneda, monto estimado), (5) adjudicación a nivel de licitación (tipo, fecha, número de acta, número de oferentes), (6) tabla de ítems con su adjudicación por ítem cuando exista.

#### Scenario: Secciones separadas visualmente y en orden fijo
- **WHEN** la página muestra la ficha de una Licitación (local, verificada o en vista previa)
- **THEN** cada grupo de datos se muestra en su propia tarjeta o bloque diferenciado del resto
- **AND** el cronograma aparece como la segunda sección, inmediatamente después del encabezado
- **AND** ningún grupo de datos se mezcla dentro de otro

#### Scenario: Cronograma de la licitación es solo informativo, con iconos de estado y hora real
- **WHEN** el payload de la Licitación incluye hitos de cronograma
- **THEN** la ficha los muestra como línea de tiempo de solo lectura, con un ícono circular por etapa (relleno con un check cuando la etapa está completada, vacío cuando no) conectados por una línea, igual que en la ficha de una OC
- **AND** cada etapa muestra su nombre, su fecha y hora reales tal como las entrega Mercado Público, y la palabra "Completado" cuando corresponde
- **AND** ninguna interacción sobre ese cronograma modifica el workflow interno de un `proceso_adquisicion`

#### Scenario: Sección sin datos disponibles
- **WHEN** alguna sección (p. ej. adjudicación) no viene informada en el payload de la Licitación
- **THEN** la ficha muestra esa sección con un estado vacío explícito en lugar de omitirla u ocultarla sin indicación

#### Scenario: Ítem con adjudicación propia
- **WHEN** la ficha muestra la tabla de ítems y alguno de ellos tiene adjudicación informada
- **THEN** la fila de ese ítem muestra el proveedor adjudicado (RUT y nombre) y el monto unitario adjudicado
- **AND** un ítem sin adjudicación muestra "—" en esas columnas, sin error

### Requirement: Página de comparación de diferencias entre la Licitación local y la API
El sistema SHALL renderizar la comparación devuelta por el backend entre el dato local y el dato de la API, permitiendo al usuario elegir explícitamente aplicar la actualización o mantener el dato local.

#### Scenario: Mostrar diferencias
- **WHEN** el backend devuelve diferencias entre la Licitación local y la API
- **THEN** la página muestra cada campo distinto con su valor local y su valor de la API
- **AND** presenta las acciones "Actualizar" y "Mantener" sin preseleccionar ninguna

#### Scenario: Confirmar actualización
- **WHEN** el usuario selecciona "Actualizar" tras ver las diferencias
- **THEN** la página envía la confirmación al backend y refleja el registro local ya actualizado

#### Scenario: Mantener el dato local
- **WHEN** el usuario selecciona "Mantener" tras ver las diferencias
- **THEN** la página cierra la comparación sin enviar ninguna actualización al backend

### Requirement: Página de vista previa de una Licitación nueva antes de guardar
El sistema SHALL renderizar una vista previa de la Licitación y sus ítems obtenidos de la API, con la acción de guardado siempre habilitada, sin ofrecer ningún flujo de verificación o alta de proveedor (una Licitación no tiene un único proveedor emisor).

#### Scenario: Confirmar guardado de una Licitación nueva
- **WHEN** el usuario confirma guardar la vista previa de una Licitación nueva
- **THEN** la página envía la confirmación al backend y navega al listado de Licitaciones

### Requirement: Licitación no encontrada en Mercado Público
El sistema SHALL informar de forma explícita cuando la API de Mercado Público no encuentra una Licitación, sin ofrecer ninguna acción de guardado.

#### Scenario: Código no encontrado en ningún lado
- **WHEN** un código de licitación no existe localmente ni la API de Mercado Público lo encuentra
- **THEN** la página muestra un mensaje de "Licitación no encontrada" sin ofrecer acciones de guardado

### Requirement: Página de listado de Licitaciones guardadas localmente
El sistema SHALL renderizar, en `GET /adquisiciones/licitaciones-mercado-publico` sin un código de búsqueda, un listado paginado de las `licitacion_mercado_publico` guardadas localmente para un usuario con el permiso `adquisiciones.consultar_licitacion_mp`, siguiendo el patrón de listado tabular denso ya definido en la capability `tema-visual-layout`. El listado SHALL ofrecer un acceso explícito hacia la página de búsqueda por código para consultar una Licitación que todavía no esté guardada localmente.

#### Scenario: Listado de Licitaciones guardadas
- **WHEN** un usuario con el permiso requerido visita `/adquisiciones/licitaciones-mercado-publico` sin indicar un código
- **THEN** la página muestra un listado paginado de las Licitaciones ya guardadas localmente, con su proceso de adquisición vinculado (o "—" si no tiene)

#### Scenario: Filtrar el listado por código
- **WHEN** un usuario escribe un texto en el buscador del listado
- **THEN** el listado se filtra por coincidencia de código de licitación tras un debounce, sin navegar a la página de búsqueda por código

#### Scenario: Acceso al flujo de búsqueda por código desde el listado
- **WHEN** un usuario visualiza el listado de Licitaciones guardadas
- **THEN** la página ofrece una acción explícita para ir a la búsqueda por código (para traer una Licitación que no está en el listado), sin fusionar ambas páginas

#### Scenario: Listado vacío
- **WHEN** no existe ninguna `licitacion_mercado_publico` guardada localmente
- **THEN** el listado muestra un estado vacío en vez de una tabla sin filas ni indicación

#### Scenario: Fila navega al detalle
- **WHEN** un usuario hace clic en una fila del listado
- **THEN** el sistema navega al detalle (`show`) de esa Licitación

### Requirement: Acciones de encabezado para ver el JSON y el enlace a Mercado Público
El sistema SHALL ofrecer, junto al encabezado de la ficha de una Licitación, una acción "Ver JSON" que muestra el payload crudo del snapshot vinculado a esa Licitación, y una acción "Mercado Público" que abre en una pestaña nueva el detalle oficial de esa Licitación en `mercadopublico.cl`. La acción "Ver PDF" SHALL mostrarse deshabilitada ("Disponible próximamente"), porque no existe todavía un patrón de extracción verificado del PDF de una Licitación.

#### Scenario: Ver JSON con snapshot disponible
- **WHEN** un usuario hace clic en "Ver JSON" sobre una Licitación que tiene un snapshot de Mercado Público vinculado
- **THEN** el sistema muestra el payload crudo de ese snapshot

#### Scenario: Ver JSON sin snapshot disponible
- **WHEN** una Licitación no tiene ningún snapshot de Mercado Público vinculado
- **THEN** la acción "Ver JSON" queda deshabilitada

#### Scenario: Mercado Público abre el detalle oficial de la Licitación
- **WHEN** un usuario hace clic en "Mercado Público" sobre cualquier Licitación (guardada, local o en vista previa)
- **THEN** el sistema abre en una pestaña nueva el detalle oficial de esa Licitación en `mercadopublico.cl`, identificado por su código

#### Scenario: Ver PDF no implementado
- **WHEN** un usuario visualiza el encabezado de la ficha de una Licitación
- **THEN** la acción "Ver PDF" aparece deshabilitada con la indicación "Disponible próximamente", sin intentar ninguna descarga

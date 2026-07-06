## MODIFIED Requirements

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

## ADDED Requirements

### Requirement: Acciones de encabezado para ver el JSON, el PDF y el enlace a Mercado Público
El sistema SHALL ofrecer, junto al encabezado de la ficha de una OC, una acción "Ver JSON" que muestra el payload crudo del snapshot vinculado a esa OC, y dos acciones adicionales ("Ver PDF" y "Ver en Mercado Público") visibles pero deshabilitadas mientras no exista una fuente confiable de PDF ni un enlace externo verificado hacia el detalle público de esa OC.

#### Scenario: Ver JSON con snapshot disponible
- **WHEN** un usuario hace clic en "Ver JSON" sobre una OC que tiene un snapshot de Mercado Público vinculado
- **THEN** el sistema muestra el payload crudo de ese snapshot

#### Scenario: Ver JSON sin snapshot disponible
- **WHEN** una OC no tiene ningún snapshot de Mercado Público vinculado
- **THEN** la acción "Ver JSON" queda deshabilitada

#### Scenario: Ver PDF y Ver en Mercado Público quedan deshabilitadas
- **WHEN** se muestra el encabezado de la ficha de cualquier OC
- **THEN** las acciones "Ver PDF" y "Ver en Mercado Público" aparecen visibles pero deshabilitadas, indicando "Disponible próximamente"

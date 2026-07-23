## MODIFIED Requirements

### Requirement: Página de detalle de egreso CGU
El sistema SHALL renderizar una página de detalle de un egreso CGU que identifique de un vistazo tanto el egreso como cada caso que cubre. En la cabecera, la página SHALL mostrar el número de egreso, la fecha, el monto total, la glosa (`observaciones`), el periodo, el centro financiero, la cantidad de casos cubiertos, el usuario que lo registró y si fue generado automáticamente; los campos opcionales que estén en `null` SHALL mostrarse con un fallback explícito en vez de una celda vacía. Por cada `egresos_cgu_item`, la página SHALL mostrar el caso cubierto identificado por el nombre y RUT del proveedor, su `sgf_id`, su número de factura (`numero`/DTE), su fecha SII, un badge del estado actual del `Proceso` del caso, y el monto de la línea; los campos opcionales nulos SHALL usar el mismo fallback explícito. La lista de casos cubiertos SHALL seguir el patrón de listado tabular denso del proyecto (avatar con iniciales del proveedor, badge de estado con tokens semánticos, columnas secundarias truncadas con tooltip y ocultas progresivamente en pantallas angostas) y cada fila SHALL enlazar al detalle de ese caso. La página SHALL únicamente **leer** el estado del workflow de cada caso; SHALL NOT ejecutar ni implicar ninguna transición del `Proceso`.

#### Scenario: Ver el detalle de un egreso CGU
- **WHEN** un usuario autenticado visita la página de detalle de un egreso CGU
- **THEN** la cabecera muestra el número de egreso, la fecha, el monto total, la glosa, el periodo, el centro financiero, la cantidad de casos cubiertos y el usuario que lo registró
- **AND** la lista de casos cubiertos muestra una fila por cada caso, con el nombre y RUT del proveedor, el `sgf_id`, el número de factura, la fecha SII, un badge del estado actual del `Proceso` y el monto de la línea

#### Scenario: Campos opcionales del caso o del egreso ausentes
- **WHEN** un egreso o un caso cubierto tiene en `null` alguno de sus campos opcionales (glosa, periodo, centro financiero, número de factura, fecha SII o RUT del proveedor)
- **THEN** la página muestra un fallback explícito ("—") en ese lugar en vez de una celda vacía

#### Scenario: Navegar al detalle de un caso desde el egreso
- **WHEN** un usuario hace clic en una fila de la lista de casos cubiertos
- **THEN** la aplicación navega a la página de detalle de ese caso de pago de proveedor

#### Scenario: Egreso sin casos cubiertos
- **WHEN** un egreso CGU no tiene ningún `egresos_cgu_item`
- **THEN** la página muestra un estado vacío en la sección de casos cubiertos en vez de una tabla vacía

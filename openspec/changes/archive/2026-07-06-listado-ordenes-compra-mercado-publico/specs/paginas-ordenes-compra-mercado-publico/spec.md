## ADDED Requirements

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

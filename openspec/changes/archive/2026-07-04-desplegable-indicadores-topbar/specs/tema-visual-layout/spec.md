## ADDED Requirements

### Requirement: Desplegable de indicadores económicos en el topbar
El sistema SHALL presentar en el topbar de las páginas autenticadas, junto al control de tema, un botón con ícono de indicadores económicos que al pulsarse despliega el último valor registrado de UF, UTM, dólar e IPC, con el mismo formato (decimales, símbolo, etiqueta) que usan las tarjetas de indicadores del panel general. El sistema SHALL NOT exigir un permiso adicional para ver este desplegable, dado que los indicadores económicos ya son consultables por cualquier usuario autenticado.

#### Scenario: Abrir el desplegable de indicadores
- **WHEN** un usuario autenticado pulsa el botón de indicadores económicos del topbar
- **THEN** se despliega una lista con el último valor registrado de UF, UTM, dólar e IPC

#### Scenario: Disponible en cualquier página autenticada
- **WHEN** un usuario autenticado visualiza cualquier página con el layout de sidebar, no solo el panel general
- **THEN** el botón de indicadores económicos del topbar está presente y funcional

#### Scenario: Sin datos para un indicador
- **WHEN** no existe ningún valor importado todavía para alguno de los cuatro indicadores
- **THEN** el desplegable omite esa fila en vez de mostrar un valor inventado o un error

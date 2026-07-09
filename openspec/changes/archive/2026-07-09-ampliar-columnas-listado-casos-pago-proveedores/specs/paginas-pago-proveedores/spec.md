## MODIFIED Requirements

### Requirement: Página de listado de casos de pago de proveedores
El sistema SHALL renderizar una página que muestre los casos de pago de proveedores paginados, con id (`sgf_id`), periodo, observación, folio de egreso, RUT y nombre del proveedor, número, fecha SII, monto, estado SGF y estado actual del workflow, sin filtros ni búsqueda no soportados por el backend. Los campos de referencia SGF que no estén disponibles para un caso SHALL mostrarse con un fallback explícito en vez de una celda vacía.

#### Scenario: Listado con casos
- **WHEN** un usuario autenticado visita la página de casos de pago de proveedores
- **THEN** la página muestra una fila por cada caso recibido, con su `sgf_id`, periodo, observación, folio de egreso, RUT y nombre del proveedor, número, fecha SII, monto y un badge del estado actual del `Proceso`

#### Scenario: Campo de referencia SGF no disponible
- **WHEN** un caso listado tiene `periodo`, `observacion`, `folio_egreso`, `numero` o `fecha_sii` en `null`
- **THEN** la columna correspondiente muestra `"—"` en vez de una celda vacía

#### Scenario: Navegar al detalle desde el listado
- **WHEN** un usuario hace clic en un caso del listado
- **THEN** la aplicación navega a la página de detalle de ese caso

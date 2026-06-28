## MODIFIED Requirements

### Requirement: Página de listado de egresos CGU
El sistema SHALL renderizar una página que muestre los egresos CGU paginados junto con los casos de pago de proveedores que cada uno cubre, y cada fila SHALL navegar al detalle de ese egreso.

#### Scenario: Listado con egresos
- **WHEN** un usuario autenticado visita la página de egresos CGU
- **THEN** la página muestra una fila por cada egreso, con su número, fecha, monto total y los `sgf_id` de los casos que cubre

#### Scenario: Navegar al detalle desde el listado
- **WHEN** un usuario hace clic en un egreso del listado
- **THEN** la aplicación navega a la página de detalle de ese egreso CGU

## ADDED Requirements

### Requirement: Página de detalle de egreso CGU con documentos vinculados
El sistema SHALL renderizar una página de detalle de un egreso CGU que muestre sus `egresos_cgu_items` (caso cubierto y monto) y sus documentos vinculados, permitiendo subir, descargar y desvincular documentos para ese egreso.

#### Scenario: Ver el detalle de un egreso CGU
- **WHEN** un usuario autenticado visita la página de detalle de un egreso CGU
- **THEN** la página muestra el número de egreso, fecha, monto total, observaciones y la lista de casos cubiertos con su monto

#### Scenario: Subir un documento al egreso
- **WHEN** un usuario con el permiso `documentos.gestionar` sube un archivo válido junto con un tipo de documento desde la página de detalle de un egreso CGU
- **THEN** el documento queda vinculado al egreso y aparece en la lista sin recargar la página completa

#### Scenario: Sin documentos vinculados
- **WHEN** un egreso CGU no tiene ningún documento vinculado todavía
- **THEN** la página muestra un estado vacío explícito en lugar de una lista vacía sin contexto

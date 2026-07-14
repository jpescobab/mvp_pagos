## MODIFIED Requirements

### Requirement: Asignar obligatoriedad documental por tipo de proceso de pago mediante una matriz
El sistema SHALL exponer, a un usuario con el permiso `pago_proveedores.administrar_requisitos_documentales`, una vista de matriz con los `TipoDocumento` activos como filas y los `TipoProcesoPago` activos como columnas (más una columna "Todos los tipos" que representa `tipo_proceso_pago_id = null`), donde cada celda permite fijar el estado obligatorio, opcional, o no aplica para esa combinación, dentro del conjunto de requisitos `pago_proveedores` exclusivamente. Un cambio en una celda SHALL crear, actualizar, o eliminar el `RequisitoDocumental` correspondiente de inmediato, sin afectar filas de otros conjuntos de requisitos documentales (ej. `adquisiciones`) ni las dimensiones `modalidad_id`, `estado_workflow_id`, `monto_desde`/`monto_hasta` (que la matriz siempre deja en `null`). Eliminar un `RequisitoDocumental` (celda marcada "no aplica") SHALL tener éxito incluso si existen `checklist_documental_proceso_items` cacheados que lo referencian, dado que esos items son un caché regenerable de la resolución del checklist, no evidencia.

#### Scenario: Marcar un documento como obligatorio para un tipo de proceso
- **WHEN** un usuario con el permiso requerido fija la celda de un `TipoDocumento` y un `TipoProcesoPago` como "obligatorio"
- **THEN** el sistema crea o actualiza un `RequisitoDocumental` con `tipo_requisito = 'obligatorio'` para esa combinación dentro del conjunto `pago_proveedores`
- **AND** el checklist documental de cualquier caso con ese tipo de proceso, al recargarse, refleja el nuevo requisito sin necesidad de un seeder ni un deploy

#### Scenario: Quitar un requisito marcando "no aplica"
- **WHEN** un usuario fija una celda como "no aplica" sobre una combinación que ya tenía un `RequisitoDocumental`
- **THEN** el sistema elimina esa fila de `requisitos_documentales`
- **AND** la eliminación tiene éxito aunque existan `checklist_documental_proceso_items` de casos ya resueltos que referencien ese `RequisitoDocumental` (se eliminan en cascada junto con la fila)

#### Scenario: Asignar un requisito universal vía la columna "Todos los tipos"
- **WHEN** un usuario fija la celda de un `TipoDocumento` en la columna "Todos los tipos" como "obligatorio"
- **THEN** el sistema crea un `RequisitoDocumental` con `tipo_proceso_pago_id = null`, que aplica a casos de cualquier tipo de proceso clasificado o sin clasificar

#### Scenario: La matriz no expone ni modifica requisitos de Adquisiciones
- **WHEN** un usuario visualiza o edita la matriz de requisitos documentales de Pago de Proveedores
- **THEN** el sistema filtra explícitamente por el conjunto de requisitos `pago_proveedores` y su definición de workflow, sin mostrar ni permitir modificar los `RequisitoDocumental` del conjunto `adquisiciones`

#### Scenario: Usuario sin permiso no puede editar la matriz
- **WHEN** un usuario sin el permiso `pago_proveedores.administrar_requisitos_documentales` intenta modificar una celda de la matriz
- **THEN** el sistema bloquea la operación

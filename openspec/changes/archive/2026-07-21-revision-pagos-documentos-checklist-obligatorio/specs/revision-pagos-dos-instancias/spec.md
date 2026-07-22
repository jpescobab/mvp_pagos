## MODIFIED Requirements

### Requirement: Revisión documental por instancia
Cada instancia de revisión SHALL revisar los documentos del pago de forma independiente, aprobando o rechazando cada documento con motivo. La validación de un documento se registra asociada a la instancia que la emitió (`finanzas` o `zonal`), de modo que un documento aprobado por Finanzas vuelve a estar pendiente para el Administrador Zonal sin perder el rastro de la validación de Finanzas. Un pago solo SHALL poder aprobarse cuando todos sus documentos **obligatorios según el checklist documental del proceso** estén aprobados en la instancia activa **y** no exista ningún ítem obligatorio del checklist sin documento vinculado. Los documentos **opcionales** (todo documento vinculado cuyo `tipo_documento` no corresponde a un ítem obligatorio del checklist) SHALL poder aprobarse o rechazarse por instancia, pero su estado NO SHALL bloquear la aprobación del pago ni el avance del Egreso.

#### Scenario: El Zonal ve como pendientes los documentos que Finanzas ya aprobó
- **WHEN** un pago con documentos aprobados por Finanzas llega a `en_revision_zonal`
- **THEN** en la vista del Administrador Zonal esos documentos figuran como pendientes para su instancia
- **AND** la validación previa de Finanzas sigue registrada y consultable

#### Scenario: No se puede aprobar un pago con un documento obligatorio pendiente en la instancia activa
- **WHEN** se intenta aprobar un pago que tiene al menos un documento obligatorio del checklist sin aprobar en la instancia activa
- **THEN** la aprobación del pago es rechazada

#### Scenario: No se puede aprobar un pago con un obligatorio del checklist sin documento vinculado
- **WHEN** el checklist del proceso tiene un ítem obligatorio para el que no existe documento vinculado al proceso
- **THEN** la aprobación del pago es rechazada mientras ese obligatorio siga faltante

#### Scenario: Un documento opcional pendiente o rechazado no bloquea la aprobación
- **WHEN** todos los documentos obligatorios del checklist están aprobados en la instancia activa y no hay obligatorios faltantes
- **AND** existen documentos opcionales pendientes o rechazados en esa instancia
- **THEN** el pago SHALL poder aprobarse (siempre que también se hayan verificado los totales)

#### Scenario: Rechazar un documento exige un motivo
- **WHEN** un revisor rechaza un documento sin motivo
- **THEN** la operación es rechazada y no se registra el evento

### Requirement: Pantalla de revisión de pagos condicionada por permiso e instancia
El sistema SHALL exponer una pantalla de Revisión de Pagos que liste los Egresos pendientes de revisión, permita seleccionar un pago del Egreso, ver sus documentos en un visor y operar el panel de revisión. Las acciones disponibles (aprobar/rechazar documento, verificar totales, aprobar/rechazar/devolver pago, avanzar/devolver Egreso) SHALL condicionarse por los permisos del usuario (`pago_proveedores.revisar_finanzas`, `pago_proveedores.revisar_zonal`) y por la instancia activa del Egreso. La pantalla no SHALL hardcodear los requisitos documentales ni la clasificación obligatorio/opcional/faltante; los recibe del backend y solo los renderiza.

#### Scenario: Un usuario sin permiso de revisión no ve acciones de revisión
- **WHEN** un usuario sin `pago_proveedores.revisar_finanzas` ni `pago_proveedores.revisar_zonal` abre la pantalla
- **THEN** no se le ofrecen acciones de aprobación/rechazo/devolución

#### Scenario: Solo la instancia activa puede operar el Egreso
- **WHEN** un Egreso está en la instancia Zonal
- **THEN** el Jefe de Finanzas ve el detalle en modo lectura y no puede aprobar/rechazar sus pagos

#### Scenario: La pantalla no decide la clasificación documental
- **WHEN** la pantalla renderiza la lista de documentos de un pago
- **THEN** presenta cada documento con la clasificación (obligatorio, opcional o faltante) que entregó el backend, sin recalcularla en el cliente

## ADDED Requirements

### Requirement: Documentos de la revisión derivados del checklist documental
La lista de documentos que la Revisión de Pagos presenta para un pago SHALL derivarse del checklist documental del proceso asociado. El backend SHALL clasificar cada elemento en una de tres categorías y entregarlas al frontend:

- **Obligatorio**: documento vinculado al proceso cuyo `tipo_documento` corresponde a un ítem del checklist con `tipo_requisito = obligatorio`.
- **Opcional**: cualquier otro documento vinculado al proceso (incluidos los documentos extra importados desde SGF que no corresponden a un ítem obligatorio del checklist).
- **Faltante**: ítem obligatorio del checklist para el que no existe documento vinculado al proceso; se representa como una fila placeholder sin documento asociado.

Los obligatorios (presentes y faltantes) SHALL presentarse antes que los opcionales. La clasificación SHALL calcularse en el backend (Service), nunca en el controlador ni en el cliente.

#### Scenario: Un documento importado que no está en el checklist se presenta como opcional
- **WHEN** el proceso tiene documentos vinculados cuyo `tipo_documento` no corresponde a ningún ítem obligatorio del checklist
- **THEN** esos documentos se presentan en la sección de opcionales, después de los obligatorios
- **AND** siguen siendo consultables en el visor y aprobables/rechazables por instancia

#### Scenario: Un obligatorio del checklist sin documento aparece como fila faltante
- **WHEN** el checklist tiene un ítem obligatorio sin documento vinculado
- **THEN** la revisión presenta una fila faltante para ese ítem obligatorio, identificando el tipo de documento esperado
- **AND** esa fila no ofrece acción de aprobar/rechazar documento porque no hay documento aún

#### Scenario: El indicador de avance documental cuenta solo obligatorios
- **WHEN** un pago tiene documentos obligatorios y opcionales
- **THEN** el indicador de avance ("docs OK") y la condición de "listo para aprobar" se calculan solo sobre los documentos obligatorios y los obligatorios faltantes
- **AND** los documentos opcionales no alteran ese indicador ni esa condición

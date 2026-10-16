# revision-pagos-dos-instancias Specification

## Purpose
Revisión de pagos en dos instancias secuenciales con devolución a la instancia anterior, revisión documental por instancia y verificación de totales.
## Requirements
### Requirement: Revisión de pagos en dos instancias secuenciales
Los pagos (`casos_pago_proveedor`) agrupados en un Egreso SHALL revisarse en dos instancias secuenciales antes de avanzar al registro CGU: primero la instancia de Finanzas (Jefe de Finanzas) y luego la instancia Zonal (Administrador Zonal). El caso SHALL pasar de la instancia de Finanzas a la instancia Zonal únicamente cuando la instancia de Finanzas lo apruebe, y solo puede quedar aprobado para registro CGU cuando la instancia Zonal lo apruebe. Todo cambio de estado del caso SHALL ejecutarse exclusivamente a través de `TransicionWorkflowService::execute()`.

#### Scenario: Un pago aprobado por Finanzas pasa a la instancia Zonal
- **WHEN** el Jefe de Finanzas aprueba un pago que está en `en_revision_finanzas`
- **THEN** el caso transiciona a `en_revision_zonal` vía `TransicionWorkflowService`
- **AND** queda visible para la instancia Zonal y ya no editable por Finanzas

#### Scenario: Un pago aprobado por Zonal queda listo para registro CGU
- **WHEN** el Administrador Zonal aprueba un pago que está en `en_revision_zonal`
- **THEN** el caso transiciona a `lista_para_registro_cgu`
- **AND** queda fuera del alcance de ambas instancias de revisión

#### Scenario: No se puede saltar la instancia de Finanzas
- **WHEN** se intenta ejecutar la aprobación Zonal sobre un pago que está en `en_revision_finanzas`
- **THEN** la operación es rechazada porque no existe esa transición desde el estado origen

### Requirement: Devolución a la instancia anterior
Cada instancia de revisión SHALL poder devolver un pago a la instancia anterior. La instancia Zonal SHALL poder devolver un pago a la instancia de Finanzas, y la instancia de Finanzas SHALL poder devolverlo a la etapa previa (observado/subsanación). Toda devolución SHALL exigir un comentario obligatorio que quede registrado en el historial de transiciones.

#### Scenario: Zonal devuelve un pago a Finanzas
- **WHEN** el Administrador Zonal devuelve un pago en `en_revision_zonal` con un comentario
- **THEN** el caso vuelve a `en_revision_finanzas`
- **AND** el comentario queda en `historial_transiciones_workflow`

#### Scenario: Devolver sin comentario es rechazado
- **WHEN** se intenta devolver un pago sin comentario
- **THEN** la operación es rechazada y el estado del caso no cambia

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

### Requirement: Verificación de totales del pago
Antes de aprobar un pago, la instancia activa SHALL verificar los totales del pago: monto de factura, monto de recepción/OC y monto a pagar. El sistema SHALL indicar si los totales coinciden o si hay una diferencia. Un pago solo SHALL poder aprobarse cuando sus totales fueron verificados por la instancia activa.

#### Scenario: Aprobar un pago requiere totales verificados
- **WHEN** se intenta aprobar un pago cuyos totales no han sido verificados en la instancia activa
- **THEN** la aprobación es rechazada

#### Scenario: El sistema señala una diferencia de totales
- **WHEN** el monto de factura no coincide con el monto de recepción/OC o con el monto a pagar
- **THEN** el pago se marca con diferencia de totales detectada

### Requirement: El Egreso avanza cuando todos sus pagos se aprueban en la instancia actual
El estado de revisión de un Egreso SHALL derivarse de los estados de sus pagos y no persistirse como fuente de verdad. Un Egreso SHALL avanzar a la instancia siguiente solo cuando todos sus pagos hayan sido aprobados por la instancia actual. La aprobación o devolución a nivel de Egreso SHALL iterar sobre sus pagos y disparar la transición de workflow de cada uno vía `TransicionWorkflowService`.

#### Scenario: Aprobar el Egreso completo desde Finanzas
- **WHEN** el Jefe de Finanzas aprueba un Egreso cuyos pagos están todos listos (documentos aprobados y totales verificados)
- **THEN** cada pago del Egreso transiciona a `en_revision_zonal`
- **AND** el estado derivado del Egreso pasa a la instancia Zonal

#### Scenario: El Egreso no avanza si algún pago no está listo
- **WHEN** se intenta avanzar un Egreso que tiene al menos un pago no aprobado por la instancia actual
- **THEN** el avance del Egreso es rechazado y ningún pago cambia de estado

#### Scenario: El estado del Egreso es derivado
- **WHEN** se consulta un Egreso con pagos en distintos estados
- **THEN** su estado de revisión se calcula a partir de los estados de sus pagos y no se guarda como columna de verdad

### Requirement: Alcance zonal del Administrador Zonal
El Administrador Zonal SHALL ver y actuar únicamente sobre Egresos de su jurisdicción/zona. La jurisdicción de un Egreso se deriva de sus pagos (a través del centro financiero de cada caso). El sistema SHALL negar el acceso de un Administrador Zonal a Egresos fuera de su jurisdicción y registrar el intento denegado en `security_audit_logs`.

#### Scenario: El Zonal solo ve Egresos de su zona
- **WHEN** un Administrador Zonal abre la pantalla de revisión
- **THEN** solo se listan los Egresos cuya jurisdicción coincide con la suya

#### Scenario: Acceso a un Egreso de otra zona es denegado
- **WHEN** un Administrador Zonal intenta abrir o actuar sobre un Egreso de otra jurisdicción
- **THEN** el sistema bloquea la operación
- **AND** registra el evento de autorización denegada en `security_audit_logs`

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


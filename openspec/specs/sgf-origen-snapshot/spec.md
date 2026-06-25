# Spec: sgf-origen-snapshot

## Requirement: SGF como dato de origen

El sistema debe usar información SGF solo como origen, contexto, trazabilidad, reportabilidad y conciliación.

### Scenario: Importar caso SGF

Given una fila SGF contiene ID, estado, grupo actual, observaciones, RUT, documento y monto
When se importa al sistema
Then se crea o actualiza un `supplier_payment_case`
And se guarda `sgf_id`
And se guarda `sgf_status` como referencia externa
And se guarda `sgf_current_group_raw` como referencia externa
And no se usa el estado SGF como workflow interno
And no se usa el grupo SGF como unidad interna

## Requirement: Conservar snapshot de datos y documentos SGF

Todo caso SGF debe conservar snapshot de datos y documentos recibidos.

### Scenario: Guardar snapshot SGF

Given se recibe información desde SGF
When el sistema importa el caso
Then guarda `raw_sgf_payload`
And guarda payload normalizado
And registra fuente, método de captura, usuario o job
And calcula hash del contenido recibido
And vincula el snapshot al caso de pago

### Scenario: Guardar documentos SGF

Given SGF entrega documentos asociados al caso
When el sistema los importa
Then los guarda en el expediente documental
And marca origen `sgf`
And registra hash de archivo
And vincula cada documento al snapshot SGF

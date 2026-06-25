# Spec: pago-proveedores-sgf

## Requirement: Cada SGF ID es un caso individual

El sistema debe tratar cada `sgf_id` como un caso de pago independiente.

### Scenario: Crear caso de pago desde SGF

Given existe una fila SGF con ID único
When el sistema la importa
Then crea un `supplier_payment_case`
And crea un proceso workflow asociado
And asigna estado interno `importada_desde_sgf`
And conserva datos SGF como referencia externa

## Requirement: No usar lotes iniciales

El sistema no debe modelar envíos o lotes iniciales de SGF.

### Scenario: Evitar payment submissions

Given se importa información SGF
When se crean registros internos
Then no se crean `payment_submissions`
And no se crean `payment_submission_items`
And no se crea `sgf_submission_id`

## Requirement: Registrar CGU, BancoEstado y egreso como evidencia

El sistema debe registrar referencias y respaldos de CGU, BancoEstado y egreso CGU sin reemplazar esos sistemas oficiales.

### Scenario: Asociar egreso CGU

Given un caso ya fue pagado
When se registra un egreso CGU
Then se crea `cgu_egress`
And se asocian casos mediante `cgu_egress_items`
And se guarda respaldo documental si existe

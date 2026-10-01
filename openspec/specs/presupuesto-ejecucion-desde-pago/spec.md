# presupuesto-ejecucion-desde-pago Specification

## Purpose
TBD - created by archiving change presupuesto-ejecucion-desde-pago. Update Purpose after archive.
## Requirements
### Requirement: Registrar ejecución y liberación de compromiso al pagar un caso
El sistema SHALL registrar automáticamente, cuando un `CasoPagoProveedor` ejecuta la transición `marcar_pagada_bancoestado`, un movimiento presupuestario `ejecucion` y un movimiento `liberacion_compromiso`, ambos por el monto del caso, contra el CDP firmado vigente de la adquisición vinculada al caso. Ambos movimientos SHALL tener como origen el `CasoPagoProveedor` y SHALL quedar vinculados al CDP. El registro SHALL ocurrir solo si el módulo Presupuesto está activo.

#### Scenario: Pago de un caso con un único CDP vigente
- **WHEN** un caso con adquisición cuyo único CDP firmado vigente es de $1.000.000 ejecuta `marcar_pagada_bancoestado` con monto $400.000
- **THEN** se crean un movimiento `ejecucion` y un movimiento `liberacion_compromiso`, ambos de $400.000, con origen el caso y vinculados a ese CDP
- **AND** el saldo disponible del presupuesto no cambia y el compromiso remanente del CDP es $600.000

#### Scenario: Pagos parciales de varios casos de la misma adquisición
- **WHEN** dos casos de la misma adquisición se pagan por $400.000 y $300.000 contra un CDP de $1.000.000
- **THEN** cada caso registra su propio par de movimientos y el compromiso remanente del CDP es $300.000

#### Scenario: Módulo Presupuesto desactivado
- **WHEN** el módulo Presupuesto está desactivado y un caso se paga
- **THEN** no se registra ningún movimiento y el pago se completa con normalidad

### Requirement: Resolver el CDP vigente sin adivinar
El sistema SHALL considerar CDP vigente de una adquisición a un CDP firmado de monto positivo que no tenga un CDP de anulación firmado que lo referencie. El sistema SHALL registrar la ejecución únicamente cuando exista exactamente un CDP vigente; si hay cero o más de uno, SHALL NOT registrar movimientos y SHALL dejar constancia auditable del motivo y de los CDP candidatos.

#### Scenario: Adquisición sin CDP vigente
- **WHEN** se paga un caso cuya adquisición no tiene ningún CDP firmado vigente (o solo tiene CDP anulados)
- **THEN** no se crean movimientos y se registra una constancia de auditoría `presupuesto.ejecucion_no_resuelta` con el motivo
- **AND** el pago no se bloquea

#### Scenario: Adquisición con más de un CDP vigente
- **WHEN** se paga un caso cuya adquisición tiene dos CDP firmados vigentes
- **THEN** no se crean movimientos y la constancia de auditoría lista ambos CDP para resolución humana

#### Scenario: Caso sin adquisición vinculada
- **WHEN** se paga un caso sin adquisición vinculada
- **THEN** no se registra ejecución ni constancia de falla

### Requirement: Idempotencia del registro de ejecución
El sistema SHALL registrar como máximo un movimiento `ejecucion` y un movimiento `liberacion_compromiso` por `CasoPagoProveedor`, tanto a nivel de servicio como de base de datos, de modo que un reintento o un doble disparo no dupliquen movimientos.

#### Scenario: Doble disparo para el mismo caso
- **WHEN** el registro de ejecución se invoca dos veces para el mismo caso pagado
- **THEN** existe un único par de movimientos para ese caso

### Requirement: Alertar la sobre-ejecución sin bloquear el pago
El sistema SHALL detectar cuando la suma de ejecuciones contra un CDP supera su monto y, en ese caso, SHALL registrar auditoría `presupuesto.sobre_ejecucion` y notificar por el canal `database` a los usuarios con el permiso `presupuesto.firmar_cdp`. El sistema SHALL NOT impedir ni revertir el pago por sobre-ejecución.

#### Scenario: El pago excede el monto del CDP
- **WHEN** se paga un caso por $700.000 contra un CDP de $1.000.000 que ya tiene $400.000 ejecutados
- **THEN** se registran los movimientos, se audita `presupuesto.sobre_ejecucion` y se notifica a los usuarios con `presupuesto.firmar_cdp`
- **AND** el caso queda pagado

### Requirement: Aislar las fallas del registro de ejecución del pago
El sistema SHALL aislar cualquier falla del registro de ejecución de la transición de pago ya confirmada: la falla SHALL registrarse en auditoría y en log y SHALL NOT revertir ni impedir el pago.

#### Scenario: Falla al registrar la ejecución
- **WHEN** el registro de ejecución lanza una excepción tras confirmarse el pago
- **THEN** el caso permanece en `pagada_bancoestado` y se deja constancia de la falla en auditoría y log

### Requirement: Mostrar la ejecución en el detalle del CDP
El sistema SHALL entregar, en el detalle de un CDP firmado de monto positivo, los pagos ejecutados contra él, el monto ejecutado, el compromiso remanente y si está sobre-ejecutado, calculados en el backend. El frontend SHALL limitarse a renderizar esos datos.

#### Scenario: CDP con pagos ejecutados
- **WHEN** un usuario con `presupuesto.consultar` abre el detalle de un CDP con dos pagos ejecutados
- **THEN** ve la lista de pagos, el monto ejecutado, el compromiso remanente y, si corresponde, un indicador de sobre-ejecución

#### Scenario: CDP sin pagos ejecutados
- **WHEN** se abre el detalle de un CDP firmado sin ejecuciones
- **THEN** la tarjeta de ejecución muestra monto ejecutado $0 y el compromiso remanente igual al monto del CDP


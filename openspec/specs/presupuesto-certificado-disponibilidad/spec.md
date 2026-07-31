## Purpose

Esta capability cubre el Certificado de Disponibilidad Presupuestaria (CDP): el documento que
CAPJ Coyhaique emite para autorizar el gasto y reservar la línea presupuestaria antes de comprar.
Es la pieza que permite que el módulo Presupuesto (que hoy solo importa el monto asignado desde
CGU, ver `presupuesto-importacion-cgu`) funcione realmente como capa de control — comprometiendo
presupuesto contra una compra real, con evidencia y trazabilidad, en vez de solo mostrar montos
asignados. Sigue el mismo patrón de `ProcesoAdquisicion`: un modelo propio gobernado por su propia
`DefinicionWorkflow` a través de `TransicionWorkflowService`.
## Requirements
### Requirement: Crear un CDP en estado Borrador sin comprometer presupuesto
El sistema SHALL permitir crear un Certificado de Disponibilidad Presupuestaria (CDP) en estado
`borrador`, asignándole folio (`CDP {correlativo}-{año}`) en el momento de la creación, sin
comprometer saldo ni crear ningún `movimiento_presupuestario`. El `correlativo` SHALL ser una
secuencia global, única y autonumérica — el año es solo el sufijo de display del folio y NO
SHALL reiniciar el contador. El CDP referencia una única cuenta presupuestaria (una línea de
`presupuesto`) — no admite cabecera con líneas múltiples.

#### Scenario: Crear un borrador
- **WHEN** un usuario con permiso `presupuesto.crear_cdp` crea un CDP indicando línea de
  presupuesto, cfinanciero, monto, moneda de compra y demás datos requeridos
- **THEN** el sistema crea el CDP en estado `borrador` con folio correlativo
- **AND** no crea ningún `movimiento_presupuestario` ni descuenta saldo disponible

#### Scenario: El folio se asigna al crear, no al firmar
- **WHEN** se crean dos CDP en borrador consecutivos, sin firmar ninguno
- **THEN** cada uno recibe su propio folio correlativo en el momento de la creación
- **AND** el folio no cambia si el CDP se firma más tarde

#### Scenario: El correlativo no se reinicia entre años distintos
- **WHEN** existe un CDP con `anio_validez` 2025 y correlativo N, y se crea un CDP nuevo con
  `anio_validez` 2026
- **THEN** el CDP nuevo recibe el correlativo N+1, nunca 1

#### Scenario: Usuario sin permiso no puede crear un CDP
- **WHEN** un usuario autenticado sin `presupuesto.crear_cdp` intenta crear un CDP
- **THEN** el sistema deniega la acción
- **AND** registra el intento en `security_audit_logs` como `acceso_denegado`

### Requirement: Resolver la paridad y el monto contra el indicador económico real, no como input del cliente
Cuando `moneda_compra` no es `CLP`, el sistema SHALL resolver `paridad` contra el indicador
económico real vigente para `fecha_paridad` (mismo mecanismo que ya usa el resto del sistema para
UF/USD), y SHALL calcular `monto = total_moneda_compra × paridad`. `paridad` y `monto` NUNCA SHALL
aceptarse como valores enviados directamente por el cliente — se ignoran o sobrescriben. En `CLP`,
`monto = total_moneda_compra` y no hay paridad.

#### Scenario: Crear un CDP en UF resuelve la paridad desde el indicador real
- **WHEN** un usuario crea un CDP con `moneda_compra=UF`, `total_moneda_compra` y `fecha_paridad`
  para una fecha con indicador UF registrado
- **THEN** el sistema fija `paridad` con el valor de ese indicador
- **AND** calcula `monto` como `total_moneda_compra × paridad`

#### Scenario: No hay indicador registrado para la fecha de paridad
- **WHEN** un usuario crea un CDP en UF o USD con `fecha_paridad` sin valor de indicador
  registrado para esa fecha
- **THEN** el sistema rechaza la creación sin crear el CDP

#### Scenario: El cliente no puede fijar paridad o monto directamente
- **WHEN** una solicitud de creación incluye valores de `paridad` o `monto`
- **THEN** el sistema los ignora y calcula ambos igualmente contra el indicador real (o el total,
  en CLP)

### Requirement: Editar un CDP mientras esté en Borrador
El sistema SHALL permitir editar los datos de un CDP (monto, cuenta, moneda, materia, etc.)
únicamente mientras su `Proceso` esté en estado `borrador`. Un CDP en estado `firmado` es
inmutable.

#### Scenario: Editar un borrador
- **WHEN** un usuario con permiso `presupuesto.crear_cdp` edita un CDP cuyo estado actual es
  `borrador`
- **THEN** el sistema aplica los cambios

#### Scenario: Intentar editar un CDP firmado
- **WHEN** un usuario intenta editar un CDP cuyo estado actual es `firmado`
- **THEN** el sistema rechaza la edición sin aplicar ningún cambio

### Requirement: Firmar un CDP compromete presupuesto vía el motor de workflow
El sistema SHALL comprometer presupuesto contra un CDP únicamente al ejecutar la transición
`firmar` (`borrador` → `firmado`) a través de `TransicionWorkflowService::execute()`, nunca por
una actualización directa de estado. Al firmar, el sistema SHALL calcular el saldo disponible de
la línea de presupuesto (`monto_asignado − compromisos + liberaciones − ejecutado`), registrar un
`movimiento_presupuestario` de tipo `compromiso` por el monto del CDP, y generar el PDF oficial
replicando la plantilla exacta usada por CAPJ (sin el campo `Programa Presupuestario`, que es solo
dato de control interno).

#### Scenario: Firmar un CDP con saldo suficiente
- **WHEN** un usuario con permiso `presupuesto.firmar_cdp` firma un CDP en estado `borrador` cuyo
  monto no excede el saldo disponible de su línea de presupuesto
- **THEN** el sistema transiciona el `Proceso` del CDP a `firmado` vía
  `TransicionWorkflowService::execute()`
- **AND** registra un `movimiento_presupuestario` de tipo `compromiso` por el monto del CDP
- **AND** guarda `saldo_disponible_al_emitir`, `firmado_por` y `firmado_en`
- **AND** genera el PDF del CDP y lo registra como `Documento` de tipo `CDP`, vinculado por
  `VinculoDocumento` al `Proceso` del propio CDP

#### Scenario: Firmar un CDP con sobregiro no bloquea
- **WHEN** un usuario con permiso `presupuesto.firmar_cdp` firma un CDP cuyo monto excede el saldo
  disponible de su línea de presupuesto
- **THEN** el sistema completa la firma igualmente, transicionando el CDP a `firmado`
- **AND** marca `hubo_sobregiro_al_emitir` en verdadero
- **AND** deja evidencia del sobregiro visible para quien consulte el CDP

#### Scenario: Un CDP firmado nunca cambia de estado
- **WHEN** se consulta el catálogo de transiciones disponibles para un CDP en estado `firmado`
- **THEN** el sistema no ofrece ninguna transición de salida — `firmado` es un estado final

#### Scenario: Usuario sin permiso no puede firmar
- **WHEN** un usuario autenticado sin `presupuesto.firmar_cdp` intenta ejecutar la transición
  `firmar` sobre un CDP en borrador
- **THEN** el sistema rechaza la transición
- **AND** el CDP permanece en estado `borrador`

### Requirement: Anular un CDP firmado emitiendo uno nuevo con monto negativo
El sistema SHALL modelar la anulación de un CDP firmado como la creación y firma de un CDP nuevo
con el mismo monto en valor absoluto pero negativo (100% del monto original — la anulación
parcial no está soportada), mismo `requerimiento_numero`, y `cdp_original_id` apuntando al CDP
que corrige. El sistema NUNCA SHALL modificar ni reabrir el estado de un CDP ya firmado.

#### Scenario: Anular un CDP firmado
- **WHEN** un usuario con permiso `presupuesto.anular_cdp` anula un CDP en estado `firmado`
- **THEN** el sistema crea un CDP nuevo en `borrador` con `monto` igual al negativo del CDP
  original, mismo `requerimiento_numero`, y `cdp_original_id` apuntando al original
- **AND** el CDP original permanece sin cambios en estado `firmado`

#### Scenario: El CDP de anulación sigue el mismo ciclo borrador→firmado
- **WHEN** se crea un CDP de anulación
- **THEN** el sistema lo deja en estado `borrador` hasta que se firme explícitamente, igual que
  cualquier otro CDP
- **AND** al firmarlo, el `movimiento_presupuestario` de tipo `compromiso` (negativo) compensa el
  saldo comprometido por el CDP original sin necesidad de un tipo de movimiento especial

#### Scenario: Usuario sin permiso no puede anular
- **WHEN** un usuario autenticado sin `presupuesto.anular_cdp` intenta anular un CDP firmado
- **THEN** el sistema deniega la acción

### Requirement: Vincular opcionalmente un CDP a una Adquisición o a Mercado Público, sin gate de workflow
El sistema SHALL permitir asociar un CDP a un `ProcesoAdquisicion` existente
(`proceso_adquisicion_id`) y opcionalmente a una `OrdenCompraMercadoPublico` o
`LicitacionMercadoPublico` ya importada, como referencia de datos. Este vínculo NO SHALL
condicionar ni gobernar transiciones del workflow de Adquisiciones — es puramente informativo en
este change.

#### Scenario: Vincular un CDP a una Adquisición existente
- **WHEN** un usuario crea o edita un CDP en borrador indicando un `proceso_adquisicion_id`
  válido
- **THEN** el sistema guarda la referencia
- **AND** el CDP aparece listado entre los CDP de esa Adquisición

#### Scenario: Un CDP sin vínculo a Adquisiciones es válido
- **WHEN** un usuario crea y firma un CDP sin indicar `proceso_adquisicion_id`
- **THEN** el sistema lo permite sin restricción

#### Scenario: El vínculo no bloquea ninguna transición de Adquisiciones
- **WHEN** una `ProcesoAdquisicion` no tiene ningún CDP firmado vinculado
- **THEN** sus transiciones de workflow (`publicar`, `adjudicar`, `formalizar_contrato`, etc.)
  siguen disponibles sin cambios respecto al comportamiento actual

### Requirement: Consultar CDP con su estado, saldo y documento generado
El sistema SHALL exponer un listado y detalle de CDP (folio, estado, monto, línea de presupuesto,
cuenta, vínculo a Adquisición si existe) a usuarios con permiso `presupuesto.consultar`, incluyendo
el PDF generado al firmar.

#### Scenario: Consultar el listado de CDP
- **WHEN** un usuario con permiso `presupuesto.consultar` visita el listado de CDP
- **THEN** el sistema muestra folio, estado actual, monto, cuenta presupuestaria y línea de
  presupuesto de cada CDP

#### Scenario: Descargar el PDF de un CDP firmado
- **WHEN** un usuario con permiso `presupuesto.consultar` abre el detalle de un CDP en estado
  `firmado`
- **THEN** el sistema ofrece el PDF generado al firmar, replicando la plantilla oficial de CAPJ

#### Scenario: Usuario sin permiso no puede consultar
- **WHEN** un usuario autenticado sin `presupuesto.consultar` intenta ver el listado o detalle de
  un CDP
- **THEN** el sistema deniega el acceso
- **AND** registra el intento en `security_audit_logs` como `acceso_denegado`

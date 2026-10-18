# servicios-contratados Specification

## Purpose
Catálogo de servicios recurrentes (aseo, vigilancia, mantención, arriendos) vinculados a un contrato aprobado, con estado derivado de la vigencia, alerta de vencimiento y vista de períodos mensuales cruzada, solo informativamente, con los casos de pago del proveedor.
## Requirements
### Requirement: Registrar un servicio contratado vinculado a un contrato aprobado
El sistema SHALL permitir, a un usuario con el permiso `servicios_contratados.crear`, registrar un `ServicioContratado` con `contrato_id`, `nombre`, `categoria` (`aseo`, `vigilancia`, `mantencion`, `arriendo`, `otro`), `monto_mensual_esperado`, `moneda`, `fecha_inicio`, `fecha_fin`, y opcionalmente `ccosto_id`, `administrador_funcionario_id` y `observaciones`. El proveedor del servicio SHALL ser el del contrato. El contrato SHALL estar en estado `aprobado` y la vigencia del servicio SHALL estar contenida en la del contrato.

#### Scenario: Registro exitoso
- **WHEN** un usuario con el permiso requerido registra un servicio sobre un contrato aprobado con una vigencia dentro de la del contrato
- **THEN** el sistema crea el `ServicioContratado` y registra la acción en auditoría

#### Scenario: Contrato no aprobado
- **WHEN** el contrato indicado está en `borrador`, `pendiente` o `rechazado`
- **THEN** el sistema rechaza el registro con un mensaje de validación y no crea nada

#### Scenario: Vigencia fuera de la del contrato
- **WHEN** `fecha_inicio` es anterior o `fecha_fin` es posterior a la vigencia del contrato, o `fecha_fin` es anterior a `fecha_inicio`
- **THEN** el sistema rechaza el registro con un mensaje de validación

### Requirement: Editar y terminar un servicio contratado
El sistema SHALL permitir editar los datos de un servicio con el permiso `servicios_contratados.editar` (sin cambiar el contrato) y marcarlo como terminado con `servicios_contratados.terminar`, registrando `terminado_en`. Un servicio terminado SHALL no poder editarse.

#### Scenario: Edición exitosa
- **WHEN** un usuario con el permiso edita un servicio no terminado con datos válidos
- **THEN** el sistema aplica el cambio y lo audita

#### Scenario: Terminar un servicio
- **WHEN** un usuario con `servicios_contratados.terminar` marca un servicio como terminado
- **THEN** el sistema registra `terminado_en` y el estado derivado pasa a `terminado`

#### Scenario: Editar un servicio terminado
- **WHEN** un usuario intenta editar un servicio terminado
- **THEN** el sistema rechaza la edición

### Requirement: Estado derivado de la vigencia
El sistema SHALL derivar el estado de un servicio al consultarlo, sin persistirlo: `terminado` si tiene `terminado_en`; si no, `vencido` si `fecha_fin` es anterior a hoy; `por_vencer` si `fecha_fin` cae dentro de la ventana de alerta (config `servicios_contratados.dias_alerta_vencimiento`, 60 días por defecto); en otro caso `vigente`.

#### Scenario: Servicio por vencer
- **WHEN** a un servicio no terminado le faltan menos días que la ventana de alerta para su `fecha_fin`
- **THEN** el listado y el detalle lo muestran como `por_vencer` con una alerta visible

#### Scenario: Servicio vencido
- **WHEN** la `fecha_fin` de un servicio no terminado ya pasó
- **THEN** su estado derivado es `vencido`

### Requirement: Períodos mensuales cruzados con los casos de pago
El sistema SHALL calcular, sin persistir, un período por cada mes entre `fecha_inicio` y el menor entre `fecha_fin` y el mes actual, cruzando con los `CasoPagoProveedor` del mismo proveedor cuyo `periodo` sea exactamente `YYYY-MM`. Cada período SHALL ser `pagado` (algún caso en `pagada_bancoestado` o posterior), `en_tramite` (hay caso pero ninguno pagado), `sin_caso` o `ambiguo` (el proveedor tiene más de un servicio vigente ese mes). El cruce SHALL ser informativo y nunca bloquear, validar ni alterar un caso de pago.

#### Scenario: Mes pagado
- **WHEN** existe un caso del proveedor con `periodo` `2026-07` en estado `pagada_bancoestado` y el proveedor tiene un único servicio vigente ese mes
- **THEN** el período `2026-07` del servicio es `pagado` y referencia el caso

#### Scenario: Mes sin caso
- **WHEN** no existe ningún caso del proveedor para un mes ya transcurrido
- **THEN** ese período es `sin_caso`

#### Scenario: Atribución ambigua
- **WHEN** el proveedor tiene dos servicios vigentes el mismo mes y existe un caso para ese `periodo`
- **THEN** el período es `ambiguo` en ambos servicios y el sistema no atribuye el caso a ninguno

#### Scenario: Periodo con formato inválido
- **WHEN** un caso del proveedor tiene un `periodo` que no es `YYYY-MM`
- **THEN** el sistema lo ignora en el cruce

### Requirement: Listado, detalle y permisos
El sistema SHALL ofrecer un listado denso con búsqueda y filtro por estado derivado, y un detalle con datos, alerta y períodos, accesibles con `servicios_contratados.ver`. Las acciones sin el permiso correspondiente SHALL ser denegadas.

#### Scenario: Usuario sin permiso
- **WHEN** un usuario sin `servicios_contratados.ver` accede al listado
- **THEN** el sistema deniega el acceso

#### Scenario: Filtro por estado
- **WHEN** un usuario filtra el listado por `por_vencer`
- **THEN** el listado muestra solo servicios no terminados dentro de la ventana de alerta


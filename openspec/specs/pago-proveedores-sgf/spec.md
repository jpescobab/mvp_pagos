# Spec: pago-proveedores-sgf

## Purpose

Primer módulo funcional activable: convierte la evidencia SGF (tarea 7) en casos de pago gobernados por workflow (tarea 5), con expediente documental (tarea 6) y evidencia de registro CGU/BancoEstado/egreso, sin reemplazar la lógica de esos sistemas oficiales.
## Requirements
### Requirement: Cada sgf_id es un caso de pago individual
El sistema SHALL tratar cada `sgf_id` como un `caso_pago_proveedor` independiente, con su propio `Proceso` de workflow. Los datos SGF (`sgf_status`, `sgf_current_group_raw`, `periodo`, `observacion`, `observacion_egreso`, `folio_egreso`, `numero`, `fecha_sii`) SHALL conservarse solo como referencia externa, sin gobernar el estado interno del caso.

#### Scenario: Crear caso de pago desde un snapshot SGF
- **WHEN** se registra un `snapshot_datos_externo` del sistema externo `SGF` cuyo `referencia_externa` (`sgf_id`) no tiene un `caso_pago_proveedor` previo
- **THEN** se crea un `caso_pago_proveedor`
- **AND** se crea un `Proceso` asociado en el estado inicial (`importada_desde_sgf`) del workflow "pago_proveedores"
- **AND** se conservan `sgf_status`, `sgf_current_group_raw`, `periodo`, `observacion`, `observacion_egreso`, `folio_egreso`, `numero` y `fecha_sii` como referencia externa, cuando el payload normalizado del snapshot los incluye

#### Scenario: Reimportar un sgf_id existente no altera su workflow interno
- **WHEN** se registra un `snapshot_datos_externo` del sistema externo `SGF` cuyo `referencia_externa` (`sgf_id`) ya tiene un `caso_pago_proveedor`
- **THEN** se actualizan los campos de referencia SGF del caso (rut, monto, estado, grupo SGF, periodo, observación, observación de egreso, folio de egreso, número y fecha SII)
- **AND** el estado interno del `Proceso` asociado no cambia

#### Scenario: Un campo de referencia SGF no viene en el payload normalizado
- **WHEN** se registra o reimporta un `snapshot_datos_externo` de SGF cuyo payload normalizado no incluye `periodo`, `observacion`, `observacion_egreso`, `folio_egreso`, `numero` o `fecha_sii`
- **THEN** el `caso_pago_proveedor` conserva `null` en el campo faltante en vez de fallar la importación

### Requirement: No modelar lotes ni envíos iniciales
El sistema SHALL NOT crear `payment_submissions`, `payment_submission_items` ni un `sgf_submission_id` al importar casos desde SGF.

#### Scenario: Importar sin generar lotes
- **WHEN** se importan una o más filas SGF como casos de pago
- **THEN** no se crea ningún registro de lote o envío agrupado
- **AND** cada caso se gobierna de forma individual por su propio `Proceso`

### Requirement: Registrar CGU, BancoEstado y egreso CGU como evidencia
El sistema SHALL registrar referencias y respaldos de registro contable CGU, pago BancoEstado y egreso CGU como evidencia de gestión, sin reemplazar la lógica de esos sistemas oficiales. El registro contable CGU y el pago BancoEstado SHALL ser registros manuales independientes de cualquier transición de workflow, autorizados respectivamente por los permisos `pago_proveedores.registrar_cgu` y `pago_proveedores.pagar`.

#### Scenario: Asociar un egreso CGU a uno o más casos
- **WHEN** se registra un egreso CGU que cubre uno o más casos ya pagados
- **THEN** se crea un `egreso_cgu`
- **AND** se asocian los casos correspondientes mediante `egresos_cgu_items`
- **AND** se puede vincular respaldo documental al egreso mediante `vinculos_documento`

#### Scenario: Registrar evidencia de registro contable CGU
- **WHEN** un usuario con el permiso `pago_proveedores.registrar_cgu` registra un número de registro, fecha y monto para un `caso_pago_proveedor`
- **THEN** se crea un `registro_contable_cgu` asociado al caso, con el usuario autenticado como `registrado_por`
- **AND** se registra un evento de auditoría con la acción `caso_pago_proveedor.registrar_contable_cgu`
- **AND** no se ejecuta ninguna transición de `TransicionWorkflowService`

#### Scenario: Registrar evidencia de pago bancario
- **WHEN** un usuario con el permiso `pago_proveedores.pagar` registra un número de operación, fecha de pago y monto para un `caso_pago_proveedor`
- **THEN** se crea un `registro_pago_bancario` asociado al caso, con el usuario autenticado como `registrado_por`
- **AND** se registra un evento de auditoría con la acción `caso_pago_proveedor.registrar_pago_bancario`
- **AND** no se ejecuta ninguna transición de `TransicionWorkflowService`

#### Scenario: Usuario sin permiso intenta registrar evidencia
- **WHEN** un usuario sin el permiso `pago_proveedores.registrar_cgu` intenta registrar un `registro_contable_cgu`, o un usuario sin el permiso `pago_proveedores.pagar` intenta registrar un `registro_pago_bancario`
- **THEN** el sistema bloquea la operación
- **AND** registra el evento de autorización denegada en `security_audit_logs`

#### Scenario: El detalle de un caso de pago muestra el historial completo de evidencia
- **WHEN** un usuario abre el detalle de un `caso_pago_proveedor`
- **THEN** la respuesta incluye todos los `registro_contable_cgu` y `registro_pago_bancario` asociados al caso, no solo el más reciente

#### Scenario: Mostrar los egresos CGU asociados en el detalle de un caso de pago
- **WHEN** un usuario abre el detalle de un `caso_pago_proveedor` que ya tiene uno o más `egresos_cgu_items` asociados
- **THEN** la respuesta incluye cada `egreso_cgu` asociado, con su número, fecha y el monto del item correspondiente a ese caso
- **AND** cada egreso mostrado permite navegar a su propio detalle (`pago-proveedores.egresos-cgu.show`)

### Requirement: Vincular manualmente un caso de pago a un proceso de adquisición
El sistema SHALL permitir vincular un `caso_pago_proveedor` a un `proceso_adquisicion` mediante una acción manual y explícita, distinta de cualquier transición de workflow. El vínculo SHALL ser opcional (nullable) y SHALL permitir que varios `caso_pago_proveedor` apunten al mismo `proceso_adquisicion`, pero un `caso_pago_proveedor` SHALL apuntar a lo sumo a un `proceso_adquisicion` a la vez.

#### Scenario: Vincular un caso de pago a una adquisición
- **WHEN** un usuario con el permiso `pago_proveedores.vincular_adquisicion` selecciona un `proceso_adquisicion` desde la búsqueda asistida en el detalle de un `caso_pago_proveedor` sin vínculo previo
- **THEN** se registra `proceso_adquisicion_id` en el `caso_pago_proveedor`
- **AND** se registra un evento de auditoría con la acción `caso_pago_proveedor.vincular_adquisicion`, el usuario, y el estado antes/después del vínculo
- **AND** no se ejecuta ninguna transición de `TransicionWorkflowService`

#### Scenario: Reemplazar o quitar un vínculo existente
- **WHEN** un usuario con el permiso `pago_proveedores.vincular_adquisicion` desvincula un `caso_pago_proveedor` que ya tenía `proceso_adquisicion_id` asignado
- **THEN** `proceso_adquisicion_id` queda en `null`
- **AND** se registra un evento de auditoría con la acción `caso_pago_proveedor.desvincular_adquisicion`

#### Scenario: Usuario sin permiso intenta vincular
- **WHEN** un usuario sin el permiso `pago_proveedores.vincular_adquisicion` intenta vincular o desvincular un `caso_pago_proveedor`
- **THEN** el sistema bloquea la operación
- **AND** registra el evento de autorización denegada en `security_audit_logs`

### Requirement: Búsqueda asistida de procesos de adquisición desde un caso de pago
El sistema SHALL exponer una búsqueda de `proceso_adquisicion` por código, objeto, proveedor o monto, limitada a un número acotado de resultados, sin intentar matching automático sobre el texto libre de SGF (`observaciones`, `payload_crudo`).

#### Scenario: Buscar procesos de adquisición candidatos
- **WHEN** un usuario autorizado escribe un término de búsqueda en la acción de vincular adquisición de un `caso_pago_proveedor`
- **THEN** el sistema devuelve los `proceso_adquisicion` cuyo código, objeto, proveedor o monto coincidan con el término, limitados a un máximo de resultados
- **AND** cada resultado muestra su código `ADQ-XXXX` para confirmación visual antes de vincular

### Requirement: El checklist documental de un caso de pago se resuelve con una matriz real
El sistema SHALL mantener una matriz de `requisitos_documentales` concreta para el workflow "pago_proveedores", asociada a un `conjunto_requisitos_documentales` propio, reutilizando el catálogo de `tipos_documento` ya existente. El `tipo_documento` con código `FACTURA` SHALL existir y aplicarse, dado que ya es referenciado por la transición `aprobar_documentacion` del workflow de Pago de Proveedores.

#### Scenario: Seeder de requisitos documentales disponible
- **WHEN** se ejecuta el seeder de requisitos documentales de Pago de Proveedores
- **THEN** existe un `conjunto_requisitos_documentales` para el workflow "pago_proveedores"
- **AND** existen `requisitos_documentales` que incluyen `FACTURA` como obligatorio

### Requirement: El detalle de un caso de pago resuelve y muestra su checklist documental real
El sistema SHALL invocar la resolución del checklist documental al abrir el detalle de un `caso_pago_proveedor`, usando el `conjunto_requisitos_documentales` de Pago de Proveedores.

#### Scenario: Abrir el detalle de un caso de pago genera un checklist no vacío
- **WHEN** un usuario abre el detalle de un `caso_pago_proveedor`
- **THEN** el backend resuelve o actualiza su `checklist_documental_proceso` usando las reglas de Pago de Proveedores
- **AND** la respuesta incluye al menos el item correspondiente a `FACTURA`

### Requirement: La revisión documental del workflow se ejecuta en dos instancias
El workflow de Pago de Proveedores SHALL expandir su etapa de revisión documental en dos estados diferenciados —`en_revision_finanzas` y `en_revision_zonal`— ubicados antes del registro CGU. Las transiciones entre estos estados SHALL definirse en `WorkflowPagoProveedoresSeeder` con sus permisos requeridos (`pago_proveedores.revisar_finanzas`, `pago_proveedores.revisar_zonal`), comentario obligatorio en las devoluciones/rechazos y documentos requeridos donde corresponda. Estos estados internos SHALL gobernarse por el sistema propio y no derivarse de los grupos/estados de SGF.

#### Scenario: El caso recorre las dos instancias antes de CGU
- **WHEN** un caso importado desde SGF inicia su revisión documental
- **THEN** pasa por `en_revision_finanzas` y luego `en_revision_zonal` antes de `lista_para_registro_cgu`
- **AND** cada transición exige el permiso de su instancia

#### Scenario: La aprobación de Finanzas requiere su permiso
- **WHEN** un usuario sin `pago_proveedores.revisar_finanzas` intenta la transición de aprobación de Finanzas
- **THEN** `TransicionWorkflowService` rechaza la transición por falta de permiso

### Requirement: Las transiciones de trámite general del caso exigen pertenecer a Finanzas
El sistema SHALL exigir el permiso `pago_proveedores.gestionar_caso` para las transiciones de trámite general de un `caso_pago_proveedor` que no pertenecen a la revisión en dos instancias ni tienen ya un permiso propio: `recibir_en_finanzas`, `iniciar_revision_documental`, `subsanar`, `reenviar_revision`, `rechazar` (desde `observada`), `marcar_lista_para_pago`, `asociar_egreso_cgu` y `cerrar`. Este permiso SHALL otorgarse a los roles `jefe_finanzas` y `administrativo_finanzas` en `WorkflowPagoProveedoresSeeder`. La transición `observar_finanzas` SHALL exigir específicamente `pago_proveedores.revisar_finanzas` — el mismo permiso que sus transiciones hermanas `aprobar_finanzas` y `rechazar_finanzas` — y no `gestionar_caso`.

#### Scenario: Usuario sin gestionar_caso no puede tramitar un caso
- **WHEN** un usuario sin `pago_proveedores.gestionar_caso` intenta ejecutar una de las transiciones de trámite general
- **THEN** `TransicionWorkflowService` rechaza la transición por falta de permiso

#### Scenario: Observar en Finanzas exige el permiso de esa instancia, no gestionar_caso
- **WHEN** un usuario con `pago_proveedores.revisar_zonal` pero sin `pago_proveedores.revisar_finanzas` intenta devolver (observar) un pago que aún está en `en_revision_finanzas`
- **THEN** `TransicionWorkflowService` rechaza la transición por falta de permiso, aunque el usuario pueda operar el egreso en su instancia Zonal

### Requirement: Centro financiero por defecto configurable cuando no hay adquisición vinculada
El sistema SHALL resolver un `cfinanciero_id` por defecto, configurado vía parámetro de aplicación (`config('pago-proveedores.cfinanciero_default_codigo')`, con variable de entorno propia), para usar en `CasoPagoProveedor::cfinancieroId()` únicamente cuando el caso no tiene `proceso_adquisicion` vinculado. El vínculo real a `proceso_adquisicion` SHALL tener siempre prioridad sobre este default. El código configurado SHALL resolverse contra un `cfinanciero` existente y activo; si no resuelve, el sistema SHALL registrar un warning y comportarse como si no hubiera default configurado (sin lanzar una excepción visible al usuario).

#### Scenario: Caso sin adquisición vinculada usa el cfinanciero por defecto
- **WHEN** se consulta `cfinancieroId()` de un `caso_pago_proveedor` sin `proceso_adquisicion_id`
- **THEN** el sistema retorna el `cfinanciero_id` correspondiente al código configurado en `pago-proveedores.cfinanciero_default_codigo`

#### Scenario: Caso con adquisición vinculada ignora el default
- **WHEN** se consulta `cfinancieroId()` de un `caso_pago_proveedor` con `proceso_adquisicion_id` asignado y su `proceso_adquisicion->ccosto->cfinanciero_id` resuelto
- **THEN** el sistema retorna ese `cfinanciero_id` real, no el default configurado

#### Scenario: Código configurado no corresponde a un cfinanciero activo
- **WHEN** el código configurado en `pago-proveedores.cfinanciero_default_codigo` no coincide con ningún `cfinanciero` activo
- **THEN** el sistema registra un warning en el log
- **AND** `cfinancieroId()` retorna `null` para casos sin adquisición vinculada, igual que si no existiera default configurado

### Requirement: La aprobación desde Finanzas ya no se bloquea por falta de adquisición cuando hay default configurado
`RevisionEgresoService::aprobarPago()` SHALL considerar la jurisdicción determinable cuando `CasoPagoProveedor::cfinancieroId()` retorna un valor no nulo, incluyendo el caso en que ese valor provenga del cfinanciero por defecto configurado. Al aprobar exitosamente desde la instancia Finanzas, el sistema SHALL persistir ese `cfinanciero_id` (real o default) en el `EgresoCgu` asociado si aún no tiene uno asignado, para que la revisión de la instancia Zonal pueda filtrar por jurisdicción.

#### Scenario: Aprobar desde Finanzas sin adquisición vinculada pero con default configurado
- **WHEN** un usuario con `pago_proveedores.revisar_finanzas` aprueba un pago cuyo `caso_pago_proveedor` no tiene `proceso_adquisicion` vinculado, y hay un cfinanciero por defecto configurado y resoluble
- **THEN** la aprobación no se bloquea por el guardrail de jurisdicción
- **AND** el `EgresoCgu` asociado queda con `cfinanciero_id` igual al valor por defecto, si no tenía uno asignado previamente

#### Scenario: Aprobar desde Finanzas sin adquisición vinculada y sin default configurado
- **WHEN** un usuario con `pago_proveedores.revisar_finanzas` intenta aprobar un pago cuyo `caso_pago_proveedor` no tiene `proceso_adquisicion` vinculado, y no hay cfinanciero por defecto configurado o resoluble
- **THEN** el sistema bloquea la aprobación con el mensaje existente indicando vincular el caso a un Proceso de Adquisición

### Requirement: Clasificar el tipo de proceso o de pago de un caso
El sistema SHALL permitir clasificar cada `caso_pago_proveedor` con un tipo de proceso de pago (`COMPRA`, `CONTRATO`, `CONVENIO`, `REEMBOLSO`, `ANTICIPO`, `OTRO`, catálogo `tipos_proceso_pago`), a un usuario con el permiso `pago_proveedores.gestionar_caso`, mediante una acción explícita distinta de cualquier transición de workflow. La clasificación SHALL persistirse en `procesos.tipo_proceso_pago_id`.

#### Scenario: Clasificar el tipo de proceso de un caso
- **WHEN** un usuario con `pago_proveedores.gestionar_caso` selecciona un tipo de proceso de pago activo en el detalle de un `caso_pago_proveedor`
- **THEN** se registra `tipo_proceso_pago_id` en el `Proceso` del caso
- **AND** se registra un evento de auditoría con la acción, el usuario, y el valor antes/después
- **AND** no se ejecuta ninguna transición de `TransicionWorkflowService`

#### Scenario: Usuario sin permiso intenta clasificar
- **WHEN** un usuario sin `pago_proveedores.gestionar_caso` intenta clasificar el tipo de proceso de un caso
- **THEN** el sistema bloquea la operación

#### Scenario: Cambiar el tipo de proceso ya clasificado
- **WHEN** un usuario con `pago_proveedores.gestionar_caso` selecciona un tipo de proceso distinto al ya registrado en un caso
- **THEN** `procesos.tipo_proceso_pago_id` se actualiza al nuevo valor
- **AND** el checklist documental refleja los requisitos del nuevo tipo en la siguiente resolución

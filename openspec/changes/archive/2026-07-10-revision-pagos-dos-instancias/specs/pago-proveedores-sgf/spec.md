## ADDED Requirements

### Requirement: La revisión documental del workflow se ejecuta en dos instancias
El workflow de Pago de Proveedores SHALL expandir su etapa de revisión documental en dos estados diferenciados —`en_revision_finanzas` y `en_revision_zonal`— ubicados antes del registro CGU. Las transiciones entre estos estados SHALL definirse en `WorkflowPagoProveedoresSeeder` con sus permisos requeridos (`pago_proveedores.revisar_finanzas`, `pago_proveedores.revisar_zonal`), comentario obligatorio en las devoluciones/rechazos y documentos requeridos donde corresponda. Estos estados internos SHALL gobernarse por el sistema propio y no derivarse de los grupos/estados de SGF.

#### Scenario: El caso recorre las dos instancias antes de CGU
- **WHEN** un caso importado desde SGF inicia su revisión documental
- **THEN** pasa por `en_revision_finanzas` y luego `en_revision_zonal` antes de `lista_para_registro_cgu`
- **AND** cada transición exige el permiso de su instancia

#### Scenario: La aprobación de Finanzas requiere su permiso
- **WHEN** un usuario sin `pago_proveedores.revisar_finanzas` intenta la transición de aprobación de Finanzas
- **THEN** `TransicionWorkflowService` rechaza la transición por falta de permiso

### Requirement: Agrupación automática de pagos en Egresos al importar desde SGF
Al importar pagos desde SGF, el sistema SHALL agruparlos automáticamente en Egresos (`egresos_cgu`) usando el folio de egreso que entrega SGF (`folio_egreso`) como clave natural —SGF ya agrupa sus pagos en egresos—. El Egreso generado automáticamente SHALL quedar marcado como tal (`generado_automaticamente`) y registrar el período del caso; su centro financiero (`cfinanciero_id`) —del que se deriva la jurisdicción/zona— SHALL poblarse cuando sea determinable a partir de la vinculación del caso a su proceso de adquisición. La agrupación automática SHALL poder ajustarse manualmente antes de enviar el Egreso a revisión, sin romper la trazabilidad ni los snapshots de origen de cada caso.

#### Scenario: Importación agrupa por folio de egreso de SGF
- **WHEN** se importan casos que comparten el mismo `folio_egreso` desde SGF
- **THEN** quedan asociados como items del mismo Egreso, marcado `generado_automaticamente`
- **AND** el `monto_total` del Egreso refleja la suma de sus pagos

#### Scenario: Casos con distinto folio de egreso quedan en Egresos separados
- **WHEN** los casos importados tienen folios de egreso distintos
- **THEN** se generan Egresos separados, uno por folio

#### Scenario: Un caso sin folio de egreso no se agrupa automáticamente
- **WHEN** se importa un caso sin `folio_egreso`
- **THEN** no se crea ni modifica ningún Egreso automáticamente

#### Scenario: Ajuste manual de la agrupación antes de revisar
- **WHEN** un usuario reasigna manualmente un caso a otro Egreso antes de enviarlo a revisión
- **THEN** el cambio se aplica sin alterar el `sgf_id`, los snapshots ni el historial del caso

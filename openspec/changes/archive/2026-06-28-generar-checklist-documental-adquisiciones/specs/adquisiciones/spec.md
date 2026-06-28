## ADDED Requirements

### Requirement: El checklist documental de un proceso de adquisición se resuelve con reglas reales por modalidad
El sistema SHALL mantener una matriz de `requisitos_documentales` concreta para el workflow "adquisiciones", asociada a un `conjunto_requisitos_documentales` propio, con reglas distintas según la `modalidad_id` del proceso (licitación pública, licitación privada, trato directo, convenio marco). El `tipo_documento` con código `CONTRATO` SHALL existir en el catálogo, dado que ya es referenciado por la transición `formalizar_contrato` del workflow de Adquisiciones.

#### Scenario: Seeder de requisitos documentales disponible
- **WHEN** se ejecuta el seeder de requisitos documentales de Adquisiciones
- **THEN** existen `tipos_documento` activos (incluyendo `CONTRATO`)
- **AND** existe un `conjunto_requisitos_documentales` para el workflow "adquisiciones"
- **AND** existen `requisitos_documentales` que varían según la modalidad

### Requirement: El detalle de un proceso de adquisición resuelve y muestra su checklist documental real
El sistema SHALL invocar la resolución del checklist documental (`ResolutorChecklistDocumentalProceso::resolve()`) al abrir el detalle de un `proceso_adquisicion`, usando el `conjunto_requisitos_documentales` de Adquisiciones, de modo que el checklist refleje los documentos exigibles según la modalidad, monto y estado actual del proceso.

#### Scenario: Abrir el detalle de un proceso con modalidad asignada genera un checklist no vacío
- **WHEN** un usuario abre el detalle de un `proceso_adquisicion` con una modalidad activa asignada
- **THEN** el backend resuelve o actualiza su `checklist_documental_proceso` usando las reglas de Adquisiciones
- **AND** la respuesta incluye al menos un item de checklist correspondiente a esa modalidad

#### Scenario: Distintas modalidades resuelven distintos documentos requeridos
- **WHEN** se abre el detalle de procesos con modalidades distintas (p. ej. trato directo vs. licitación pública)
- **THEN** cada uno resuelve el subconjunto de `requisitos_documentales` aplicable a su propia modalidad
- **AND** un proceso de trato directo no exige `BASES_LICITACION`

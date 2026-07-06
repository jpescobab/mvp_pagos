## Purpose

Esta capacidad cubre el modelo de dominio interno y el workflow de Adquisiciones: cada proceso de adquisición es gobernado por su propia definición de workflow a través de `TransicionWorkflowService`, reutilizando la infraestructura genérica de `workflow-core` y `documentos-expediente-variable` (proceso polimórfico, checklist documental resuelto por modalidad/monto/estado). No incluye todavía integración externa con Mercado Público ni HTTP/UI; es exclusivamente el dominio interno.

## Requirements

### Requirement: Cada proceso de adquisición tiene un Proceso de workflow propio
El sistema SHALL tratar cada `proceso_adquisicion` como un `sujeto` polimórfico individual de `Proceso`, gobernado por la definición de workflow `adquisiciones`. El estado interno del proceso SHALL gobernarse exclusivamente por `TransicionWorkflowService::execute()`.

#### Scenario: Crear un proceso de adquisición
- **WHEN** se crea un `proceso_adquisicion` con una modalidad, una unidad responsable (`ccosto_id`) y un objeto
- **THEN** se crea un `Proceso` asociado en el estado marcado como `es_inicial` del workflow "adquisiciones"
- **AND** el proceso queda vinculado mediante `sujeto_type`/`sujeto_id`

#### Scenario: Ejecutar una transición del workflow de adquisiciones
- **WHEN** un usuario con el permiso requerido ejecuta una transición disponible desde el estado actual de un proceso de adquisición
- **THEN** el `Proceso` transiciona al estado destino siguiendo las mismas reglas de `workflow-core` (permiso, comentario requerido, documentos requeridos)

### Requirement: La modalidad de adquisición gobierna el checklist documental
El sistema SHALL resolver el checklist documental de un proceso de adquisición reutilizando `requisitos_documentales` filtrados por su `modalidad_id`, sin lógica de negocio adicional específica de Adquisiciones.

#### Scenario: Resolver checklist según modalidad
- **WHEN** se abre el expediente de un proceso de adquisición con una modalidad asignada
- **THEN** el backend resuelve los `requisitos_documentales` aplicables a esa modalidad, monto y estado actual
- **AND** genera o actualiza el `ChecklistDocumentalProceso` del proceso, igual que cualquier otro módulo funcional

### Requirement: Catálogo de modalidades de adquisición disponible
El sistema SHALL mantener un catálogo de `modalidades_adquisicion` activas (licitación pública, licitación privada, trato directo, convenio marco) que todo `proceso_adquisicion` SHALL referenciar.

#### Scenario: Crear un proceso sin modalidad activa
- **WHEN** se intenta crear un `proceso_adquisicion` referenciando una modalidad inexistente o inactiva
- **THEN** el sistema rechaza la creación

### Requirement: Un proceso de adquisición expone sus casos de pago vinculados
El sistema SHALL permitir que un `proceso_adquisicion` consulte todos los `caso_pago_proveedor` que se hayan vinculado manualmente a él, sin que esto implique gobernar el workflow de esos casos.

#### Scenario: Ver casos de pago vinculados desde el detalle de una adquisición
- **WHEN** un usuario consulta el detalle de un `proceso_adquisicion` que tiene uno o más `caso_pago_proveedor` vinculados
- **THEN** el detalle incluye la lista de esos casos, identificados por su `sgf_id`
- **AND** la lista queda vacía si ningún caso ha sido vinculado todavía


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

### Requirement: Integración con Mercado Público como origen de evidencia, no de gobierno
El sistema SHALL permitir que una Orden de Compra de Mercado Público (capability `ordenes-compra-mercado-publico`) se vincule opcionalmente a un `proceso_adquisicion` existente, como evidencia externa trazable. Mercado Público SHALL NOT gobernar el workflow, los estados, los responsables ni las unidades internas de ningún `proceso_adquisicion`: esa vinculación es únicamente informativa y no dispara transiciones de `TransicionWorkflowService`. Los `proceso_adquisicion` SHALL seguir creándose y transicionando exclusivamente por los mecanismos internos ya definidos, con o sin una OC vinculada.

#### Scenario: Crear un proceso de adquisición sin OC vinculada
- **WHEN** se crea un `proceso_adquisicion`
- **THEN** no se exige ni se genera ningún vínculo con una `orden_compra_mercado_publico`

#### Scenario: Vincular una OC no altera el workflow del proceso
- **WHEN** se vincula una `orden_compra_mercado_publico` a un `proceso_adquisicion` existente
- **THEN** el estado del `Proceso` de ese `proceso_adquisicion` permanece sin cambios
- **AND** la vinculación queda registrada en auditoría como una acción independiente del workflow

#### Scenario: La verificación de una OC contra Mercado Público no requiere un proceso de adquisición
- **WHEN** un usuario busca, verifica o guarda una `orden_compra_mercado_publico` sin vincularla a ningún `proceso_adquisicion`
- **THEN** el sistema completa la operación normalmente, dejando el vínculo pendiente para más adelante

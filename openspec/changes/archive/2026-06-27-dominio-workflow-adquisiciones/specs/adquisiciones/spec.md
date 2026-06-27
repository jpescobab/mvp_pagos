## ADDED Requirements

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

### Requirement: No modelar integración externa todavía
El sistema SHALL NOT integrar con Mercado Público ni ningún otro sistema externo como origen de datos para Adquisiciones en este alcance. Los procesos de adquisición se crean internamente.

#### Scenario: Crear un proceso de adquisición sin snapshot externo
- **WHEN** se crea un `proceso_adquisicion`
- **THEN** no se genera ni se espera ningún `snapshot_datos_externos` asociado

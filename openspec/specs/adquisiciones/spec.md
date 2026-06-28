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

### Requirement: No modelar integración externa todavía
El sistema SHALL NOT integrar con Mercado Público ni ningún otro sistema externo como origen de datos para Adquisiciones en este alcance. Los procesos de adquisición se crean internamente.

#### Scenario: Crear un proceso de adquisición sin snapshot externo
- **WHEN** se crea un `proceso_adquisicion`
- **THEN** no se genera ni se espera ningún `snapshot_datos_externos` asociado


### Requirement: Un proceso de adquisición expone sus casos de pago vinculados
El sistema SHALL permitir que un `proceso_adquisicion` consulte todos los `caso_pago_proveedor` que se hayan vinculado manualmente a él, sin que esto implique gobernar el workflow de esos casos.

#### Scenario: Ver casos de pago vinculados desde el detalle de una adquisición
- **WHEN** un usuario consulta el detalle de un `proceso_adquisicion` que tiene uno o más `caso_pago_proveedor` vinculados
- **THEN** el detalle incluye la lista de esos casos, identificados por su `sgf_id`
- **AND** la lista queda vacía si ningún caso ha sido vinculado todavía

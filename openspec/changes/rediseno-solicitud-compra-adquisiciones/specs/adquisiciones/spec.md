## MODIFIED Requirements

### Requirement: Cada proceso de adquisición tiene un Proceso de workflow propio
El sistema SHALL tratar cada `proceso_adquisicion` como un `sujeto` polimórfico individual de `Proceso`, gobernado por la definición de workflow `adquisiciones`. El estado interno del proceso SHALL gobernarse exclusivamente por `TransicionWorkflowService::execute()`.

#### Scenario: Crear un proceso de adquisición
- **WHEN** se crea un `proceso_adquisicion` con sus antecedentes generales (unidad requirente, funcionario requirente, características, motivo de contratación, monto estimado) y la verificación de Convenio Marco
- **THEN** se crea un `Proceso` asociado en el estado marcado como `es_inicial` del workflow "adquisiciones"
- **AND** el proceso queda vinculado mediante `sujeto_type`/`sujeto_id`

#### Scenario: Ejecutar una transición del workflow de adquisiciones
- **WHEN** un usuario con el permiso requerido ejecuta una transición disponible desde el estado actual de un proceso de adquisición
- **THEN** el `Proceso` transiciona al estado destino siguiendo las mismas reglas de `workflow-core` (permiso, comentario requerido, documentos requeridos)

### Requirement: El checklist documental de un proceso de adquisición se resuelve con reglas reales por modalidad
El sistema SHALL mantener una matriz de `requisitos_documentales` concreta para el workflow "adquisiciones", asociada a un `conjunto_requisitos_documentales` propio, con reglas distintas según la `modalidad_id` del proceso (licitación pública, licitación privada, trato directo, convenio marco). El `tipo_documento` con código `CONTRATO` SHALL existir en el catálogo, dado que ya es referenciado por la transición `formalizar_contrato` del workflow de Adquisiciones. La modalidad `trato_directo` SHALL exigir además el `tipo_documento` `INFORME_JUSTIFICACION_TRATO_DIRECTO` como obligatorio, dado que un proceso solo llega a esa modalidad cuando la verificación de Convenio Marco se respondió "No".

#### Scenario: Seeder de requisitos documentales disponible
- **WHEN** se ejecuta el seeder de requisitos documentales de Adquisiciones
- **THEN** existen `tipos_documento` activos (incluyendo `CONTRATO` e `INFORME_JUSTIFICACION_TRATO_DIRECTO`)
- **AND** existe un `conjunto_requisitos_documentales` para el workflow "adquisiciones"
- **AND** existen `requisitos_documentales` que varían según la modalidad

#### Scenario: Trato directo exige el informe de justificación
- **WHEN** se resuelve el checklist documental de un `proceso_adquisicion` con modalidad `trato_directo`
- **THEN** el checklist incluye `INFORME_JUSTIFICACION_TRATO_DIRECTO` como documento obligatorio

### Requirement: Un proceso de adquisición se puede actualizar solo mientras está en borrador

El sistema SHALL permitir actualizar los datos de un `proceso_adquisicion` (código, antecedentes generales, unidad requirente, funcionario requirente, características, motivo de contratación, verificación de Convenio Marco, monto estimado) únicamente mientras su `Proceso` asociado esté en el estado `borrador`. Si el estado actual no es `borrador`, el sistema SHALL rechazar la actualización con una excepción de dominio y no modificar ningún dato. La modalidad destino SHALL derivarse de la verificación de Convenio Marco igual que en la creación, no ser un valor editable directo. La actualización SHALL ejecutarse dentro de una transacción y, cuando cambie la modalidad derivada o el monto estimado, SHALL sincronizar los campos `modalidad_id` y `monto` del `Proceso` asociado, de modo que el checklist documental —que se resuelve leyendo esos campos desde el `Proceso`— refleje los nuevos valores en su próxima resolución.

#### Scenario: Actualizar un proceso en borrador

- **WHEN** se actualiza un `proceso_adquisicion` cuyo `Proceso` está en estado `borrador`, con datos válidos
- **THEN** el sistema guarda los nuevos valores del `proceso_adquisicion`
- **AND** sincroniza `modalidad_id` y `monto` en el `Proceso` asociado

#### Scenario: Rechazar la actualización fuera de borrador

- **WHEN** se intenta actualizar un `proceso_adquisicion` cuyo `Proceso` ya no está en estado `borrador` (por ejemplo, `en_revision` o `publicada`)
- **THEN** el sistema rechaza la operación con una excepción de dominio
- **AND** no modifica ningún dato del `proceso_adquisicion` ni de su `Proceso`

#### Scenario: Cambiar la verificación de Convenio Marco re-resuelve el checklist

- **WHEN** se actualiza la verificación de Convenio Marco de un `proceso_adquisicion` en `borrador` (cambiando su modalidad derivada) y luego se resuelve nuevamente su checklist documental
- **THEN** el checklist refleja los requisitos de la nueva modalidad, no los de la anterior

## ADDED Requirements

### Requirement: La solicitud de compra registra antecedentes generales y un funcionario requirente
El sistema SHALL registrar en cada `proceso_adquisicion` sus antecedentes generales: fecha de inicio de la compra, nombre de la compra, un identificador de requerimiento opcional (referencia de texto libre, sin relación), la unidad requirente (`ccosto_id`), un funcionario requirente, las características del bien o servicio, el motivo de la contratación, un indicador de si la compra está en el Plan Anual de Compras (con el ID del PAC opcional cuando aplica) y un código BIP opcional. El funcionario requirente SHALL pertenecer a la unidad requirente (`ccosto_id`) seleccionada.

#### Scenario: Crear una solicitud con antecedentes completos
- **WHEN** se crea un `proceso_adquisicion` indicando fecha de inicio, nombre, unidad requirente, funcionario requirente perteneciente a esa unidad, características, motivo de contratación y monto estimado
- **THEN** el sistema crea el `proceso_adquisicion` con esos antecedentes

#### Scenario: Rechazar un funcionario requirente que no pertenece a la unidad elegida
- **WHEN** se envía un `funcionario_requirente_id` que pertenece a un `ccosto_id` distinto del enviado como unidad requirente
- **THEN** el sistema rechaza la creación o actualización con un error de validación

#### Scenario: El ID del PAC es opcional aunque la compra esté en el Plan de Compras
- **WHEN** se indica que la compra está en el Plan Anual de Compras sin enviar un ID del PAC
- **THEN** el sistema acepta la solicitud igualmente

### Requirement: El código de un proceso de adquisición se genera automáticamente
El sistema SHALL generar automáticamente un `codigo` único al crear un `proceso_adquisicion`. El `codigo` SHALL NOT ser un valor editable por el usuario en la creación.

#### Scenario: Crear una solicitud sin enviar código
- **WHEN** se crea un `proceso_adquisicion` sin especificar un `codigo`
- **THEN** el sistema asigna automáticamente un `codigo` único

#### Scenario: El código generado nunca colisiona
- **WHEN** se crean dos `proceso_adquisicion` sucesivos
- **THEN** cada uno recibe un `codigo` distinto

### Requirement: La modalidad de una solicitud de compra se deriva de la verificación de Convenio Marco
El sistema SHALL derivar el `modalidad_id` de un `proceso_adquisicion` a partir de una verificación Sí/No sobre si el bien o servicio está en Convenio Marco, en vez de recibirlo como una elección manual directa. Responder "Sí" SHALL fijar la modalidad `convenio_marco`. Responder "No" SHALL fijar la modalidad `trato_directo` y SHALL exigir, como parte del checklist documental obligatorio de esa modalidad, un informe de justificación (`INFORME_JUSTIFICACION_TRATO_DIRECTO`) de por qué no se usa el Convenio Marco disponible.

#### Scenario: Responder "Sí" fija Convenio Marco
- **WHEN** se crea un `proceso_adquisicion` respondiendo "Sí" a la verificación de Convenio Marco
- **THEN** el `proceso_adquisicion` queda con `modalidad_id` correspondiente a `convenio_marco`

#### Scenario: Responder "No" fija Trato Directo y exige informe de justificación
- **WHEN** se crea un `proceso_adquisicion` respondiendo "No" a la verificación de Convenio Marco
- **THEN** el `proceso_adquisicion` queda con `modalidad_id` correspondiente a `trato_directo`
- **AND** su checklist documental exige `INFORME_JUSTIFICACION_TRATO_DIRECTO` como obligatorio

### Requirement: El monto estimado de una solicitud de compra debe ser menor a 1.000 UTM
El sistema SHALL validar que el `monto_estimado` de un `proceso_adquisicion` sea menor al valor de 1.000 UTM vigente (resuelto mediante el indicador económico UTM), dado que este flujo de creación es específico para compras bajo ese umbral. El sistema SHALL rechazar la creación o actualización cuando el monto estimado alcance o supere ese umbral.

#### Scenario: Aceptar un monto bajo el umbral
- **WHEN** se crea un `proceso_adquisicion` con un `monto_estimado` menor a 1.000 UTM vigentes
- **THEN** el sistema acepta la solicitud

#### Scenario: Rechazar un monto que alcanza o supera el umbral
- **WHEN** se crea un `proceso_adquisicion` con un `monto_estimado` igual o mayor a 1.000 UTM vigentes
- **THEN** el sistema rechaza la creación con un error de validación

### Requirement: La aprobación de una solicitud de compra se registra mediante una transición de workflow
El sistema SHALL tratar la aprobación de la jefatura de la unidad requirente como la ejecución de una transición de workflow existente (`en_revision` → `publicada`), no como datos de texto libre capturados en el formulario de creación. El usuario y la fecha de quien aprueba SHALL quedar registrados en el historial de transiciones del `Proceso`, igual que cualquier otra transición.

#### Scenario: Aprobar una solicitud registra quién y cuándo
- **WHEN** un usuario con el permiso requerido ejecuta la transición que lleva un `proceso_adquisicion` de `en_revision` a `publicada`
- **THEN** el historial de transiciones registra ese usuario y la fecha de la aprobación

#### Scenario: No existe un campo de aprobador de texto libre
- **WHEN** se crea un `proceso_adquisicion`
- **THEN** el sistema no acepta ni almacena un nombre o cargo de aprobador como texto libre

### Requirement: Una solicitud de compra puede exportarse como PDF
El sistema SHALL permitir exportar un `proceso_adquisicion` como un documento PDF que incluya sus antecedentes generales, bajo demanda. Exportar el PDF SHALL NOT modificar el estado del `Proceso` ni ningún dato del `proceso_adquisicion`.

#### Scenario: Exportar una solicitud a PDF
- **WHEN** un usuario con permiso de consulta solicita el PDF de un `proceso_adquisicion`
- **THEN** el sistema genera y entrega un documento PDF con los antecedentes de esa solicitud

#### Scenario: Exportar no altera el proceso
- **WHEN** se exporta el PDF de un `proceso_adquisicion`
- **THEN** su estado y sus datos permanecen sin cambios

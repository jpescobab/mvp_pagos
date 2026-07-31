## Purpose

Esta capacidad cubre el modelo de dominio interno y el workflow de Adquisiciones: cada proceso de adquisición es gobernado por su propia definición de workflow a través de `TransicionWorkflowService`, reutilizando la infraestructura genérica de `workflow-core` y `documentos-expediente-variable` (proceso polimórfico, checklist documental resuelto por modalidad/monto/estado). No incluye todavía integración externa con Mercado Público ni HTTP/UI; es exclusivamente el dominio interno.
## Requirements
### Requirement: Cada proceso de adquisición tiene un Proceso de workflow propio
El sistema SHALL tratar cada `proceso_adquisicion` como un `sujeto` polimórfico individual de `Proceso`, gobernado por la definición de workflow `adquisiciones`. El estado interno del proceso SHALL gobernarse exclusivamente por `TransicionWorkflowService::execute()`.

#### Scenario: Crear un proceso de adquisición
- **WHEN** se crea un `proceso_adquisicion` con sus antecedentes generales (unidad requirente, funcionario requirente, características, motivo de contratación, monto estimado) y la verificación de Convenio Marco
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
El sistema SHALL mantener una matriz de `requisitos_documentales` concreta para el workflow "adquisiciones", asociada a un `conjunto_requisitos_documentales` propio, con reglas distintas según la `modalidad_id` del proceso (licitación pública, licitación privada, trato directo, convenio marco). El `tipo_documento` con código `CONTRATO` SHALL existir en el catálogo, dado que ya es referenciado por la transición `formalizar_contrato` del workflow de Adquisiciones. La modalidad `trato_directo` SHALL exigir además el `tipo_documento` `INFORME_JUSTIFICACION_TRATO_DIRECTO` como obligatorio, dado que un proceso solo llega a esa modalidad cuando la verificación de Convenio Marco se respondió "No".

#### Scenario: Seeder de requisitos documentales disponible
- **WHEN** se ejecuta el seeder de requisitos documentales de Adquisiciones
- **THEN** existen `tipos_documento` activos (incluyendo `CONTRATO` e `INFORME_JUSTIFICACION_TRATO_DIRECTO`)
- **AND** existe un `conjunto_requisitos_documentales` para el workflow "adquisiciones"
- **AND** existen `requisitos_documentales` que varían según la modalidad

#### Scenario: Trato directo exige el informe de justificación
- **WHEN** se resuelve el checklist documental de un `proceso_adquisicion` con modalidad `trato_directo`
- **THEN** el checklist incluye `INFORME_JUSTIFICACION_TRATO_DIRECTO` como documento obligatorio

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

### Requirement: Un proceso de adquisición expone sus compras de Mercado Público vinculadas
Un proceso de adquisición SHALL exponer las órdenes de compra y las licitaciones de Mercado Público que tienen su `proceso_adquisicion_id`. Para cada una, la exposición SHALL incluir al menos su identificador interno, su código, su estado en Mercado Público y su organismo comprador, de modo que puedan mostrarse y enlazarse a su detalle. El modelo `ProcesoAdquisicion` SHALL ofrecer relaciones para consultar tanto sus órdenes de compra como sus licitaciones de Mercado Público.

#### Scenario: El detalle expone las órdenes de compra vinculadas
- **WHEN** se consulta un proceso de adquisición que tiene órdenes de compra de Mercado Público vinculadas
- **THEN** la respuesta incluye esas órdenes de compra con su código, estado en Mercado Público y organismo comprador

#### Scenario: El detalle expone las licitaciones vinculadas
- **WHEN** se consulta un proceso de adquisición que tiene licitaciones de Mercado Público vinculadas
- **THEN** la respuesta incluye esas licitaciones con su código, estado en Mercado Público y organismo comprador

#### Scenario: Un proceso sin compras vinculadas expone colecciones vacías
- **WHEN** se consulta un proceso de adquisición sin órdenes de compra ni licitaciones vinculadas
- **THEN** ambas colecciones se exponen vacías, sin error

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

### Requirement: El monto estimado se calcula desde una moneda de compra y su paridad
El sistema SHALL registrar la moneda de compra (CLP, UF o USD) y el monto solicitado en esa moneda para cada `proceso_adquisicion`. Cuando la moneda es CLP, el `monto_estimado` SHALL ser igual al monto solicitado, sin paridad. Cuando la moneda es UF o USD, el sistema SHALL exigir una fecha de paridad y resolver la paridad vigente para esa fecha contra el indicador económico real correspondiente (mismo mecanismo que ya usa el Certificado de Disponibilidad Presupuestaria); el `monto_estimado` SHALL ser el resultado de multiplicar el monto solicitado por esa paridad. Si no existe un valor de paridad registrado para la fecha indicada, el sistema SHALL rechazar la creación o actualización.

#### Scenario: Monto en CLP no tiene paridad
- **WHEN** se crea un `proceso_adquisicion` con moneda de compra CLP y un monto solicitado
- **THEN** el `monto_estimado` es igual al monto solicitado, sin paridad asociada

#### Scenario: Monto en UF o USD se convierte usando la paridad de la fecha indicada
- **WHEN** se crea un `proceso_adquisicion` con moneda de compra UF o USD, un monto solicitado y una fecha de paridad con valor registrado
- **THEN** el sistema resuelve la paridad vigente para esa fecha
- **AND** el `monto_estimado` resulta de multiplicar el monto solicitado por esa paridad

#### Scenario: Rechazar cuando no hay paridad registrada para la fecha
- **WHEN** se crea un `proceso_adquisicion` con moneda de compra UF o USD y una fecha de paridad sin valor registrado
- **THEN** el sistema rechaza la creación con un error de validación

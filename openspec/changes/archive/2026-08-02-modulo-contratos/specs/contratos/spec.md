## ADDED Requirements

### Requirement: Crear un Contrato en estado Borrador
El sistema SHALL permitir crear un `Contrato` con `id_institucional` (el identificador que la institución usa para todo efecto sobre el contrato, distinto del `id` interno autoincremental y del `id_proceso_mp` de Mercado Público), `codigo` autogenerado (formato `CTR {correlativo}-{año}`, correlativo global autonumérico), `modalidad_compra` (`licitacion`, `trato_directo`, `fuera_de_portal`), `tipo_contrato` (`contrato`, `convenio_precio`, `orden_compra`, `arriendo`), `referencia`, `fecha_inicio_vigencia`, `fecha_fin_vigencia`, `proveedor_id` y opcionalmente `id_proceso_mp`, `materia`, `submateria`, `tiene_convenio_precio`. El `Contrato` SHALL quedar gobernado por su propio `Proceso` de workflow (`DefinicionWorkflow` código `contratos`) en estado inicial `borrador`.

#### Scenario: Creación exitosa
- **WHEN** un usuario con el permiso `contratos.crear` envía los datos obligatorios de un contrato nuevo, incluyendo un `id_institucional` que no existe todavía
- **THEN** el sistema crea el `Contrato`, su `Proceso` de workflow asociado en estado `borrador`, persiste el `id_institucional`, y asigna el `codigo` autogenerado

#### Scenario: Falta un campo obligatorio
- **WHEN** un usuario intenta crear un contrato sin `id_institucional`, `modalidad_compra`, `tipo_contrato`, `fecha_inicio_vigencia`, `fecha_fin_vigencia` o `proveedor_id`
- **THEN** el sistema rechaza la creación con un mensaje de validación, sin crear ningún registro

### Requirement: id_institucional es único e indexado para consultas rápidas
El sistema SHALL exigir que `id_institucional` sea único entre todos los `Contrato` y SHALL mantenerlo indexado en la base de datos para que la búsqueda por ese identificador sea eficiente, incluyendo desde el listado de Contratos.

#### Scenario: Duplicado rechazado al crear
- **WHEN** un usuario intenta crear un `Contrato` con un `id_institucional` que ya existe en otro contrato
- **THEN** el sistema rechaza la creación con un mensaje de validación, sin crear ningún registro

#### Scenario: Duplicado rechazado al editar
- **WHEN** un usuario intenta editar un `Contrato` en `borrador` asignándole el `id_institucional` de otro contrato existente
- **THEN** el sistema rechaza la edición, sin aplicar el cambio

#### Scenario: Búsqueda por id_institucional en el listado
- **WHEN** un usuario busca en el listado de Contratos usando un valor de `id_institucional`
- **THEN** el sistema devuelve el `Contrato` correspondiente usando el índice único de la columna, sin recorrer la tabla completa

### Requirement: Resolver el proveedor del Contrato por RUT normalizado
El sistema SHALL resolver el proveedor de un `Contrato` reutilizando `Proveedor::normalizarRut()` para deduplicar por RUT, siguiendo el mismo criterio de completado de campos vacíos usado en la resolución de proveedor de Órdenes de Compra de Mercado Público (nunca sobrescribir un campo ya cargado).

#### Scenario: El proveedor ya existe
- **WHEN** se crea un `Contrato` con un RUT de proveedor que ya existe en el catálogo (normalizado)
- **THEN** el sistema vincula el proveedor existente sin duplicarlo

#### Scenario: El proveedor no existe
- **WHEN** se crea un `Contrato` con un RUT de proveedor que no existe en el catálogo
- **THEN** el sistema crea el proveedor con estado `activo` y los datos disponibles

### Requirement: Editar un Contrato solo mientras esté en Borrador
El sistema SHALL permitir editar los datos de un `Contrato` (incluyendo sus ítems de convenio de precio) únicamente mientras su `Proceso` esté en estado `borrador`. Un `Contrato` en estado `aprobado` es inmutable.

#### Scenario: Edición permitida en borrador
- **WHEN** un usuario con el permiso `contratos.editar` modifica un `Contrato` cuyo `Proceso` está en `borrador`
- **THEN** el sistema aplica los cambios

#### Scenario: Edición rechazada tras aprobación
- **WHEN** un usuario intenta modificar un `Contrato` cuyo `Proceso` está en `aprobado`
- **THEN** el sistema rechaza la edición sin aplicar ningún cambio

### Requirement: Transicionar el estado de un Contrato exclusivamente vía TransicionWorkflowService
El sistema SHALL cambiar el estado de un `Contrato` (`borrador → pendiente`, `pendiente → aprobado`, `pendiente → rechazado`) únicamente a través de `TransicionWorkflowService::execute()`, validando permisos, documentos obligatorios del checklist y registrando auditoría, notificación e historial. Ningún controlador, job, seeder o componente React SHALL actualizar el estado directamente.

#### Scenario: Enviar a pendiente
- **WHEN** un usuario con el permiso requerido envía un `Contrato` en `borrador` a revisión
- **THEN** el sistema ejecuta la transición `pendiente` vía `TransicionWorkflowService::execute()`, validando el checklist documental correspondiente

#### Scenario: Aprobar
- **WHEN** un usuario con el permiso `contratos.aprobar` aprueba un `Contrato` en `pendiente`
- **THEN** el sistema ejecuta la transición `aprobado`, que queda como estado terminal e inmutable

#### Scenario: Rechazar
- **WHEN** un usuario con el permiso `contratos.rechazar` rechaza un `Contrato` en `pendiente`
- **THEN** el sistema ejecuta la transición `rechazado`

### Requirement: Ítems de convenio de precio de un Contrato
El sistema SHALL permitir registrar, únicamente cuando `tiene_convenio_precio = true` y el `Contrato` esté en `borrador`, uno o más `ContratoItemConvenioPrecio` con `descripcion`, `unidad_medida`, `precio_unitario`, `moneda` y opcionalmente `vigente_desde`/`vigente_hasta`, como referencia consultable del precio pactado.

#### Scenario: Agregar un ítem de convenio
- **WHEN** un usuario agrega un ítem de convenio de precio a un `Contrato` con `tiene_convenio_precio = true` en `borrador`
- **THEN** el sistema crea el `ContratoItemConvenioPrecio` vinculado al contrato

#### Scenario: Contrato sin convenio de precios
- **WHEN** un usuario intenta agregar un ítem de convenio de precio a un `Contrato` con `tiene_convenio_precio = false`
- **THEN** el sistema rechaza la operación

### Requirement: Vínculo opcional entre un Contrato y un proceso de adquisición o licitación de Mercado Público
El sistema SHALL permitir vincular y desvincular manualmente un `Contrato` a un `proceso_adquisicion` o a una `licitacion_mercado_publico` existente, sin que ese vínculo dispare ninguna transición de workflow en ninguno de los dos procesos.

#### Scenario: Vincular un Contrato a un proceso de adquisición
- **WHEN** un usuario con el permiso requerido vincula un `Contrato` a un `proceso_adquisicion` existente
- **THEN** el sistema guarda la referencia y registra la acción en auditoría
- **AND** el estado del `Proceso` de ninguna de las dos entidades cambia como consecuencia de este vínculo

#### Scenario: Contrato sin vínculo
- **WHEN** se consulta un `Contrato` que nunca fue vinculado a un `proceso_adquisicion` ni a una `licitacion_mercado_publico`
- **THEN** el sistema lo muestra sin vínculo, sin error

### Requirement: Checklist documental del Contrato
El sistema SHALL resolver los documentos obligatorios de un `Contrato` según su estado, reutilizando `requisitos_documentales`/`conjunto_requisitos_documentales`/`ResolutorChecklistDocumentalProceso`, y SHALL reutilizar el `tipo_documento` código `CONTRATO` ya existente en el catálogo. React SHALL renderizar el checklist recibido del backend, sin hardcodearlo.

#### Scenario: Checklist para transición a aprobado
- **WHEN** un usuario intenta transicionar un `Contrato` de `pendiente` a `aprobado` sin haber adjuntado los documentos obligatorios del checklist
- **THEN** el sistema rechaza la transición indicando los documentos faltantes

### Requirement: Permisos del módulo Contratos
El sistema SHALL exponer los permisos `contratos.crear`, `contratos.editar`, `contratos.aprobar`, `contratos.rechazar`, `contratos.ver` y `contratos.vincular_pago`, en convención `modulo_accion.verbo`, y SHALL condicionar toda acción de creación, edición, transición, vínculo de pago y visualización de `Contrato` a que el usuario autenticado posea el permiso correspondiente.

#### Scenario: Usuario sin permiso de creación
- **WHEN** un usuario sin el permiso `contratos.crear` intenta crear un `Contrato`
- **THEN** el sistema rechaza la acción y registra el evento en `security_audit_logs`

### Requirement: Generar automáticamente el calendario de pago (cuotas) de un Contrato
El sistema SHALL permitir marcar un `Contrato` con `tiene_calendario_pago = true` y una `periodicidad_pago` (`mensual`, `bimestral`, `trimestral`, `semestral`, `anual`, `unica`), y SHALL generar automáticamente las `ContratoCuota` correspondientes espaciando las fechas de vencimiento según la periodicidad indicada entre `fecha_inicio_vigencia` y `fecha_fin_vigencia` del contrato, distribuyendo `monto_total` entre las cuotas generadas (ajustando la última cuota por cualquier resto de la división).

#### Scenario: Generación exitosa de calendario mensual
- **WHEN** un usuario con el permiso `contratos.editar` marca `tiene_calendario_pago = true` con `periodicidad_pago = mensual` y `monto_total` en un `Contrato` en `borrador` con vigencia de 12 meses
- **THEN** el sistema genera 12 `ContratoCuota`, una por mes, cada una con `estado = pendiente` y el monto resultante de dividir `monto_total` entre 12

#### Scenario: Periodicidad única
- **WHEN** un usuario marca `tiene_calendario_pago = true` con `periodicidad_pago = unica`
- **THEN** el sistema genera una sola `ContratoCuota` con `fecha_vencimiento = fecha_fin_vigencia` y `monto = monto_total`

#### Scenario: Falta monto_total
- **WHEN** un usuario intenta generar el calendario de pago sin haber indicado `monto_total`
- **THEN** el sistema rechaza la generación con un mensaje de validación

### Requirement: Editar cuotas individuales mientras el Contrato esté en Borrador
El sistema SHALL permitir editar la `fecha_vencimiento` y el `monto` de una `ContratoCuota` individual únicamente mientras el `Proceso` del `Contrato` esté en estado `borrador`, para cubrir calendarios que no calzan con una división exacta.

#### Scenario: Edición permitida en borrador
- **WHEN** un usuario con el permiso `contratos.editar` modifica la fecha o el monto de una `ContratoCuota` de un `Contrato` en `borrador`
- **THEN** el sistema aplica el cambio a esa cuota sin afectar las demás

#### Scenario: Edición rechazada tras aprobación
- **WHEN** un usuario intenta modificar una `ContratoCuota` de un `Contrato` cuyo `Proceso` está en `aprobado`
- **THEN** el sistema rechaza la edición

### Requirement: Vincular una cuota del calendario de pago a un caso de pago a proveedores
El sistema SHALL permitir vincular y desvincular manualmente una `ContratoCuota` a un `caso_pago_proveedor` existente, mediante una acción explícita distinta de cualquier transición de workflow, siguiendo el mismo patrón que el vínculo `caso_pago_proveedor.proceso_adquisicion_id` de Pago de Proveedores. Al vincular una cuota a un `caso_pago_proveedor`, el sistema SHALL actualizar el `estado` de la cuota a `pagada`; al desvincularla, SHALL volver a `pendiente`.

#### Scenario: Vincular una cuota a un caso de pago
- **WHEN** un usuario con el permiso `contratos.vincular_pago` vincula una `ContratoCuota` en estado `pendiente` a un `caso_pago_proveedor` existente
- **THEN** el sistema guarda la referencia, actualiza el `estado` de la cuota a `pagada`, y registra la acción en auditoría
- **AND** el estado del `Proceso` del `caso_pago_proveedor` no cambia como consecuencia de este vínculo

#### Scenario: Desvincular una cuota
- **WHEN** un usuario con el permiso `contratos.vincular_pago` desvincula una `ContratoCuota` de su `caso_pago_proveedor`
- **THEN** el sistema quita la referencia, vuelve el `estado` de la cuota a `pendiente`, y registra la acción en auditoría

#### Scenario: Usuario sin permiso
- **WHEN** un usuario sin el permiso `contratos.vincular_pago` intenta vincular o desvincular una `ContratoCuota`
- **THEN** el sistema rechaza la acción y registra el evento en `security_audit_logs`

### Requirement: Estado "vencida" es derivado, no persistido
El sistema SHALL calcular el estado "vencida" de una `ContratoCuota` en el momento de la consulta (cuando `estado = pendiente` y `fecha_vencimiento` es anterior a la fecha actual), sin persistir ese estado como columna ni requerir un job programado que lo actualice.

#### Scenario: Cuota pendiente con fecha pasada
- **WHEN** se consulta una `ContratoCuota` con `estado = pendiente` cuya `fecha_vencimiento` ya pasó
- **THEN** el sistema la presenta como "vencida" en la respuesta, sin modificar la columna `estado` almacenada

#### Scenario: Cuota pagada con fecha pasada
- **WHEN** se consulta una `ContratoCuota` con `estado = pagada` cuya `fecha_vencimiento` ya pasó
- **THEN** el sistema la presenta como "pagada", nunca como "vencida"

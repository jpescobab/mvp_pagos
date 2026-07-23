## ADDED Requirements

### Requirement: El estado del proveedor gobierna su disponibilidad para operar
Un `proveedor` SHALL tener exactamente uno de tres estados: `borrador` (registro identificado que todavía no está habilitado para operar), `activo` (habilitado) o `inactivo` (dado de baja). El sistema SHALL ofrecer únicamente proveedores en estado `activo` allí donde se elige un proveedor **para operar** —al crear un proceso de adquisición y al asociar un cliente medidor—. El catálogo de proveedores (listado, búsqueda y detalle) SHALL mostrar los proveedores en cualquiera de los tres estados, porque es la pantalla desde la que se administra el catálogo y desde la que hay que completar un borrador. Un proveedor creado por una vía distinta del formulario de alta —por ejemplo, resuelto automáticamente desde una importación externa— SHALL quedar en estado `activo`.

#### Scenario: Selector operativo ofrece solo proveedores activos
- **WHEN** un usuario abre el formulario de creación de un proceso de adquisición o el de asociación de un cliente medidor
- **THEN** la lista de proveedores ofrecida incluye únicamente los que están en estado `activo`
- **AND** no incluye los que están en estado `borrador` ni en estado `inactivo`

#### Scenario: El catálogo muestra los tres estados
- **WHEN** un usuario con permiso `core_institucional.administrar` abre el listado de proveedores
- **THEN** se muestran los proveedores en estado `borrador`, `activo` e `inactivo`
- **AND** cada uno indica su estado de forma distinguible

#### Scenario: Proveedor creado desde una importación externa
- **WHEN** el sistema resuelve o crea un proveedor a partir de datos recibidos de un sistema externo
- **THEN** ese proveedor queda en estado `activo`

## MODIFIED Requirements

### Requirement: Registrar un proveedor institucional nuevo
El sistema SHALL permitir a un usuario con el permiso `core_institucional.administrar` registrar un nuevo `proveedor` con RUT y nombre obligatorios, y de forma opcional: giro, tipo de contribuyente, rubros, contacto comercial (nombre, cargo, teléfono), domicilio (dirección, región, comuna), datos bancarios (banco, tipo de cuenta, número de cuenta, condición de pago, moneda, correo para pagos, documento de respaldo) y notas internas. El alta SHALL ofrecer dos acciones —registrar el proveedor como `activo` o guardarlo como `borrador`— que SHALL exigir los mismos campos obligatorios y aplicar la misma validación, diferenciándose únicamente en el estado con el que nace el registro. El sistema SHALL rechazar el alta si el RUT ya existe en el catálogo, cualquiera sea el estado del registro existente.

#### Scenario: Alta exitosa con datos mínimos
- **WHEN** un usuario con permiso `core_institucional.administrar` envía el formulario de alta con solo RUT y nombre válidos
- **THEN** se crea el `proveedor` con esos datos, en estado `activo`, y el resto de los campos nuevos en `null`

#### Scenario: Alta exitosa con todos los datos
- **WHEN** un usuario con permiso `core_institucional.administrar` envía el formulario de alta completando identificación, clasificación, contacto, domicilio y datos bancarios, incluyendo un documento de respaldo
- **THEN** se crea el `proveedor` con todos los campos informados y el documento de respaldo se guarda en almacenamiento privado, quedando su ruta asociada al proveedor

#### Scenario: Guardar como borrador
- **WHEN** un usuario con permiso `core_institucional.administrar` completa el RUT y la razón social y elige guardar como borrador
- **THEN** se crea el `proveedor` en estado `borrador` con los datos informados
- **AND** ese proveedor no se ofrece en los selectores donde se elige un proveedor para operar

#### Scenario: Guardar como borrador exige los mismos campos obligatorios
- **WHEN** un usuario intenta guardar como borrador sin haber completado el RUT o la razón social
- **THEN** el sistema rechaza la operación con el mismo error de validación que en el registro normal y no crea ningún registro

#### Scenario: RUT duplicado
- **WHEN** un usuario envía el formulario de alta con un `rutproveedor` que ya existe en el catálogo
- **THEN** el sistema rechaza la operación con un error de validación en el campo RUT y no crea un registro duplicado

#### Scenario: Sin permiso para registrar
- **WHEN** un usuario autenticado sin el permiso `core_institucional.administrar` intenta acceder al formulario de alta o enviar el envío
- **THEN** el sistema responde con un error de autorización y no crea el proveedor

### Requirement: Formulario de alta por pasos con resumen de completitud
El sistema SHALL presentar el alta y edición de proveedor como un formulario dividido en los pasos Identificación, Clasificación, Contacto, Domicilio y Datos bancarios, mostrados como un stepper con separadores tipo flecha entre cada paso y navegables libremente, junto a un panel de resumen que muestra una vista previa de los datos ingresados y el porcentaje de completitud del registro según cuántos de esos pasos tienen sus campos mínimos completos. El sistema SHALL mostrar las acciones de guardado de forma permanente junto a los controles de navegación entre pasos, habilitadas únicamente cuando los campos obligatorios (RUT y razón social) están completos, sin exigir que los campos opcionales (nullable) de los demás pasos estén completos. El sistema SHALL NOT enviar los datos al backend paso por paso; el envío ocurre una sola vez al confirmar. Ninguna acción del formulario SHALL mostrarse deshabilitada con la indicación "Disponible próximamente".

#### Scenario: Navegación libre entre pasos
- **WHEN** un usuario que está completando el formulario de alta hace clic en un paso distinto al actual
- **THEN** el formulario muestra los campos de ese paso sin perder los valores ya ingresados en los demás pasos

#### Scenario: Resumen refleja el progreso
- **WHEN** un usuario completa los campos mínimos de un paso (por ejemplo, al menos un rubro en Clasificación)
- **THEN** el panel de resumen marca ese paso como completo y actualiza el porcentaje de completitud del registro

#### Scenario: Errores de validación llevan al paso correspondiente
- **WHEN** el envío del formulario es rechazado por errores de validación en campos de un paso distinto al actualmente visible
- **THEN** el formulario cambia automáticamente al primer paso que contiene un campo con error y lo muestra resaltado

#### Scenario: Acciones de guardado deshabilitadas con datos obligatorios incompletos
- **WHEN** un usuario no ha completado el RUT o la razón social, sin importar en qué paso del formulario se encuentre
- **THEN** tanto la acción de registrar como la de guardar como borrador se muestran deshabilitadas

#### Scenario: Acciones de guardado habilitadas con datos obligatorios completos
- **WHEN** un usuario completa el RUT y la razón social, aunque los demás pasos (clasificación, contacto, domicilio, datos bancarios) queden sin completar
- **THEN** las acciones de guardado se habilitan, visibles desde cualquier paso del formulario

### Requirement: Editar un proveedor existente
El sistema SHALL permitir a un usuario con el permiso `core_institucional.administrar` editar cualquier campo de un proveedor ya registrado, incluido su estado, mediante el mismo formulario por pasos usado en el alta, precargado con los datos actuales. La edición SHALL permitir mover el proveedor a cualquiera de los tres estados, en particular promover un `borrador` a `activo` para habilitarlo. Cambiar el estado SHALL ser una edición del dato maestro y SHALL NOT constituir una transición de workflow. El sistema SHALL rechazar la edición si el nuevo RUT coincide con el de otro proveedor distinto del que se está editando. Si se reemplaza el documento de respaldo bancario, el sistema SHALL descartar el archivo anterior.

#### Scenario: Edición exitosa
- **WHEN** un usuario con permiso `core_institucional.administrar` envía el formulario de edición con cambios válidos
- **THEN** el proveedor queda actualizado con los nuevos valores

#### Scenario: Promover un borrador a activo
- **WHEN** un usuario con permiso `core_institucional.administrar` edita un proveedor en estado `borrador` y lo cambia a `activo`
- **THEN** el proveedor queda en estado `activo`
- **AND** pasa a ofrecerse en los selectores donde se elige un proveedor para operar

#### Scenario: Estado inválido
- **WHEN** un usuario envía el formulario de edición con un estado que no es `borrador`, `activo` ni `inactivo`
- **THEN** el sistema rechaza la operación con un error de validación y no modifica el proveedor

#### Scenario: RUT en conflicto con otro proveedor
- **WHEN** un usuario edita un proveedor y cambia su RUT a uno que ya pertenece a otro proveedor distinto
- **THEN** el sistema rechaza la operación con un error de validación en el campo RUT y no modifica ningún registro

#### Scenario: Reemplazo del documento de respaldo
- **WHEN** un usuario adjunta un nuevo documento de respaldo bancario al editar un proveedor que ya tenía uno
- **THEN** el sistema guarda el nuevo documento y descarta el anterior

#### Scenario: Sin permiso para editar
- **WHEN** un usuario autenticado sin el permiso `core_institucional.administrar` intenta acceder al formulario de edición o enviarlo
- **THEN** el sistema responde con un error de autorización y no modifica el proveedor

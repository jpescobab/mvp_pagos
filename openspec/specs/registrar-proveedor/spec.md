# Spec: registrar-proveedor

## Purpose

Permitir el alta de proveedores institucionales con los datos que Finanzas necesita para pagarles (identificación tributaria, clasificación por rubros, contacto comercial, domicilio y datos bancarios de la cuenta de destino), mediante un formulario por pasos con panel de resumen y completitud.

## Requirements

### Requirement: Registrar un proveedor institucional nuevo
El sistema SHALL permitir a un usuario con el permiso `core_institucional.administrar` registrar un nuevo `proveedor` con RUT y nombre obligatorios, y de forma opcional: giro, tipo de contribuyente, rubros, contacto comercial (nombre, cargo, teléfono), domicilio (dirección, región, comuna), datos bancarios (banco, tipo de cuenta, número de cuenta, condición de pago, moneda, correo para pagos, documento de respaldo) y notas internas. El sistema SHALL rechazar el alta si el RUT ya existe en el catálogo.

#### Scenario: Alta exitosa con datos mínimos
- **WHEN** un usuario con permiso `core_institucional.administrar` envía el formulario de alta con solo RUT y nombre válidos
- **THEN** se crea el `proveedor` con esos datos, `activo` en verdadero por defecto, y el resto de los campos nuevos en `null`

#### Scenario: Alta exitosa con todos los datos
- **WHEN** un usuario con permiso `core_institucional.administrar` envía el formulario de alta completando identificación, clasificación, contacto, domicilio y datos bancarios, incluyendo un documento de respaldo
- **THEN** se crea el `proveedor` con todos los campos informados y el documento de respaldo se guarda en almacenamiento privado, quedando su ruta asociada al proveedor

#### Scenario: RUT duplicado
- **WHEN** un usuario envía el formulario de alta con un `rutproveedor` que ya existe en el catálogo
- **THEN** el sistema rechaza la operación con un error de validación en el campo RUT y no crea un registro duplicado

#### Scenario: Sin permiso para registrar
- **WHEN** un usuario autenticado sin el permiso `core_institucional.administrar` intenta acceder al formulario de alta o enviar el envío
- **THEN** el sistema responde con un error de autorización y no crea el proveedor

### Requirement: Formulario de alta por pasos con resumen de completitud
El sistema SHALL presentar el alta y edición de proveedor como un formulario dividido en los pasos Identificación, Clasificación, Contacto, Domicilio y Datos bancarios, mostrados como un stepper con separadores tipo flecha entre cada paso y navegables libremente, junto a un panel de resumen que muestra una vista previa de los datos ingresados y el porcentaje de completitud del registro según cuántos de esos pasos tienen sus campos mínimos completos. El sistema SHALL mostrar el botón de guardar (registrar o guardar cambios, según corresponda) de forma permanente junto a los controles de navegación entre pasos, habilitado únicamente cuando los campos obligatorios (RUT y razón social) están completos, sin exigir que los campos opcionales (nullable) de los demás pasos estén completos. El sistema SHALL NOT enviar los datos al backend paso por paso; el envío ocurre una sola vez al confirmar el registro.

#### Scenario: Navegación libre entre pasos
- **WHEN** un usuario que está completando el formulario de alta hace clic en un paso distinto al actual
- **THEN** el formulario muestra los campos de ese paso sin perder los valores ya ingresados en los demás pasos

#### Scenario: Resumen refleja el progreso
- **WHEN** un usuario completa los campos mínimos de un paso (por ejemplo, al menos un rubro en Clasificación)
- **THEN** el panel de resumen marca ese paso como completo y actualiza el porcentaje de completitud del registro

#### Scenario: Errores de validación llevan al paso correspondiente
- **WHEN** el envío del formulario es rechazado por errores de validación en campos de un paso distinto al actualmente visible
- **THEN** el formulario cambia automáticamente al primer paso que contiene un campo con error y lo muestra resaltado

#### Scenario: Botón de guardar deshabilitado con datos obligatorios incompletos
- **WHEN** un usuario no ha completado el RUT o la razón social, sin importar en qué paso del formulario se encuentre
- **THEN** el botón de guardar se muestra deshabilitado

#### Scenario: Botón de guardar habilitado con datos obligatorios completos
- **WHEN** un usuario completa el RUT y la razón social, aunque los demás pasos (clasificación, contacto, domicilio, datos bancarios) queden sin completar
- **THEN** el botón de guardar se habilita, visible desde cualquier paso del formulario

### Requirement: Clasificación de proveedor por rubros
El sistema SHALL permitir seleccionar cero o más rubros institucionales predefinidos para un proveedor, y SHALL NOT limitar la selección a un único rubro.

#### Scenario: Selección de múltiples rubros
- **WHEN** un usuario marca más de un rubro en el paso de Clasificación y confirma el alta
- **THEN** el proveedor queda registrado con todos los rubros seleccionados

### Requirement: Datos bancarios y documento de respaldo del proveedor
El sistema SHALL permitir registrar el banco, tipo de cuenta, número de cuenta, condición de pago, moneda y correo para pagos de un proveedor, junto a un documento de respaldo bancario opcional (PDF o imagen, hasta 8 MB), almacenado en almacenamiento privado y nunca expuesto por una URL pública directa.

#### Scenario: Documento de respaldo válido
- **WHEN** un usuario adjunta un PDF o imagen de hasta 8 MB como documento de respaldo bancario
- **THEN** el sistema guarda el archivo y asocia su ruta al proveedor

#### Scenario: Documento de respaldo inválido
- **WHEN** un usuario adjunta un archivo que no es PDF ni imagen, o que supera 8 MB
- **THEN** el sistema rechaza el envío con un error de validación en el campo del documento

### Requirement: Ver el detalle de un proveedor registrado
El sistema SHALL permitir a un usuario con el permiso `core_institucional.administrar` ver el detalle completo de un `proveedor` registrado, incluyendo identificación tributaria, clasificación, contacto comercial, domicilio, datos bancarios y notas internas. El sistema SHALL indicar si el proveedor tiene un documento de respaldo bancario adjunto, sin exponer un enlace de descarga directa.

#### Scenario: Ver el detalle completo
- **WHEN** un usuario con permiso `core_institucional.administrar` abre el detalle de un proveedor registrado
- **THEN** la vista muestra todos sus campos, incluyendo los que quedaron en `null` por no haberse completado en el alta

#### Scenario: Sin permiso para ver el detalle
- **WHEN** un usuario autenticado sin el permiso `core_institucional.administrar` intenta abrir el detalle de un proveedor
- **THEN** el sistema responde con un error de autorización

### Requirement: Editar un proveedor existente
El sistema SHALL permitir a un usuario con el permiso `core_institucional.administrar` editar cualquier campo de un proveedor ya registrado, mediante el mismo formulario por pasos usado en el alta, precargado con los datos actuales. El sistema SHALL rechazar la edición si el nuevo RUT coincide con el de otro proveedor distinto del que se está editando. Si se reemplaza el documento de respaldo bancario, el sistema SHALL descartar el archivo anterior.

#### Scenario: Edición exitosa
- **WHEN** un usuario con permiso `core_institucional.administrar` envía el formulario de edición con cambios válidos
- **THEN** el proveedor queda actualizado con los nuevos valores

#### Scenario: RUT en conflicto con otro proveedor
- **WHEN** un usuario edita un proveedor y cambia su RUT a uno que ya pertenece a otro proveedor distinto
- **THEN** el sistema rechaza la operación con un error de validación en el campo RUT y no modifica ningún registro

#### Scenario: Reemplazo del documento de respaldo
- **WHEN** un usuario adjunta un nuevo documento de respaldo bancario al editar un proveedor que ya tenía uno
- **THEN** el sistema guarda el nuevo documento y descarta el anterior

#### Scenario: Sin permiso para editar
- **WHEN** un usuario autenticado sin el permiso `core_institucional.administrar` intenta acceder al formulario de edición o enviarlo
- **THEN** el sistema responde con un error de autorización y no modifica el proveedor

### Requirement: Eliminar un proveedor sin relaciones activas
El sistema SHALL permitir a un usuario con el permiso `core_institucional.administrar` eliminar (soft delete) un proveedor que no tenga clientes medidores, casos de pago, facturas ni procesos de adquisición asociados. El sistema SHALL rechazar la eliminación de un proveedor que sí tenga alguna de esas relaciones, indicando cuál lo impide.

#### Scenario: Eliminación exitosa sin relaciones
- **WHEN** un usuario con permiso `core_institucional.administrar` elimina un proveedor que no tiene clientes medidores, casos de pago, facturas ni procesos de adquisición asociados
- **THEN** el proveedor queda eliminado (soft delete) y deja de aparecer en el catálogo

#### Scenario: Eliminación rechazada por relaciones activas
- **WHEN** un usuario intenta eliminar un proveedor que tiene al menos un cliente medidor, caso de pago, factura o proceso de adquisición asociado
- **THEN** el sistema rechaza la eliminación e indica qué relación la impide, sin eliminar el proveedor

#### Scenario: Sin permiso para eliminar
- **WHEN** un usuario autenticado sin el permiso `core_institucional.administrar` intenta eliminar un proveedor
- **THEN** el sistema responde con un error de autorización y no elimina el proveedor

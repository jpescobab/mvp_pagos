## ADDED Requirements

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
El sistema SHALL presentar el alta de proveedor como un formulario dividido en los pasos Identificación, Clasificación, Contacto, Domicilio y Datos bancarios, navegables libremente, junto a un panel de resumen que muestra una vista previa de los datos ingresados y el porcentaje de completitud del registro según cuántos de esos pasos tienen sus campos mínimos completos. El sistema SHALL NOT enviar los datos al backend paso por paso; el envío ocurre una sola vez al confirmar el registro.

#### Scenario: Navegación libre entre pasos
- **WHEN** un usuario que está completando el formulario de alta hace clic en un paso distinto al actual
- **THEN** el formulario muestra los campos de ese paso sin perder los valores ya ingresados en los demás pasos

#### Scenario: Resumen refleja el progreso
- **WHEN** un usuario completa los campos mínimos de un paso (por ejemplo, al menos un rubro en Clasificación)
- **THEN** el panel de resumen marca ese paso como completo y actualiza el porcentaje de completitud del registro

#### Scenario: Errores de validación llevan al paso correspondiente
- **WHEN** el envío del formulario es rechazado por errores de validación en campos de un paso distinto al actualmente visible
- **THEN** el formulario cambia automáticamente al primer paso que contiene un campo con error y lo muestra resaltado

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

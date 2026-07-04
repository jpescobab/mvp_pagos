## MODIFIED Requirements

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

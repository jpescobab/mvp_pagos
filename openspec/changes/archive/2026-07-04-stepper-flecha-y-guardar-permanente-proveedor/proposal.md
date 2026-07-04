## Why

Los pasos del formulario de alta/edición de proveedor (Identificación, Clasificación, Contacto, Domicilio, Datos bancarios) se veían como pestañas sueltas, sin comunicar que es un proceso secuencial de izquierda a derecha. Además, el botón de guardar solo aparecía al llegar al último paso, obligando a navegar hasta el final para saber si el registro ya se podía guardar.

## What Changes

- Los pasos del formulario pasan a mostrarse como un stepper con separadores tipo flecha (`›`) entre cada paso, y un círculo numerado (o con check si el paso ya está completo) junto a cada etiqueta, para comunicar visualmente el flujo de izquierda a derecha.
- El botón de guardar ("Registrar proveedor" / "Guardar cambios" según el modo) pasa a mostrarse de forma permanente en el pie del formulario, junto a "Cancelar" y "Anterior"/"Siguiente", sin importar en qué paso esté el usuario.
- El botón de guardar permanece deshabilitado hasta que los campos obligatorios (RUT y razón social — los únicos que el backend exige) estén completos; los campos opcionales (nullable) no lo bloquean.

## Capabilities

### New Capabilities
(ninguna)

### Modified Capabilities
- `registrar-proveedor`: se ajusta el requirement "Formulario de alta por pasos con resumen de completitud" para reflejar el stepper con flechas y el botón de guardar permanente.

## Impact

- **Frontend**: `resources/js/components/maestros/proveedor-formulario.tsx` (usado por `create.tsx` y `edit.tsx`, sin cambios en esos wrappers).

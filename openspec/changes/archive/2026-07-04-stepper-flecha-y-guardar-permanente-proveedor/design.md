## Context

`ProveedorFormulario` ya calculaba `pasosCompletos` (un booleano por paso, usado por el panel de resumen) y ya reemplazaba el botón "Siguiente" por el de guardar solo en el último paso. El cambio pedido es puramente de presentación: no se tocan reglas de validación del backend ni el contrato de datos enviado.

## Goals / Non-Goals

**Goals:**
- Que el stepper se lea de forma evidente como un proceso de izquierda a derecha.
- Que el usuario pueda intentar guardar desde cualquier paso, con el botón deshabilitado hasta que RUT y razón social estén completos.

**Non-Goals:**
- Cambiar qué se considera "obligatorio" — sigue siendo únicamente RUT y razón social, igual que ya validaba `StoreProveedorRequest`/`UpdateProveedorRequest`. Los demás campos de `pasosCompletos` (rubros, contacto, domicilio, datos bancarios) siguen siendo solo indicadores de progreso en el panel "Resumen", no condiciones para habilitar el guardado.

## Decisions

- **Separadores con `ChevronRight` entre `TabsTrigger`, no un componente de stepper nuevo**: se mantiene `Tabs`/`TabsList`/`TabsTrigger` de Radix (navegación por teclado, estado activo, todo ya integrado con `pasoActivo`) y solo se inserta un ícono decorativo entre cada trigger dentro del mismo `TabsList`. Evita reemplazar la base accesible ya probada por un stepper hecho a mano.
- **Círculo numerado con check al completarse**: reutiliza exactamente la misma condición `pasosCompletos[paso.key]` que ya alimentaba el ícono de check en la versión anterior; solo se le agrega el número como estado "pendiente" en vez de no mostrar nada.
- **Condición para habilitar el guardado sigue siendo `pasosCompletos.identificacion`**: es la misma condición que ya gobernaba el botón antes de este cambio (identificación = RUT + nombre no vacíos), que coincide exactamente con los únicos campos `required` de `StoreProveedorRequest`/`UpdateProveedorRequest`. No hizo falta una condición nueva.
- **"Siguiente" pasa a `variant="outline"`**: al quedar el botón de guardar visible todo el tiempo como acción primaria, "Siguiente"/"Anterior" quedan como navegación secundaria (outline) para no competir visualmente con la acción de guardar.

## Risks / Trade-offs

- [Riesgo] Mostrar el botón de guardar siempre visible podría tentar a un usuario a guardar sin revisar los pasos opcionales. → Mitigación: el panel "Resumen" con la barra de completitud sigue visible en paralelo, mostrando qué pasos faltan aunque no bloqueen el guardado.

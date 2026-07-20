## Context

La página Importaciones SGF lista `trabajos_integracion` del sistema externo SGF vía `ImportacionSgfController@index`, con paginación y búsqueda por texto (`q`). No tiene filtro de estado; el menú de acciones de cada fila no tiene ícono.

El pedido original nombraba un filtro por defecto en estado `importada_desde_sgf`. Investigar el código confirmó que ese valor no existe en `trabajos_integracion` (que solo admite `en_progreso`/`completado`/`error`/`huerfano`) — es el estado inicial del workflow interno de `caso_pago_proveedor` (`Proceso.estadoActual.codigo`, vía `estados_workflow`), una entidad distinta con su propio listado y filtro (`pago-proveedores/casos`). Se confirmó directamente con el usuario que el filtro pedido aquí debe aplicar sobre `trabajos_integracion.estado`, mostrando por defecto los que no terminaron en `completado`.

## Goals / Non-Goals

**Goals:**
- Reducir el ruido del listado por defecto, mostrando solo trabajos que aún requieren atención (no `completado`).
- Permitir ver explícitamente todos los estados o uno puntual, incluido `completado`.
- Dar una vía visual (ícono) más reconocible para navegar al detalle de una fila.

**Non-Goals:**
- No cambia el estado de workflow de `casos_pago_proveedor` ni su listado/filtro en `pago-proveedores/casos`.
- No introduce un enum de base de datos ni validación estricta de los valores de `estado` — sigue siendo un string libre.
- No cambia autorización del listado (sigue accesible a cualquier usuario autenticado).
- No toca `show.tsx`, el badge de estado, ni los controladores que inician importaciones.

## Decisions

1. **Filtro inline en el controlador, sin Service nuevo.** El precedente análogo (`ListadoCasoPagoProveedorService`) extrae un Service porque filtra sobre una relación (`whereHas('proceso.estadoActual', ...)`). Acá `estado` es una columna propia de `trabajos_integracion` — un `where()` directo de una línea, de complejidad comparable a la búsqueda por `q` que ya vive inline en el mismo método. Extraer un Service para esto sería sobre-ingeniería para el tamaño real del cambio.

2. **El sentinel de "no completadas" nunca se envía como query param.** Igual que el patrón ya usado en `pago-proveedores/casos/index.tsx` (`FILTRO_PENDIENTES`): el valor por defecto del `<Select>` es un sentinel local (`no_completadas`) que, al estar seleccionado, no agrega `estado` a la URL — deja que el backend aplique su propia regla por defecto (`estado != 'completado'`). Esto mantiene "sin parámetro" como la única fuente de verdad del comportamiento por defecto, sin duplicar el valor mágico en el frontend.

3. **Las 4 estados concretos quedan seleccionables explícitamente, incluido `completado`.** Un usuario debe poder pedir expresamente "solo completados" (por ejemplo para auditar una corrida puntual), no solo "todos" o "no completados".

4. **Merge explícito de `q` y `estado` en ambos manejadores (debounce de búsqueda y cambio de Select).** A diferencia de `casos/index.tsx` (sin buscador) o de `usuarios/index.tsx` (que resuelve esto con una función `navegar(partial)` más genérica por tener 4 filtros), acá conviven solo 2 filtros — alcanza con mergear inline en cada handler sin introducir una abstracción nueva.

5. **Sin validación estricta de `estado` contra los 4 valores conocidos.** Mismo comportamiento que el precedente de `casos`: un valor no reconocido simplemente no matchea ninguna fila (0 resultados), sin error. Agregar un `Rule::in` o un Form Request sería una superficie nueva no pedida ni justificada por un riesgo real (Eloquent parametriza la comparación; no hay riesgo de inyección).

## Risks / Trade-offs

- **[Riesgo]** Tests existentes que crean trabajos `completado` y esperan verlos en el listado sin filtro se rompen con el nuevo default. → Mitigación: ajustar esos tests agregando `estado=todos` explícito (detallado en tasks.md), antes de dar el cambio por completo.
- **[Riesgo]** Un enlace guardado o compartido sin `estado` en la URL mostrará menos filas que antes. → Es el comportamiento buscado (reducir ruido); "Todos los estados" queda a un clic y la URL lo refleja para poder compartir ese enlace puntual.
- **[Trade-off]** Las etiquetas del `<Select>` ("En progreso", "Completado", etc.) son texto fijo en el frontend, igual que el precedente del badge de estado (`importacion-estado-badge.tsx`), que también hardcodea los mismos 4 valores. Si apareciera un 5° valor de estado, ninguno de los dos lugares lo reconocería automáticamente — aceptable porque los 4 valores están fijados por el código que los asigna (`IntegracionExternaService`), no por datos externos.

## Open Questions

Ninguna — la ambigüedad original (qué "estado" filtrar) se resolvió directamente con el usuario antes de proponer este change.

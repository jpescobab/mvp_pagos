## Why

El listado de "Importaciones SGF" siempre muestra todos los trabajos de importación sin distinción, obligando a revisar filas ya `completado` para encontrar las que realmente necesitan atención (en curso, con error, o huérfanas). Además, el menú de acciones de cada fila no tiene ícono junto a "Ver detalle", inconsistente con la iconografía usada en el resto de la interfaz.

## What Changes

- Agregar un ícono `Eye` (lucide-react) junto al texto "Ver detalle" en el menú de acciones de cada fila del listado de Importaciones SGF. Cambio puramente visual, sin efecto en el comportamiento.
- Agregar un filtro de estado (`<Select>`) en el header del listado de Importaciones SGF, que por defecto excluye los trabajos en estado `completado` (mostrando `en_progreso`, `error` y `huerfano`), con opción de elegir "Todos los estados" o un estado puntual.
- El filtro opera sobre `trabajos_integracion.estado` (el estado propio del trabajo/corrida de importación) — no sobre el estado del workflow interno del `caso_pago_proveedor` resultante, que es una entidad distinta con su propio filtro en otra página y queda fuera de este cambio.
- El filtro de estado y el buscador de texto existente (`q`) se combinan (AND) y se preservan mutuamente al navegar.

## Capabilities

### New Capabilities

Ninguna — este cambio no introduce una capability nueva, solo modifica el comportamiento de una existente.

### Modified Capabilities

- `consulta-importaciones-sgf`: el Requirement "Listar las corridas de importación SGF" incorpora un filtro por estado del trabajo de importación, con exclusión por defecto de los trabajos `completado`, override explícito para verlos todos, y filtro por un estado puntual.

## Impact

- `app/Http/Controllers/Sgf/ImportacionSgfController.php`: nueva lectura del parámetro `estado` en `index()` y nueva prop Inertia `filtroEstado`.
- `resources/js/pages/sgf/importaciones/index.tsx`: ícono en el menú de acciones, `<Select>` de estado en el header, y lógica de merge entre el filtro de texto y el de estado.
- `tests/Feature/Sgf/ConsultarImportacionesSgfTest.php`: 3 tests existentes ajustados para no verse afectados por el nuevo filtro por defecto, más 4 tests nuevos.
- `openspec/specs/consulta-importaciones-sgf/spec.md`: spec delta sobre el Requirement de listado.
- Sin impacto en `sgf/importaciones/show.tsx`, en el badge de estado (`importacion-estado-badge.tsx`), en los controladores que inician importaciones (`ImportarCasosPendientesSgfController`, `ImportarCasosGrupoPagoOperacionesSgfController`), ni en rutas/Wayfinder (el cambio es solo de querystring, igual que `q` hoy).

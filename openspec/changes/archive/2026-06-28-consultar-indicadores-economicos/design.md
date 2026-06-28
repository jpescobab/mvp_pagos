## Context

`IndicadorEconomico` y su importador/selector (tarea 04, ya archivada) nunca tuvieron como alcance una capa HTTP — su spec original (`indicadores-economicos-cmf-sii`) solo cubre "importar" y "seleccionar para cálculos". No hay precedente en el repo de un controlador para datos de referencia/maestros puros (no existe ningún controlador para `tablas-maestras-institucionales` tampoco), así que esta es la primera vez que se expone una tabla de referencia vía HTTP.

## Goals / Non-Goals

**Goals:**
- Página de solo lectura para consultar los valores ya importados de UF/USD/UTM/UTA/IPC, paginada y filtrable por tipo.
- Cualquier usuario autenticado puede verla — son datos de referencia institucional, no datos sensibles ni ligados a la jerarquía `instituciones -> jurisdicciones -> cfinancieros -> ccostos`.

**Non-Goals:**
- Disparar una importación manual desde la UI (el job/importador ya corre en su propio ciclo; agregar un botón "importar ahora" requeriría decidir manejo de rate-limiting/duplicados con la API de la CMF, fuera de alcance de este change).
- Editar o eliminar un indicador desde la UI — su fuente de verdad es la CMF; permitir mutación manual rompería la trazabilidad (`source_payload`, `hash` implícito vía importación) que exige el harness.
- Selector de fecha/periodo para "qué indicador aplica a tal fecha" (eso ya existe como `IndicadorEconomicoSelector`, usado por servicios internos, no por humanos navegando la UI).

## Decisions

**Sin Policy ni permiso nuevo — solo middleware `auth`.** A diferencia de `Proceso`/`EgresoCgu` (que sí tienen Policies porque su visibilidad puede depender de roles/jerarquía en el futuro), los indicadores económicos son una tabla de referencia global sin relación con `ccosto`/`institucion`. Introducir una Policy trivial que siempre retorna `true` sería ceremonia sin beneficio — `Gate::after` no necesita auditar accesos a datos no sensibles y sin jerarquía. Si en el futuro se requiere restringir esto, se agrega una Policy entonces.

**Filtro por `tipo` vía query string (`?tipo=UF`), sin tabs duras en frontend.** El backend valida `tipo` contra el enum real (`UF`, `USD`, `UTM`, `UTA`, `IPC`) y por defecto (sin filtro) muestra todos paginados ordenados por `fecha_valor`/`periodo` descendente — consistente con "React no hardcodea reglas de negocio", aunque aquí el "enum" es estable y no configurable como sí lo son los requisitos documentales.

**Orden por `COALESCE(fecha_valor, periodo)` no es necesario — basta con `orderByDesc('id')`.** Los indicadores se insertan en orden de importación; ordenar por `id` descendente ya refleja "más reciente importado primero" sin necesitar lógica condicional por tipo (UF/USD usan `fecha_valor`, UTM/UTA/IPC usan `periodo`, y mezclarlos en un único `ORDER BY` requeriría un `COALESCE` con tipos de columna distintos).

## Risks / Trade-offs

- **[Riesgo] Sin paginación eficiente si el volumen de UF diario crece mucho (un registro por día desde que el job corre)** → **Mitigación**: aceptado; se usa `paginate()` estándar igual que el resto del sistema, y el filtro por tipo ya acota el volumen por vista.
- **[Riesgo] Mostrar `source_payload` completo (JSON crudo de la CMF) podría ser ruidoso en la UI** → **Mitigación**: el `IndicadorEconomicoResource` no expone `source_payload` en el listado, solo los campos relevantes para lectura humana (`tipo`, `fecha_valor`, `periodo`, `valor`, `fuente`, `vigente_desde`, `vigente_hasta`).

## Migration Plan

Sin cambios de esquema ni datos — solo un controlador, una ruta, un Resource y una página nuevos.

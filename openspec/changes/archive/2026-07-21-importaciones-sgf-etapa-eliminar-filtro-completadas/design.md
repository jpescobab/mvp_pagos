## Context

El listado de Importaciones SGF lo sirve `ImportacionSgfController@index`, que pagina `TrabajoIntegracion` del sistema `SGF` y los serializa con `ImportacionSgfResource`. Hoy el Resource solo incluye `snapshots`/`resumen` cuando la relación `snapshotsDatosExternos` está cargada (caso del detalle), así que el índice no trae ninguna información de los casos producidos. El default del filtro (`estado === null`) excluye `completado` (`ImportacionSgfController` línea 35).

Una corrida enlaza a sus casos indirectamente: `trabajo_integracion` → `snapshots_datos_externos` (por `trabajo_integracion_id`), y cada snapshot tiene `referencia_externa = sgf_id`, que enlaza a `CasoPagoProveedor` por `sgf_id` (no hay FK directa trabajo→caso). El estado de workflow del caso vive en `caso.proceso.estadoActual` (código + nombre), con un orden definido en la definición de workflow `pago_proveedores`.

`TrabajoIntegracion` tiene relaciones `snapshotsDatosExternos`, `ejecucionesAutomatizacionNavegador` y `solicitudesApiExternas`. La capa transversal ya audita mediante `SecurityAuditLog`.

## Goals / Non-Goals

**Goals:**
- Mostrar, por corrida, el desglose de etapas del workflow de los casos que produjo, calculado en el backend sin N+1 por página.
- Permitir eliminar corridas que no produjeron trazabilidad (sin snapshots y no `en_progreso`), auditando la eliminación, con permiso dedicado.
- Invertir el filtro por defecto a "solo completadas", conservando el resto de filtros.

**Non-Goals:**
- No se borra trazabilidad: nunca snapshots, casos, procesos ni auditoría preexistente. No se elimina una corrida con snapshots.
- No se agrega soft delete ni cambios de esquema.
- No se toca el detalle (`show`) más allá de reutilizar patrones; su payload ya incluye estado por caso.
- No se cambia el workflow ni `TransicionWorkflowService`.

## Decisions

### 1. Agregación de etapas en un Presenter/Service, en una sola pasada por página
Se introduce un servicio de presentación (p. ej. `app/Services/Sgf/ImportacionesSgfPresenter` o extensión del flujo del controller vía Service) que, dada la página de `TrabajoIntegracion`:
1. Recolecta los `trabajo_integracion_id` de la página y consulta en bloque sus `snapshots_datos_externos` (id de trabajo + `referencia_externa`).
2. Con el conjunto de `sgf_id` únicos, hace **una** consulta `CasoPagoProveedor::whereIn('sgf_id', …)->with('proceso.estadoActual')`.
3. Construye, por trabajo, el desglose `{estado_codigo, estado_nombre, cantidad}[]` agrupando los casos por estado y ordenando por el orden del estado en el workflow.

`ImportacionSgfResource` expone `desglose_estados` a partir de un mapa inyectado (mismo patrón que `withCasos`), para no consultar dentro del Resource. El controller queda liviano: pagina y delega el armado del payload al Presenter.

Alternativa descartada: cargar `snapshotsDatosExternos.???` y resolver casos por Resource fila a fila — sería N+1 y metería lógica de negocio en el Resource.

Nota de rendimiento (lección del harness): el cruce caso↔snapshot es por `sgf_id` (string), no por FK; se resuelve con dos consultas en bloque (snapshots de la página, casos por `sgf_id`), no una por corrida. Se agrega índice solo si `EXPLAIN` lo justifica; `snapshots_datos_externos.referencia_externa` y `casos_pago_proveedor.sgf_id` ya se consultan por igualdad — verificar antes de proponer cualquier índice.

### 2. Elegibilidad de borrado: sin snapshots y no en progreso
Una corrida es elegible para eliminar sólo si `snapshotsDatosExternos()->doesntExist()` y `estado !== 'en_progreso'`. Esto cubre error/huérfana/completada-sin-elementos y excluye cualquier corrida que haya dejado evidencia. La elegibilidad se calcula en backend y se expone al frontend (`eliminable: bool`) para condicionar la UI; la guardia real se re-valida en el Service al borrar (no confiar en el cliente).

Alternativa descartada: permitir borrar completadas desvinculando snapshots/casos — viola la regla del harness de no eliminar trazabilidad; el usuario eligió explícitamente la variante segura.

### 3. Borrado transaccional + auditoría; alcance limitado a artefactos del propio intento
`EliminarImportacionSgfService::eliminar(TrabajoIntegracion $trabajo, User $user)`:
1. Revalida la guardia (sin snapshots, no `en_progreso`); si falla, lanza una excepción de dominio y no borra nada.
2. En una `DB::transaction`: borra ejecuciones de automatización (y sus pasos, por cascada o explícito), solicitudes API del trabajo, y el `trabajo_integracion`.
3. Registra un `SecurityAuditLog` de la eliminación (actor, id de corrida, tipo, estado) — se **audita la acción**, no se borra auditoría.

El controller `EliminarImportacionSgfController@destroy` es delgado: `Gate::authorize('eliminarImportacionSgf', $trabajo)`, delega en el Service, y redirige con flash de éxito/error.

### 4. Autorización: permiso dedicado + policy
Permiso nuevo `integraciones_sgf.eliminar_importacion` (convención `modulo_accion.verbo`), sembrado en `RolesAndPermissionsSeeder` y asignado a `superadmin` y `jefe_finanzas`. Se agrega una policy/Gate `eliminarImportacionSgf` sobre `TrabajoIntegracion` que exige el permiso. El permiso se comparte al frontend por el resolver de permisos existente (superadmin recibe todos). La UI muestra "Eliminar" solo si el usuario tiene el permiso y la corrida es `eliminable`.

### 5. Filtro por defecto = completadas
Backend: `index` con `estado === null` filtra `where('estado', 'completado')`. Se mantiene un valor de filtro explícito "no completadas" para el comportamiento anterior (`whereIn('estado', ['en_progreso','error','huerfano'])`), "todos" (sin filtro de estado) y los puntuales. Frontend: el `<Select>` por defecto muestra "Completadas"; se ajustan las constantes (hoy `FILTRO_NO_COMPLETADAS` es el default) para que el default mapee a completadas y se agregue la opción "No completadas".

## Risks / Trade-offs

- **[Cruce caso↔snapshot por sgf_id string]** → Dos consultas en bloque por página, no por fila; se mide con query log antes de cerrar. Si un `sgf_id` produjo más de un caso históricamente (no debería: un sgf_id = un caso), el desglose los contaría a ambos; se asume la invariante del harness "un sgf_id = un caso".
- **[Borrado físico irreversible]** → Mitigado porque solo aplica a corridas sin trazabilidad (ningún snapshot/caso), la acción se audita, y la guardia se revalida server-side. No hay pérdida de evidencia porque, por definición, no había evidencia asociada.
- **[Cambio del default del filtro sorprende a usuarios acostumbrados al comportamiento anterior]** → Se documenta como BREAKING de comportamiento; el filtro "No completadas" replica exactamente la vista anterior.
- **[Cascadas de borrado de ejecuciones/solicitudes]** → Verificar las FK de `ejecuciones_automatizacion_navegador`, sus pasos y `solicitudes_api_externas` hacia `trabajo_integracion`: si están en `cascade`, basta borrar el trabajo; si no, borrarlas explícitamente en el Service. Confirmar con el esquema real antes de implementar.

## Migration Plan

Sin migraciones de datos. Despliegue: seeder de permiso (`RolesAndPermissionsSeeder` idempotente), build de frontend, deploy backend. El permiso nuevo se agrega vía seeder; invalidar la caché de permisos compartidos de los usuarios afectados (o dejar que expire por TTL). Rollback: revertir el change; no hay datos que restaurar.

## Open Questions

Ninguna pendiente: presentación (desglose por etapa), alcance del borrado (solo corridas sin trazabilidad) y default del filtro (completadas) fueron confirmados por el usuario. Queda por verificar en implementación el modo de cascada de las FK de artefactos del trabajo (decisión mecánica, no de producto).

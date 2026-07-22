## 1. Desglose de etapas por corrida (backend)

- [x] 1.1 Crear un Presenter/Service en `app/Services/Sgf/` (p. ej. `ImportacionesSgfPresenter`) que, dada una colección/página de `TrabajoIntegracion`, calcule el desglose de etapas sin N+1: (a) consulta en bloque los `snapshots_datos_externos` de esos trabajos (trabajo_id + `referencia_externa`), (b) una sola consulta `CasoPagoProveedor::whereIn('sgf_id', …)->with('proceso.estadoActual')`, (c) agrupa por trabajo y estado, devolviendo `{estado_codigo, estado_nombre, cantidad}[]` ordenado por el orden del estado en el workflow. Corrida sin casos → desglose vacío.
- [x] 1.2 Exponer `desglose_estados` y `eliminable` en `app/Http/Resources/Sgf/ImportacionSgfResource.php` a partir de mapas inyectados (mismo patrón que `withCasos`), sin consultar dentro del Resource.
- [x] 1.3 En `ImportacionSgfController@index`, delegar en el Presenter para armar el payload de la página con el desglose y la elegibilidad; mantener el controlador liviano (solo paginación + delegación).

## 2. Filtro por defecto = completadas (backend)

- [x] 2.1 En `ImportacionSgfController@index`, cambiar el default del filtro: sin `estado` explícito → `where('estado', 'completado')`. Agregar el valor de filtro "no completadas" (`whereIn('estado', ['en_progreso','error','huerfano'])`), conservar "todos" (sin filtro de estado) y los estados puntuales. El término de búsqueda sigue combinándose con el filtro.

## 3. Eliminar corrida sin trazabilidad (backend)

- [x] 3.1 Verificar en el esquema las FK hacia `trabajos_integracion` de `ejecuciones_automatizacion_navegador` (+ sus pasos) y `solicitudes_api_externas` (cascade o no), para decidir borrado por cascada o explícito.
- [x] 3.2 Crear `app/Services/Integraciones/EliminarImportacionSgfService.php` con `eliminar(TrabajoIntegracion $trabajo, User $user)`: revalida la guardia (sin `snapshotsDatosExternos` y `estado !== 'en_progreso'`); si falla, lanza excepción de dominio sin borrar nada. En `DB::transaction`: borra ejecuciones+pasos y solicitudes API del trabajo (según 3.1) y el `trabajo_integracion`; registra un `SecurityAuditLog` de la eliminación. NUNCA borra snapshots, casos, procesos ni auditoría preexistente.
- [x] 3.3 Agregar el permiso `integraciones_sgf.eliminar_importacion` en `RolesAndPermissionsSeeder` y asignarlo a `superadmin` y `jefe_finanzas` (no a `administrativo_finanzas`).
- [x] 3.4 Agregar la autorización: policy method `eliminarImportacionSgf` sobre `TrabajoIntegracion` (o Gate) que exige `integraciones_sgf.eliminar_importacion`. Registrar la policy si corresponde.
- [x] 3.5 Crear `app/Http/Controllers/Sgf/EliminarImportacionSgfController.php` (`destroy`) delgado: `Gate::authorize('eliminarImportacionSgf', $trabajoIntegracion)`, delega en el Service, redirige con flash de éxito/error (traduciendo la excepción de guardia a un toast de error).
- [x] 3.6 Agregar la ruta `DELETE sgf/importaciones/{trabajoIntegracion}` en `routes/sgf.php` (nombre `sgf.importaciones.destroy`) y regenerar Wayfinder (`php artisan wayfinder:generate --with-form`).

## 4. Frontend

- [x] 4.1 En `resources/js/types/sgf.ts`, agregar los tipos del desglose (`{ estado_codigo, estado_nombre, cantidad }[]`) y los campos `desglose_estados` y `eliminable` en `ImportacionSgf`.
- [x] 4.2 En `resources/js/pages/sgf/importaciones/index.tsx`, agregar una columna "Etapa del proceso" que renderice el desglose (ej. chips "N Nombre etapa"); corrida sin casos muestra "—" / "Sin casos".
- [x] 4.3 Agregar el ítem "Eliminar" al `DropdownMenu` de acciones, visible solo si el usuario tiene `integraciones_sgf.eliminar_importacion` y `importacion.eliminable` es true; para corridas no elegibles, mostrarlo deshabilitado con tooltip explicativo ("tiene casos/snapshots asociados"). Con confirmación antes de borrar; usar `router.delete` con el helper tipado de Wayfinder (no URL hardcodeada) y refrescar el listado.
- [x] 4.4 Ajustar el `<Select>` de estado: default "Completadas" (mapea al filtro por defecto del backend), agregar opción "No completadas" (comportamiento anterior), conservar "Todos los estados" y los puntuales. Actualizar constantes y el valor por defecto para que no reintroduzca el filtro viejo.

## 5. Tests

- [x] 5.1 Feature: el listado incluye `desglose_estados` correcto para una corrida con casos en varias etapas (conteo por estado, orden de workflow) y desglose vacío para una corrida sin casos.
- [x] 5.2 Feature: el listado no dispara N+1 desmedido al calcular el desglose (verificar con conteo de queries / `DB::getQueryLog` acotado por página).
- [x] 5.3 Feature: eliminar una corrida en `error` sin snapshots la borra (junto a sus artefactos) y registra un `SecurityAuditLog`; los snapshots/casos de otras corridas no se tocan.
- [x] 5.4 Feature: eliminar una corrida con snapshots/casos es rechazado y no borra nada; eliminar una `en_progreso` es rechazado; sin el permiso `integraciones_sgf.eliminar_importacion` la acción es denegada.
- [x] 5.5 Feature: el listado por defecto (sin filtro) devuelve solo `completado`; "no completadas" devuelve `en_progreso`/`error`/`huerfano`; "todos" devuelve todos; el filtro se combina con la búsqueda por tipo/usuario.

## 6. Validación y cierre

- [x] 6.1 `vendor/bin/pint --dirty --format agent` sobre los PHP tocados.
- [x] 6.2 `composer test` (config:clear + lint:check + types:check + Pest) y `npm run types:check` + `npm run lint:check` para el frontend.
- [x] 6.3 Revisar los controllers tocados contra la regla de controladores livianos; confirmar que la eliminación nunca borra snapshots/casos/auditoría y que queda auditada; verificar en la pantalla real que el desglose, el borrado condicionado y el default del filtro se comportan como se especificó.

## 1. Clasificador presupuestario — ampliar a Subtítulo 29 y 31

- [x] 1.1 En `database/seeders/ItemsSeeder.php`, agregar (vía `Item::firstOrCreate(['codigo' => ...], [...])`, mismo patrón ya usado) los ítems confirmados por evidencia real: `2904` "Mobiliario y Otros", `2905` "Máquinas y Equipos de Oficina" (Subtítulo 29 — Adquisición de Activos No Financieros), y `31` "Iniciativas de Inversión" (Subtítulo 31), con sus descripciones oficiales.
- [x] 1.2 En `database/seeders/AsignacionesSeeder.php`, agregar las asignaciones necesarias para llegar a los códigos de catálogo confirmados (`2904000000`, `2905001000`, `3102004000`), resolviendo el `item` por `codigo` (mismo patrón ya usado).
- [x] 1.3 En `database/seeders/CatalogosSeeder.php`, agregar los catálogos confirmados por evidencia real: `2904000000` "Mobiliario", `2905001000` "Máquinas y Equipos de Oficina", `3102004000` "Obras Civiles" — mismo patrón `firstOrCreate` por `codigo`.
- [x] 1.4 Ejecutar `php artisan db:seed --class=ItemsSeeder && php artisan db:seed --class=AsignacionesSeeder && php artisan db:seed --class=CatalogosSeeder` localmente y verificar que no rompen los datos ya sembrados de Subtítulo 22 (los seeders son aditivos vía `firstOrCreate`).

## 2. Activar el sistema externo CGU

- [x] 2.1 En `database/seeders/IntegracionesSeeder.php`, cambiar la fila `['codigo' => 'CGU', ...]` de `'activo' => false` a `'activo' => true`.

## 3. Migraciones y modelos

- [x] 3.1 Migración `create_planes_tarea_table`: `id`, `codigo` (string, unique), `nombre`, `activo` (boolean, default true), `timestamps`, soft deletes (mismo patrón que `Item`/`Asignacion`/`Catalogo`).
- [x] 3.2 Migración `create_presupuestos_table`: `id`, `cfinanciero_id` (fk `cfinancieros`), `catalogo_id` (fk `catalogos`), `plan_tarea_id` (fk `planes_tarea`), `anio` (unsignedSmallInteger), `monto_asignado` (decimal 14,2), `importacion_presupuesto_id` (fk, nullable), `timestamps`; índice único compuesto `(cfinanciero_id, catalogo_id, plan_tarea_id, anio)`.
- [x] 3.3 Migración `create_importaciones_presupuesto_table`: `id`, `nro_version` (string), `anio` (unsignedSmallInteger), `estado` (string, default `pending`), `total_recibidos`/`total_creados`/`total_actualizados`/`total_omitidos`/`total_fallidos` (unsignedInteger, default 0), `errores`/`advertencias` (json nullable), `iniciado_en`/`finalizado_en` (timestamp nullable), `creado_por_user_id` (fk `users`, nullable), `snapshot_datos_externo_id` (fk `snapshots_datos_externos`, nullable), `timestamps` — mismo shape que `indicadores_economicos_importaciones` (`IndicadorEconomicoImportacion`).
- [x] 3.4 Modelo `app/Models/Presupuesto/PlanTarea.php`: `fillable = ['codigo', 'nombre', 'activo']`, cast `activo => boolean`, `hasMany(Presupuesto::class)`.
- [x] 3.5 Modelo `app/Models/Presupuesto/Presupuesto.php`: `fillable` con los campos de 3.2, casts `monto_asignado => decimal:2`, `anio => integer`; `belongsTo(Cfinanciero::class)`, `belongsTo(Catalogo::class)`, `belongsTo(PlanTarea::class)`, `belongsTo(ImportacionPresupuesto::class)`.
- [x] 3.6 Modelo `app/Models/Presupuesto/ImportacionPresupuesto.php`: `fillable` con los campos de 3.3, casts `errores => array`, `advertencias => array`, `iniciado_en`/`finalizado_en => datetime`; `hasMany(Presupuesto::class)`; `belongsTo(User::class, 'creado_por_user_id')`; `belongsTo(SnapshotDatosExterno::class)`; métodos `marcarComoRunning()` y `marcarComoFinalizada(string $estado, array $conteos = [])` (mismo patrón que `IndicadorEconomicoImportacion`).

## 4. Lector de Excel y servicio de importación

- [x] 4.1 `app/Services/Presupuesto/LectorExcelPresupuestoCgu.php`: recibe una ruta de archivo, usa `PhpOffice\PhpSpreadsheet\IOFactory::load()`, valida que los encabezados de la primera fila calcen exactamente con `Nro.Versión, Catalogo, Descripción, P.Pptario., U.Ejecutora, PROG, SUBPR, ACTIV, TAREA, Enero, Febrero, ..., Diciembre, Total Proyectado, Ppto.Vigente` (lanza excepción con el nombre de columna que no calza si falla) y devuelve un array de filas normalizadas (`nro_version`, `catalogo_codigo`, `cfinanciero_codigo`, `plan_tarea_codigo` [concatenación de PROG/SUBPR/ACTIV/TAREA], `monto_asignado` [de `Ppto.Vigente`, parseado desde formato con puntos de miles]).
- [x] 4.2 `app/Services/Presupuesto/ImportadorPresupuestoCguService.php`, método `importar(string $rutaArchivo, int $anio, ?User $usuario = null): ImportacionPresupuesto`, en una `DB::transaction`:
  - Llama a `IntegracionExternaService::iniciarTrabajo()` con el sistema externo `CGU` y `IntegracionExternaService::registrarSnapshot()` con `metodo_captura = 'excel'` y el payload crudo del archivo (bytes o array parseado).
  - Crea la `ImportacionPresupuesto` (estado `running`) referenciando el snapshot y el `trabajo_integracion`.
  - Usa `LectorExcelPresupuestoCgu` para leer las filas; si falla la validación de encabezados, marca la importación como fallida (sin crear ninguna línea) y relanza el error al controlador.
  - Por cada fila: resuelve `Cfinanciero` por `codigo` y `Catalogo` por `codigo`; si el catálogo no existe, cuenta la fila como omitida (no crea la línea) y sigue con la siguiente fila; hace `PlanTarea::firstOrCreate(['codigo' => ...], ['nombre' => ...])`; hace upsert de `Presupuesto` por `(cfinanciero_id, catalogo_id, plan_tarea_id, anio)` actualizando `monto_asignado` e `importacion_presupuesto_id`.
  - Llama a `IntegracionExternaService::finalizarTrabajo()` y a `ImportacionPresupuesto::marcarComoFinalizada()` con los totales.
- [x] 4.3 Registrar el archivo Excel original como `Documento` (tipo `PRESUPUESTO_CGU`, ver tarea 6.1) + `VersionDocumento` (mismo mecanismo de subida que usan otros módulos) y vincularlo al snapshot vía `SnapshotDatosExternoDocumento::create(['snapshot_datos_externo_id' => ..., 'documento_id' => ...])`.

## 5. Autorización, rutas y controlador

- [x] 5.1 `database/seeders/PresupuestoSeeder.php`: sembrar permisos `presupuesto.importar` y `presupuesto.consultar` (convención `modulo_accion.verbo`), asignados al rol `jefe_finanzas` (o el rol que corresponda revisar contra `WorkflowPagoProveedoresSeeder`/`FuncionariosCapjSeeder` — confirmar antes de fijar el rol). Idempotente (`givePermissionTo`).
- [x] 5.2 `app/Http/Requests/Presupuesto/ImportarPresupuestoRequest.php`: valida `archivo` (`required|file|mimes:xlsx,xls`) y `anio` (`required|integer|min:2020`); `authorize()` verifica `presupuesto.importar`.
- [x] 5.3 `app/Http/Controllers/Presupuesto/ImportacionPresupuestoController.php` con `index()` (historial de importaciones, paginado) y `store(ImportarPresupuestoRequest $request)` que solo guarda el archivo subido y delega en `ImportadorPresupuestoCguService::importar()` — sin lógica de negocio en el controlador.
- [x] 5.4 `app/Http/Controllers/Presupuesto/PresupuestoController.php` con `index()` (listado denso de líneas de presupuesto, filtrable por año/cfinanciero, autorizado por `presupuesto.consultar`).
- [x] 5.5 `app/Http/Resources/Presupuesto/{PresupuestoResource,ImportacionPresupuestoResource}.php`.
- [x] 5.6 `app/Policies/Presupuesto/{ImportacionPresupuestoPolicy,PresupuestoPolicy}.php` con `viewAny`/`create` gateando los permisos de 5.1; registrarlas en `AppServiceProvider::configureAuthorization()` (no hay auto-discovery).
- [x] 5.7 `routes/presupuesto.php` con las rutas de importación (`index`, `store`) y consulta (`index`); agregar `require __DIR__.'/presupuesto.php';` a `routes/web.php`.
- [x] 5.8 Ítem "Presupuesto" nuevo en `resources/js/components/app-sidebar.tsx`, condicionado a `presupuesto.consultar` en `auth.permissions`.

## 6. Expediente documental y datos base

- [x] 6.1 Agregar tipo de documento `PRESUPUESTO_CGU` en `database/seeders/TiposDocumentoSeeder.php` (mismo patrón que los códigos existentes).

## 7. Frontend

- [x] 7.1 `resources/js/pages/presupuesto/importaciones/index.tsx`: formulario de subida de Excel (archivo + año) y listado de importaciones anteriores con sus totales, siguiendo el patrón de "listados tabulares densos" (`openspec/specs/tema-visual-layout/spec.md`).
- [x] 7.2 `resources/js/pages/presupuesto/lineas/index.tsx`: listado denso de líneas de presupuesto (cfinanciero, cuenta, plan de tarea, año, monto asignado), con búsqueda/filtro por año.
- [x] 7.3 Ejecutar `php artisan wayfinder:generate --with-form` para generar los helpers tipados de las rutas nuevas.

## 8. Tests

- [x] 8.1 `tests/Feature/Presupuesto/ImportarPresupuestoTest.php`: importación válida crea líneas y snapshot; Excel con encabezados inválidos se rechaza sin crear líneas; fila con cuenta inexistente se omite y queda contada; reimportación con `Nro.Versión` mayor actualiza `monto_asignado` sin duplicar líneas ni borrar snapshots anteriores; un mismo `plan_tarea` bajo cuentas distintas no se duplica.
- [x] 8.2 `tests/Feature/Presupuesto/ConsultarPresupuestoTest.php`: listado de líneas y de historial de importaciones visibles con permiso; acceso denegado sin `presupuesto.importar`/`presupuesto.consultar` queda registrado en `security_audit_logs`.
- [x] 8.3 `tests/Unit/Presupuesto/LectorExcelPresupuestoCguTest.php`: parseo de filas reales (incluyendo montos con formato de puntos de miles) y detección de encabezados inválidos.

## 9. Validación final

- [x] 9.1 `composer ci:check` (lint + format + types + test) en verde.
- [x] 9.2 `php artisan test` completo en verde (confirmar que no se rompió nada de Adquisiciones/Maestros por la ampliación del clasificador).
- [x] 9.3 Invalidar la caché de permisos (`PermisosCompartidosResolver::invalidarParaRol`) tras sembrar los permisos nuevos, para que el ítem del sidebar aparezca sin esperar el TTL de 5 min.

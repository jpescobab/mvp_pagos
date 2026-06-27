## 1. Migraciones — capa core de reportabilidad

- [x] 1.1 Crear migración `periodos_reportabilidad` (codigo string unique, fecha_inicio date, fecha_fin date, estado string default 'abierto', timestamps)
- [x] 1.2 Crear migración `cortes_reportabilidad` (periodo_reportabilidad_id FK restrictOnDelete, fecha_corte timestamp useCurrent, estado string default 'borrador', publicado_por FK users nullable, publicado_en timestamp nullable, timestamps)
- [x] 1.3 Crear migración `cortes_reportabilidad_items` (corte_reportabilidad_id FK cascadeOnDelete, vinculable_type string, vinculable_id unsignedBigInteger, etiqueta string, incluido_en timestamp useCurrent; sin updated_at/created_at, `$timestamps = false` en el modelo; index(['vinculable_type', 'vinculable_id']))
- [x] 1.4 Crear migración `snapshots_corte_reportabilidad` (corte_reportabilidad_id FK cascadeOnDelete, corte_reportabilidad_item_id FK nullable->cortes_reportabilidad_items cascadeOnDelete, payload_crudo json, hash string, capturado_en timestamp useCurrent; sin updated_at/created_at, `$timestamps = false` en el modelo)

## 2. Migraciones — módulo informes_razonados

- [x] 2.1 Crear migración `definiciones_informe_razonado` (codigo string unique, nombre string, descripcion text nullable, activo boolean default true, timestamps)
- [x] 2.2 Crear migración `ejecuciones_informe_razonado` (definicion_informe_razonado_id FK restrictOnDelete, corte_reportabilidad_id FK restrictOnDelete, generado_por FK users nullable, generado_en timestamp useCurrent, timestamps) — sin columna `estado` propia; el estado vive en el `Proceso` asociado vía `sujeto`
- [x] 2.3 Crear migración `secciones_informe_razonado` (ejecucion_informe_razonado_id FK cascadeOnDelete, codigo string, titulo string, orden unsignedInteger default 0, timestamps)
- [x] 2.4 Crear migración `metricas_informe_razonado` (ejecucion_informe_razonado_id FK cascadeOnDelete, seccion_informe_razonado_id FK nullable->secciones_informe_razonado cascadeOnDelete, codigo string, etiqueta string, valor decimal(15,4) nullable, unidad string nullable, orden unsignedInteger default 0, timestamps)
- [x] 2.5 Crear migración `graficos_informe_razonado` (ejecucion_informe_razonado_id FK cascadeOnDelete, seccion_informe_razonado_id FK nullable->secciones_informe_razonado cascadeOnDelete, codigo string, titulo string, tipo string, datos json, orden unsignedInteger default 0, timestamps)
- [x] 2.6 Crear migración `excepciones_informe_razonado` (ejecucion_informe_razonado_id FK cascadeOnDelete, codigo string, descripcion text, severidad string default 'info', vinculable_type string nullable, vinculable_id unsignedBigInteger nullable, timestamps; index(['vinculable_type', 'vinculable_id']))
- [x] 2.7 Crear migración `narrativas_informe_razonado` (ejecucion_informe_razonado_id FK cascadeOnDelete, seccion_informe_razonado_id FK nullable->secciones_informe_razonado cascadeOnDelete, contenido text, generado_por_ia boolean default false, revisado_por FK users nullable, revisado_en timestamp nullable, timestamps)
- [x] 2.8 Crear migración `snapshots_informe_razonado` (ejecucion_informe_razonado_id FK cascadeOnDelete, payload_crudo json, hash string, capturado_en timestamp useCurrent; sin updated_at/created_at, `$timestamps = false` en el modelo)
- [x] 2.9 Crear migración `aprobaciones_informe_razonado` (ejecucion_informe_razonado_id FK cascadeOnDelete, aprobado_por FK users restrictOnDelete, decision string, comentario text nullable, decidido_en timestamp useCurrent; sin updated_at/created_at, `$timestamps = false` en el modelo)
- [x] 2.10 Crear migración `exportaciones_informe_razonado` (ejecucion_informe_razonado_id FK cascadeOnDelete, formato string, ruta_archivo string, generado_por FK users nullable, generado_en timestamp useCurrent; sin updated_at/created_at, `$timestamps = false` en el modelo)

## 3. Modelos Eloquent — capa core

- [x] 3.1 Crear `PeriodoReportabilidad` (hasMany cortesReportabilidad)
- [x] 3.2 Crear `CorteReportabilidad` (belongsTo periodoReportabilidad, belongsTo publicadoPor; hasMany items/snapshots; hasMany ejecucionesInformeRazonado; método `estaPublicado()`)
- [x] 3.3 Crear `CorteReportabilidadItem` (belongsTo corteReportabilidad; `vinculable(): MorphTo`; hasMany snapshots)
- [x] 3.4 Crear `SnapshotCorteReportabilidad` (belongsTo corteReportabilidad, belongsTo corteReportabilidadItem; casts payload_crudo a array)

## 4. Modelos Eloquent — módulo informes_razonados

- [x] 4.1 Crear `DefinicionInformeRazonado` (hasMany ejecuciones)
- [x] 4.2 Crear `EjecucionInformeRazonado` (belongsTo definicionInformeRazonado/corteReportabilidad/generadoPor; `proceso(): MorphOne` vía `sujeto`; hasMany secciones/metricas/graficos/excepciones/narrativas/snapshots/aprobaciones/exportaciones)
- [x] 4.3 Crear `SeccionInformeRazonado` (belongsTo ejecucionInformeRazonado; hasMany metricas/graficos/narrativas)
- [x] 4.4 Crear `MetricaInformeRazonado` (belongsTo ejecucionInformeRazonado, belongsTo seccionInformeRazonado)
- [x] 4.5 Crear `GraficoInformeRazonado` (belongsTo ejecucionInformeRazonado, belongsTo seccionInformeRazonado; casts datos a array)
- [x] 4.6 Crear `ExcepcionInformeRazonado` (belongsTo ejecucionInformeRazonado; `vinculable(): MorphTo`)
- [x] 4.7 Crear `NarrativaInformeRazonado` (belongsTo ejecucionInformeRazonado/seccionInformeRazonado/revisadoPor)
- [x] 4.8 Crear `SnapshotInformeRazonado` (belongsTo ejecucionInformeRazonado; casts payload_crudo a array)
- [x] 4.9 Crear `AprobacionInformeRazonado` (belongsTo ejecucionInformeRazonado, belongsTo aprobadoPor)
- [x] 4.10 Crear `ExportacionInformeRazonado` (belongsTo ejecucionInformeRazonado, belongsTo generadoPor)

## 5. Servicios de dominio

- [x] 5.1 Crear excepción de dominio `App\Exceptions\CorteReportabilidadException` (factories `sinPermiso()`, `corteYaPublicado()`, `corteNoPublicado()`)
- [x] 5.2 Crear `App\Services\Reportabilidad\CorteReportabilidadService` con `abrirPeriodo()`, `crearCorte()`, `agregarItem()`, `capturarSnapshot()` (rechaza si el corte está publicado) y `publicarCorte()` (exige permiso `reportabilidad.publicar_corte`)
- [x] 5.3 Crear `App\Services\InformesRazonados\InformeRazonadoService` con `iniciarEjecucion()` (exige corte publicado, crea `EjecucionInformeRazonado` + `Proceso` inicial del workflow "informes_razonados"), `agregarSeccion()`, `agregarMetrica()`, `agregarGrafico()`, `agregarExcepcion()`, `agregarNarrativa()`, `revisarNarrativa()`, `enviarARevision()`, `aprobar()` (vía `TransicionWorkflowService` + crea `AprobacionInformeRazonado`), `rechazar()` (idem), `publicar()` (vía `TransicionWorkflowService` + crea `SnapshotInformeRazonado` con el contenido ensamblado) y `exportar()` (crea `ExportacionInformeRazonado`)

## 6. Permisos y seeder de workflow

- [x] 6.1 Crear permisos `reportabilidad.publicar_corte`, `informes.aprobar`, `informes.publicar` vía `Permission::firstOrCreate`, otorgados al rol `admin` vía `givePermissionTo`
- [x] 6.2 Crear `database/seeders/WorkflowInformesRazonadosSeeder`: `DefinicionWorkflow` codigo `informes_razonados` + 5 estados (`en_elaboracion` es_inicial; `en_revision`; `aprobado`; `publicado` es_final; `rechazado` es_final) + 4 transiciones (`enviar_a_revision`, `aprobar` con `permiso_requerido = informes.aprobar`, `rechazar` con `requiere_comentario = true`, `publicar` con `permiso_requerido = informes.publicar`)
- [x] 6.3 Registrar `WorkflowInformesRazonadosSeeder` en `DatabaseSeeder`

## 7. Tests

- [x] 7.1 Test feature: `CorteReportabilidadService` crea un corte en borrador, agrega items/snapshots, y `publicarCorte()` exige el permiso `reportabilidad.publicar_corte`
- [x] 7.2 Test feature: `CorteReportabilidadService::agregarItem()`/`capturarSnapshot()` rechazan operar sobre un corte ya publicado
- [x] 7.3 Test feature: `InformeRazonadoService::iniciarEjecucion()` rechaza iniciar sobre un corte no publicado y no crea ninguna `EjecucionInformeRazonado`
- [x] 7.4 Test feature: `InformeRazonadoService::iniciarEjecucion()` sobre un corte publicado crea la ejecución con su `Proceso` en el estado inicial del workflow "informes_razonados"
- [x] 7.5 Test feature: el ciclo completo (`enviarARevision` → `aprobar` → `publicar`) transiciona el `Proceso` correctamente vía `TransicionWorkflowService`, crea `AprobacionInformeRazonado` al aprobar y `SnapshotInformeRazonado` al publicar
- [x] 7.6 Test feature: `aprobar()` exige el permiso `informes.aprobar` y `rechazar()` exige comentario, igual que el resto de transiciones gobernadas por `TransicionWorkflowService`
- [x] 7.7 Test feature: una `NarrativaInformeRazonado` con `generado_por_ia = true` queda con `revisado_por`/`revisado_en` nulos hasta que se revisa explícitamente
- [x] 7.8 Test feature: `exportar()` crea una `ExportacionInformeRazonado` con formato, ruta y responsable

## 8. Validación

- [x] 8.1 `composer lint:check`
- [x] 8.2 `composer types:check`
- [x] 8.3 `php artisan test --compact`

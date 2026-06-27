## 1. Migraciones — capa API

- [x] 1.1 Crear migración `sistemas_externos` (codigo string unique, nombre string, tipo_integracion string default 'manual', activo boolean default true, url_base string nullable, descripcion text nullable, timestamps)
- [x] 1.2 Crear migración `trabajos_integracion` (sistema_externo_id FK restrictOnDelete, tipo string, mecanismo string, estado string default 'en_progreso', iniciado_por FK users nullable, iniciado_en timestamp useCurrent, finalizado_en timestamp nullable, total_elementos unsignedInteger default 0, error text nullable, timestamps)
- [x] 1.3 Crear migración `solicitudes_api_externas` (sistema_externo_id FK restrictOnDelete, trabajo_integracion_id FK nullable->trabajos_integracion nullOnDelete, metodo_http string, endpoint string, payload_enviado json nullable, payload_recibido json nullable, codigo_respuesta_http unsignedSmallInteger nullable, estado string, error text nullable, duracion_ms unsignedInteger nullable, ejecutado_en timestamp useCurrent; sin updated_at/created_at, `$timestamps = false` en el modelo; index sistema_externo_id, index trabajo_integracion_id)
- [x] 1.4 Crear migración `snapshots_datos_externos` (sistema_externo_id FK restrictOnDelete, trabajo_integracion_id FK nullable->trabajos_integracion nullOnDelete, solicitud_api_externa_id FK nullable->solicitudes_api_externas nullOnDelete, metodo_captura string ('api'|'playwright'|'manual'|'csv'|'excel'), referencia_externa string nullable, payload_crudo json, payload_normalizado json nullable, hash string, capturado_en timestamp useCurrent, capturado_por FK users nullable, columnas polimórficas `vinculable_type`/`vinculable_id` nullable; sin updated_at/created_at, `$timestamps = false` en el modelo; index(['sistema_externo_id', 'referencia_externa']))

## 2. Migraciones — capa Playwright

- [x] 2.1 Crear migración `conectores_automatizacion_navegador` (sistema_externo_id FK restrictOnDelete, codigo string unique, nombre string, activo boolean default false, autorizado_por FK users nullable, autorizado_en timestamp nullable, descripcion text nullable, timestamps)
- [x] 2.2 Crear migración `perfiles_autenticacion_navegador` (conector_automatizacion_navegador_id FK cascadeOnDelete, nombre string, almacen_secreto string, referencia_secreto string, activo boolean default true, creado_por FK users nullable, timestamps) — nunca guarda el valor del secreto, solo almacén+referencia
- [x] 2.3 Crear migración `ejecuciones_automatizacion_navegador` (conector_automatizacion_navegador_id FK restrictOnDelete, perfil_autenticacion_navegador_id FK nullable->perfiles_autenticacion_navegador nullOnDelete, trabajo_integracion_id FK nullable->trabajos_integracion nullOnDelete, iniciado_por FK users nullable, estado string default 'en_progreso', iniciado_en timestamp useCurrent, finalizado_en timestamp nullable, resumen_resultado text nullable, error text nullable, timestamps; index conector_automatizacion_navegador_id)
- [x] 2.4 Crear migración `pasos_automatizacion_navegador` (ejecucion_automatizacion_navegador_id FK cascadeOnDelete, orden unsignedInteger, accion string, detalle json nullable, estado string, error text nullable, ejecutado_en timestamp useCurrent; sin updated_at/created_at, `$timestamps = false` en el modelo)
- [x] 2.5 Crear migración `artefactos_automatizacion_navegador` (ejecucion_automatizacion_navegador_id FK cascadeOnDelete, paso_automatizacion_navegador_id FK nullable->pasos_automatizacion_navegador nullOnDelete, tipo string ('screenshot'|'trace'|'pdf'|'html'|'archivo'), ruta_almacenamiento string, hash string, capturado_en timestamp useCurrent; sin updated_at/created_at, `$timestamps = false` en el modelo)

## 3. Modelos Eloquent

- [x] 3.1 Crear `SistemaExterno` (hasMany trabajosIntegracion/solicitudesApiExternas/snapshotsDatosExternos/conectoresAutomatizacionNavegador); confirmado nombre de tabla con `getTable()` vía tinker — coincide con `$table` explícito
- [x] 3.2 Crear `TrabajoIntegracion` (belongsTo sistemaExterno, belongsTo iniciadoPor; hasMany solicitudesApiExternas/snapshotsDatosExternos/ejecucionesAutomatizacionNavegador)
- [x] 3.3 Crear `SolicitudApiExterna` (belongsTo sistemaExterno, belongsTo trabajoIntegracion; casts payload_enviado/payload_recibido a array)
- [x] 3.4 Crear `SnapshotDatosExterno` (belongsTo sistemaExterno/trabajoIntegracion/solicitudApiExterna/capturadoPor; `vinculable(): MorphTo`; casts payload_crudo/payload_normalizado a array)
- [x] 3.5 Crear `ConectorAutomatizacionNavegador` (belongsTo sistemaExterno, belongsTo autorizadoPor; hasMany perfilesAutenticacionNavegador/ejecucionesAutomatizacionNavegador; método `estaAutorizado()`)
- [x] 3.6 Crear `PerfilAutenticacionNavegador` (belongsTo conectorAutomatizacionNavegador, belongsTo creadoPor) — sin accessor ni cast que expongan secretos
- [x] 3.7 Crear `EjecucionAutomatizacionNavegador` (belongsTo conectorAutomatizacionNavegador/perfilAutenticacionNavegador/trabajoIntegracion/iniciadoPor; hasMany pasos/artefactos)
- [x] 3.8 Crear `PasoAutomatizacionNavegador` (belongsTo ejecucionAutomatizacionNavegador; casts detalle a array)
- [x] 3.9 Crear `ArtefactoAutomatizacionNavegador` (belongsTo ejecucionAutomatizacionNavegador, belongsTo pasoAutomatizacionNavegador)

## 4. Servicios de dominio

- [x] 4.1 Crear `App\Services\Integraciones\IntegracionExternaService` con `iniciarTrabajo()`, `registrarSolicitud()`, `registrarSnapshot()` y `finalizarTrabajo()`
- [x] 4.2 Crear excepción de dominio `App\Exceptions\ConectorAutomatizacionNoAutorizadoException` (namespace plano, igual que `TransicionWorkflowException`, no `App\Exceptions\Integraciones\...`)
- [x] 4.3 Crear `App\Services\Integraciones\AutomatizacionNavegadorService` con `iniciarEjecucion()` (valida `ConectorAutomatizacionNavegador::estaAutorizado()`, lanza `ConectorAutomatizacionNoAutorizadoException` si falla), `registrarPaso()`, `registrarArtefacto()` y `finalizarEjecucion()`

## 5. Permisos y seeder de catálogo

- [x] 5.1 Crear permisos `integraciones.gestionar_conectores` e `integraciones.ejecutar_playwright` vía `Permission::firstOrCreate`, otorgados al rol `admin` vía `givePermissionTo`
- [x] 5.2 Crear `database/seeders/IntegracionesSeeder` que siembra `sistemas_externos` (SGF activo/manual; CGU, BancoEstado, SII, CMF, Mercado Público inactivos/manual, sin URLs ni credenciales reales) y los permisos de 5.1
- [x] 5.3 Registrar `IntegracionesSeeder` en `DatabaseSeeder`

## 6. Tests

- [x] 6.1 Test feature: `IntegracionExternaService` inicia un trabajo, registra una solicitud exitosa y una fallida, y cierra el trabajo con su estado final
- [x] 6.2 Test feature: `IntegracionExternaService::registrarSnapshot()` crea un `snapshot_datos_externos` inmutable vinculable a un modelo arbitrario, y volver a capturar la misma `referencia_externa` crea un snapshot nuevo sin alterar el anterior
- [x] 6.3 Test feature: `AutomatizacionNavegadorService::iniciarEjecucion()` crea la ejecución cuando el conector está activo y autorizado
- [x] 6.4 Test feature: `AutomatizacionNavegadorService::iniciarEjecucion()` lanza `ConectorAutomatizacionNoAutorizadoException` y no crea ninguna ejecución cuando el conector está inactivo o sin autorización
- [x] 6.5 Test feature: registrar pasos y artefactos de una ejecución los persiste asociados correctamente (incluyendo un artefacto ligado a un paso específico)
- [x] 6.6 Test feature: el seeder `IntegracionesSeeder` crea el catálogo de `sistemas_externos` esperado y los permisos de integraciones

## 7. Validación

- [x] 7.1 `composer lint:check`
- [x] 7.2 `composer types:check`
- [x] 7.3 `php artisan test --compact`

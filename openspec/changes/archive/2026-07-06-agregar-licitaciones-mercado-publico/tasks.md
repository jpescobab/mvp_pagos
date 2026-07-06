## 1. Datos y modelos

- [x] 1.1 Migración `create_licitaciones_mercado_publico_table`: `id`, `codigo` (string, unique), `nombre`, `estado_mercado_publico` (nullable), `codigo_estado_mercado_publico` (integer, nullable), `moneda` (nullable), `monto_estimado` (decimal 15,2 nullable), `organismo_comprador` (jsonb, nullable), `cronograma` (jsonb, nullable), `adjudicacion` (jsonb, nullable), `proceso_adquisicion_id` (FK nullable a `procesos_adquisicion`, `nullOnDelete`), `snapshot_datos_externo_id` (FK a `snapshots_datos_externos`), timestamps.
- [x] 1.2 Migración `create_licitaciones_mercado_publico_items_table`: `id`, `licitacion_mercado_publico_id` (FK, `cascadeOnDelete`), `correlativo` (integer nullable), `codigo_producto` (string nullable), `categoria` (string nullable), `nombre_producto` (string nullable), `descripcion` (text), `unidad_medida` (string nullable), `cantidad` (decimal), `adjudicacion` (jsonb nullable), timestamps.
- [x] 1.3 Modelo `LicitacionMercadoPublico` (`app/Models/`): `$fillable`, `casts()` (`monto_estimado` decimal:2, `organismo_comprador`/`cronograma`/`adjudicacion` array), relaciones `items()` (HasMany), `procesoAdquisicion()` (BelongsTo), `snapshot()` (BelongsTo a `SnapshotDatosExterno` vía `snapshot_datos_externo_id`), factory.
- [x] 1.4 Modelo `LicitacionMercadoPublicoItem` (`app/Models/`): `$fillable`, `casts()` (`cantidad` decimal, `adjudicacion` array), relación `licitacion()` (BelongsTo), factory.
- [x] 1.5 Factories `LicitacionMercadoPublicoFactory` y `LicitacionMercadoPublicoItemFactory` en `database/factories/`, siguiendo el patrón de las de OC.

## 2. Servicio de integración

- [x] 2.1 Crear `App\Services\Adquisiciones\LicitacionMercadoPublicoService` con `buscarLocal(string $codigo)`, `consultarApi(string $codigo)`, `compararConApi(LicitacionMercadoPublico $licitacion)`, `guardarDesdeApi(array $payloadNormalizado, SnapshotDatosExterno $snapshot, ?int $procesoAdquisicionId)`, `aplicarActualizacion(...)` — mismo esqueleto que `OrdenCompraMercadoPublicoService` pero sin resolución de proveedor (ver design.md, Non-Goals).
- [x] 2.2 Implementar `consultarApiInterno()` contra `licitaciones.json` (parámetros `codigo` y `ticket`) reutilizando `IntegracionExternaService::iniciarTrabajo/registrarSolicitud/registrarSnapshot/finalizarTrabajo` sobre el `SistemaExterno` `MERCADO_PUBLICO` ya existente (job `consulta_licitacion`).
- [x] 2.3 Implementar `apiDevuelveLicitacion()`: exige `Listado` no vacío y que `Listado[0].CodigoExterno` coincida (case-insensitive) con el código solicitado.
- [x] 2.4 Implementar `normalizarPayload()` según el mapeo de campos definido en design.md (codigo, nombre, estado, codigo_estado, moneda, monto_estimado, organismo_comprador, cronograma, adjudicacion, items con su adjudicación por ítem).
- [x] 2.5 Implementar `cronogramaDesdeFechas()`: hitos en el orden fijo definido en design.md, omitiendo los no informados, conservando fecha y hora reales sin truncar.
- [x] 2.6 Implementar `calcularDiferencias()` comparando los campos propios de la licitación (mismo patrón de `numerosDifieren`/comparación de arrays que en `OrdenCompraMercadoPublicoService`).
- [x] 2.7 Implementar `crearItems()`/reemplazo de ítems en `guardarDesdeApi()`/`aplicarActualizacion()` dentro de una transacción `DB::transaction`, sin tocar el catálogo de `Proveedor`.
- [x] 2.8 Test `LicitacionMercadoPublicoServiceTest` (Feature): normalización de payload real (usar un payload de ejemplo capturado de la API, con y sin adjudicación), cronograma con hitos parciales, no duplicar por código, transacción atómica.

## 3. HTTP y autorización

- [x] 3.1 Form Requests `BuscarLicitacionMercadoPublicoRequest` y `GuardarLicitacionMercadoPublicoRequest` en `app/Http/Requests/Adquisiciones/`, análogos a los de OC.
- [x] 3.2 `LicitacionMercadoPublicoResource` (`app/Http/Resources/Adquisiciones/`): expone todos los campos normalizados, `payload_crudo` vía `whenLoaded('snapshot')`, `proceso_adquisicion` cuando esté cargado, `items` con su `adjudicacion` por ítem.
- [x] 3.3 `LicitacionMercadoPublicoPolicy` (`app/Policies/`): `viewAny`/`view`/`create`/`vincularProcesoAdquisicion` sobre el permiso `adquisiciones.consultar_licitacion_mp`.
- [x] 3.4 `LicitacionMercadoPublicoController` (`app/Http/Controllers/Adquisiciones/`) con `index` (listado o búsqueda por `codigo`/`nuevo`, igual dispatch que `OrdenCompraMercadoPublicoController::index`), `buscar`, `guardar` (sin lógica de proveedor), `show`, `verificar`, `actualizar` — sin acción `pdf`.
- [x] 3.5 `VinculoProcesoAdquisicionLicitacionMercadoPublicoController` (`store`/`destroy`) análogo al de OC, auditando la acción.
- [x] 3.6 Rutas en `routes/adquisiciones.php` bajo `licitaciones-mercado-publico` / nombre `licitaciones_mp.*` con las mismas acciones que `ordenes_compra_mp.*` salvo `pdf`.
- [x] 3.7 Agregar el permiso `adquisiciones.consultar_licitacion_mp` en `database/seeders/IntegracionesSeeder.php` (mismo arreglo `$permisos` que ya tiene `adquisiciones.consultar_orden_compra_mp`); no se crea un nuevo `sistema_externo` (se reutiliza `MERCADO_PUBLICO`).
- [x] 3.8 Regenerar rutas/acciones tipadas de Wayfinder: `php artisan wayfinder:generate --with-form`.
- [x] 3.9 Test `ApiLicitacionesMercadoPublicoTest` (Feature): mockear `Http::fake()` para `licitaciones.json`, cubrir encontrada/no encontrada/guardar/verificar/actualizar, y que cada consulta deja `solicitud_api_externa` + `snapshot_datos_externos`.
- [x] 3.10 Test `ListadoLicitacionesMercadoPublicoTest` (Feature): paginación, búsqueda por código, permiso requerido, estado vacío.
- [x] 3.11 Test `VinculoLicitacionMercadoPublicoTest` (Feature): vincular/desvincular con y sin permiso, auditoría registrada, sin disparar workflow.

## 4. Frontend

- [x] 4.1 Generalizar `AccionesEncabezadoFichaMercadoPublico` en `resources/js/components/mercado-publico/ficha-consulta.tsx` para recibir `urlDetalle: string` y `urlPdf: string | null` como props (deshabilita "Ver PDF" cuando `urlPdf` es `null`), y actualizar `ordenes-compra-mercado-publico/show.tsx` para pasar explícitamente su `urlDetalle`/`urlPdf` sin cambiar su comportamiento actual.
- [x] 4.2 Tipos en `resources/js/types/adquisiciones.ts`: `LicitacionMercadoPublico`, `LicitacionMercadoPublicoItem`, `PayloadNormalizadoLicitacionMercadoPublico`, `AdjudicacionLicitacionMercadoPublico`, `AdjudicacionItemLicitacionMercadoPublico`, `DiferenciaCampoLicitacionMercadoPublico`.
- [x] 4.3 Página `resources/js/pages/adquisiciones/licitaciones-mercado-publico/index.tsx`: listado tabular denso (mismo patrón que `ordenes-compra-mercado-publico/index.tsx`) con columnas código/nombre, estado (badge), organismo comprador, monto estimado, adquisición vinculada, búsqueda con debounce 300ms, paginación simple, acceso a "Consultar licitación a Mercado Público".
- [x] 4.4 Página `resources/js/pages/adquisiciones/licitaciones-mercado-publico/buscar.tsx`: búsqueda por código (fila horizontal sobre la ficha), vista previa antes de guardar (sin ningún paso de proveedor), comparación de diferencias, aviso de no encontrada — siguiendo `ordenes-compra-mercado-publico/buscar.tsx`.
- [x] 4.5 Página `resources/js/pages/adquisiciones/licitaciones-mercado-publico/show.tsx`: ficha con `FichaConsultaMercadoPublico` y secciones en el orden de la spec (cronograma, organismo comprador, condiciones, adjudicación, ítems con su adjudicación por ítem), reutilizando `CronogramaTimeline`; vínculo/desvínculo de proceso de adquisición igual que en la ficha de OC; encabezado con "Ver JSON" y "Mercado Público" habilitados y "Ver PDF" deshabilitado ("Disponible próximamente").
- [x] 4.6 Agregar el ítem de navegación "Licitaciones (Mercado Público)" en `resources/js/components/app-sidebar.tsx`, condicionado al permiso `adquisiciones.consultar_licitacion_mp`, junto al de Órdenes de Compra.

## 5. Validación

- [x] 5.1 `vendor/bin/pint --dirty --format agent`
- [x] 5.2 `npm run lint` / `npm run format`
- [x] 5.3 `npm run types:check`
- [x] 5.4 `composer test` (o `php artisan test --compact --filter=Licitacion`)
- [x] 5.5 Probar manualmente en el navegador: listado vacío, búsqueda de una licitación real por código, vista previa y guardado, ficha de detalle con cronograma, vínculo/desvínculo de proceso de adquisición.

## Why

La importación de indicadores económicos (UF, UTM, UTA, IPC, USD) ya funciona como MVP (`IndicadorEconomicoImporter`, jobs, scheduler, caché de selección), pero el esquema y las reglas actuales no alcanzan lo que exige el spec detallado entregado por el usuario: falta distinguir el código del indicador (UF/USD/UTM/UTA/IPC) de su categoría semántica, falta un estado de ejecución completo (`pending/running/success/partial_success/failed/cancelled`), falta trazabilidad de captura (`capturado_en`/`capturado_por_job`), falta la clave única exigida (`codigo+fecha_valor+periodo+fuente+es_proyectado`), falta el comando Artisan y el disparo manual autorizado para reprocesos controlados, y el scheduler corre en UTC en vez de `America/Coyhaique` (Aysén).

## What Changes

- **Esquema**: modificar directamente las 2 migraciones existentes (`indicadores_economicos_importaciones`, `indicadores_economicos`) — el proyecto está en construcción activa, sin datos de producción en juego. **BREAKING** para el entorno de desarrollo: requiere `migrate:fresh`, se pierden los indicadores ya importados localmente.
  - `indicadores_economicos`: renombrar `tipo` (UF/USD/UTM/UTA/IPC) a `codigo`; agregar una columna `tipo` NUEVA con la categoría semántica (`unidad_reajustable`/`unidad_tributaria`/`moneda`/`indice`); agregar `nombre`, `unidad_medida`, `moneda_base`, `endpoint`, `capturado_en`, `capturado_por_user_id`, `capturado_por_job`, `requiere_dia_habil`, `es_proyectado`, `es_oficial`, `activo`, `metadata`; eliminar `advertencias` (se centraliza en la importación); reemplazar los 2 índices únicos actuales por uno solo: `unique(codigo, fecha_valor, periodo, fuente, es_proyectado)`.
  - `indicadores_economicos_importaciones`: renombrar `tipo` a `tipo_importacion` (valores `mensual_indicadores`/`diaria_usd`/`manual`/`reproceso_controlado`); ampliar `estado` a `pending/running/success/partial_success/failed/cancelled`; agregar `indicadores_solicitados`, `fuente_principal`, `fuente_fallback`, `fecha_programada`, `periodo`, `fecha_desde`, `fecha_hasta`, `iniciado_en`, `finalizado_en`, `creado_por_user_id`, `ejecutado_por_job`, `total_recibidos`, `total_creados`, `total_omitidos`, `total_fallidos`; eliminar `endpoint`/`source_payload` (se centralizan por-indicador).
- **Servicios nuevos** (español, namespace `App\Services\Indicadores`): `ServicioImportacionIndicadores` (coordina), `ServicioNormalizadorIndicadores` (normaliza valores crudos a decimal), `ServicioPersistenciaIndicadores` (solo crea, nunca actualiza), `RegistradorImportacionIndicadores` (transiciona estados y registra conteos). `IndicadorEconomicoImporter` se reemplaza por esta descomposición; `CmfClient` se mantiene sin cambios.
- **Jobs**: `ImportarIndicadoresMensualesJob`/`ImportarDolarDiarioJob` se refactorizan para usar los servicios nuevos y agregan `withoutOverlapping()`.
- **Comandos Artisan nuevos**: `indicadores:importar-mensual --periodo=` y `indicadores:importar-usd --fecha=`, para reprocesos controlados (quedan registrados con `tipo_importacion = reproceso_controlado`).
- **Disparo manual autorizado**: nuevo permiso `indicadores.importar` (asignado a `superadmin`/`admin`), acción HTTP para lanzar la importación mensual bajo demanda, botón en la página de indicadores visible solo con ese permiso.
- **Scheduler**: agregar `->timezone('America/Coyhaique')` y `->withoutOverlapping()` explícitos a los 2 `Schedule::job(...)` ya existentes en `routes/console.php` (día/hora ya son correctos).
- **Frontend/Resource**: `IndicadorEconomicoResource`, `topbar-indicadores.tsx`, `lib/indicadores.ts`, `types/indicadores.ts` y `pages/indicadores-economicos/index.tsx` pasan de referenciar `tipo` a `codigo` para filtrar/mostrar UF/USD/UTM/UTA/IPC (el `HandleInertiaRequests::share()` que alimenta `indicadoresTopbar` también se ajusta).
- **Fuera de alcance**: cliente SII (CMF sigue siendo la única fuente implementada), edición manual de indicadores, aprobación institucional de indicadores proyectados, uso de "último valor disponible" en cálculos (ya vive en el selector, no se toca), dashboards avanzados, informes razonados, comparación histórica, alertas por variación anómala.

## Capabilities

### New Capabilities

(ninguna — reescritura de un capability ya existente)

### Modified Capabilities

- `indicadores-economicos-cmf-sii`: reescritura sustancial de los 2 requirements existentes (esquema, servicios, estados de importación) y nuevos requirements para estados de ejecución, comandos Artisan/reproceso controlado y disparo manual autorizado.
- `consulta-indicadores-economicos`: los requirements que filtran/muestran por `tipo` pasan a referenciar `codigo` (el campo que identifica UF/USD/UTM/UTA/IPC), sin cambiar el control de acceso (sigue abierto a cualquier autenticado, ya ratificado).
- `seguridad-auditoria`: nuevo permiso `indicadores.importar` que gatea el disparo manual de la importación.

## Impact

- Afecta: las 2 migraciones de indicadores económicos (modificadas in-place, requiere `migrate:fresh` en desarrollo), `app/Models/IndicadorEconomico.php`, `app/Models/IndicadorEconomicoImportacion.php`, `app/Services/Indicadores/*`, `app/Jobs/Importar*Job.php`, `routes/console.php`, `app/Http/Controllers/Indicadores/IndicadorEconomicoController.php`, `app/Http/Resources/Indicadores/IndicadorEconomicoResource.php`, `app/Http/Middleware/HandleInertiaRequests.php`, `database/seeders/RolesAndPermissionsSeeder.php`, `resources/js/components/topbar-indicadores.tsx`, `resources/js/lib/indicadores.ts`, `resources/js/types/indicadores.ts`, `resources/js/pages/indicadores-economicos/index.tsx`, y los 3 archivos de test existentes en `tests/Feature/Indicadores/`.
- No afecta: `CmfClient` (se mantiene), la jerarquía institucional, el workflow de casos de pago, ni ningún otro módulo funcional.
- Riesgo declarado: se pierden los indicadores económicos ya importados en el entorno de desarrollo al correr `migrate:fresh` — aceptable porque no hay datos de producción en juego todavía.

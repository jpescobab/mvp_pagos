## Why

`ProcesoAdquisicionPolicy` autoriza `viewAny`/`view`/`create` con `return true`, sin verificar ningún permiso: cualquier usuario autenticado puede listar, ver y crear procesos de adquisición. No existen permisos para ver ni crear procesos internos (solo hay los de transición `publicar`/`adjudicar`/`anular` y los de Mercado Público `consultar_orden_compra_mp`/`consultar_licitacion_mp`). Esto rompe la convención de gobernanza por permisos que ya cumplen Pago de Proveedores e Informes Razonados, y deja el proceso de adquisición como el único flujo del módulo sin control de acceso real.

## What Changes

- Se definen dos permisos nuevos siguiendo la convención `modulo_accion.verbo`: `adquisiciones.consultar_proceso` (listar + ver detalle) y `adquisiciones.crear_proceso` (crear).
- `ProcesoAdquisicionPolicy` deja de devolver `true` incondicionalmente: `viewAny`/`view` exigen `adquisiciones.consultar_proceso` y `create` exige `adquisiciones.crear_proceso`.
- Ambos permisos se siembran en `WorkflowAdquisicionesSeeder` y se asignan a los roles `admin` y `administrativo_adquisiciones` (idempotente/aditivo).
- El ítem "Procesos" del sidebar se oculta a quien no tenga `adquisiciones.consultar_proceso`.
- Se invalida la caché de permisos por usuario tras la resiembra, según la convención del proyecto.
- Los tests existentes de Adquisiciones que crean/listan procesos se actualizan para otorgar los permisos nuevos; se agregan casos negativos (sin permiso → 403).

Sin cambios en el workflow ni en las transiciones. `superadmin` conserva acceso total vía `Gate::before`. No se incluye edición de procesos ni visibilidad bidireccional con Mercado Público (gaps #2 y #3, separados).

## Capabilities

### New Capabilities
<!-- Ninguna capability nueva: el cambio endurece el comportamiento de una capability existente. -->

### Modified Capabilities
- `api-adquisiciones`: se agrega el requerimiento de que listar, ver y crear procesos de adquisición vía HTTP exige un permiso explícito (`adquisiciones.consultar_proceso` / `adquisiciones.crear_proceso`); sin él la petición se rechaza con 403.

## Impact

- **Policy**: `app/Policies/ProcesoAdquisicionPolicy.php` (ya registrada en `AppServiceProvider`; solo cambia su lógica).
- **Seeder / permisos**: `database/seeders/WorkflowAdquisicionesSeeder.php` (dos permisos nuevos + asignación a `admin` y `administrativo_adquisiciones`). `RolesAndPermissionsSeederTest` afirma solo los permisos core, así que estos (de módulo) no lo afectan; revisar `WorkflowAdquisicionesSeederTest`/`IntegracionesSeederTest` si existen.
- **Caché de permisos**: invalidación vía `PermisosCompartidosResolver` (store `database`, TTL 5 min).
- **Frontend**: `resources/js/components/app-sidebar.tsx` (gating del ítem "Procesos").
- **Tests**: `tests/Feature/Adquisiciones/*` que crean o consultan procesos (p. ej. `ProcesoAdquisicionServiceTest`, `ApiAdquisicionesTest`, `ChecklistDocumentalAdquisicionTest`, `VinculoOrdenCompraMercadoPublicoTest`, `VinculoLicitacionMercadoPublicoTest`) — otorgar los permisos nuevos donde el usuario deba tener acceso; agregar casos negativos.
- **Sin migraciones ni cambios de esquema.** Sin impacto en integraciones externas.

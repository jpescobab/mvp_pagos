## 1. Permiso y policies

- [x] 1.1 `database/seeders/WorkflowPagoProveedoresSeeder.php` (no `RolesAndPermissionsSeeder.php` — ese archivo es solo para permisos core; los permisos reales de `pago_proveedores.*` se seedean aquí): agregado `pago_proveedores.administrar_requisitos_documentales` y asignado a `admin` y `administrativo_finanzas`
- [x] 1.2 `app/Policies/TipoProcesoPagoPolicy.php` (nueva): `viewAny`/`view`/`create`/`update`/`delete` → `pago_proveedores.administrar_requisitos_documentales`
- [x] 1.3 `app/Policies/TipoDocumentoPolicy.php` (nueva): `viewAny`/`view`/`create`/`update`/`delete` → `core_institucional.administrar` (mismo patrón que `CfinancieroPolicy`)
- [x] 1.4 `app/Enums/TipoRequisitoDocumental.php` (nuevo, backed enum de string): `Obligatorio = 'obligatorio'`, `Opcional = 'opcional'`

## 2. Backend: CRUD de TipoProcesoPago

- [x] 2.1 `app/Http/Requests/Maestros/StoreTipoProcesoPagoRequest.php` / `UpdateTipoProcesoPagoRequest.php`: `codigo` (required, string, max, unique case-insensitive, ignorando el propio registro en update), `nombre` (required, string, max), `activo` (boolean)
- [x] 2.2 `app/Http/Controllers/Maestros/TipoProcesoPagoController.php` (nuevo): `index`/`create`/`store`/`show`/`edit`/`update`/`destroy`, siguiendo exactamente el patrón de `CfinancieroController.php` (`Gate::authorize`, `Inertia::flash('toast', ...)`, `to_route()`, `destroy()` bloqueado si existen `RequisitoDocumental` relacionados vía `relacionQueImpideEliminar()`)
- [x] 2.3 `app/Http/Resources/Maestros/TipoProcesoPagoResource.php` (nuevo)
- [x] 2.4 `routes/maestros.php`: rutas `maestros.tipos-proceso-pago.*` (index/create/store/show/edit/update/destroy)
- [x] 2.5 Test: crear con permiso persiste `activo=true` por defecto
- [x] 2.6 Test: código duplicado (case-insensitive) es rechazado
- [x] 2.7 Test: desactivar no afecta casos que ya lo tienen asignado
- [x] 2.8 Test: eliminar con `RequisitoDocumental` asociados es rechazado
- [x] 2.9 Test: usuario sin permiso recibe 403 en cada acción

## 3. Backend: CRUD de TipoDocumento

- [x] 3.1 `app/Http/Requests/Maestros/StoreTipoDocumentoRequest.php` / `UpdateTipoDocumentoRequest.php`: `codigo`, `nombre`, `descripcion` (nullable), `activo`
- [x] 3.2 `app/Http/Controllers/Maestros/TipoDocumentoController.php` (nuevo): mismo patrón que 2.2; `destroy()` bloqueado si existen `RequisitoDocumental` **o** `Documento` relacionados
- [x] 3.3 `app/Http/Resources/Maestros/TipoDocumentoResource.php` (nuevo)
- [x] 3.4 `routes/maestros.php`: rutas `maestros.tipos-documento.*`
- [x] 3.5 Test: crear con permiso `core_institucional.administrar`
- [x] 3.6 Test: eliminar con `Documento` asociados es rechazado
- [x] 3.7 Test: eliminar con `RequisitoDocumental` asociados es rechazado
- [x] 3.8 Test: usuario sin permiso recibe 403

## 4. Backend: matriz de requisitos documentales

- [x] 4.1 `app/Http/Requests/PagoProveedores/ActualizarRequisitoDocumentalRequest.php` (nuevo): `tipo_proceso_pago_id` (nullable, `exists:tipos_proceso_pago,id` activo), `tipo_requisito` (nullable, `Rule::enum(TipoRequisitoDocumental::class)`)
- [x] 4.2 `app/Http/Controllers/PagoProveedores/RequisitoDocumentalController.php` (nuevo): `index()` devuelve `Inertia::render(...)` con los `TipoDocumento` activos, `TipoProcesoPago` activos, y el mapa de `RequisitoDocumental` existentes del conjunto `pago_proveedores` (id de tipo_documento + tipo_proceso_pago_id-o-null → tipo_requisito); `update(TipoDocumento $tipoDocumento, ActualizarRequisitoDocumentalRequest $request)` resuelve `conjunto_requisitos_documentales` y `definicion_workflow` por código `pago_proveedores` server-side (nunca desde el request), y hace `updateOrCreate`/`delete` sobre `RequisitoDocumental` con `modalidad_id/estado_workflow_id/monto_desde/monto_hasta` siempre `null`
- [x] 4.3 `routes/pago-proveedores.php`: `GET requisitos-documentales` (`pago-proveedores.requisitos-documentales.index`), `PUT requisitos-documentales/{tipoDocumento}` (`pago-proveedores.requisitos-documentales.update`)
- [x] 4.4 Test: fijar una celda como obligatorio crea el `RequisitoDocumental` correcto (conjunto, definición de workflow, `tipo_proceso_pago_id`, `tipo_requisito`)
- [x] 4.5 Test: fijar "no aplica" sobre una combinación existente elimina la fila
- [x] 4.6 Test: fijar la columna "Todos los tipos" crea la fila con `tipo_proceso_pago_id = null`
- [x] 4.7 Test: el checklist de un caso existente refleja un cambio de la matriz al recargar la página del caso, sin pasos intermedios
- [x] 4.8 Test: la matriz no puede crear ni listar filas del conjunto `adquisiciones` (intentar forzar un `conjunto_requisitos_documentales_id` distinto vía payload no tiene efecto, el servidor siempre usa el de `pago_proveedores`)
- [x] 4.9 Test: usuario sin permiso recibe 403 en `index` y `update`

## 5. Frontend: CRUD de TipoProcesoPago

- [x] 5.1 `resources/js/pages/maestros/tipos-proceso-pago/index.tsx`, `create.tsx`, `edit.tsx`, `show.tsx` — siguiendo el patrón visual de `maestros/cfinancieros/*` pero sin buscador ni paginación (volumen bajo, tabla simple con badge de estado activo/inactivo y acciones en dropdown)
- [x] 5.2 `resources/js/types/maestros.ts` (o archivo de tipos correspondiente): tipo `TipoProcesoPagoMaestro`
- [x] 5.3 Correr `php artisan wayfinder:generate --with-form`

## 6. Frontend: CRUD de TipoDocumento

- [x] 6.1 `resources/js/pages/maestros/tipos-documento/index.tsx`, `create.tsx`, `edit.tsx`, `show.tsx` — mismo patrón que 5.1
- [x] 6.2 Tipo `TipoDocumentoMaestro` en el archivo de tipos de maestros

## 7. Frontend: matriz de requisitos documentales

- [x] 7.1 `resources/js/pages/pago-proveedores/requisitos-documentales/index.tsx` (nuevo): tabla ancha (`overflow-x: auto`) con `TipoDocumento` en filas, `TipoProcesoPago` activos + columna "Todos los tipos" en columnas, cada celda un `Select` de 3 estados (obligatorio/opcional/no aplica) que dispara `router.put(...)` al cambiar, con `preserveScroll: true`
- [x] 7.2 `resources/js/types/pago-proveedores.ts`: tipos para la matriz (`RequisitoDocumentalMatriz` o similar)
- [x] 7.3 Enlace desde la página de administración hacia la matriz y viceversa (breadcrumbs/navegación cruzada entre "Tipos de proceso de pago", "Tipos de documento" y "Requisitos documentales")

## 8. Navegación y verificación

- [x] 8.1 `resources/js/components/app-sidebar.tsx`: agregar los 3 ítems nuevos (Tipos de proceso de pago, Tipos de documento, Requisitos documentales) en los grupos correspondientes (Maestros / Pago de Proveedores), condicionados por los permisos reales del usuario (mismo patrón ya usado para ítems existentes del sidebar)
- [x] 8.2 `composer test` (Pint, PHPStan, Pest) sin errores
- [x] 8.3 `npm run types:check`, `npm run lint:check`, `npm run build` sin errores
- [x] 8.4 Verificar en el navegador: crear el tipo de proceso "Consumos básicos" y el tipo de documento "FURBS" desde los CRUD nuevos, ir a la matriz, marcar Factura como obligatorio y FURBS como opcional para "Consumos básicos", y confirmar que el checklist de un caso clasificado como "Consumos básicos" (real o de prueba) refleja ambos documentos correctamente sin recargar seeds
- [x] 8.5 (cubierto por el test automatizado "la matriz no expone ni permite crear filas del conjunto de requisitos de adquisiciones", que crea un `RequisitoDocumental` real de Adquisiciones y confirma que la matriz ni lo lista ni lo toca) Verificar que la matriz no afecta ni muestra ningún requisito de Adquisiciones (abrir un proceso de adquisición con checklist y confirmar que sus requisitos no cambiaron)

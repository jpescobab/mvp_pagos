## 1. Permisos y seeder

- [x] 1.1 En `database/seeders/WorkflowAdquisicionesSeeder.php`, agregar `adquisiciones.consultar_proceso` y `adquisiciones.crear_proceso` al arreglo de permisos sembrado con `Permission::firstOrCreate`.
- [x] 1.2 En el mismo seeder, asignar ambos permisos al rol `admin` (ya referenciado) y al rol `administrativo_adquisiciones` (`Role::firstOrCreate(['name' => 'administrativo_adquisiciones'])` + `givePermissionTo`), manteniendo idempotencia/aditividad.

## 2. Policy

- [x] 2.1 En `app/Policies/ProcesoAdquisicionPolicy.php`, cambiar `viewAny` y `view` para devolver `$user->can('adquisiciones.consultar_proceso')` en vez de `true`.
- [x] 2.2 En la misma policy, cambiar `create` para devolver `$user->can('adquisiciones.crear_proceso')` en vez de `true`.
- [x] 2.3 Verificar que no se requiere tocar `AppServiceProvider` (la policy ya está registrada en la línea ~125) y que `superadmin` sigue con acceso vía `Gate::before`.

## 3. Frontend (sidebar)

- [x] 3.1 En `resources/js/components/app-sidebar.tsx`, agregar `permiso: 'adquisiciones.consultar_proceso'` al ítem "Procesos" del arreglo `adquisicionesNavItems`, de modo que `filtrarPorPermiso` lo oculte a quien no tenga el permiso.

## 4. Tests

- [x] 4.1 Actualizar los tests que crean/listan/ven procesos vía HTTP para otorgar `adquisiciones.consultar_proceso` y/o `adquisiciones.crear_proceso` al usuario de prueba. Además de `tests/Feature/Adquisiciones/{ApiAdquisicionesTest,ChecklistDocumentalAdquisicionTest}`, también golpean endpoints gated: `tests/Feature/Documentos/HistorialValidacionesDocumentoTest`, `tests/Feature/PagoProveedores/VinculoAdquisicionCasoPagoProveedorTest` (2 tests de `show`) y `tests/Feature/Maestros/SelectoresProveedorPorEstadoTest` (`create`). `ProcesoAdquisicionServiceTest` y los tests de vínculo MP NO golpean endpoints gated (usan el service directo / endpoints MP) — no requieren cambios.
- [x] 4.2 Agregar casos negativos en `ApiAdquisicionesTest`: usuario autenticado sin `adquisiciones.consultar_proceso` recibe 403 en `index` y `show`; usuario con solo consulta (sin `adquisiciones.crear_proceso`) recibe 403 en `create` y `store`, y no se crea ningún `proceso_adquisicion`.
- [x] 4.3 Caso positivo: los tests actualizados de `index`/`show`/`create`/`store` cubren el acceso con permiso; se agrega un test de seeder que afirma que `admin` y `administrativo_adquisiciones` reciben `adquisiciones.consultar_proceso` y `adquisiciones.crear_proceso`.

## 5. Validación

- [x] 5.1 Ejecutar los tests y dejar la suite en verde. Corrida completa de `tests/Feature`: 752/753 (1 fallo detectado y corregido); `tests/Feature/Adquisiciones` final: 105/105.
- [x] 5.2 `vendor/bin/pint --dirty` (passed), `npm run types:check` (tsc, passed), `npm run lint:check` (eslint, passed) y `composer types:check` (PHPStan, 0 errores, por tocar PHP).
- [x] 5.3 Resembrado `WorkflowAdquisicionesSeeder` en la DB de desarrollo e invalidada la caché de permisos (`invalidarParaRol`) de `admin` y `administrativo_adquisiciones`; verificado que ambos roles tienen `adquisiciones.consultar_proceso` y `adquisiciones.crear_proceso`.

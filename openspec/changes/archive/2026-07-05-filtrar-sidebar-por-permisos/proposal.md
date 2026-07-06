## Why

El sidebar muestra hoy casi todos sus ítems a cualquier usuario autenticado, sin importar sus permisos reales. Solo 2 de 19 ítems (Centros Financieros, Centros de Costos) filtran correctamente. Peor aún: para varios de esos ítems (Usuarios, Auditoría, Roles y Permisos, Proveedores, Clientes Medidores, Ítems Presupuestarios) el backend ya exige un permiso específico para *actuar* (ver detalle, crear, editar), pero el listado (`index`) no lo exige — un usuario sin permiso ve el enlace, entra al listado, y solo se topa con el rechazo al intentar ver un detalle o editar. Esto confunde al usuario y expone información de listado que no debería ver.

## What Changes

- Backend: agregar `viewAny()` a `ProveedorPolicy`, `ItemPolicy` y `ClienteMedidorPolicy` (reutilizando `core_institucional.administrar`, el mismo permiso que ya gobierna su `view`/`create`/`update`/`delete`), y llamar `$this->authorize('viewAny', Modelo::class)` en el `index()` de sus 3 controladores — cerrando el hueco de que el listado no estaba protegido aunque el resto del CRUD sí.
- Backend: crear 2 permisos nuevos, `reportabilidad.ver` e `informes.ver`, con sus Policies/Gates y el `authorize()` correspondiente en `PeriodoReportabilidadController@index`, `DefinicionInformeRazonadoController@index` y `EjecucionInformeRazonadoController@index` (hoy sin ningún control de acceso más allá de `auth`, y sin que ninguna spec archivada haya decidido dejarlos abiertos — a diferencia de Workflow/SGF/Sistemas Externos/Indicadores Económicos, que sí tienen esa decisión ya ratificada como "abierto a cualquier autenticado" y por eso quedan fuera de este cambio).
- Backend: asignar los 2 permisos nuevos a los roles `superadmin` y `admin` en `RolesAndPermissionsSeeder`.
- Frontend: `resources/js/components/app-sidebar.tsx` filtra cada ítem individualmente contra `auth.permissions`, no solo por grupo, y oculta un `NavGroup` completo si tras filtrar queda sin ítems. Los ítems cuyo `viewAny()` es intencionalmente público (Casos, Egresos CGU, Procesos de Adquisición, Conectores Playwright, Definiciones de Workflow, Importaciones SGF, Sistemas Externos, Indicadores Económicos) siguen visibles para cualquier autenticado, sin cambios.
- Tests: feature tests nuevos que verifiquen que `index()` rechaza (403) sin el permiso correspondiente y permite (200) con él, para los 5 controladores tocados (Proveedor, Item, ClienteMedidor, PeriodoReportabilidad, DefinicionInformeRazonado/EjecucionInformeRazonado).

## Capabilities

### New Capabilities

(ninguna — no se crea un dominio nuevo)

### Modified Capabilities

- `tablas-maestras-institucionales`: nuevo requirement que exige el permiso `core_institucional.administrar` también para listar (no solo para ver/crear/editar/eliminar) proveedores, ítems presupuestarios y clientes medidores.
- `consulta-catalogo-proveedores`: el requirement "Buscar y listar el catálogo de proveedores" pasa de "abierto a cualquier usuario autenticado" a exigir `core_institucional.administrar` — descubierto durante la implementación como inconsistente con que el resto del CRUD de `Proveedor` ya exige ese permiso.
- `consultar-catalogo-clientes-medidores`: mismo ajuste que el anterior para el requirement "Listar el catálogo de clientes medidores".
- `gestionar-periodos-cortes-reportabilidad`: nuevo requirement que exige el permiso `reportabilidad.ver` para listar períodos y cortes de reportabilidad (las operaciones de abrir/crear/publicar ya documentadas no cambian).
- `gestionar-informes-razonados`: nuevo requirement que exige el permiso `informes.ver` para listar definiciones y ejecuciones de informes razonados (crear/mover por workflow ya documentados no cambian).
- `tema-visual-layout`: el requirement "Navegación principal como riel de íconos" se extiende para exigir que el sidebar filtre cada ítem según los permisos del usuario autenticado y oculte grupos que queden vacíos tras filtrar.

## Impact

- Afecta: `app/Policies/{Proveedor,Item,ClienteMedidor}Policy.php`, sus controladores `index()` en `app/Http/Controllers/Maestros/`, `app/Http/Controllers/Reportabilidad/PeriodoReportabilidadController.php`, `app/Http/Controllers/InformesRazonados/{DefinicionInformeRazonado,EjecucionInformeRazonado}Controller.php`, `database/seeders/RolesAndPermissionsSeeder.php`, `resources/js/components/app-sidebar.tsx`.
- No afecta: jerarquía institucional, workflow de transición de estados, snapshots, ni los módulos cuyo acceso público a la vista ya está ratificado en spec (Workflow, SGF, Sistemas Externos, Indicadores Económicos, Casos, Egresos CGU, Procesos de Adquisición, Conectores Playwright).
- Riesgo de regresión: usuarios sin los permisos nuevos (`reportabilidad.ver`, `informes.ver`) dejarán de ver esos ítems y, si navegan directo a la URL, recibirán 403 en vez del listado — comportamiento deseado, pero a comunicar porque cambia acceso hoy abierto a todo autenticado.

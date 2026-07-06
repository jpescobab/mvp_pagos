## 1. Backend: cerrar el hueco de listado en tablas maestras

- [x] 1.1 Agregar `viewAny(User $user): bool { return $user->can('core_institucional.administrar'); }` a `app/Policies/ProveedorPolicy.php`, `app/Policies/ItemPolicy.php` y `app/Policies/ClienteMedidorPolicy.php`.
- [x] 1.2 Agregar `Gate::authorize('viewAny', Proveedor::class)` al inicio de `ProveedorController::index()`, `Gate::authorize('viewAny', Item::class)` a `ItemController::index()` y `Gate::authorize('viewAny', ClienteMedidor::class)` a `ClienteMedidorController::index()`, calcando el patrón ya usado en `CfinancieroController::index()`.

## 2. Backend: permisos nuevos para Reportabilidad e Informes Razonados

- [x] 2.1 Agregar `reportabilidad.ver` e `informes.ver` al arreglo de permisos y a `syncPermissions()` de `superadmin` y `admin` en `database/seeders/RolesAndPermissionsSeeder.php`.
- [x] 2.2 Crear `app/Policies/PeriodoReportabilidadPolicy.php` con `viewAny(User $user): bool { return $user->can('reportabilidad.ver'); }` y agregar `Gate::authorize('viewAny', PeriodoReportabilidad::class)` al inicio de `PeriodoReportabilidadController::index()`.
- [x] 2.3 Crear `app/Policies/DefinicionInformeRazonadoPolicy.php` y `app/Policies/EjecucionInformeRazonadoPolicy.php`, ambas con `viewAny(User $user): bool { return $user->can('informes.ver'); }`, y agregar `Gate::authorize('viewAny', DefinicionInformeRazonado::class)` / `Gate::authorize('viewAny', EjecucionInformeRazonado::class)` al inicio de `DefinicionInformeRazonadoController::index()` y `EjecucionInformeRazonadoController::index()` respectivamente. También se registraron las 3 policies nuevas en `AppServiceProvider::configureAuthorization()` (`Gate::policy(...)`), siguiendo el patrón ya usado ahí para el resto de policies del proyecto — no estaba explícito en la tarea pero es necesario para que Laravel las resuelva.

## 3. Frontend: filtrar el sidebar por permiso

- [x] 3.1 En `resources/js/components/app-sidebar.tsx`, agregar un campo opcional `permiso?: string` a los objetos de los arreglos `administracionNavItems`, `estructuraInstitucionalNavItems`, `reportabilidadNavItems` (solo los ítems de Períodos e Informes, no Definiciones/Ejecuciones de Workflow) con el permiso que le corresponde a cada ítem: Usuarios→`usuarios.ver`, Auditoría→`auditoria.ver`, Roles y Permisos→`roles.administrar`, Proveedores/Clientes Medidores/Ítems Presupuestarios/Centros Financieros/Centros de Costos→`core_institucional.administrar`, Períodos de Reportabilidad→`reportabilidad.ver`, Definiciones de Informes/Ejecuciones de Informes→`informes.ver`. Los ítems sin `permiso` (Definiciones de Workflow, Casos, Egresos CGU, Importaciones SGF, Procesos, Sistemas Externos, Conectores Playwright, Indicadores Económicos) quedan sin ese campo.
- [x] 3.2 Agregar una función `filtrarPorPermiso(items: (NavItem & { permiso?: string })[], permisos: string[]): NavItem[]` que retorne solo los ítems sin `permiso` o cuyo `permiso` esté incluido en `permisos`, y aplicarla a cada arreglo antes de pasarlo a `NavGroup`/`NavMain`, reemplazando la lógica ad-hoc actual de `puedeAdministrarEstructura`.
- [x] 3.3 Antes de renderizar cada `<NavGroup>`, calcular sus ítems filtrados y omitir el `<NavGroup>` completo (no renderizarlo) si el arreglo resultante queda vacío.

## 4. Tests

- [x] 4.1 Ya existían tests de índice para Proveedor/Item/ClienteMedidor (`ConsultarCatalogoProveedoresTest.php`, `ConsultarCatalogoItemsTest.php`, `ConsultarCatalogoClientesMedidoresTest.php`) que asumían acceso abierto a cualquier autenticado — se actualizaron para sembrar permisos y autenticar con `core_institucional.administrar`, y se agregó a cada uno un caso "sin el permiso no puede listar" (403). Esto reveló un conflicto no detectado en el diseño: 2 specs ya ratificadas (`consulta-catalogo-proveedores`, `consultar-catalogo-clientes-medidores`) decían "abierto a cualquier autenticado" — se agregaron sus deltas MODIFIED correspondientes (confirmado con el usuario).
- [x] 4.2 Cubierto junto con 4.1 (mismo commit de tests, mismo hallazgo).
- [x] 4.3 `tests/Feature/Reportabilidad/IndexPeriodoReportabilidadAutorizacionTest.php`: verificar 403 sin `reportabilidad.ver` y 200 con él, para `reportabilidad.periodos.index`.
- [x] 4.4 `tests/Feature/InformesRazonados/IndexInformeRazonadoAutorizacionTest.php`: verificar 403 sin `informes.ver` y 200 con él, para `informes-razonados.definiciones.index` e `informes-razonados.ejecuciones.index`.

## 5. Validación

- [x] 5.1 `vendor/bin/pint --dirty --format agent` sobre los archivos PHP modificados. Passed.
- [x] 5.2 `npm run types:check` y `npm run lint:check` sobre el frontend. Sin errores.
- [x] 5.3 `php artisan test --compact --filter="Autorizacion|ConsultarCatalogo"`. 34/34 passed.
- [x] 5.4 Verificado en el navegador (build de producción, `npm run build` + servidor `php artisan serve`) con dos usuarios: `sadmin@pjud.cl` (superadmin, ve los 14 ítems gateados + los de acceso abierto) y un usuario de prueba sin ningún permiso (creado y eliminado en la misma verificación) que solo ve los ítems de acceso abierto (Definiciones de Workflow, Casos, Egresos CGU, Importaciones SGF, Procesos, Sistemas Externos, Conectores Playwright, Indicadores Económicos) — el grupo "Reportabilidad" completo desaparece del sidebar al no tener `reportabilidad.ver` ni `informes.ver`. Confirmado además que navegar directo a `/maestros/proveedores` y `/reportabilidad/periodos` sin el permiso devuelve 403 (no solo se oculta en el sidebar, también se rechaza en el servidor).
- [x] 5.5 `composer test` completo antes de cerrar el change. También se actualizó `tests/Feature/Seguridad/RolesAndPermissionsSeederTest.php` (test de lista exhaustiva de permisos, no estaba en el plan original) para incluir `reportabilidad.ver`/`informes.ver`. 377/377 passed (373 passed + 4 skipped preexistentes), Pint y PHPStan sin errores.

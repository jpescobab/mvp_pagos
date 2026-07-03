## 1. Backend: autorización

- [x] 1.1 Crear `app/Policies/CfinancieroPolicy.php` con `viewAny(User $user): bool` que retorna `$user->can('core_institucional.administrar')`.
- [x] 1.2 Crear `app/Policies/CcostoPolicy.php` con el mismo patrón.
- [x] 1.3 Registrar ambas policies en `app/Providers/AppServiceProvider.php` (`Gate::policy(Cfinanciero::class, CfinancieroPolicy::class)` y `Gate::policy(Ccosto::class, CcostoPolicy::class)`).

## 2. Backend: catálogo de centros financieros

- [x] 2.1 Crear `app/Http/Resources/Maestros/CfinancieroResource.php` con `id`, `codigo`, `nombre`, `activo` y `jurisdiccion` (`{ id, nombre }`).
- [x] 2.2 Crear `app/Http/Controllers/Maestros/CfinancieroController.php` con `index()`: `Gate::authorize('viewAny', Cfinanciero::class)`, búsqueda por `codigo`/`nombre` vía `q`, `with('jurisdiccion')`, `orderBy('codigo')`, `paginate(20)->withQueryString()`.
- [x] 2.3 Registrar la ruta `GET maestros/cfinancieros` → `maestros.cfinancieros.index` en `routes/maestros.php`.

## 3. Backend: catálogo de centros de costo

- [x] 3.1 Crear `app/Http/Resources/Maestros/CcostoResource.php` con `id`, `codigo`, `nombre`, `cod_edificio`, `activo` y `cfinanciero` (`{ id, nombre }`).
- [x] 3.2 Crear `app/Http/Controllers/Maestros/CcostoController.php` con `index()`: `Gate::authorize('viewAny', Ccosto::class)`, búsqueda por `codigo`/`nombre` vía `q`, `with('cfinanciero')`, `orderBy('codigo')`, `paginate(20)->withQueryString()`.
- [x] 3.3 Registrar la ruta `GET maestros/ccostos` → `maestros.ccostos.index` en `routes/maestros.php`.

## 4. Frontend: tipos y permisos compartidos

- [x] 4.1 Agregar `Cfinanciero` y `Ccosto` a `resources/js/types/maestros.ts`.
- [x] 4.2 Agregar `permissions: string[]` al share `auth` en `app/Http/Middleware/HandleInertiaRequests.php` (`$request->user()?->getAllPermissions()->pluck('name') ?? []`).
- [x] 4.3 Extender `Auth` en `resources/js/types/auth.ts` a `{ user: User; permissions: string[] }`.

## 5. Frontend: componentes compartidos

- [x] 5.1 Crear `resources/js/components/maestros/cfinanciero-status-badge.tsx` (activo/inactivo, mismo patrón que `proveedor-status-badge.tsx`).
- [x] 5.2 Crear `resources/js/components/maestros/cfinanciero-actions-menu.tsx` (menú desplegable con "Ver detalle" deshabilitado y tooltip "Disponible próximamente", mismo patrón que `proveedor-actions-menu.tsx`).
- [x] 5.3 Crear `resources/js/components/maestros/ccosto-status-badge.tsx` y `resources/js/components/maestros/ccosto-actions-menu.tsx` con el mismo patrón.

## 6. Frontend: páginas de listado

- [x] 6.1 Ejecutar Wayfinder (`php artisan wayfinder:generate --with-form`, igual que la configuración `formVariants: true` del plugin de Vite) para generar `resources/js/routes/maestros/cfinancieros` y `.../ccostos` a partir de las rutas nuevas.
- [x] 6.2 Crear `resources/js/pages/maestros/cfinancieros/index.tsx` siguiendo el patrón denso de `proveedores/index.tsx`: columnas código, nombre, jurisdicción, estado, acciones; búsqueda con debounce; paginación; breadcrumb.
- [x] 6.3 Crear `resources/js/pages/maestros/ccostos/index.tsx` con el mismo patrón: columnas código, nombre, centro financiero, código de edificio (`—` si es null), estado, acciones.

## 7. Frontend: navegación

- [x] 7.1 Agregar "Centros Financieros" y "Centros de Costos" al grupo "Maestros" en `resources/js/components/app-sidebar.tsx`, visibles solo si `auth.permissions.includes('core_institucional.administrar')`.

## 8. Tests automatizados

- [x] 8.1 Crear `tests/Feature/Maestros/ConsultarCatalogoCentrosFinancierosTest.php` (listar, buscar por código, acceso denegado sin el permiso).
- [x] 8.2 Crear `tests/Feature/Maestros/ConsultarCatalogoCentrosDeCostoTest.php` (listar, buscar por código, `cod_edificio` nulo, acceso denegado sin el permiso).

## 9. Verificación

- [x] 9.1 Levantar el servidor de desarrollo y verificar en el preview, autenticado como superadmin: ambos listados cargan, buscan y paginan correctamente; las entradas del sidebar aparecen.
- [x] 9.2 Verificar acceso denegado sin el permiso `core_institucional.administrar` mediante los tests automatizados (8.1/8.2) en vez de prueba manual.
- [x] 9.3 Ejecutar `composer test` (incluye `lint:check` + `types:check` + suite Pest), `npm run lint:check`, `npm run format:check` y `npm run types:check`.

## 10. Documentación y cierre

- [x] 10.1 Ejecutar `/opsx:archive` para archivar el change (crea `openspec/specs/consulta-catalogo-centros-financieros-costos/spec.md`).

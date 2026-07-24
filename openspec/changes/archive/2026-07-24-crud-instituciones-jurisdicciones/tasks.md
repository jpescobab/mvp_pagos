## 1. Preparación

- [x] 1.1 Crear la rama de trabajo a partir de `feature/auditar-crud-tablas-maestras` (o de `master` si el PR #27 ya está fusionado), verificando que `app/Models/Concerns/RegistraAuditoria.php` exista antes de continuar
- [x] 1.2 Releer `app/Http/Controllers/Maestros/CfinancieroController.php`, sus Form Requests, su Resource, su Policy y `resources/js/pages/maestros/cfinancieros/*.tsx` como referencia exacta de patrón a replicar

## 2. Autorización

- [x] 2.1 Crear `app/Policies/InstitucionPolicy.php` con `viewAny`, `view`, `create`, `update` y `delete`, todos contra el permiso `core_institucional.administrar` (espejo de `CfinancieroPolicy`)
- [x] 2.2 Crear `app/Policies/JurisdiccionPolicy.php` con los mismos cinco métodos y el mismo permiso
- [x] 2.3 Registrar ambas policies con `Gate::policy(...)` en `AppServiceProvider::configureAuthorization()` — sin este paso no surten efecto (no hay auto-discovery)

## 3. Backend de instituciones

- [x] 3.1 Crear `app/Http/Requests/Maestros/StoreInstitucionRequest.php`: `authorize()` contra `core_institucional.administrar`; reglas `codigo` requerido/string/max:255/`unique:instituciones,codigo`, `nombre` requerido/string/max:255, `activo` booleano opcional
- [x] 3.2 Crear `app/Http/Requests/Maestros/UpdateInstitucionRequest.php`: mismas reglas con `Rule::unique('instituciones','codigo')->ignore($institucion->id)` tomando el modelo desde `$this->route('institucion')`
- [x] 3.3 Crear `app/Http/Resources/Maestros/InstitucionResource.php` exponiendo `id`, `codigo`, `nombre`, `activo` y, cuando esté cargado, `jurisdicciones_count` y la lista de jurisdicciones (`id`, `codigo`, `nombre`, `activo`)
- [x] 3.4 Crear `app/Http/Controllers/Maestros/InstitucionController.php` con `index` (búsqueda parcial en `codigo`/`nombre`, `withCount('jurisdicciones')`, `orderBy('codigo')`, `paginate(20)->withQueryString()`), `create`, `store`, `show` (carga sus jurisdicciones ordenadas por código), `edit`, `update` y `destroy`, cada uno con su `Gate::authorize` y su `Inertia::flash('toast', ...)`, siguiendo `CfinancieroController` línea por línea
- [x] 3.5 En `InstitucionController::destroy`, bloquear la eliminación con un método privado `relacionQueImpideEliminar()` que devuelva `'jurisdicciones'` si `$institucion->jurisdicciones()->exists()`, con flash de error y `back()` — sin extraer a Service (decisión registrada en `design.md`)
- [x] 3.6 Registrar en `routes/maestros.php` las siete rutas de instituciones (`index`, `create`, `store`, `show`, `edit`, `update`, `destroy`) con los mismos verbos, URIs y nombres que el bloque de `cfinancieros`

## 4. Backend de jurisdicciones

- [x] 4.1 Crear `app/Http/Requests/Maestros/StoreJurisdiccionRequest.php`: reglas `institucion_id` requerido/`exists:instituciones,id`, `codigo` requerido/`unique:jurisdicciones,codigo`, `nombre` requerido, `descripcion` opcional/nullable/max:255, `activo` booleano opcional
- [x] 4.2 Crear `app/Http/Requests/Maestros/UpdateJurisdiccionRequest.php` con `Rule::unique('jurisdicciones','codigo')->ignore($jurisdiccion->id)`
- [x] 4.3 Crear `app/Http/Resources/Maestros/JurisdiccionResource.php` exponiendo `id`, `codigo`, `nombre`, `descripcion`, `activo`, la institución (`id`, `codigo`, `nombre`) y, cuando estén cargados, `cfinancieros_count` y la lista de centros financieros (`id`, `codigo`, `nombre`, `activo`)
- [x] 4.4 Crear `app/Http/Controllers/Maestros/JurisdiccionController.php` con las siete acciones; `index` con `with('institucion')`, búsqueda parcial en `codigo`/`nombre` y paginación de 20; `show` cargando la institución y los centros financieros ordenados por código; `create`/`edit` entregando el catálogo de instituciones activas para el `<select>` mediante un método privado `catalogos()`
- [x] 4.5 En `JurisdiccionController::destroy`, bloquear con `relacionQueImpideEliminar()` que devuelva `'centros financieros'` si `$jurisdiccion->cfinancieros()->exists()`
- [x] 4.6 Registrar en `routes/maestros.php` las siete rutas de jurisdicciones, ubicadas antes del bloque de `cfinancieros` para que el archivo siga el orden jerárquico

## 5. Auditoría

- [x] 5.1 Aplicar el trait `RegistraAuditoria` a `app/Models/Institucion.php` y verificar que las acciones se registren como `crear_institucion` / `editar_institucion` / `eliminar_institucion`
- [x] 5.2 Aplicar el trait `RegistraAuditoria` a `app/Models/Jurisdiccion.php` y verificar la convención equivalente para jurisdicciones

## 6. Frontend

- [x] 6.1 Regenerar Wayfinder con `php artisan wayfinder:generate --with-form` y confirmar que aparecen `resources/js/routes/maestros/instituciones` y `.../jurisdicciones`
- [x] 6.2 Crear `resources/js/pages/maestros/instituciones/index.tsx` siguiendo el patrón de listado denso de `cfinancieros/index.tsx`: búsqueda con debounce 300 ms, columnas código / nombre (con avatar de iniciales) / cantidad de jurisdicciones / estado con badge `success`|`danger`, dropdown de acciones y paginación simple
- [x] 6.3 Crear `resources/js/pages/maestros/instituciones/show.tsx` con los atributos de la institución y la tabla de sus jurisdicciones enlazadas a su detalle, con estado vacío explícito
- [x] 6.4 Crear `resources/js/pages/maestros/instituciones/{create,edit}.tsx` con los campos código, nombre y activo, usando el mismo formulario y manejo de errores que las páginas de `cfinancieros`
- [x] 6.5 Crear `resources/js/pages/maestros/jurisdicciones/index.tsx` con columnas código / nombre / institución / descripción truncada con tooltip y fallback `"—"` / estado
- [x] 6.6 Crear `resources/js/pages/maestros/jurisdicciones/show.tsx` con los atributos, la institución enlazada a su detalle y la tabla de centros financieros enlazados al suyo, con estado vacío explícito
- [x] 6.7 Crear `resources/js/pages/maestros/jurisdicciones/{create,edit}.tsx` con `<select>` de institución, código, nombre, descripción y activo
- [x] 6.8 Agregar los ítems "Instituciones" y "Jurisdicciones" al arreglo `estructuraInstitucionalNavItems` de `resources/js/components/app-sidebar.tsx`, antes de Centros Financieros, con `permiso: 'core_institucional.administrar'` y un icono de lucide coherente con los existentes

## 7. Tests

- [x] 7.1 `tests/Feature/Maestros/ConsultarCatalogoInstitucionesTest.php`: listar con permiso, buscar por código y por nombre, conteo de jurisdicciones visible, y acceso denegado sin permiso
- [x] 7.2 `tests/Feature/Maestros/ShowInstitucionTest.php`: detalle con jurisdicciones, detalle sin jurisdicciones, y acceso denegado sin permiso
- [x] 7.3 `tests/Feature/Maestros/StoreInstitucionTest.php`: creación válida queda activa, código duplicado falla la validación, y creación denegada sin permiso
- [x] 7.4 `tests/Feature/Maestros/UpdateInstitucionTest.php`: edición válida, guardar sin cambiar el código no reporta duplicado, código de otra institución falla, y edición denegada sin permiso
- [x] 7.5 `tests/Feature/Maestros/DestroyInstitucionTest.php`: eliminación sin jurisdicciones, eliminación bloqueada con jurisdicciones (verificando que nada se borró), y eliminación denegada sin permiso
- [x] 7.6 `tests/Feature/Maestros/ConsultarCatalogoJurisdiccionesTest.php`: listar con institución asociada, buscar, jurisdicción sin descripción no rompe el listado, y acceso denegado sin permiso
- [x] 7.7 `tests/Feature/Maestros/ShowJurisdiccionTest.php`: detalle con y sin centros financieros, y acceso denegado sin permiso
- [x] 7.8 `tests/Feature/Maestros/StoreJurisdiccionTest.php`: creación válida, código duplicado, institución inexistente, y creación denegada sin permiso
- [x] 7.9 `tests/Feature/Maestros/UpdateJurisdiccionTest.php`: edición válida, conservar el propio código, reasignar a otra institución, y edición denegada sin permiso
- [x] 7.10 `tests/Feature/Maestros/DestroyJurisdiccionTest.php`: eliminación sin centros financieros, eliminación bloqueada con centros financieros, y eliminación denegada sin permiso
- [x] 7.11 Extender `tests/Feature/Maestros/AuditoriaTablasMaestrasTest.php` con los casos de instituciones y jurisdicciones: crear/editar/eliminar dejan registro con usuario y diff, y una siembra sin usuario autenticado no genera registros

## 8. Validación y cierre

- [x] 8.1 Correr `php artisan test --compact tests/Feature/Maestros/` y dejar la carpeta completa en verde
- [x] 8.2 Correr la suite completa (`php artisan test`) para descartar regresiones en otros dominios
- [x] 8.3 Correr `vendor/bin/pint --dirty --format agent`, `composer types:check`, `npm run types:check` y `npm run lint:check`
- [x] 8.4 Revisar los dos controladores nuevos contra la regla de controladores livianos antes de cerrar: confirmar que no quedó ninguna `DB::transaction`, `whereHas` de negocio ni `app(Clase::class)` dentro de ellos
- [ ] 8.5 Verificar en el navegador el recorrido completo de la jerarquía: institución → sus jurisdicciones → una jurisdicción → sus centros financieros, y la vuelta hacia arriba

## 1. Permiso y autorización

- [x] 1.1 Agregar `informes.administrar` al arreglo `$permisos` de `database/seeders/WorkflowInformesRazonadosSeeder.php` (se otorga al rol `admin` en el `givePermissionTo` existente, junto a `informes.aprobar`/`informes.publicar`)
- [x] 1.2 Agregar los métodos `create`, `update` y `delete` a `app/Policies/DefinicionInformeRazonadoPolicy.php`, todos contra `informes.administrar` (dejar `viewAny`/`view` como están, contra `informes.ver`)
- [x] 1.3 Verificar que `DefinicionInformeRazonado::class` ya esté registrado con `Gate::policy(...)` en `AppServiceProvider::configureAuthorization()`; si no, registrarlo

## 2. Auditoría

- [x] 2.1 Aplicar el trait `RegistraAuditoria` a `app/Models/DefinicionInformeRazonado.php` y confirmar la relación `ejecuciones()` existente (se reutiliza para el bloqueo de eliminación y el conteo)

## 3. Form Requests

- [x] 3.1 En `app/Http/Requests/InformesRazonados/CrearDefinicionInformeRazonadoRequest.php`: agregar `authorize()` que retorne `$this->user()?->can('informes.administrar')`; agregar a las reglas `unique:definiciones_informe_razonado,codigo` y `activo` booleano opcional
- [x] 3.2 Crear `app/Http/Requests/InformesRazonados/ActualizarDefinicionInformeRazonadoRequest.php`: `authorize()` contra `informes.administrar`; reglas iguales con `Rule::unique('definiciones_informe_razonado','codigo')->ignore($definicion->id)` tomando el modelo desde `$this->route('definicion')`

## 4. Controlador

- [x] 4.1 En `DefinicionInformeRazonadoController::index`: agregar búsqueda parcial en `codigo`/`nombre` (`when($q !== '', ...)`) y `paginate(20)->withQueryString()`; mantener `withCount('ejecuciones')` y el `Gate::authorize('viewAny', ...)`
- [x] 4.2 En `DefinicionInformeRazonadoController::store`: agregar `Gate::authorize('create', DefinicionInformeRazonado::class)`; redirigir a `informes-razonados.definiciones.index` con `Inertia::flash('toast', ...)` en vez de `back()`
- [x] 4.3 Agregar `create()` (autoriza `create`, renderiza el formulario) y `show(DefinicionInformeRazonado $definicion)` (autoriza `view`, carga las ejecuciones con su corte/periodo y estado de workflow ordenadas por fecha, renderiza el detalle)
- [x] 4.4 Agregar `edit(DefinicionInformeRazonado $definicion)` (autoriza `update`) y `update(ActualizarDefinicionInformeRazonadoRequest $request, DefinicionInformeRazonado $definicion)` (autoriza `update`, actualiza y redirige a `show` con toast)
- [x] 4.5 Agregar `destroy(DefinicionInformeRazonado $definicion)` (autoriza `delete`); bloquear con un método privado `relacionQueImpideEliminar()` que devuelva `'ejecuciones'` si `$definicion->ejecuciones()->exists()`, con flash de error y `back()`; si no, eliminar y redirigir al índice con toast
- [x] 4.6 Actualizar `DefinicionInformeRazonadoResource` si hace falta para exponer, en el detalle, la lista de ejecuciones (id, corte/fecha, estado de workflow, enlace) además de `ejecuciones_count`

## 5. Rutas

- [x] 5.1 En `routes/informes-razonados.php` agregar, en el grupo existente, las rutas `definiciones/crear` (create), `definiciones/{definicion}` (show), `definiciones/{definicion}/editar` (edit), `PATCH definiciones/{definicion}` (update) y `DELETE definiciones/{definicion}` (destroy), conservando `index` y `store`

## 6. Frontend

- [x] 6.1 Regenerar Wayfinder con `php artisan wayfinder:generate --with-form`
- [x] 6.2 Reescribir `resources/js/pages/informes-razonados/definiciones/index.tsx` al patrón de listado denso de `maestros/cfinancieros/index.tsx`: quitar el formulario incrustado y el `text-green-600`; búsqueda con debounce 300 ms, columnas código / nombre (avatar de iniciales) / descripción truncada con fallback `"—"` / estado con badge `success`|`danger` / cantidad de ejecuciones, dropdown de acciones (ver/editar/eliminar con diálogo de confirmación) y paginación; botón "Nueva definición" hacia `create`
- [x] 6.3 Crear `resources/js/pages/informes-razonados/definiciones/create.tsx` con los campos código, nombre, descripción y activo, y manejo de errores como las páginas de Maestros
- [x] 6.4 Crear `resources/js/pages/informes-razonados/definiciones/show.tsx` con los atributos de la definición y la tabla de sus ejecuciones (fecha del corte, estado de workflow, enlace al detalle de la ejecución), con estado vacío explícito
- [x] 6.5 Crear `resources/js/pages/informes-razonados/definiciones/edit.tsx` con los campos código, nombre, descripción y activo, precargados
- [x] 6.6 Crear los componentes `definicion-informe-razonado-status-badge` y `-actions-menu` (o reutilizar el patrón de badge/menú de Maestros) si el índice los necesita separados; ajustar el tipo `DefinicionInformeRazonado` en `resources/js/types/informes-razonados.ts` (agregar `ejecuciones` opcional para el detalle)

## 7. Tests

- [x] 7.1 `tests/Feature/InformesRazonados/CrearDefinicionInformeRazonadoTest.php` (o extender el existente si lo hay): crear con `informes.administrar` funciona; **crear sin el permiso es rechazado (assertForbidden) y no crea nada** (el caso que hoy está roto); código duplicado falla la validación
- [x] 7.2 `tests/Feature/InformesRazonados/ConsultarDefinicionesInformeRazonadoTest.php`: listar con `informes.ver`, buscar por código y por nombre, conteo de ejecuciones visible, y acceso denegado sin permiso
- [x] 7.3 `tests/Feature/InformesRazonados/ShowDefinicionInformeRazonadoTest.php`: detalle con y sin ejecuciones, y acceso denegado sin `informes.ver`
- [x] 7.4 `tests/Feature/InformesRazonados/ActualizarDefinicionInformeRazonadoTest.php`: editar con `informes.administrar`, conservar el propio código, código de otra definición falla, y edición denegada sin permiso
- [x] 7.5 `tests/Feature/InformesRazonados/EliminarDefinicionInformeRazonadoTest.php`: eliminar sin ejecuciones; eliminación bloqueada con ejecuciones (verificando que nada se borró); eliminación denegada sin permiso
- [x] 7.6 Test de auditoría: crear/editar/eliminar una definición con usuario autenticado deja registro en `audit_logs` con el diff; sin usuario no genera registros
- [x] 7.7 Actualizar `WorkflowInformesRazonadosSeeder`-dependientes si algún test afirma el conjunto de permisos del rol `admin` del módulo (agregar `informes.administrar` a la aserción)

## 8. Validación y cierre

- [x] 8.1 Correr `php artisan test --compact tests/Feature/InformesRazonados/` y dejarlo en verde (34/34)
- [x] 8.2 Correr la suite completa (`php artisan test`) para descartar regresiones (735 tests, 731 en verde, 4 skipped, 0 fallidos)
- [x] 8.3 Correr `vendor/bin/pint --dirty --format agent`, `composer types:check`, `npm run types:check` y `npm run lint:check`
- [x] 8.4 Revisar el controlador contra la regla de controladores livianos: confirmar que no quedó `DB::transaction`, `whereHas` de negocio ni `app(Clase::class)` dentro; el bloqueo por dependencias queda en método privado como en Maestros
- [x] 8.5 Verificar en el navegador el recorrido: listar definiciones → crear → ver detalle con sus ejecuciones → editar → intentar eliminar una con ejecuciones (rechazada) y una sin ejecuciones (eliminada). Verificado en `pagos.test` (listar/crear/detalle/editar/eliminar OK). Ajuste: contraste de la opción "Eliminar" del menú de acciones corregido en el primitivo compartido `dropdown-menu.tsx` (`text-destructive` en reposo en vez de `text-destructive-foreground`, que en modo claro era blanco sobre fondo claro)

## 1. Permiso y seeder

- [x] 1.1 En `database/seeders/WorkflowInformesRazonadosSeeder.php` agregar `informes.elaborar` al arreglo `$permisos` (junto a `informes.administrar`/`informes.aprobar`/`informes.publicar`) para que se cree con `Permission::firstOrCreate` y quede otorgado al rol `admin`.
- [x] 1.2 En el mismo seeder, agregar `permiso_requerido` a las transiciones sembradas: `enviar_a_revision` → `informes.elaborar`; `rechazar` → `informes.aprobar`. Mantener `aprobar`/`publicar` como están.

## 2. Autorización backend

- [x] 2.1 En `app/Policies/EjecucionInformeRazonadoPolicy.php` agregar el método `create(User $user): bool` que devuelva `$user->can('informes.elaborar')`. (La policy ya está registrada en `AppServiceProvider::configureAuthorization()`; no requiere registro nuevo.)
- [x] 2.2 En `app/Http/Requests/InformesRazonados/IniciarEjecucionInformeRazonadoRequest.php` agregar `authorize(): bool` que devuelva `(bool) $this->user()?->can('informes.elaborar')`.
- [x] 2.3 En `app/Http/Controllers/InformesRazonados/EjecucionInformeRazonadoController.php`, método `store`, agregar `Gate::authorize('create', EjecucionInformeRazonado::class)` como primera línea.

## 3. Gating de UI

- [x] 3.1 En `resources/js/pages/informes-razonados/ejecuciones/index.tsx` leer `auth.permissions` (vía `usePage`/tipos existentes) y ocultar el control de "Iniciar ejecución" (formulario/selects/botón) cuando el usuario no tenga `informes.elaborar`. No hardcodear rutas nuevas.

## 4. Tests

- [x] 4.1 Crear `tests/Feature/InformesRazonados/IniciarEjecucionInformeRazonadoAutorizacionTest.php` con: iniciar con `informes.elaborar` sobre un corte publicado crea la ejecución y su `Proceso` en `en_elaboracion`; iniciar sin el permiso responde 403 y no crea nada; iniciar sobre un corte `borrador` (con permiso) es rechazado.
- [x] 4.2 Agregar cobertura de las transiciones gateadas: `enviar_a_revision` exige `informes.elaborar` (sin él, bloquea y el estado no cambia); `rechazar` exige `informes.aprobar` (sin él, bloquea). Reutilizar helpers/factories existentes del dominio.
- [x] 4.3 Revisar y ajustar los tests existentes que ejerzan estas rutas/transiciones (`InformeRazonadoServiceTest`, `GestionarInformesRazonadosTest`, `IndexInformeRazonadoAutorizacionTest`) para que el usuario de prueba tenga los permisos ahora requeridos donde corresponda.

## 5. Validación

- [x] 5.1 `vendor/bin/pint --dirty --format agent` sobre los PHP tocados.
- [x] 5.2 Correr la suite de informes razonados (`php artisan test --compact tests/Feature/InformesRazonados/`) y `npm run types:check` para el cambio de frontend; que todo pase.
- [x] 5.3 Verificar que `RolesAndPermissionsSeederTest` sigue verde (no se tocó la lista de permisos core).

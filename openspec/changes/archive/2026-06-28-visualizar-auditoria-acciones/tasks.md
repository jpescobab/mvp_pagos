## 1. Backend

- [x] 1.1 Agregar permiso `auditoria.ver` a `RolesAndPermissionsSeeder` (superadmin y admin) y actualizar `RolesAndPermissionsSeederTest`.
- [x] 1.2 Crear `App\Policies\AuditLogPolicy::viewAny(User $user): bool` delegando en `$user->can('auditoria.ver')`, y registrarla en `AppServiceProvider::configureAuthorization()`.
- [x] 1.3 Crear `App\Http\Resources\Seguridad\AuditLogResource` exponiendo `id`, `user` (nombre), `action`, `auditable_type`, `auditable_id`, `before`, `after`, `metadata`, `created_at`.
- [x] 1.4 Crear `App\Http\Controllers\Seguridad\AuditoriaController::index()`: `Gate::authorize('viewAny', AuditLog::class)`, pagina `AuditLog::with('user')` ordenado por `id` descendente.
- [x] 1.5 Crear `routes/seguridad.php` con `GET /auditoria` (middleware `auth`) y registrarla en `routes/web.php`.

## 2. Frontend

- [x] 2.1 Agregar tipo `AuditLogEntry` en `resources/js/types/seguridad.ts`.
- [x] 2.2 Crear `resources/js/pages/auditoria/index.tsx`: tabla paginada (usuario, acción, entidad, fecha) con fila expandible mostrando `before`/`after`/`metadata` como JSON, estado vacío explícito.
- [x] 2.3 Agregar entrada "Auditoría" en `mainNavItems` de `resources/js/components/app-sidebar.tsx`.

## 3. Tests y validación

- [x] 3.1 Feature test: listar auditoría con el permiso requerido devuelve los registros paginados.
- [x] 3.2 Feature test: usuario sin `auditoria.ver` es bloqueado y queda auditado como acceso denegado.
- [x] 3.3 Feature test: usuario no autenticado es redirigido al login.
- [x] 3.4 Ejecutar `composer test` y `npm run lint:check`/`npm run types:check`.
- [x] 3.5 Verificación manual en navegador: confirmar que los registros reales ya existentes (transiciones de workflow, vínculo de adquisición) se muestran correctamente y que un usuario sin el permiso es bloqueado.

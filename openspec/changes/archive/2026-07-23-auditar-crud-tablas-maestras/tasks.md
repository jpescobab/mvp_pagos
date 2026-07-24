## 1. Trait de auditoría

- [x] 1.1 Crear `app/Models/Concerns/RegistraAuditoria.php`: un trait con `protected static function booted(): void` que registra observers para los eventos `created`, `updated` y `deleted` de Eloquent. Cada observer, **solo si `Auth::check()`**, llama a `app(AuditLogger::class)->log(...)` (decisión 2 del design: sin usuario no se audita).
- [x] 1.2 En el trait, derivar la acción con la convención `<verbo>_<entidad>`: `created → crear`, `updated → editar`, `deleted → eliminar`, más `Str::snake(class_basename($modelo))` (p. ej. `crear_cfinanciero`, `editar_proveedor`, `eliminar_tipo_documento`). Derivación automática, sin override por modelo (YAGNI): ninguno de los nueve lo necesita.
- [x] 1.3 En el trait, armar `before`/`after` desde el diff de Eloquent (decisión 4): created → `after = getAttributes()`, `before = []`; updated → `after = getChanges()` y `before` los mismos campos desde `getOriginal()`; deleted → `before = getOriginal()`, `after = []`. Pasar la entidad como `$auditable` para que `AuditLogger` complete `auditable_type`/`auditable_id`.

## 2. Aplicar el trait a los modelos maestros

- [x] 2.1 Agregar `use RegistraAuditoria;` a los nueve modelos: `Cfinanciero`, `Ccosto`, `Proveedor`, `ClienteMedidor`, `Item`, `Asignacion`, `Catalogo`, `TipoDocumento`, `TipoProcesoPago`. No tocar los controladores de `Maestros`.
- [x] 2.2 Verificar que ningún modelo maestro defina ya su propio `booted()` que el trait pisaría; si alguno lo hace, componer ambos (llamar a `parent::booted()` o combinar los registros de eventos) en vez de sobrescribir.

## 3. Tests

- [x] 3.1 Crear `tests/Feature/Maestros/AuditoriaTablasMaestrasTest.php`: bajo `actingAs`, crear un `Cfinanciero` genera un `audit_log` con `action = crear_cfinanciero`, `auditable_type`/`auditable_id` correctos, `user_id` del actor y `after` con los atributos; editar genera `editar_cfinanciero` con `before`/`after` acotados al campo cambiado; eliminar genera `eliminar_cfinanciero` con `before`.
- [x] 3.2 Cubrir el guard de usuario (decisión 2): crear un modelo maestro **sin** `actingAs` (contexto de consola/seeder) **no** genera ningún `audit_log`. Cubrir también un segundo modelo con `SoftDeletes` (p. ej. `Proveedor`) para confirmar que el `deleted` audita el soft delete.
- [x] 3.3 Verificar que el flujo real por controlador audita: hacer un `post`/`patch`/`delete` autenticado sobre las rutas de, al menos, un modelo con soft delete y uno sin él, y afirmar que cada uno dejó su `audit_log` con la acción esperada.

## 4. Spec y validaciones

- [x] 4.1 Correr `php artisan test --compact tests/Feature/Maestros/` y `tests/Feature/Seguridad/` y dejarlos verdes. Correr además la suite completa: el observer ahora audita toda mutación maestra bajo `actingAs`, así que si algún test contaba `audit_logs` hay que ajustarlo (no se detectó ninguno al proponer).
- [x] 4.2 Correr `vendor/bin/pint --dirty --format agent`, `composer types:check` (PHPStan), `npm run types:check` y `npm run lint:check` — todo verde. (Cambio solo de backend; `types:check`/`lint:check` de JS deberían pasar sin tocar nada, confirmar.)

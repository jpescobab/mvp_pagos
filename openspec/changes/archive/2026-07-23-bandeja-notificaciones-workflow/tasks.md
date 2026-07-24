## 1. Backend — payload enriquecido de la notificación

- [x] 1.1 En `app/Models/Proceso.php`, agregar `descriptorNotificacion(): array` que devuelva `['descripcion' => string, 'url' => ?string]` derivado del sujeto polimórfico: `CasoPagoProveedor` → descripción con su identificador (p. ej. número/SGF) y `route('pago-proveedores.casos.show', ...)`; `ProcesoAdquisicion` → su código y `route('adquisiciones.procesos.show', ...)`. Un sujeto sin ruta de detalle devuelve `url = null` (decisión 4 del design). El mapeo tipo→ruta vive solo acá.
- [x] 1.2 En `app/Notifications/TransicionWorkflowNotification::toDatabase()`, reemplazar el payload de códigos por el enriquecido (decisión 3): `proceso_id`, `estado_anterior`, `estado_anterior_nombre`, `estado_nuevo`, `estado_nuevo_nombre`, y `descripcion` + `url` desde `descriptorNotificacion()`. Cargar el proceso con su sujeto y estados para no hacer N+1 al construir el payload.

## 2. Backend — endpoint, share y resource

- [x] 2.1 Crear `app/Http/Resources/Notificaciones/NotificacionResource` que exponga `id`, `data` (con defaults tolerantes: si falta `estado_nuevo_nombre` usar el código, si falta `url` dejar `null`), `leida` (bool desde `read_at`) y `created_at`. Tolerar el payload viejo de solo códigos (decisión: riesgo de payload viejo).
- [x] 2.2 Crear `app/Http/Controllers/Notificaciones/NotificacionController` liviano: `index(Request)` devuelve `NotificacionResource::collection($request->user()->notifications()->latest()->limit(N)->get())`; `marcarLeidas(Request)` hace `$request->user()->unreadNotifications->markAsRead()` y responde `back()`/JSON. Opera siempre sobre `$request->user()`, nunca sobre un id ajeno (decisión 6). No hace falta policy ni permiso.
- [x] 2.3 Agregar las rutas autenticadas en `routes/notificaciones.php` (requerido desde `routes/web.php`): `GET notificaciones` (`notificaciones.index`) y `POST notificaciones/marcar-leidas` (`notificaciones.marcar-leidas`).
- [x] 2.4 En `app/Http/Middleware/HandleInertiaRequests::share()`, agregar `notificaciones_no_leidas` con `$request->user()?->unreadNotifications()->count() ?? 0`. Sin caché (decisión 2).
- [x] 2.5 Regenerar rutas tipadas con `php artisan wayfinder:generate --with-form`.

## 3. Frontend — campana en el header

- [x] 3.1 Agregar el tipo compartido `notificaciones_no_leidas: number` donde se tipa el share de Inertia (junto a `auth`, `indicadoresTopbar`), y un tipo `NotificacionWorkflow` para la forma del Resource (`id`, `data.descripcion`, `data.estado_nuevo_nombre`, `data.url`, `leida`, `created_at`).
- [x] 3.2 Crear `resources/js/components/notificaciones-campana.tsx`: un `DropdownMenu` con un botón de campana (icono `lucide-react`) que muestra el badge con el conteo (del share) cuando es > 0. Al abrirse, hace `GET notificaciones.index()` para traer la lista y dispara `POST notificaciones.marcar-leidas()` (decisión 5), refrescando el conteo del share (recargar la prop parcial o `router.reload({ only: [...] })`).
- [x] 3.3 En el panel, renderizar cada notificación con su descripción, el nombre legible del estado nuevo y la fecha; enlazar al `data.url` cuando exista (si es `null`, sin enlace). Distinguir visualmente las no leídas. Estado vacío cuando no hay notificaciones.
- [x] 3.4 Montar la campana en `resources/js/components/app-header.tsx`, en la zona `ml-auto` junto a los iconos existentes, visible solo para usuario autenticado.

## 4. Tests y validaciones

- [x] 4.1 Crear `tests/Feature/Notificaciones/BandejaNotificacionesTest.php`: el share expone `notificaciones_no_leidas` con el conteo correcto del usuario y cero para quien no tiene; `notificaciones.index` devuelve solo las del usuario autenticado, de la más reciente a la más antigua; `marcar-leidas` deja el conteo del usuario en cero sin tocar las de otro usuario.
- [x] 4.2 Cubrir el aislamiento (decisión 6): con notificaciones de dos usuarios, cada uno ve y marca solo las suyas (usar `Notification::fake` no sirve acá porque probamos la lectura; crear notificaciones reales con `$user->notify(...)` o insertándolas, y afirmar por `user_id`/`notifiable_id`).
- [x] 4.3 Cubrir el payload enriquecido: ejecutar una transición real (vía `TransicionWorkflowService`) sobre un proceso con sujeto `CasoPagoProveedor` y afirmar que la notificación guardada trae `estado_nuevo_nombre`, `descripcion` y `url` al detalle del caso; y un caso donde el descriptor devuelve `url = null` se tolera. Reutilizar los helpers de creación de proceso/caso ya existentes en `tests/Feature/Workflow/`.
- [x] 4.4 Correr `php artisan test --compact tests/Feature/Notificaciones/ tests/Feature/Workflow/` y dejarlos verdes; luego la suite completa para confirmar que enriquecer el payload no rompió tests existentes que afirmen sobre la notificación vieja.
- [x] 4.5 Correr `vendor/bin/pint --dirty --format agent`, `composer types:check` (PHPStan), `npm run types:check` y `npm run lint:check` — todo verde.

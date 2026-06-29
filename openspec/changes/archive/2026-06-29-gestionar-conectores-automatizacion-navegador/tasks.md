## 1. Backend

- [x] 1.1 Crear `App\Policies\ConectorAutomatizacionNavegadorPolicy`: `viewAny`/`view` → `true`; `create` y `gestionar(User, ConectorAutomatizacionNavegador)` → `$user->can('integraciones.gestionar_conectores')`.
- [x] 1.2 Registrar la policy en `app/Providers/AppServiceProvider.php` (`Gate::policy(ConectorAutomatizacionNavegador::class, ConectorAutomatizacionNavegadorPolicy::class)`).
- [x] 1.3 Crear `App\Http\Requests\Integraciones\CrearConectorAutomatizacionNavegadorRequest` (`sistema_externo_id` required exists, `codigo`/`nombre` required string, `descripcion` nullable).
- [x] 1.4 Crear `App\Http\Requests\Integraciones\CrearPerfilAutenticacionNavegadorRequest` (`nombre`, `almacen_secreto`, `referencia_secreto` required string).
- [x] 1.5 Crear `App\Http\Controllers\Integraciones\ConectorAutomatizacionNavegadorController::index()` (con sistemaExterno, autorizadoPor, perfilesAutenticacionNavegador), `::store()` (`Gate::authorize('create', ...)`, crea el conector, `AuditLogger` con acción `integraciones.crear_conector`), `::autorizar()` (`Gate::authorize('gestionar', $conector)`, set `activo/autorizado_por/autorizado_en`, `AuditLogger` con acción `integraciones.autorizar_conector`).
- [x] 1.6 Crear `App\Http\Controllers\Integraciones\PerfilAutenticacionNavegadorController::store(ConectorAutomatizacionNavegador $conector, ...)`: `Gate::authorize('gestionar', $conector)`, crea el perfil con `creado_por` del usuario autenticado.
- [x] 1.7 Crear `App\Http\Resources\Integraciones\{ConectorAutomatizacionNavegadorResource,PerfilAutenticacionNavegadorResource}`.
- [x] 1.8 Agregar a `routes/integraciones.php`: `GET integraciones/conectores`, `POST integraciones/conectores`, `POST integraciones/conectores/{conector}/autorizar`, `POST integraciones/conectores/{conector}/perfiles`.

## 2. Frontend

- [x] 2.1 Agregar tipos `ConectorAutomatizacionNavegador`, `PerfilAutenticacionNavegador` en `resources/js/types/integraciones.ts`.
- [x] 2.2 Página `resources/js/pages/integraciones/conectores/index.tsx`: formulario para registrar un conector (sistema externo, código, nombre), listado de conectores con su estado, botón "Autorizar" si no está autorizado, formulario anidado por conector para registrar un perfil de autenticación y su listado.
- [x] 2.3 Agregar ítem de navegación "Conectores Playwright" en `resources/js/components/app-sidebar.tsx`.

## 3. Tests

- [x] 3.1 Feature test: usuario con permiso `integraciones.gestionar_conectores` registra un conector → se crea con `activo=false`.
- [x] 3.2 Feature test: usuario sin el permiso intenta registrar un conector → bloqueado.
- [x] 3.3 Feature test: usuario con permiso autoriza un conector → `activo=true`, `autorizado_por`/`autorizado_en` correctos, `estaAutorizado()` verdadero.
- [x] 3.4 Feature test: usuario sin permiso intenta autorizar → bloqueado, conector sigue sin autorizar.
- [x] 3.5 Feature test: registrar un perfil de autenticación persiste solo almacén + referencia, nunca un secreto.
- [x] 3.6 Feature test: el listado de conectores incluye su sistema externo y sus perfiles de autenticación.

## 4. Validación

- [x] 4.1 Ejecutar `vendor/bin/pint --dirty --format agent`, `npm run lint:check`, `npm run types:check` y `php artisan test`. Verificado además en navegador real: registrar conector, autorizar, registrar perfil de autenticación — todo funcionando end-to-end.

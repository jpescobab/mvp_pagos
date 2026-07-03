## 1. Backend — validación y servicio

- [x] 1.1 Crear `app/Http/Requests/Seguridad/CrearUsuarioRequest.php`: autoriza contra `usuarios.crear` (`Gate::allows` o `$this->user()->can(...)`); valida `name` (required|string|max:255), `email` (required|email|max:255|unique:users,email), `rut` (required|string|max:20|unique:funcionarios,rut), `cargo`/`unidad` (nullable|string|max:255), `roles` (required|array|min:1, cada elemento `exists:roles,id`), `cfinanciero_id`/`ccosto_id` (nullable|exists:cfinancieros,id / exists:ccostos,id).
- [x] 1.2 `app/Services/Seguridad/GestionUsuariosService.php`: agregar `crear(array $datos): array{usuario: User, passwordTemporal: string}` que, dentro de `DB::transaction()`, crea el `User` (`name`, `email`, contraseña temporal vía `Str::password()`, `active = true`, `must_change_password = true`), crea el `Funcionario` asociado (`user_id`, `rut`, `nombre` = `name`, `cargo`, `unidad`, `cfinanciero_id`, `ccosto_id`), sincroniza los roles (`syncRoles`) y registra auditoría (`crear_usuario`) igual que los métodos existentes.

## 2. Backend — controlador y rutas

- [x] 2.1 `UserController@create(Request $request): Response`: autoriza `Gate::authorize('create', User::class)`; renderiza `seguridad/usuarios/create` con los mismos `catalogs` (roles, jurisdicciones, centros financieros, centros de costo activos) que ya arma `index()`.
- [x] 2.2 `UserController@store(CrearUsuarioRequest $request): RedirectResponse`: llama a `GestionUsuariosService::crear()` con los datos validados y redirige a `usuarios.index()` con flash `passwordTemporal` y `usuarioNombre` (mismo patrón que `resetPassword`).
- [x] 2.3 En `routes/seguridad.php`, agregar bajo el grupo `usuarios.`: `GET /usuarios/create` (`usuarios.create`) y `POST /usuarios` (`usuarios.store`).
- [x] 2.4 Regenerar rutas/acciones Wayfinder (`npm run build` o `php artisan wayfinder:generate`) para que `resources/js/actions`/`resources/js/routes` incluyan las nuevas rutas.

## 3. Frontend

- [x] 3.1 Crear `resources/js/pages/seguridad/usuarios/create.tsx`: formulario con `useForm` de Inertia (nombre, email, rut, cargo, unidad, selección múltiple de roles sobre `catalogs.roles`, selects opcionales de jurisdicción→centro financiero/centro de costo sobre `catalogs`), botón "Crear usuario" y "Cancelar" (vuelve a `usuarios.index()`), mostrando errores de validación por campo.
- [x] 3.2 En `resources/js/pages/seguridad/usuarios/index.tsx`, reemplazar `<Link href="/usuarios/create">` por la función tipada de Wayfinder generada para `UserController@create`.
- [x] 3.3 Si faltan tipos en `resources/js/types/seguridad.ts` para el formulario (p. ej. payload de creación), agregarlos reutilizando `CatalogosUsuarios` ya existente.

## 4. Pruebas

- [x] 4.1 `tests/Feature/Seguridad/CrearUsuarioTest.php`: acceso al formulario y al alta permitido/denegado por `usuarios.crear`; alta exitosa crea `User` + `Funcionario` con roles asignados y `must_change_password = true`; la respuesta incluye la contraseña en texto plano una sola vez; rechaza email duplicado; rechaza rut duplicado; registra el evento de auditoría `crear_usuario`.

## 5. Validación

- [x] 5.1 `vendor/bin/pint --dirty --format agent` sobre archivos PHP tocados.
- [x] 5.2 `composer test` (config:clear + lint:check + types:check + Pest).
- [x] 5.3 `npm run lint:check` y `npm run types:check`.
- [x] 5.4 Verificación manual en navegador: acceder a "Nuevo usuario" desde la bandeja, completar el formulario, confirmar que el usuario aparece en el listado, que la contraseña temporal se muestra una única vez, y que errores de validación (email/rut duplicado) se muestran correctamente.

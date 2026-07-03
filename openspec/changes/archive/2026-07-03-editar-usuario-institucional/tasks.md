## 1. Backend — validación y servicio

- [x] 1.1 Crear `app/Http/Requests/Seguridad/EditarUsuarioRequest.php`: autoriza contra `usuarios.editar`; valida `name` (required|string|max:255), `email` (required|email|max:255|`Rule::unique('users','email')->ignore($usuario)`), `rut` (required|string|max:20|`Rule::unique('funcionarios','rut')->ignore($funcionarioId)`), `cargo`/`unidad` (nullable|string|max:255), `cfinanciero_id`/`ccosto_id` (nullable|exists). Sin campo `roles`.
- [x] 1.2 `GestionUsuariosService`: agregar `editar(User $usuario, array $datos): void` que, en `DB::transaction()`, captura before (name, email, rut, cargo, unidad, cfinanciero_id, ccosto_id), actualiza el `User` (name, email), actualiza o crea el `Funcionario` (`updateOrCreate` por `user_id` con rut, nombre = name, cargo, unidad, cfinanciero_id, ccosto_id), y registra auditoría `editar_usuario` con before/after. No toca roles, password ni active.

## 2. Backend — controlador y rutas

- [x] 2.1 `UserController@edit(User $usuario): Response`: `Gate::authorize('update', $usuario)`; renderiza `seguridad/usuarios/edit` con `usuario` (id, name, email, rut, cargo, unidad, cfinanciero_id, ccosto_id — desde su funcionario, nullables si no tiene) y `catalogs` (reusar `catalogos()`).
- [x] 2.2 `UserController@update(EditarUsuarioRequest $request, User $usuario): RedirectResponse`: delega en `GestionUsuariosService::editar()` con los datos validados y redirige a `usuarios.index`.
- [x] 2.3 En `routes/seguridad.php`, agregar al grupo `usuarios.`: `GET {usuario}/editar` (`usuarios.edit`) y `PATCH {usuario}` (`usuarios.update`).
- [x] 2.4 Regenerar Wayfinder con `php artisan wayfinder:generate --with-form` (sin `--with-form` se rompen las variantes `.form` de otras páginas).

## 3. Frontend

- [x] 3.1 Crear `resources/js/pages/seguridad/usuarios/edit.tsx`: mismo estilo que `create.tsx`, precargado con las props del usuario, sin sección de roles; botones "Guardar cambios" (PATCH a la ruta tipada `usuarios.update`) y "Cancelar" (vuelve a `usuarios.index()`); errores de validación por campo.
- [x] 3.2 En `resources/js/components/seguridad/user-actions-menu.tsx`: sacar "Editar usuario" de las acciones diferidas y agregarla como `DropdownMenuItem` real (condicionada a `can_edit_user`) que navega a la ruta tipada de `usuarios.edit` con el id del usuario.

## 4. Pruebas

- [x] 4.1 `tests/Feature/Seguridad/EditarUsuarioTest.php`: formulario y update permitidos/denegados por `usuarios.editar`; edición exitosa actualiza `User` + `Funcionario`; crea el `Funcionario` si no existía; acepta conservar email/rut propios; rechaza email de otro usuario y rut de otro funcionario; registra auditoría `editar_usuario` con before/after; los roles del usuario no cambian al editar.

## 5. Validación

- [x] 5.1 `vendor/bin/pint --dirty --format agent` sobre archivos PHP tocados.
- [x] 5.2 `composer test` (config:clear + lint:check + types:check + Pest).
- [x] 5.3 `npm run lint:check` y `npm run types:check`.
- [x] 5.4 Verificación manual en navegador: abrir "Editar usuario" desde el menú de la bandeja, ver el formulario precargado, guardar cambios y verlos reflejados en el listado, validar errores por email/rut duplicado, y confirmar que los roles no cambian.

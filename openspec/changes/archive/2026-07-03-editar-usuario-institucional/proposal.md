## Why

En la bandeja de usuarios, la acción "Editar usuario" del menú por fila está deshabilitada con tooltip "Disponible próximamente", aunque `UserPolicy::update()` y el permiso `usuarios.editar` ya existen y `can_edit_user` ya viaja al frontend. Sin edición, cualquier error en los datos de un usuario (email, RUT, cargo, adscripción institucional) obliga a intervenir la base de datos a mano.

## What Changes

- Agregar `GET /usuarios/{usuario}/editar` (formulario precargado) y `PATCH /usuarios/{usuario}` (actualización) a `UserController`, autorizados contra `usuarios.editar` vía `UserPolicy::update()`.
- Nueva página React `seguridad/usuarios/edit` con el mismo estilo del formulario de alta, precargada y **sin sección de roles** (la asignación de roles es una acción separada gobernada por `usuarios.asignar_roles`, fuera de alcance).
- `GestionUsuariosService::editar()` actualiza `User` (name, email) y su `Funcionario` (rut, nombre, cargo, unidad, cfinanciero_id, ccosto_id) en una transacción; si el usuario no tiene `Funcionario` (caso legado, p. ej. el usuario seedeado), lo crea. Registra auditoría `editar_usuario` con before/after.
- Unicidad de email/RUT ignora al propio usuario (puede guardar sin cambiar esos campos).
- Habilitar "Editar usuario" en el menú de acciones de la bandeja, navegando a la ruta tipada de Wayfinder.

## Capabilities

### New Capabilities
- `editar-usuario-institucional`: edición de los datos personales e institucionales de un usuario existente (sin tocar roles, contraseña ni estado activo) con auditoría before/after.

### Modified Capabilities
(ninguna — no cambian requisitos de `listar-usuarios-institucionales` ni `crear-usuario-institucional`; se agrega una capacidad nueva)

## Impact

- Backend: `app/Http/Controllers/Seguridad/UserController.php`, nuevo `app/Http/Requests/Seguridad/EditarUsuarioRequest.php`, `app/Services/Seguridad/GestionUsuariosService.php` (nuevo método `editar`), `routes/seguridad.php`.
- Frontend: nueva página `resources/js/pages/seguridad/usuarios/edit.tsx`, ajuste de `resources/js/components/seguridad/user-actions-menu.tsx` (habilitar la acción), rutas Wayfinder regeneradas (`--with-form`).
- Tests: `tests/Feature/Seguridad/EditarUsuarioTest.php`.
- Sin cambios de esquema de base de datos.

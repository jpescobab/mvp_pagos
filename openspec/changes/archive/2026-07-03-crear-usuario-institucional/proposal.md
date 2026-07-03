## Why

La bandeja de usuarios institucionales (`/usuarios`) ya permite listar, activar, desactivar y resetear contraseña, pero no existe forma de dar de alta un usuario nuevo: el botón "Nuevo usuario" enlaza a `/usuarios/create`, una ruta que no existe (404 hoy). Sin alta de usuarios, el módulo de Seguridad queda incompleto para operación real.

## What Changes

- Agregar `GET /usuarios/create` (formulario) y `POST /usuarios` (alta) a `UserController`, autorizados contra `usuarios.crear`.
- Nueva página React `seguridad/usuarios/create` con formulario (nombre, email, rut, cargo, unidad, roles, jurisdicción/centro financiero/centro de costo del funcionario).
- El alta crea el `User` y su `Funcionario` asociado en una transacción, asigna los roles elegidos, genera una contraseña temporal (mismo mecanismo que el reseteo de contraseña) marcando `must_change_password = true`, y registra el alta en auditoría.
- Tras crear, redirige al listado mostrando la contraseña temporal una única vez, reutilizando el diálogo que ya existe en `seguridad/usuarios/index` para el reseteo de contraseña.
- Reemplazar el enlace hardcodeado `/usuarios/create` por la ruta tipada que genera Wayfinder una vez exista el controlador.

## Capabilities

### New Capabilities
- `crear-usuario-institucional`: alta de un usuario institucional nuevo (con su ficha de funcionario) desde la bandeja de usuarios, con contraseña temporal de un solo uso y auditoría.

### Modified Capabilities
(ninguna — no cambian los requisitos ya definidos en `listar-usuarios-institucionales` ni `seguridad-auditoria`, solo se les suma una capacidad nueva)

## Impact

- Backend: `app/Http/Controllers/Seguridad/UserController.php`, nuevo `app/Http/Requests/Seguridad/CrearUsuarioRequest.php`, `app/Services/Seguridad/GestionUsuariosService.php` (nuevo método `crear`), `routes/seguridad.php`.
- Frontend: nueva página `resources/js/pages/seguridad/usuarios/create.tsx`, ajuste de `resources/js/pages/seguridad/usuarios/index.tsx` (enlace tipado), rutas/acciones regeneradas por Wayfinder.
- Tests: `tests/Feature/Seguridad/CrearUsuarioTest.php`.
- Sin cambios de esquema de base de datos (reutiliza columnas ya existentes en `users` y `funcionarios`).

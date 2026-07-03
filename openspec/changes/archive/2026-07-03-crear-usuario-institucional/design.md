## Context

`UserController` (`app/Http/Controllers/Seguridad/UserController.php`) ya expone `index`, `activar`, `desactivar` y `resetPassword`, delegando la lógica de negocio en `GestionUsuariosService`. La bandeja (`resources/js/pages/seguridad/usuarios/index.tsx`) ya renderiza un diálogo que muestra una contraseña temporal desde `flash.passwordTemporal`/`flash.usuarioNombre` (usado hoy por "Resetear contraseña"). Falta el alta de usuarios nuevos.

## Goals / Non-Goals

**Goals:**
- Permitir crear un `User` + su `Funcionario` asociado desde un formulario, con roles iniciales y contraseña temporal de un solo uso.
- Reutilizar el patrón ya existente (Service + Policy + flash de contraseña temporal) en vez de introducir uno nuevo.

**Non-Goals:**
- Editar un usuario existente, "ver detalle" o reasignar roles a un usuario ya creado (siguen deshabilitados con tooltip "Disponible próximamente" en `user-actions-menu.tsx`; no se tocan en este cambio).
- Invitación por correo o autoservicio de registro (prohibido por `AGENTS.md` — no auto-registro público).

## Decisions

- **Un solo `store()` transaccional crea `User` y `Funcionario` juntos.** Alternativa descartada: crear el `User` primero y pedir "completar ficha de funcionario" en un segundo paso — añade complejidad de estado intermedio (usuario sin funcionario) sin necesidad real, ya que el formulario ya pide ambos conjuntos de datos a la vez.
- **`GestionUsuariosService::crear()` reutiliza el mecanismo de contraseña temporal de `resetearPassword()`** (`Str::password()` + `must_change_password = true`), para mantener una única forma de generar credenciales temporales en el sistema.
- **Redirigir a `usuarios.index()` con flash `passwordTemporal`/`usuarioNombre`** en vez de crear un diálogo nuevo en `create.tsx` — el diálogo ya existe en `index.tsx` y ya maneja "cópiala ahora, no se muestra de nuevo"; evita duplicar UI y mantiene un único lugar para esa lógica sensible.
- **Roles se asignan en la creación, no en un flujo aparte de "asignar roles"** — el formulario incluye selección de roles (multi-select sobre `catalogs.roles`, ya cargado por `index()`), reutilizando el catálogo que ya viaja al frontend en el listado.
- **Autorización con `Gate::authorize('create', User::class)`**, igual que `viewAny` en `index()`; `UserPolicy::create()` ya existe y ya chequea `usuarios.crear` (permiso ya seedeado).

## Risks / Trade-offs

- [Riesgo] Crear `User` sin `Funcionario` si la transacción falla a mitad de camino → Mitigación: `DB::transaction()` envolviendo ambas inserciones.
- [Riesgo] Duplicar `rut` o `email` → Mitigación: validación `unique` en el FormRequest sobre `funcionarios.rut` y `users.email`.
- [Riesgo] Olvidar registrar auditoría de alta → Mitigación: mismo `AuditLogger::log('crear_usuario', ...)` que ya usan `activar`/`desactivar`/`resetearPassword` en `GestionUsuariosService`.

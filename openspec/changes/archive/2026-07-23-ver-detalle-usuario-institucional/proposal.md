## Why

El listado de usuarios institucionales ofrece "Ver detalle" en el menú de acciones, pero la opción está deshabilitada con la indicación "Disponible próximamente": es la última acción diferida que queda en ese menú. No existe ni la ruta `usuarios.show` ni el método `UserController::show`, pese a que toda la infraestructura de autorización ya está puesta y sin usar — `UserPolicy::view()` está implementado y nunca se invoca, el permiso `usuarios.ver` ya está sembrado para superadmin/admin, y `UserController::index` ya expone `can_view_user` al frontend. Hoy, para responder "¿quién es este usuario, qué alcance institucional tiene, qué puede hacer y qué ha hecho?" hay que entrar a editarlo (lo que expone un formulario mutable para una consulta de solo lectura) y cruzar a mano la página global de auditoría.

## What Changes

- El sistema SHALL exponer una página de detalle de usuario institucional en `GET usuarios/{usuario}` (`usuarios.show`), autorizada con la policy `view` ya existente.
- La página SHALL mostrar la identidad y el estado de la cuenta (nombre, email, RUT, cargo, unidad, activa/inactiva, último acceso, fecha de creación) y su ámbito institucional (jurisdicción → centro financiero → centro de costo), con fallback explícito cuando el usuario no tiene funcionario asociado.
- La página SHALL mostrar los roles asignados y los permisos efectivos que de ellos se derivan, distinguiendo el caso de `superadmin` (acceso total vía `Gate::before`) de una lista de permisos concretos.
- La página SHALL mostrar la actividad reciente del usuario en dos secciones separadas y de **solo lectura**: acciones de negocio (`audit_logs`) y eventos de seguridad (`security_audit_logs`), acotadas a los últimos N registros de cada tabla.
- La página SHALL ofrecer las acciones de cuenta ya implementadas (editar, activar, desactivar, resetear contraseña) condicionadas por el permiso correspondiente, reutilizando los mismos endpoints del listado.
- El ítem "Ver detalle" del menú de acciones del listado SHALL dejar de estar deshabilitado y SHALL navegar a esta página. Con eso el menú queda **sin ninguna acción diferida**.
- Sin cambios de esquema y sin permisos nuevos: todo el dato proviene de tablas ya modeladas (`users`, `funcionarios`, `cfinancieros`, `jurisdicciones`, `ccostos`, `audit_logs`, `security_audit_logs`) y la autorización usa `usuarios.ver`, ya sembrado.

## Capabilities

### New Capabilities

- `ver-detalle-usuario-institucional`: página de detalle de un usuario institucional en modo consulta — identidad y estado de cuenta, ámbito institucional, roles y permisos efectivos, actividad reciente de negocio y de seguridad, y acceso a las acciones de cuenta según permiso.

### Modified Capabilities

- `listar-usuarios-institucionales`: se elimina el requirement "Acciones diferidas visibles pero deshabilitadas". Ese requirement quedó obsoleto: nombraba "Ver detalle", "Editar usuario" y "Asignar roles" como diferidas, pero editar ya está implementado y navegable desde el menú, asignar roles ya no figura en él, y "Ver detalle" deja de estar diferida con este cambio. En su lugar, el listado SHALL navegar al detalle del usuario desde ese ítem.

## Impact

- **Rutas**: `routes/seguridad.php` agrega `GET usuarios/{usuario}` como `usuarios.show`. Debe declararse **después** de `usuarios/create` para que `create` no sea capturado como parámetro `{usuario}`.
- **Backend**: `UserController::show` (liviano, solo autoriza y compone la respuesta); la consulta de actividad reciente y el armado de roles/permisos efectivos viven en `GestionUsuariosService`. `UserResource` se reutiliza para la cabecera. Se agregan Resources de lectura para las dos tablas de auditoría (`AuditLogResource` ya existe; falta el equivalente de seguridad).
- **Frontend**: nueva página `resources/js/pages/seguridad/usuarios/show.tsx`; `resources/js/components/seguridad/user-actions-menu.tsx` deja de renderizar "Ver detalle" como diferida; tipos en `resources/js/types/seguridad.ts`. Rutas tipadas vía Wayfinder (`php artisan wayfinder:generate --with-form`).
- **Auditoría**: solo lectura. La página nunca escribe en `audit_logs` ni `security_audit_logs`, y no ejecuta ninguna transición ni mutación por sí misma.
- **Tests**: `tests/Feature/Seguridad/`.
- Sin migraciones de esquema, sin permisos nuevos, sin cambios en seeders. El design decide explícitamente si corresponde indexar `user_id` en las tablas de auditoría.

## Context

El módulo de seguridad ya tiene listado, creación y edición de usuarios institucionales. Falta la vista de consulta: `usuarios.show` no existe, y el menú de acciones del listado deja "Ver detalle" deshabilitado como única acción diferida que queda.

La infraestructura de autorización está completa y ociosa: `UserPolicy::view()` existe pero ningún llamador lo invoca, `usuarios.ver` está sembrado en `RolesAndPermissionsSeeder` para superadmin y admin, y `UserController::index` ya calcula `can_view_user`. Este cambio no inventa autorización nueva; conecta la que ya está.

Los datos que la página necesita están todos modelados: `users` (`name`, `email`, `active`, `last_login_at`, `created_at`), `funcionarios` (`rut`, `cargo`, `unidad`, `cfinanciero_id`, `ccosto_id`), la jerarquía `jurisdicciones → cfinancieros → ccostos`, los roles/permisos de Spatie, y las dos tablas de auditoría (`audit_logs` para negocio, `security_audit_logs` para seguridad), ambas con `user_id` nullable.

## Goals / Non-Goals

**Goals:**

- Una página de consulta que responda, sin salir de ella: quién es el usuario, cuál es su alcance institucional, qué puede hacer y qué ha hecho.
- Poner en uso la policy `view` ya existente, sin agregar permisos ni roles.
- Dejar el menú de acciones del listado sin ninguna acción diferida.
- Mantener el controlador liviano: la lectura de actividad y el armado de permisos efectivos viven en `GestionUsuariosService`.

**Non-Goals:**

- No se agrega paginación ni filtros a la actividad reciente dentro del detalle: son los últimos N registros de cada tabla, fijos.
- No se agrega un filtro por usuario a la página global de auditoría (`auditoria.index`). Es un cambio con valor propio, pero pertenece a su propia capability.
- No se toca la asignación de roles: el endpoint `usuarios.roles.update` existe y queda como está; la página **muestra** los roles, no los edita.
- No se cambia el comportamiento de `PermisosCompartidosResolver` ni su caché.
- No se agregan campos ni tablas. Ninguna migración de esquema.

## Decisions

### 1. La ruta `GET usuarios/{usuario}` se declara después de `usuarios/create`

`routes/seguridad.php` define hoy `GET usuarios/` y `GET usuarios/create` antes de las rutas con parámetro. `GET usuarios/{usuario}` debe ir **después** de `create`, o Laravel resuelve `/usuarios/create` contra `{usuario}` e intenta un binding implícito con el literal `"create"`, devolviendo 404 en la página de creación.

*Alternativa considerada*: prefijar el detalle (`usuarios/{usuario}/detalle`) para evitar del todo la colisión. Descartada: rompe la convención REST que el resto del repo ya sigue (`{caso}`, `{egresoCgu}`, `{role}` van todos en la raíz del recurso) a cambio de un problema que se resuelve con el orden de declaración.

### 2. Los métodos de lectura viven en `GestionUsuariosService`, no en un service nuevo

`GestionUsuariosService` concentra hoy solo mutaciones (`crear`, `editar`, `activar`, `desactivar`, `asignarRoles`, `resetearPassword`). Se le agregan dos métodos de lectura: uno que devuelve la actividad reciente del usuario y otro que arma sus permisos efectivos.

*Alternativa considerada*: un `ConsultaUsuarioService` separado, para no mezclar lectura y escritura. Descartada por ahora: son dos métodos sobre exactamente las mismas entidades que el service ya gobierna, y partir el dominio de usuarios en dos services por la dirección del flujo agrega una indirección que no compra nada a esta escala. Si la consulta crece (filtros, exportación, agregaciones), ese es el momento de extraerla.

### 3. Los permisos efectivos se leen sin caché y `superadmin` se muestra como acceso total, no enumerado

`PermisosCompartidosResolver` cachea permisos **por usuario autenticado** durante 5 minutos, para poblar `auth.permissions`. Esta página consulta los permisos de **otro** usuario, y usarlo aquí significaría poblar esa caché con entradas que nadie más va a leer, además de mostrar datos de hasta 5 minutos de antigüedad justo en la pantalla que se usa para auditar un cambio de roles recién hecho.

Decisión: el detalle consulta `getAllPermissions()` directo, sin caché. Y como `Gate::before` le da acceso total a `superadmin` sin pasar por la tabla de permisos, para ese rol la página muestra un distintivo de acceso total en vez de una lista — enumerar `Permission::all()` daría a entender que el acceso viene de una asignación explícita que no existe.

*Alternativa considerada*: reutilizar el resolver cacheado por consistencia. Descartada por las dos razones anteriores; la consistencia que importa es la del criterio (superadmin = acceso total), y esa se mantiene.

### 4. No se agregan índices en `audit_logs.user_id` ni `security_audit_logs.user_id`

Medición real antes de decidir: `audit_logs` tiene **85 filas**, `security_audit_logs` **184**, sobre 16 usuarios. A ese volumen el planificador de PostgreSQL elige seq scan aunque exista el índice, y la consulta del detalle (`where user_id = ? order by id desc limit N`) es irrelevante frente al costo de la request.

Decisión: **no** se agrega índice en este cambio, y por lo tanto el cambio no lleva ninguna migración.

Criterio explícito para revisarlo, para que no quede como decisión implícita: cuando `audit_logs` supere el orden de decenas de miles de filas, corresponde mirar el `EXPLAIN` real de esta consulta y, si aparece un seq scan dominante, agregar un índice compuesto `(user_id, id DESC)` — compuesto y no simple, porque el patrón es filtrar por usuario y ordenar por id descendente en una sola pasada. Nota para quien lo evalúe: `foreignId()->constrained()` **no** crea índice sobre la columna en PostgreSQL (a diferencia de MySQL), así que hoy esas columnas no están indexadas.

*Alternativa considerada*: agregarlo igual, "ya que es barato". Descartada: este repo ya descartó antes un índice propuesto por sospecha (`procesos.cerrado_en`) que al mirar el SQL real no aportaba nada. La regla del proyecto es medir primero; aquí se midió y el resultado es que no corresponde todavía.

### 5. La actividad reciente son los últimos 10 de cada tabla, en dos secciones separadas

Las dos tablas responden preguntas distintas — `audit_logs` es "qué hizo sobre el negocio", `security_audit_logs` es "qué pasó con su acceso" — y tienen columnas distintas (`action` + `auditable` vs. `event` + `ip_address`/`user_agent`). Fusionarlas en una sola línea de tiempo obligaría a un formato mínimo común que pierde justamente lo que hace útil a cada una.

Decisión: dos secciones, 10 registros cada una, orden descendente por `id`, sin paginación. Cuando una sección no tiene registros, muestra un estado vacío en vez de una tabla vacía.

### 6. `UserResource` se reutiliza sin tocarlo; la auditoría de seguridad estrena Resource

`UserResource` ya expone exactamente los campos de la cabecera (identidad, estado, jerarquía institucional completa, roles). Se reutiliza tal cual: no necesita `whenLoaded` nuevo porque el detalle carga las mismas relaciones que el listado.

Para la actividad: `AuditLogResource` ya existe y sirve. Falta el equivalente de seguridad, que se crea como `SecurityAuditLogResource` (`event`, `description`, `ip_address`, `created_at`; el `user_agent` completo se expone pero se trunca en la vista, y `metadata` se omite de la vista por ruido).

## Risks / Trade-offs

- **La página expone IP y user agent de un usuario a cualquiera con `usuarios.ver`** → Es dato de auditoría de seguridad que la página global de auditoría ya trata con el mismo criterio, y `usuarios.ver` hoy solo lo tienen superadmin y admin. No se agrega un permiso más fino porque partiría el permiso existente sin que nadie lo haya pedido; queda anotado como el punto a revisar si el permiso se reparte a más roles.
- **Sin paginación, un usuario muy activo muestra solo sus últimos 10 registros** → Aceptado: el detalle es un resumen, no el registro completo. La página global de auditoría sigue siendo la fuente exhaustiva. Si se vuelve una molestia real, el siguiente paso natural es el filtro por usuario en `auditoria.index` (ya listado como non-goal).
- **Dos consultas más por request** (actividad de negocio y de seguridad) → A 85/184 filas es despreciable, y la decisión 4 deja escrito el criterio y el índice concreto para cuando deje de serlo.
- **El orden de rutas es una trampa silenciosa**: si alguien mueve `usuarios.show` por encima de `create` en un refactor futuro, `/usuarios/create` empieza a dar 404 sin ningún error evidente → Mitigación: el cambio incluye un test que pide `usuarios.create` y espera 200, de modo que la regresión falle en CI y no en producción.

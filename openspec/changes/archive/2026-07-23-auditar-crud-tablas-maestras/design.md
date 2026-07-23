## Context

`AuditLogger::log($action, $auditable, $before, $after, $metadata)` ya existe y lo usan usuarios, roles, workflow y varios controladores de pago. Escribe en `audit_logs` (acciones de negocio), distinta de `security_audit_logs` (eventos de seguridad).

Los nueve controladores de `Maestros` hacen un CRUD simple, 1:1 desde el Form Request (`Modelo::create($request->validated())`, `$modelo->update(...)`, `$modelo->delete()`), sin service intermedio. Son controladores livianos legítimos: no tienen lógica de negocio que extraer. El único hueco es que ninguno audita.

La decisión de enfoque —trait con observer de Eloquent, no llamadas explícitas ni services nuevos— ya está tomada. Este documento resuelve el cómo.

## Goals / Non-Goals

**Goals:**

- Que crear, editar y eliminar cualquier tabla maestra institucional deje un registro en `audit_logs` con quién, qué entidad y qué cambió.
- Un solo mecanismo para los nueve modelos, sin repetir la llamada en ~27 métodos y sin engordar controladores hoy limpios.
- No auditar el ruido de siembra/sincronización (seeders, jobs, imports).

**Non-Goals:**

- No se audita la lectura (índices, detalles): `audit_logs` es para cambios.
- No se extraen services de CRUD: el CRUD 1:1 no los necesita, y crearlos solo para auditar sería sobre-ingeniería.
- No se cambia el comportamiento de `AuditLogger` ni el esquema de `audit_logs`.
- No se auditan por ahora las tablas maestras de configuración de workflow (modalidades, definiciones), que se siembran y casi no se editan a mano. El alcance es el catálogo institucional que se administra por pantalla.
- No se agrega una UI para ver la auditoría por entidad: la página global de auditoría (`auditoria.index`) ya muestra todo `audit_logs`.

## Decisions

### 1. Un trait `RegistraAuditoria` con observer en `booted()`, no llamadas explícitas

El trait define `protected static function booted()` que registra closures para `created`, `updated` y `deleted`. Cada modelo maestro hace `use RegistraAuditoria` y nada más.

Frente a las ~27 llamadas explícitas que serían la alternativa, el trait gana en tres cosas concretas, no solo en líneas:

- **Correctitud del `before`.** En un `update`, capturar el estado anterior exige leer el modelo antes de mutarlo. Inline, cada método tendría que acordarse de hacerlo; el observer lo obtiene de `getOriginal()`/`getChanges()` de Eloquent, que Laravel mantiene por diseño, sin que nadie lo recuerde.
- **Cobertura de toda vía de mutación.** El observer audita cualquier `save()`/`delete()` sobre una instancia, venga del controlador, de un futuro service o de una acción nueva. Las llamadas explícitas solo cubren los sitios donde alguien las escribió.
- **Controladores intactos.** Los nueve quedan como están.

El harness valora "Events/Listeners para efectos secundarios": un observer de modelo es exactamente ese patrón. Es cierto que el resto del repo audita explícito; la diferencia es que esos dominios auditan pocos puntos con semántica propia (una transición, un reseteo de contraseña), mientras que acá son 27 puntos mecánicamente idénticos, que es justo donde un observer paga.

*Alternativa considerada*: una clase `Observer` por modelo registrada en `AppServiceProvider` (como se registran las policies). Descartada: nueve registros manuales y nueve clases para el mismo comportamiento, cuando un trait auto-contenido que el modelo se aplica a sí mismo dice lo mismo con menos ceremonia.

### 2. La auditoría se guarda solo con usuario autenticado (`Auth::check()`)

El observer no registra nada si no hay usuario autenticado. Esto es lo que separa una acción administrativa real del ruido de infraestructura:

- Los **seeders** crean cientos de filas maestras (977 proveedores en el seed real) desde consola, sin sesión. Sin este guard, cada `migrate:fresh --seed` escribiría cientos de `audit_logs` que no auditan a nadie.
- Los **jobs de importación** (SGF, Mercado Público) hacen upsert de proveedores en cola, sin usuario. Esas escrituras ya dejan su rastro propio en la capa de integraciones (`trabajos_integracion`, `snapshots_datos_externos`); duplicarlas en `audit_logs` sin un responsable humano sería ruido.

Un `audit_log` sin `user_id` no responde la pregunta que la auditoría existe para responder ("¿quién?"). La regla es entonces: se audita el cambio deliberado de una persona, no la construcción del estado base.

*Trade-off asumido*: si en el futuro se quiere auditar también los cambios automáticos (p. ej. qué import tocó qué proveedor), ese es un requisito distinto que se resuelve con su propio evento y su propio actor-sistema, no relajando este guard. Anotado, no incluido.

### 3. La acción se deriva del nombre del modelo, con la convención `<verbo>_<entidad>`

`created → crear`, `updated → editar`, `deleted → eliminar`, seguido del nombre del modelo en snake_case: `crear_cfinanciero`, `editar_proveedor`, `eliminar_tipo_documento`. Es la convención que ya usan usuarios y roles (`crear_usuario`, `editar_rol`).

El nombre de la entidad sale de `Str::snake(class_basename($model))`. Es cierto que `auditable_type` ya guarda la clase y el sufijo es parcialmente redundante; se mantiene por consistencia con las acciones existentes y porque la página de auditoría muestra la acción como texto legible.

La derivación es automática y cubre los nueve modelos actuales sin configuración. No se agrega un punto de override por modelo mientras ninguno lo necesite (YAGNI); si en el futuro un modelo requiere otro nombre, se resuelve ahí con lo mínimo.

### 4. `before`/`after` desde el diff de Eloquent

- **created**: `after = $model->getAttributes()` (los atributos persistidos), `before = []`.
- **updated**: `after = $model->getChanges()` (solo lo que cambió), y `before` = esos mismos campos tomados de `getOriginal()`. Un update que no cambia nada no dispara el evento, así que no se auditan no-cambios.
- **deleted**: `before = $model->getOriginal()`, `after = []`.

Esto mantiene los registros acotados: un `update` de un solo campo audita un solo campo, no la fila entera.

*Nota sobre datos sensibles*: las tablas maestras no guardan contraseñas ni tokens (a diferencia de `users`), así que volcar los atributos cambiados no expone secretos. El `documento_respaldo_path` de proveedor es una ruta, no el contenido. No se necesita una lista de exclusión en este alcance; si un modelo futuro con el trait tuviera un campo sensible, se agrega ahí.

## Risks / Trade-offs

- **El observer audita también las escrituras de los tests** que corren bajo `actingAs`. → Es correcto (hay usuario) y sin efecto práctico: ningún test de Maestros cuenta `audit_logs`. Se verifica corriendo la suite completa; si algún test contara filas de auditoría, se ajusta.
- **`Model::where(...)->update(...)` masivo no dispara eventos de Eloquent** y por lo tanto no se auditaría. → No aplica al CRUD de Maestros, que siempre opera sobre instancias. Queda anotado como límite conocido del patrón por si aparece un update masivo administrativo.
- **Resolver `AuditLogger` del contenedor dentro del observer** es service location, que el harness desaconseja en controladores. → En un observer de modelo no hay inyección por constructor disponible; `app(AuditLogger::class)` es el patrón idiomático de Laravel para este caso y está acotado al trait, no esparcido por la lógica de negocio.
- **Un cambio hecho por consola con un usuario simulado** (`tinker` con `Auth::login`) se auditaría. → Es el comportamiento deseado: si hay un actor, hay algo que auditar.

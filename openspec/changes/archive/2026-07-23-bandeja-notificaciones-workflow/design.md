## Context

`User` ya usa el trait `Notifiable`, así que `$user->notifications`, `$user->unreadNotifications` y `markAsRead()` están disponibles, y la tabla `notifications` existe. `TransicionWorkflowService::notificarResponsables()` envía `TransicionWorkflowNotification` (canal `database`) a los usuarios asignados a tareas del proceso. Todo el lado emisor está construido y cubierto por `workflow-core`.

Falta todo el lado consumidor. Y el payload actual (`proceso_id`, `estado_anterior`, `estado_nuevo`, solo códigos) no alcanza para renderizar algo legible ni para navegar.

El enfoque de entrega —conteo en el share + lista por endpoint— ya está decidido. Este documento resuelve el resto.

## Goals / Non-Goals

**Goals:**

- Que un usuario vea, desde cualquier pantalla, cuántas notificaciones de workflow no leídas tiene, y pueda abrir un panel con las recientes.
- Que cada notificación sea legible (estados con nombre, no código; descripción del proceso) y navegable (enlace al detalle del sujeto).
- Que marcar como leídas se refleje de inmediato.
- Que un usuario solo acceda a sus propias notificaciones.

**Non-Goals:**

- No se agrega tiempo real (websockets/polling): en un sistema institucional interno, el badge se actualiza en la siguiente navegación de Inertia, que es suficiente.
- No se agregan otros tipos de notificación (documentos, vencimientos): el alcance es la notificación de transición que ya se emite.
- No se agrega marcar-una-a-una ni borrar notificaciones: marcar todas como leídas cubre el caso real; el resto es incremental si se pide.
- No se cambia quién recibe la notificación ni el requirement de emisión de `workflow-core`.
- No se agrega un permiso: una notificación es del usuario, no un recurso con control de acceso por rol.

## Decisions

### 1. El share lleva solo el conteo de no leídas; la lista va por endpoint

El badge de la campana necesita un número en cada pantalla, así que el **conteo** de no leídas se agrega a `HandleInertiaRequests::share()`, junto a `indicadoresTopbar` y `auth.permissions` que ya viajan ahí. La **lista** de notificaciones no: se pide a `GET notificaciones` cuando el usuario abre la campana.

El motivo es la lección de rendimiento del propio repo: una prop compartida en cada request no debe cargar datos pesados. La lista de notificaciones de un usuario activo puede ser grande y se necesita raras veces (solo al abrir el panel); el conteo es un solo entero de una consulta indexada. Volcar la lista en cada request la traería a pantallas que nunca muestran la campana.

*Alternativa considerada*: `Inertia::defer()` para la lista dentro del share. Descartada: un endpoint explícito es más simple de consumir desde un dropdown que se abre bajo demanda, y no ata la lista al ciclo de vida de la página.

### 2. El conteo no se cachea

`unreadNotifications()->count()` se resuelve en cada request sin caché. Es una consulta indexada por `(notifiable_type, notifiable_id, read_at)` —barata— y, sobre todo, debe reflejar de inmediato cuando el usuario acaba de marcar todo como leído o cuando entra una transición nueva. Cachearlo mostraría un badge desactualizado justo después de la acción que lo cambia.

Es el mismo criterio con el que el detalle de usuario lee permisos sin pasar por la caché de `PermisosCompartidosResolver`: la consistencia inmediata pesa más que ahorrar una consulta barata.

### 3. El payload se enriquece en el momento de la transición (snapshot)

`toDatabase()` pasa de códigos a un payload autocontenido:

- `proceso_id`
- `estado_anterior` y `estado_anterior_nombre`
- `estado_nuevo` y `estado_nuevo_nombre`
- `descripcion`: un texto legible del proceso afectado (derivado de su sujeto)
- `url`: la ruta de destino al detalle del sujeto

Se guarda **resuelto en el momento**, no como referencias a resolver al leer. Es la doctrina de snapshot del harness aplicada a la notificación: describe lo que pasó cuando pasó. Un beneficio concreto: la notificación sigue siendo correcta y renderizable aunque el proceso avance a otro estado, cambie de nombre o su sujeto se modifique después. Y evita N+1 al pintar el panel (cada notificación se pinta con lo que ya trae, sin tocar la base por proceso).

*Trade-off*: si el título del sujeto cambia, la notificación vieja conserva el título viejo. Aceptado: una notificación es un registro histórico de un evento, no una vista viva.

### 4. El descriptor y la URL se derivan del sujeto en un método del modelo `Proceso`

El sujeto de un `Proceso` es polimórfico (`CasoPagoProveedor` o `ProcesoAdquisicion` hoy). En vez de un `match` sobre el tipo esparcido dentro de la notificación, el mapeo vive en un método del modelo `Proceso` (p. ej. `descriptorNotificacion(): array` que devuelve `['descripcion' => ..., 'url' => ...]`), que la notificación consume.

Así, cuando aparezca un tercer tipo de sujeto con workflow, hay un solo lugar donde enseñarle su descripción y su ruta, y ese lugar es el modelo que ya conoce a su sujeto. Si un tipo de sujeto no tiene una ruta de detalle, el método devuelve `url = null` y el panel muestra la notificación sin enlace, sin romperse.

*Alternativa considerada*: resolver la URL en el frontend a partir del tipo y el id. Descartada: obligaría a replicar el mapeo tipo→ruta en React y a mantenerlo sincronizado con las rutas de Laravel; con Wayfinder, el backend es el lugar natural para resolver rutas.

### 5. `marcarLeidas` marca todas; la campana la dispara al abrirse

Un solo endpoint (`POST notificaciones/marcar-leidas`) que marca como leídas todas las no leídas del usuario. La campana lo llama al abrir el panel: el gesto de "mirar las notificaciones" es lo que las da por vistas, que es como funciona la mayoría de las bandejas y evita una gestión de estado por-ítem que nadie pidió.

El panel sigue mostrando las notificaciones recién marcadas en esa misma apertura (no desaparecen al instante); el badge baja a cero. En la próxima apertura ya cuentan como leídas.

*Alternativa considerada*: marcar leída cada notificación al hacer clic en ella. Descartada por ahora: agrega estado por-ítem y endpoints para un beneficio marginal frente a "marcar todas al abrir". Queda como incremento natural si se pide distinguir leídas individualmente.

### 6. Autorización por pertenencia, no por permiso

El endpoint opera siempre sobre `$request->user()->notifications`, nunca sobre un id de notificación ajeno tomado del request. No hay forma de pedir las notificaciones de otro usuario porque el usuario nunca entra como parámetro. Por eso no hace falta una policy ni un permiso: el aislamiento es estructural.

## Risks / Trade-offs

- **Una consulta de conteo extra por request** → Indexada y de un entero; despreciable frente al resto del `share()` (que ya resuelve permisos e indicadores). Medido conceptualmente: es más barata que `indicadoresTopbar`, que ya viaja.
- **El payload enriquecido crece respecto de los tres códigos actuales** → Son unos pocos strings por notificación; la tabla `notifications` guarda el `data` como JSON y este caso es pequeño. A cambio se elimina el N+1 al render.
- **Notificaciones viejas con el payload viejo (solo códigos)** conviven con las nuevas → El Resource y el frontend deben tolerar campos ausentes (mostrar el código si no hay nombre, omitir el enlace si no hay url). Se cubre con defaults en el Resource. En un entorno sin datos de producción esto es casi teórico, pero se maneja para no romper si hubiera filas viejas.
- **El sujeto de un proceso podría no tener ruta de detalle en el futuro** → El descriptor devuelve `url = null` y el panel degrada a notificación sin enlace. No se asume que todo sujeto navegable.

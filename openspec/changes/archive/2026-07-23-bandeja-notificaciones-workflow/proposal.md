## Why

Cada transición de workflow ya notifica a los responsables del proceso: `TransicionWorkflowService::execute()` envía una `TransicionWorkflowNotification` por el canal `database`, que se guarda en la tabla `notifications`. El emisor funciona y está en el spec de `workflow-core`.

Pero el circuito está cortado en el otro extremo: **no hay nada que consuma esas notificaciones.** No existe una campana en el header, ni un endpoint para listarlas o marcarlas leídas, ni se comparten al frontend. Un jefe de finanzas o un administrativo asignado a un caso nunca ve que le tocó actuar; la notificación se escribe en la base y muere ahí.

Además, el payload guardado hoy son solo códigos (`proceso_id`, `estado_anterior`, `estado_nuevo`), insuficiente para mostrar algo legible: un usuario no debería leer "en_revision", sino "En revisión", saber sobre qué caso, y poder ir a él.

## What Changes

- El sistema SHALL exponer al usuario autenticado sus notificaciones de workflow: una campana en el header con el número de no leídas y un panel con las más recientes.
- El sistema SHALL compartir en cada request únicamente el **conteo de notificaciones no leídas** del usuario (para el badge), sin volcar la lista completa en cada request.
- El sistema SHALL exponer un endpoint autenticado que liste las notificaciones del usuario (las más recientes, paginables), consultado cuando se abre la campana.
- El sistema SHALL permitir marcar las notificaciones como leídas: todas a la vez desde la campana.
- La `TransicionWorkflowNotification` SHALL guardar un payload autocontenido y legible —etiquetas de estado (no solo códigos), una descripción del proceso afectado y la URL de destino— capturado en el momento de la transición, de modo que la notificación se pueda renderizar y navegar sin consultas adicionales y sin depender de que el proceso siga en ese estado.
- Cada notificación en el panel SHALL enlazar al detalle del sujeto del proceso (el caso de pago o el proceso de adquisición correspondiente).
- Un usuario SHALL ver y marcar **solo** sus propias notificaciones.
- Sin permisos nuevos: las notificaciones son personales del usuario autenticado, no un recurso gobernado por rol.

## Capabilities

### New Capabilities

- `bandeja-notificaciones-workflow`: la bandeja de notificaciones de workflow del usuario —conteo de no leídas compartido, listado bajo demanda, marcar como leídas, campana en el header— y el contrato de que la notificación de transición lleve un payload legible y navegable.

### Modified Capabilities

Ninguna. `workflow-core` ya especifica que la transición "notifica a los responsables de las tareas abiertas del proceso"; esta capability define cómo esas notificaciones se entregan y consumen, y qué deben contener para ser útiles, sin cambiar el requirement de emisión.

## Impact

- **Backend**: un `NotificacionController` liviano (`index`, `marcarLeidas`) sobre las relaciones `notifications`/`unreadNotifications` que `User` ya expone vía `Notifiable`; una ruta nueva (`routes/web.php` o un `routes/notificaciones.php` requerido desde `web.php`); el conteo de no leídas se agrega al `share()` de `HandleInertiaRequests`. Un Resource para la forma de la notificación hacia React.
- **Notificación**: `TransicionWorkflowNotification::toDatabase()` pasa de guardar códigos a un payload enriquecido. El descriptor y la URL del proceso se derivan de su sujeto polimórfico (`CasoPagoProveedor` → `pago-proveedores.casos.show`, `ProcesoAdquisicion` → `adquisiciones.procesos.show`) mediante un método en el modelo `Proceso`, para no esparcir el mapeo por la notificación.
- **Frontend**: un componente de campana con dropdown en `app-header.tsx` (zona `ml-auto`, junto a los iconos existentes), que lee el conteo del share y pide la lista al endpoint al abrirse; tipos en `resources/js/types`. Rutas tipadas vía Wayfinder.
- **Rendimiento**: el conteo de no leídas es una consulta indexada por `(notifiable_type, notifiable_id, read_at)` sobre `notifications`, una por request; no se cachea, a propósito, para que marcar leído se refleje de inmediato (mismo criterio que ya se usó al no cachear los permisos del detalle de usuario). La lista completa no viaja en el share.
- **Tests**: `tests/Feature/Workflow/` y/o `tests/Feature/Notificaciones/`.
- Sin migraciones (la tabla `notifications` ya existe), sin permisos nuevos, sin cambios en seeders.

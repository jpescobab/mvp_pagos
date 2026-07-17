## Context

El logout pasa por `Laravel\Fortify\Http\Controllers\AuthenticatedSessionController::destroy()` (vendor, sin override), que invoca `Auth::guard('web')->logout()` y luego resuelve la respuesta a través del contrato `Laravel\Fortify\Contracts\LogoutResponse`. Sin un binding propio, se usa la implementación default de Fortify, que solo redirige — no interactúa con Inertia. Confirmado en vivo (login → logout → botón "atrás") que el navegador restaura el dashboard completo, con datos reales, sin nueva petición al servidor: el snapshot vive en `window.history.state`, fuera del alcance de cualquier invalidación de sesión del lado servidor.

Inertia v3 resuelve esto con "History Encryption": cifra el `page.props` antes de guardarlos en el historial, con una clave guardada en `sessionStorage` del navegador. `Inertia::clearHistory()` hace que el cliente rote esa clave tras la siguiente respuesta; las entradas de historial cifradas con la clave anterior quedan indescifrables, así que Inertia descarta el snapshot cacheado y pide la página de nuevo al servidor (que redirige a `/login` porque la sesión ya está cerrada).

## Goals / Non-Goals

**Goals:**
- Que ninguna página autenticada quede recuperable vía el botón "atrás" del navegador después de logout.
- Aplicar la protección a toda la aplicación (dashboard, casos de pago, expedientes, informes), no solo a rutas puntuales, dado que todas exponen datos institucionales.
- Mantener el mismo destino de redirect que el `LogoutResponse` actual de Fortify (sin cambiar UX del logout salvo la limpieza de historial).

**Non-Goals:**
- No se toca el flujo de login, 2FA, ni ningún otro `Response` contract de Fortify.
- No se implementa cifrado de historial "por página" (`Inertia::encryptHistory()` selectivo) — se opta por el global.
- No se modifica `TransicionWorkflowService`, workflow, ni ningún estado de negocio — este cambio es puramente de sesión/cliente.

## Decisions

**Cifrado global vs. middleware por ruta.** Se usa `config('inertia.history.encrypt') = true` en vez de aplicar el middleware `EncryptHistory` a grupos de rutas específicos. Motivo: la plataforma es institucional de punta a punta (montos, proveedores, expedientes en prácticamente todas las páginas autenticadas); depender de acordarse de anotar cada ruta nueva es un olvido fácil y ya se detectó el problema una vez sin que nadie lo hubiera anotado a propósito. El costo del cifrado global es despreciable (una operación de `crypto.subtle` por navegación) y no requiere tocar `routes/*.php`.

**Dónde limpiar el historial.** Se limpia únicamente en el logout (`Inertia::clearHistory()` desde un `LogoutResponse` propio), no en cada request. Es el único punto donde una clave de cifrado "vieja" debe invalidarse — mientras la sesión sigue activa, no hay necesidad de rotarla.

**Cómo interceptar el logout.** Fortify expone `Laravel\Fortify\Contracts\LogoutResponse` precisamente para esto: se crea `App\Http\Responses\LogoutResponse` implementando el contrato, delegando el destino del redirect a `Fortify::redirects('logout', '/')` (mismo comportamiento que el default) y añadiendo `Inertia::clearHistory()` antes de responder. Se registra con `$this->app->singleton(LogoutResponseContract::class, LogoutResponse::class)` en `FortifyServiceProvider::register()`, que hoy está vacío — es el service provider correcto porque ya es dueño de toda la demás configuración de Fortify (vistas, actions, rate limiting) en este proyecto.

**Alternativa descartada: manejar esto en el frontend (`router.clearHistory()`).** Inertia también permite limpiar el historial desde el cliente. Se descarta porque depende de que el JS del botón de logout se ejecute correctamente antes de que la petición al servidor complete (la limpieza de la clave debe ocurrir en el navegador tras confirmar que el servidor ya cerró la sesión); resolverlo server-side con `Inertia::clearHistory()` es la vía documentada y no depende de no-olvidar tocar `user-menu-content.tsx` cada vez.

**Apagado condicional por entorno (`INERTIA_HISTORY_ENCRYPT`).** Añadido después de detectar en vivo que el flag global rompía el logout en `pagos.test` (vhost Laragon sobre HTTP plano, sin SSL) — ver Risk de contexto seguro abajo. `config/inertia.php` pasó de `'encrypt' => true` a `'encrypt' => env('INERTIA_HISTORY_ENCRYPT', true)`: el default sigue siendo `true` (nada que configurar en producción, donde corre HTTPS real), y el `.env` local (no versionado) define `INERTIA_HISTORY_ENCRYPT=false` para desarrollar sobre el vhost HTTP sin que el bug de Inertia cuelgue cada visita.

## Risks / Trade-offs

- [Cifrado global agrega overhead de `crypto.subtle` en cada navegación] → Despreciable en la práctica (operación nativa del navegador); es el trade-off que la propia documentación de Inertia asume como el modo recomendado para apps con datos sensibles.
- [`history.encrypt` global también cifra páginas públicas como `/login`] → Sin efecto negativo: cifrar una página sin datos sensibles no cambia su comportamiento visible, solo agrega la operación criptográfica.
- [**Confirmado en vivo, no solo teórico**: Requiere `window.crypto.subtle`, disponible solo en contextos seguros (HTTPS, o el host literal `localhost`) — un hostname custom sobre HTTP plano como `pagos.test` (vhost de Laragon, sin SSL) NO es tratado como contexto seguro por el navegador aunque resuelva a loopback. Cuando `crypto.subtle` falta, `@inertiajs/core` tiene un bug real: `getPageData()` envuelve `encryptHistory(...).then(resolve)` en un `new Promise((resolve) => ...)` **sin `reject`**; si `encryptHistory()` lanza (que es exactamente lo que hace cuando no hay `crypto.subtle`), esa promesa queda pendiente para siempre — la cola interna de Inertia se traba y la barra de progreso de cualquier visita (no solo logout) nunca termina. Diagnosticado reproduciendo la cadena completa de redirects del logout con `curl` (confirmando que el servidor respondía perfectamente) y leyendo el bundle de `@inertiajs/core` directamente] → Mitigado con `INERTIA_HISTORY_ENCRYPT=false` en `.env` local (ver Decision arriba). Producción, al correr sobre HTTPS, no se ve afectada y no requiere ninguna configuración adicional.

## Migration Plan

Cambio de configuración + un binding nuevo, sin migraciones de base de datos ni cambios de rutas. Se aplica en un solo paso (no requiere despliegue gradual): al desplegar, todas las sesiones activas empiezan a recibir historial cifrado en su siguiente navegación; el primer logout de cada usuario después del deploy ya limpia su clave correctamente. No hay rollback especial: revertir el binding y la config basta si hiciera falta.

## Open Questions

Ninguna — el mecanismo está completamente documentado por Inertia y no depende de ninguna decisión pendiente del dominio.

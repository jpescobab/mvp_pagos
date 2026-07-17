## 1. Habilitar cifrado de historial de Inertia

- [x] 1.1 En `config/inertia.php`, agregar la sección `'history' => ['encrypt' => env('INERTIA_HISTORY_ENCRYPT', true)]` (ver tarea 5 — el valor terminó vía env, no fijo, tras detectar el bug de contexto no seguro).

## 2. Limpiar el historial cifrado al cerrar sesión

- [x] 2.1 Crear `app/Http/Responses/LogoutResponse.php` implementando `Laravel\Fortify\Contracts\LogoutResponse`. El método `toResponse($request)` SHALL: mantener el mismo comportamiento que el default de Fortify (`JsonResponse('', 204)` si `$request->wantsJson()`, si no `redirect(Fortify::redirects('logout', '/'))`), y SHALL llamar a `Inertia::clearHistory()` antes de construir esa respuesta.
- [x] 2.2 En `app/Providers/FortifyServiceProvider.php`, dentro de `register()` (hoy vacío), registrar `$this->app->singleton(\Laravel\Fortify\Contracts\LogoutResponse::class, \App\Http\Responses\LogoutResponse::class)`.

## 3. Tests

- [x] 3.1 En `tests/Feature/Auth/AuthenticationTest.php`, extender el test `users can logout` (o agregar uno nuevo) para verificar que la respuesta de logout incluye `clearHistory: true` en el page object de Inertia (usar el helper de aserciones Inertia del paquete de testing, p. ej. inspeccionando el header `X-Inertia` con `wantsJson()` o `assertInertia` según el patrón ya usado en otros tests Feature del proyecto).
- [x] 3.2 Ejecutar `php artisan test --compact --filter=AuthenticationTest` y confirmar que pasa.

## 4. Verificación manual

- [x] 4.1 Con `composer dev` corriendo, iniciar sesión, navegar al dashboard, cerrar sesión, y confirmar en el navegador que el botón "atrás" ya no muestra el dashboard cacheado (debe re-pedir al servidor y terminar en `/login`).
- [x] 4.2 Correr `composer lint:check`, `npm run lint:check` y `npm run types:check` sobre los archivos tocados.

## 5. Ajuste post-implementación: contexto no seguro rompe el logout en `pagos.test`

- [x] 5.1 Detectado en el navegador real del usuario (vhost Laragon `pagos.test`, HTTP sin SSL): el logout se queda con la barra de progreso corriendo para siempre. Diagnosticado con `curl` reproduciendo la cadena completa de redirects de `/logout` (servidor responde `200` con el page object correcto en cada salto) y leyendo `node_modules/@inertiajs/core/dist/index.js`: `getPageData()` envuelve `encryptHistory(...).then(resolve)` en una `Promise` sin `reject` — si `encryptHistory()` lanza (porque `window.crypto.subtle` no existe en un contexto no seguro), la promesa queda pendiente para siempre y la cola de Inertia se traba. Bug del paquete, no de este código.
- [x] 5.2 `config/inertia.php`: cambiar `'encrypt' => true` a `'encrypt' => env('INERTIA_HISTORY_ENCRYPT', true)`.
- [x] 5.3 `.env` local: agregar `INERTIA_HISTORY_ENCRYPT=false` con comentario explicando por qué (no versionado — producción no define esta var y queda protegida por defecto).
- [x] 5.4 Confirmar con `curl` que la respuesta de `/login` ya no incluye `encryptHistory`/`clearHistory` con el flag apagado.

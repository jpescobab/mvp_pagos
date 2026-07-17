## Why

Tras cerrar sesión, si el usuario pulsa el botón "atrás" del navegador, Inertia restaura la última página visitada (dashboard, casos de pago, expedientes) completa desde el historial cacheado del navegador (`window.history.state`), sin volver a consultar el servidor. El servidor sí invalida la sesión correctamente (verificado: una petición directa a una ruta protegida tras logout redirige a `/login`), pero el navegador sigue mostrando datos institucionales sensibles ya renderizados. Esto se reportó como "el logout no cierra la sesión" y es, en el fondo, una fuga de datos vía el historial del navegador — un riesgo de seguridad real dado que la plataforma expone montos, proveedores y casos de pago.

## What Changes

- Habilitar el cifrado de historial de Inertia (`history encryption`) globalmente, dado que toda la plataforma maneja datos institucionales sensibles y no solo rutas puntuales.
- Registrar una respuesta de logout propia que limpie el historial cifrado (`Inertia::clearHistory()`) antes de redirigir, reemplazando el `LogoutResponse` default de Fortify.
- Agregar cobertura de test que confirme que la respuesta de logout incluye la limpieza de historial.

## Capabilities

### New Capabilities
(ninguna)

### Modified Capabilities
- `seguridad-auditoria`: agrega el requisito de que, tras cerrar sesión, el historial de navegación cliente no deje accesibles páginas autenticadas previas.

## Impact

- `config/inertia.php`: nueva sección `history.encrypt`.
- `app/Providers/FortifyServiceProvider.php`: registra el binding de `Laravel\Fortify\Contracts\LogoutResponse`.
- Nuevo: `app/Http/Responses/LogoutResponse.php`.
- `tests/Feature/Auth/AuthenticationTest.php`: nueva aserción sobre el logout.
- Sin cambios de base de datos ni de rutas.

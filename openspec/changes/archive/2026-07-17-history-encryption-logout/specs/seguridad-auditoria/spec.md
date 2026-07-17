## ADDED Requirements

### Requirement: El historial de navegación del cliente no expone páginas autenticadas tras cerrar sesión
El sistema SHALL cifrar el historial de navegación de Inertia (`history encryption`) para toda la aplicación de forma predeterminada, y SHALL limpiar (rotar la clave de) ese historial al cerrar sesión, de modo que ninguna página autenticada previamente visitada quede recuperable desde el navegador del cliente después del logout. El cifrado SHALL poder desactivarse por entorno (`INERTIA_HISTORY_ENCRYPT`) sin cambiar el valor predeterminado activo, para permitir el desarrollo sobre contextos no seguros (HTTP plano en hosts distintos de `localhost`) donde `window.crypto.subtle` no está disponible; producción SHALL mantener el cifrado activo por defecto.

#### Scenario: Botón "atrás" tras cerrar sesión no revela una página autenticada
- **WHEN** un usuario autenticado navega por páginas del sistema y luego cierra sesión
- **AND** presiona el botón "atrás" del navegador
- **THEN** el navegador no debe mostrar el contenido cacheado de la página autenticada previa
- **AND** en su lugar solicita la página nuevamente al servidor, que redirige a la pantalla de inicio de sesión

#### Scenario: Cerrar sesión limpia la clave de cifrado del historial
- **WHEN** un usuario autenticado cierra sesión
- **THEN** la respuesta de logout instruye a Inertia a limpiar el historial cifrado (`Inertia::clearHistory()`)
- **AND** cualquier snapshot de historial cifrado con la clave anterior queda indescifrable en el cliente

#### Scenario: Cifrado desactivado en un contexto no seguro
- **WHEN** el entorno define `INERTIA_HISTORY_ENCRYPT=false` (p. ej. desarrollo local sobre HTTP plano sin `window.crypto.subtle`)
- **THEN** el sistema no cifra el historial de navegación de Inertia
- **AND** el logout sigue funcionando sin bloquearse, aun sin la protección del historial cifrado

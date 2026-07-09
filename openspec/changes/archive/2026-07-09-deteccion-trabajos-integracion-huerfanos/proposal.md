## Why

Un `trabajo_integracion` en estado `en_progreso` puede quedar huérfano para siempre si el proceso que lo ejecuta muere sin lanzar ninguna excepción capturable (timeout del proceso supervisor de la cola, terminal cerrada, equipo suspendido, servicio externo reiniciado a mitad de camino). Esto ocurrió repetidamente calibrando el conector Playwright de SGF: cada vez, la única forma de destrabarlo fue entrar a la base de datos a mano y marcarlo como `error`. Mientras un trabajo quede huérfano, la guarda de "ya hay una importación en curso" bloquea cualquier reintento indefinidamente para un usuario autorizado, sin ninguna señal de que el trabajo realmente dejó de avanzar.

## What Changes

- El sistema detecta automáticamente cuando un `trabajo_integracion` en `en_progreso` superó un umbral de tiempo razonable sin finalizar, y deja de tratarlo como "en curso" para efectos de la guarda de un nuevo intento.
- Un `trabajo_integracion` detectado como huérfano queda marcado con un estado/mensaje que refleja explícitamente que no se puede confiar en que siga corriendo (distinto de un `error` de negocio real, para no confundir ambas causas en reportes).
- Un usuario autorizado puede disparar un nuevo intento de importación sin que nadie tenga que intervenir la base de datos a mano.
- Aplica de forma genérica a la capa transversal de integraciones (`trabajos_integracion`), no solo al conector de SGF — cualquier conector futuro (API o Playwright) hereda esta protección.

## Capabilities

### New Capabilities
(ninguna — se extiende la capacidad transversal existente)

### Modified Capabilities
- `integraciones-api-browser-automation`: nuevo requirement sobre detección y marcado automático de `trabajos_integracion` huérfanos, y sobre qué guardas de "trabajo en curso" deben dejar de considerarlos activos.
- `conector-sgf-playwright`: el escenario "Ya hay una importación masiva en curso" se ajusta para reflejar que un `trabajo_integracion` huérfano ya no bloquea un nuevo intento.

## Impact

- `app/Models/TrabajoIntegracion.php`: posible nuevo estado/columna o scope para detectar huérfanos.
- `app/Services/Integraciones/IntegracionExternaService.php`: punto natural para la lógica de detección/marcado.
- `app/Http/Controllers/Sgf/ImportarCasosPendientesSgfController.php`: la guarda de "trabajo en curso" debe considerar la detección de huérfanos antes de bloquear un nuevo intento.
- Cualquier otro controlador que dispare `trabajos_integracion` de larga duración se beneficia del mismo mecanismo transversal.
- Posible nuevo comando Artisan programado (`schedule`) o chequeo al momento de la guarda — a definir en `design.md`.

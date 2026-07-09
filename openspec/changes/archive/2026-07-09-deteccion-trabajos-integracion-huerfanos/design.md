## Context

`trabajos_integracion` es la tabla transversal que registra cada corrida de integración (API o Playwright) contra un sistema externo (`sistemas_externos`), con estados `en_progreso` → `completado`/`error`. Los conectores de larga duración (hoy, `ImportarCasosPendientesSgfJob` vía Playwright) corren como un único job síncrono que hace una sola llamada HTTP bloqueante al microservicio externo — no reportan avance incremental mientras corren (`total_elementos` solo se incrementa después de recibir la respuesta completa).

Durante la calibración real del conector Playwright de SGF esta sesión, el `trabajo_integracion` quedó huérfano en `en_progreso` varias veces por causas todas fuera del alcance del propio código PHP: el proceso hijo de `queue:listen` fue matado por el timeout de 60s de `Symfony\Process` (crasheando `queue:listen` completo sin ninguna excepción capturable), la terminal se cerró, el equipo se suspendió, y el servicio Node fue reiniciado a mitad de camino. En cada caso, `IntegracionExternaService::finalizarTrabajo()` nunca llegó a ejecutarse, y `ImportarCasosPendientesSgfController::store()` bloqueó cualquier reintento indefinidamente hasta que alguien corrigiera el registro a mano en la base de datos.

Ya existen mitigaciones para reducir la probabilidad de que esto ocurra (`$timeout` del Job, `--timeout` de `queue:listen`, `WithoutOverlapping::expireAfter()`, timeout del cliente HTTP) — ver `services/sgf-playwright/CALIBRACION.md` y `HARNESS_IA.md` sección 13. Pero ninguna de ellas cubre TODAS las formas en que un proceso puede morir sin dejar rastro (ej. un futuro corte de energía, un `kill -9` externo, un fallo de infraestructura no anticipado). Este change no busca eliminar esas causas — busca que, sin importar la causa, el sistema deje de bloquear reintentos indefinidamente por un trabajo que ya no está corriendo de verdad.

## Goals / Non-Goals

**Goals:**
- Que un `trabajo_integracion` en `en_progreso` que superó un umbral razonable de tiempo sin finalizar deje de bloquear nuevos intentos, sin intervención manual en la base de datos.
- Que la detección sea genérica (capa transversal `integraciones-api-browser-automation`), no específica del conector de SGF.
- Que un trabajo detectado como huérfano quede visualmente distinguible de un `error` de negocio real (para no confundir "SGF rechazó la operación" con "el proceso murió sin poder reportar por qué").
- Autosanación tanto proactiva (barrido periódico) como inmediata (al momento en que alguien intenta un nuevo intento, sin esperar el próximo barrido).

**Non-Goals:**
- No se implementa un mecanismo de heartbeat/progreso incremental real dentro de la corrida (ej. que Node reporte avance página por página a Laravel mientras corre) — eso requeriría rediseñar el contrato HTTP síncrono actual entre Laravel y `services/sgf-playwright/`, y es una inversión mayor no justificada solo para resolver el bloqueo de reintentos. Puede proponerse como change aparte si se necesita observabilidad de avance en tiempo real.
- No se reintenta automáticamente el trabajo huérfano — solo se libera la guarda para que un usuario autorizado dispare un nuevo intento explícito (reintentar automáticamente sin supervisión de qué falló podría reintentar contra un SGF que rechazó la operación por una razón real).
- No se toca el mecanismo de `WithoutOverlapping`/lock de cola — eso ya se corrigió esta sesión (`expireAfter`) y es ortogonal a este problema (el lock protege contra ejecución concurrente; este change protege contra el bloqueo de la guarda de negocio del controlador).

## Decisions

### Umbral configurable por tipo de trabajo, no un valor único global
Los distintos tipos de `trabajo_integracion` tienen duraciones esperadas muy distintas: `verificar_caso` (SGF, síncrono, timeout HTTP de 120s) debería considerarse huérfano mucho antes que `importar_pendientes` (Playwright, puede tomar hasta ~60 min por diseño actual). Se define un mapa de configuración `config('integraciones.umbral_huerfano_minutos')` con clave `{tipo}` (ej. `importar_pendientes` => 90, `verificar_caso` => 10) y un default conservador (ej. 120 min) para tipos no listados explícitamente — siempre por encima de los timeouts conocidos del Job/HTTP/lock correspondiente, para no marcar como huérfano un trabajo que en realidad sigue corriendo legítimamente.

Alternativa descartada: un único umbral global. Se descarta porque un umbral lo bastante alto para no cortar prematuramente una importación masiva de Playwright sería demasiado alto para detectar rápido una verificación puntual colgada, y viceversa.

### Estado nuevo `huerfano`, no reutilizar `error`
Se agrega `huerfano` como valor válido de `trabajos_integracion.estado`, junto a `error`, con `finalizado_en` y un `error` (mensaje) explicando la detección automática. No requiere migración de esquema (`estado` ya es `character varying`, sin `CHECK` constraint ni enum de base de datos). Reutilizar `error` fue descartado porque mezclaría, en reportes y en la vista de detalle, un rechazo real de SGF (ej. credenciales inválidas, conector no autorizado) con "el proceso murió sin poder decir por qué" — son causas de intervención muy distintas para quien audita.

Impacto en frontend: `resources/js/pages/sgf/importaciones/index.tsx` (y el resource equivalente `ImportacionSgfResource`) deben reconocer `huerfano` como un tercer badge semántico (siguiendo el patrón `success`/`danger` ya establecido en `tema-visual-layout`; se propone un tercer token neutro/advertencia para no confundirlo con `danger`=error real).

### Detección en dos puntos: barrido programado + chequeo perezoso en la guarda
1. **Barrido programado**: un comando Artisan (`trabajos-integracion:expirar-huerfanos`) registrado en el Scheduler, corriendo cada 5 minutos, que marca como `huerfano` todo `trabajo_integracion` en `en_progreso` cuyo `iniciado_en` supere el umbral de su `tipo`. Da observabilidad correcta (la tabla refleja la realidad aunque nadie reintente).
2. **Chequeo perezoso**: la misma lógica de detección (extraída a un método reutilizable, ej. `IntegracionExternaService::expirarSiEsHuerfano(TrabajoIntegracion $trabajo)` o un scope `TrabajoIntegracion::query()->huerfanos()`) se invoca también desde `ImportarCasosPendientesSgfController::store()` (y cualquier controlador equivalente futuro) antes de evaluar la guarda de "ya hay uno en curso" — así un usuario que reintenta de inmediato no tiene que esperar el próximo tick del scheduler.

Alternativa descartada: solo barrido programado sin chequeo perezoso. Se descarta porque deja una ventana (hasta 5 min) donde un reintento inmediato seguiría bloqueado innecesariamente después de que el umbral ya venció.

Alternativa descartada: solo chequeo perezoso sin barrido programado. Se descarta porque un trabajo huérfano en el que nadie vuelve a intentar quedaría mostrando `en_progreso` para siempre en listados y reportes, aunque ya no bloquee nada activamente.

## Risks / Trade-offs

- **[Riesgo] Marcar como huérfano un trabajo que en realidad sigue corriendo legítimamente** (falso positivo) → Mitigación: el umbral por tipo se fija con margen generoso por encima de los timeouts ya configurados en el Job/HTTP/cola correspondientes (ver `CALIBRACION.md`); further, el mensaje de error de un trabajo marcado como huérfano debe ser explícito para que un humano pueda diferenciarlo de un `error` real y, si corresponde, ajustar el umbral en config sin tocar código.
- **[Riesgo] El scheduler no está corriendo** (ej. `php artisan schedule:work` no está activo en el entorno) → el barrido programado nunca corre, pero el chequeo perezoso en la guarda sigue funcionando igual al día siguiente cuando alguien reintenta manualmente — no es un punto único de fallo.
- **[Trade-off] Ventana de hasta 5 minutos entre que un trabajo se vuelve huérfano y el barrido lo marca** — aceptable dado que el chequeo perezoso ya cubre el caso de reintento inmediato; 5 minutos de "en_progreso" fantasma en un listado no es un problema operacional grave.

## Migration Plan

- Sin migración de esquema (columna `estado` ya es de texto libre).
- Nuevo comando Artisan + registro en el Scheduler (`routes/console.php` o `bootstrap/app.php` según convención de Laravel 13 del proyecto).
- Ajuste de `IntegracionExternaService` y del controlador de SGF.
- Ajuste de frontend (badge nuevo) y de cualquier test existente que asuma solo `en_progreso`/`completado`/`error` como valores posibles de `estado`.
- Rollback: revertir el commit; no hay datos migrados que revertir (el nuevo estado solo se escribe hacia adelante).

## Open Questions

- ¿Los umbrales exactos por tipo (90 min para `importar_pendientes`, 10 min para `verificar_caso`) los define este change o deben confirmarse con quien opera el entorno de producción antes de fijarlos en config? Se proponen como default razonable basado en los timeouts ya calibrados esta sesión, pero son ajustables sin código.
- ¿El comando de barrido debe también notificar (ej. notification a los administradores) cuando marca un trabajo como huérfano, o basta con que quede visible en el listado de importaciones? Se asume por ahora que basta con la visibilidad en el listado existente.

## 1. Configuración

- [x] 1.1 Agregar `config/integraciones.php` (o sección equivalente en un config existente) con `umbral_huerfano_minutos` por `tipo` de trabajo (`importar_pendientes`, `verificar_caso`) y un default conservador para tipos no listados.
- [x] 1.2 Documentar en `.env.example` cualquier variable de entorno nueva que permita ajustar los umbrales sin tocar código.

## 2. Detección reutilizable en la capa transversal

- [x] 2.1 Agregar a `TrabajoIntegracion` (o a `IntegracionExternaService`) un método/scope que identifique si un `trabajo_integracion` en `en_progreso` superó el umbral de su `tipo` (ej. `TrabajoIntegracion::query()->huerfanos()` o `IntegracionExternaService::esHuerfano(TrabajoIntegracion $trabajo): bool`).
- [x] 2.2 Agregar `IntegracionExternaService::marcarHuerfano(TrabajoIntegracion $trabajo): void` que actualiza `estado='huerfano'`, `finalizado_en=now()` y un mensaje de error explícito de detección automática.
- [x] 2.3 Escribir tests unitarios/feature para la detección (umbral por tipo, no marcar un trabajo aún dentro del umbral, no marcar trabajos ya `completado`/`error`).

## 3. Barrido programado

- [x] 3.1 Crear comando Artisan `trabajos-integracion:expirar-huerfanos` que recorra todos los `trabajos_integracion` en `en_progreso` y marque como `huerfano` los que superen su umbral.
- [x] 3.2 Registrar el comando en el Scheduler (cada 5 minutos).
- [x] 3.3 Test feature del comando: crea trabajos dentro y fuera del umbral, corre el comando, verifica que solo los que corresponde queden marcados.

## 4. Chequeo perezoso en el punto de entrada

- [x] 4.1 Actualizar `ImportarCasosPendientesSgfController::store()` para invocar la detección de huérfanos sobre el `trabajo_integracion` existente (si lo hay) antes de evaluar la guarda de "ya hay uno en curso".
- [x] 4.2 Test feature: un `trabajo_integracion` huérfano (fuera del umbral, en `en_progreso`) no bloquea un nuevo intento de importación masiva de SGF; uno dentro del umbral sí sigue bloqueando.

## 5. Frontend — distinguir el nuevo estado

- [x] 5.1 Actualizar `ImportacionSgfResource` (o el resource equivalente) para exponer el estado `huerfano` al frontend. (Ya lo exponía genéricamente vía `'estado' => $this->estado`; sin cambios necesarios.)
- [x] 5.2 Actualizar `resources/js/pages/sgf/importaciones/index.tsx` (badge de estado) para renderizar `huerfano` con un token semántico distinto de `success`/`danger`, siguiendo el patrón de `tema-visual-layout`. (Implementado en `ImportacionEstadoBadge` con el token `warning` ya existente en `app.css`.)
- [x] 5.3 Actualizar `tests/Feature/Sgf/ConsultarImportacionesSgfTest.php` si asume un conjunto cerrado de estados posibles. (Revisado: `estado` ya es texto libre sin validación de enum cerrado; sin cambios necesarios.)

## 6. Validación de punta a punta

- [x] 6.1 `composer test` (lint:check + types:check + suite completa) sin regresiones. (482 passed, 4 skipped, 0 errores de PHPStan; también `npm run lint:check` y `npm run types:check` limpios.)
- [x] 6.2 Verificar manualmente: crear un `trabajo_integracion` de prueba con `iniciado_en` artificialmente viejo, correr el comando de barrido, confirmar que queda `huerfano` y que un nuevo intento desde la UI ya no queda bloqueado. (Verificado en navegador: el comando marcó el trabajo como `huerfano` y el badge renderiza con el token `warning`. La verificación de "un nuevo intento ya no bloquea" se cubrió con tests automatizados — Queue::fake — en vez de un clic real en la UI, para no disparar una importación real contra SGF con el conector en modo real.)

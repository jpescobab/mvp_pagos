## Context

`ConectorAutomatizacionNavegador` (tabla `conectores_automatizacion_navegador`, `activo` default `false`) y `PerfilAutenticacionNavegador` ya existen desde la tarea 09, junto con el permiso `integraciones.gestionar_conectores` (sembrado, nunca usado) y el helper `ConectorAutomatizacionNavegador::estaAutorizado(): bool` (`activo && autorizado_por !== null && autorizado_en !== null`). No existe controlador, policy ni página para ninguno de los dos modelos.

## Goals / Non-Goals

**Goals:**
- Permitir registrar un conector (asociado a un sistema externo) y autorizarlo explícitamente (usuario + fecha), cumpliendo el requisito ya documentado de la tarea 09.
- Permitir registrar un perfil de autenticación de un conector guardando solo almacén + referencia del secreto, nunca el secreto real.

**Non-Goals:**
- No se construye ningún disparador de ejecución Playwright real (`AutomatizacionNavegadorService::iniciarEjecucion` no se invoca desde esta UI) ni de trabajos de integración (`IntegracionExternaService`) — son APIs para que una corrida real las invoque, que no existe en este sistema todavía.
- No se expone UI de lectura para `ejecuciones_automatizacion_navegador`, `trabajos_integracion`, `solicitudes_api_externas` ni `snapshots_datos_externos` — sin ningún proceso que los pueble, serían listados permanentemente vacíos sin valor.

## Decisions

1. **Nueva `ConectorAutomatizacionNavegadorPolicy`** (mismo patrón que `EgresoCguPolicy`): `viewAny`/`view` abiertos a cualquier usuario autenticado; `create` y `gestionar(User, ConectorAutomatizacionNavegador)` exigen `integraciones.gestionar_conectores`. `gestionar` cubre tanto autorizar el conector como crear un perfil de autenticación bajo él (misma responsabilidad administrativa).
2. **`autorizar()` actualiza `activo=true`, `autorizado_por` y `autorizado_en` en una sola operación** — no existen estados intermedios de autorización; un conector está autorizado o no.
3. **El perfil de autenticación solo acepta `almacen_secreto` y `referencia_secreto`** (strings descriptivos, p. ej. "vault" + "secret/conectores/sgf"), nunca un campo de contraseña/token — refuerza en el Form Request lo que el harness ya exige a nivel de esquema.
4. **Auditoría**: registrar `AuditLogger` en la creación y autorización del conector (`integraciones.crear_conector`, `integraciones.autorizar_conector`), igual criterio que `caso_pago_proveedor.registrar_factura` — es una acción administrativa sensible (habilita automatización futura sobre un sistema externo).

## Risks / Trade-offs

- [Riesgo] Sin disparador real, esta UI por sí sola no demuestra una automatización Playwright funcionando end-to-end. → Mitigación: está documentado como no-goal explícito; el valor de esta tarea es cumplir el requisito de autorización explícita ya escrito en la spec de la tarea 09, no construir el runner de Playwright (que requiere autorización de producto separada).

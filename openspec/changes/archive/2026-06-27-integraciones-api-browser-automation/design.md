## Context

Hoy solo existe un conector real con un sistema externo: SGF (tarea 7), modelado de forma ad-hoc (`ImportacionSgf` + `SnapshotSgf`) porque la capa transversal genérica todavía no existía. El harness (sección 13) exige que toda integración externa futura — CGU, BancoEstado, SII, CMF, Mercado Público — pase por un mismo conjunto de tablas (`sistemas_externos`, `solicitudes_api_externas`, `snapshots_datos_externos`, `trabajos_integracion`), y que Playwright solo se use como respaldo autorizado y trazado cuando no exista API suficiente.

Esta tarea construye esa capa como infraestructura de dominio reutilizable, sin UI ni endpoints HTTP propios (mismo patrón que tareas 5-8), consumible por servicios de módulos funcionales.

## Goals / Non-Goals

**Goals:**
- Dar a cualquier conector futuro un lugar único y consistente para registrar: qué sistema externo se llamó, qué solicitud ocurrió, qué snapshot de datos quedó como evidencia y bajo qué trabajo/corrida.
- Permitir automatizaciones Playwright solo a través de un conector explícitamente catalogado y autorizado (usuario + fecha), nunca de forma ad-hoc desde un job o controlador.
- Garantizar que ninguna credencial ni cookie de automatización se guarde en la base de datos ni en Git — `perfiles_autenticacion_navegador` solo guarda un puntero (almacén + referencia) a donde vive el secreto real.
- Dejar registrado el catálogo de sistemas externos oficiales nombrados por el harness, aunque todavía no tengan integración real.
- Mantener la convención de nombres en español ya vigente en el proyecto, incluyendo actualizar el harness (`CLAUDE.md`, `HARNESS_IA.md`, `openspec/config.yaml`) para que sus referencias a esta capa también queden en español.

**Non-Goals:**
- No se retrofitea SGF (`ImportacionSgf`/`SnapshotSgf`) para usar esta capa; sigue funcionando igual que en la tarea 7. Ese retrofit, si se decide, es un change futuro independiente.
- No se implementa ningún conector real (API ni Playwright) contra CGU, BancoEstado, SII, CMF o Mercado Público; solo se siembra el catálogo `sistemas_externos` como referencia.
- No se construye UI ni endpoints HTTP; es capa de dominio/servicio, igual que tareas 5-8.
- No se decide aquí qué constituye "API insuficiente" para autorizar un conector Playwright — eso es una decisión humana de negocio registrada en `conectores_automatizacion_navegador.autorizado_por`/`autorizado_en`, no una regla que el código infiera.

## Decisions

1. **`trabajos_integracion` es el contenedor de una corrida; `solicitudes_api_externas` son las llamadas dentro de ella.** Un trabajo puede tener cero, una o muchas solicitudes (p. ej. una sincronización que pagina varias llamadas). `solicitudes_api_externas.trabajo_integracion_id` es nullable para permitir registrar una llamada puntual sin una corrida formal alrededor.

2. **`snapshots_datos_externos` es el equivalente genérico de `snapshots_sgf`, no su reemplazo.** Mismo criterio de inmutabilidad (nunca se sobrescribe, una recaptura crea snapshot nuevo) y mismos campos núcleo (`payload_crudo`, `payload_normalizado`, `hash`, `capturado_en`), generalizando `sgf_id` como `referencia_externa` y agregando vínculo polimórfico `vinculable` (mismo patrón que `VinculoDocumento.vinculable` de la tarea 6) para que cualquier caso interno pueda asociar evidencia externa sin acoplarse a un módulo concreto.

3. **`conectores_automatizacion_navegador` siempre referencia un `sistema_externo`.** Playwright no es un sistema en sí mismo, es un mecanismo alternativo para integrarse con un sistema que ya está catalogado. Esto evita que se cree un conector Playwright "suelto" sin sistema externo asociado.

4. **Autorización de Playwright es un dato, no una regla de código.** `conectores_automatizacion_navegador.autorizado_por` + `autorizado_en` deben estar presentes (no nulos) y `activo = true` para que `AutomatizacionNavegadorService::iniciarEjecucion()` permita arrancar una ejecución; el servicio valida esa precondición y lanza `ConectorAutomatizacionNoAutorizadoException` si falta, pero no intenta decidir *cuándo* Playwright está justificado — esa decisión humana ya quedó registrada al crear/autorizar el conector.

5. **`perfiles_autenticacion_navegador` nunca almacena secretos.** Solo guarda `almacen_secreto` (p. ej. `'env'`, `'vault'`) y `referencia_secreto` (p. ej. nombre de variable de entorno o ruta en el vault) — el valor real del secreto vive fuera de la base de datos y fuera de Git, igual que exige el harness.

6. **`pasos_automatizacion_navegador` y `artefactos_automatizacion_navegador` son append-only**, igual que `snapshots_sgf` y `validaciones_documento`: cada paso/artifact de una ejecución se inserta, nunca se actualiza ni se borra, para que la corrida quede como traza auditable completa.

7. **Seeding de `sistemas_externos` es solo catálogo de referencia**, con `tipo_integracion = 'manual'` y `activo = false` para los sistemas que todavía no tienen integración real (CGU, BancoEstado, SII, CMF, Mercado Público) y `tipo_integracion = 'manual'`/`activo = true` para SGF (reconociendo que hoy se integra, aunque sea por fuera de esta capa) — no se inventan credenciales ni URLs reales.

8. **Nombres en español, sufijos arquitectónicos en inglés.** Igual que `CasoPagoProveedorImporter` (tarea 8) o `TransicionWorkflowService` (tarea 5), los nombres de dominio se traducen (`SistemaExterno`, `TrabajoIntegracion`, `ConectorAutomatizacionNavegador`) y se mantiene en inglés solo el sufijo que describe el patrón de la clase (`...Service`, `...Exception`). Términos ya adoptados como préstamo en el harness (`snapshot`, `hash`, `payload`, `endpoint`) se mantienen sin traducir.

## Risks / Trade-offs

- **[Riesgo] Tablas nuevas sin consumidor real inmediato** (ningún conector real las usa todavía, ya que SGF queda fuera de alcance) → **Mitigación**: igual que tareas 5-7 (workflow-core y expediente documental se sembraron sin módulo funcional encima hasta la tarea 8), se valida con tests de servicio que ejercitan el flujo completo (crear trabajo → registrar solicitud → registrar snapshot → cerrar trabajo; crear ejecución autorizada → registrar pasos/artifacts → cerrar ejecución) aunque el "sistema externo" del test sea ficticio.
- **[Riesgo] Que un desarrollador futuro intente crear una `ejecucion_automatizacion_navegador` sin pasar por `AutomatizacionNavegadorService`** (saltando la validación de autorización) → **Mitigación**: el servicio es el único punto documentado para iniciar ejecuciones; se deja como convención igual que `TransicionWorkflowService` para workflow, reforzada por test que verifica que el servicio rechaza conectores inactivos o no autorizados.
- **[Riesgo] Confusión entre `snapshots_datos_externos` (genérico) y `snapshots_sgf` (específico)** al construir el próximo conector real → **Mitigación**: el design documenta explícitamente que son paralelos, no jerárquicos; un futuro retrofit de SGF (fuera de alcance aquí) decidiría si migra o conviven.

## Migration Plan

Tablas nuevas, sin alterar ninguna migración existente. Orden de creación (respeta FKs):
1. `sistemas_externos`
2. `trabajos_integracion` (FK a `sistemas_externos`, `users`)
3. `solicitudes_api_externas` (FK a `sistemas_externos`, `trabajos_integracion`)
4. `snapshots_datos_externos` (FK a `sistemas_externos`, `trabajos_integracion`, `solicitudes_api_externas`, `users`; columnas polimórficas `vinculable_type`/`vinculable_id`)
5. `conectores_automatizacion_navegador` (FK a `sistemas_externos`, `users`)
6. `perfiles_autenticacion_navegador` (FK a `conectores_automatizacion_navegador`, `users`)
7. `ejecuciones_automatizacion_navegador` (FK a `conectores_automatizacion_navegador`, `perfiles_autenticacion_navegador`, `trabajos_integracion`, `users`)
8. `pasos_automatizacion_navegador` (FK a `ejecuciones_automatizacion_navegador`)
9. `artefactos_automatizacion_navegador` (FK a `ejecuciones_automatizacion_navegador`, `pasos_automatizacion_navegador`)

No hay datos previos que migrar (tablas nuevas). Rollback estándar: `down()` con `dropIfExists` en orden inverso.

## Open Questions

Ninguna pendiente — el alcance (capa nueva, sin retrofit de SGF, nombres en español) quedó decidido explícitamente antes de este design.

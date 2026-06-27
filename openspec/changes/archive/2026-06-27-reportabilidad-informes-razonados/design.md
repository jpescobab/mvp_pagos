## Context

El harness (sección 14) describe un flujo lineal: `corte mensual publicado -> snapshot de datos -> métricas -> excepciones -> gráficos -> texto razonado en borrador -> revisión humana -> aprobación -> publicación -> exportación`. Hasta la tarea 9, ningún dato del sistema queda "congelado": todo vive en tablas mutables (procesos, casos, snapshots de origen). Esta tarea introduce el primer mecanismo de corte/congelamiento de datos, y el primer módulo que produce informes a partir de esos cortes, en vez de consultar datos vivos directamente.

## Goals / Non-Goals

**Goals:**
- Separar la capa "core" de reportabilidad (cortes, períodos, snapshots — siempre disponible, igual que indicadores/integraciones) del módulo funcional "informes_razonados" (puede desactivarse sin perder evidencia).
- Garantizar que un informe solo pueda construirse sobre un corte ya publicado (inmutable), nunca sobre datos en vivo.
- Aplicar la regla "workflow antes que CRUD" al ciclo de vida de cada ejecución de informe (borrador → revisión → aprobación/rechazo → publicación), reutilizando `TransicionWorkflowService` exactamente como lo hizo `CasoPagoProveedor` en la tarea 8, en vez de inventar un segundo mecanismo de estados.
- Dejar explícito en el modelo de datos que la IA puede redactar narrativa pero no aprobar (`narrativas_informe_razonado.generado_por_ia` + revisión humana separada de la aprobación de workflow).

**Non-Goals:**
- No se genera contenido real de Word/PDF/Excel/HTML ni se integra un motor de gráficos; `exportaciones_informe_razonado` solo registra la evidencia de la exportación (formato, ruta, responsable), igual que tareas anteriores no replicaron lógica de sistemas externos.
- No se decide aquí qué métricas/excepciones/gráficos concretos debe tener cada tipo de informe — eso es contenido de negocio que se construye sobre estos servicios en una iteración posterior; esta tarea entrega la infraestructura genérica.
- No se construye UI ni endpoints HTTP, igual que tareas 5-9.
- No se modela un mecanismo de "volver a elaboración" desde `rechazado`: un informe rechazado queda cerrado (es_final) y una nueva ejecución se inicia desde cero, igual de simple que `rechazada`/`anulada` en `pago_proveedores`.

## Decisions

1. **Reportabilidad (cortes/períodos/snapshots) es core; informes_razonados es módulo funcional.** Igual que el harness distingue "Core no desactivable" (incluye "reportabilidad") de "módulos funcionales activables" (incluye "Informes razonados"), separamos ambas capas: `CorteReportabilidadService` no depende de ningún `DefinicionWorkflow` ni puede desactivarse; `InformeRazonadoService` sí depende de la `DefinicionWorkflow` "informes_razonados" y respeta su flag `activo` (mismo mecanismo de `TransicionWorkflowException::moduloInactivo()` que ya usa `pago_proveedores`).

2. **`ejecucion_informe_razonado` no tiene columna `estado` propia.** Su estado vive exclusivamente en su `Proceso` asociado (`morphOne` vía `sujeto`, idéntico a `CasoPagoProveedor::proceso()`), evitando el bug de doble fuente de verdad. `Proceso.cerrado_en` (ya gestionado automáticamente por `TransicionWorkflowService` al llegar a un estado `es_final`) cubre "cuándo terminó", sin necesidad de una columna `publicado_en` redundante.

3. **`aprobaciones_informe_razonado` es evidencia adicional, no el mecanismo de cambio de estado.** El servicio ejecuta `TransicionWorkflowService::execute($proceso, 'aprobar'|'rechazar', $comentario, user: $usuario)` y, en la misma transacción, crea el registro `AprobacionInformeRazonado` (decisión, usuario, momento) como evidencia más rica que el `HistorialTransicionWorkflow` genérico — igual de espíritu a como `EgresoCgu` es evidencia adicional junto al workflow de `pago_proveedores` en la tarea 8.

4. **Un informe solo puede iniciarse sobre un corte publicado.** `InformeRazonadoService::iniciarEjecucion()` valida `$corte->estado === 'publicado'` y lanza `CorteReportabilidadException::corteNoPublicado()` si no — encarna literalmente la regla "los informes nacen de cortes... publicados".

5. **`cortes_reportabilidad` es inmutable una vez publicado.** `CorteReportabilidadService::agregarItem()` y `capturarSnapshot()` rechazan operar sobre un corte con `estado = 'publicado'` (`CorteReportabilidadException::corteYaPublicado()`). Antes de publicar, el corte puede seguir recibiendo items/snapshots (etapa de armado); `publicarCorte()` es la única operación que lo congela, y exige el permiso `reportabilidad.publicar_corte` (chequeo directo, no vía workflow — es un toggle operacional simple, igual que `IntegracionExternaService::finalizarTrabajo()` en la tarea 9, no una decisión multi-etapa).

6. **`narrativas_informe_razonado` separa "quién la escribió" de "quién la aprobó".** `generado_por_ia` marca si el borrador fue redactado automáticamente; `revisado_por`/`revisado_en` registran la revisión humana del contenido en sí, **independiente** de la aprobación de workflow del informe completo (`aprobaciones_informe_razonado`). Esto refleja la regla rectora "la IA puede redactar, pero no aprueba" sin necesitar dos sistemas de workflow.

7. **`cortes_reportabilidad_items` usa vínculo polimórfico (`vinculable`), igual que `VinculoDocumento`/`ExternalDataSnapshot`.** Permite que un corte incluya cualquier entidad interna (casos, procesos, etc.) sin acoplarse a un módulo concreto.

## Risks / Trade-offs

- **[Riesgo] Dos niveles de "snapshot" en esta tarea (`snapshots_corte_reportabilidad` y `snapshots_informe_razonado`) pueden confundirse** → **Mitigación**: el primero es la evidencia cruda del corte (qué datos existían); el segundo es la evidencia del informe ya ensamblado (secciones+métricas+narrativas+gráficos) en el momento de publicarlo. Se documenta explícitamente la diferencia, igual que se documentó `snapshots_datos_externos` vs. `snapshots_sgf` en la tarea 9.
- **[Riesgo] Reutilizar `TransicionWorkflowService` para un dominio sin documentos obligatorios** (el método valida `documentos_requeridos`, que aquí siempre será `[]`) → **Mitigación**: el servicio ya soporta `documentos_requeridos` vacío sin cambios (`ResolutorValidacionDocumental` con lista vacía no genera faltantes); no se requiere ninguna modificación al servicio existente.
- **[Riesgo] `exportaciones_informe_razonado` sin generación real de archivos** podría parecer incompleto → **Mitigación**: explícitamente fuera de alcance (Non-Goals); el harness solo exige registrar la evidencia de exportación, no implementar un motor de renderizado en esta tarea.

## Migration Plan

Tablas nuevas, sin alterar ninguna migración existente. Orden de creación (respeta FKs):
1. `periodos_reportabilidad`
2. `cortes_reportabilidad` (FK a `periodos_reportabilidad`, `users`)
3. `cortes_reportabilidad_items` (FK a `cortes_reportabilidad`; columnas polimórficas `vinculable_type`/`vinculable_id`)
4. `snapshots_corte_reportabilidad` (FK a `cortes_reportabilidad`, `cortes_reportabilidad_items`)
5. `definiciones_informe_razonado`
6. `ejecuciones_informe_razonado` (FK a `definiciones_informe_razonado`, `cortes_reportabilidad`, `users`)
7. `secciones_informe_razonado` (FK a `ejecuciones_informe_razonado`)
8. `metricas_informe_razonado` (FK a `ejecuciones_informe_razonado`, `secciones_informe_razonado`)
9. `graficos_informe_razonado` (FK a `ejecuciones_informe_razonado`, `secciones_informe_razonado`)
10. `excepciones_informe_razonado` (FK a `ejecuciones_informe_razonado`; columnas polimórficas opcionales)
11. `narrativas_informe_razonado` (FK a `ejecuciones_informe_razonado`, `secciones_informe_razonado`, `users`)
12. `snapshots_informe_razonado` (FK a `ejecuciones_informe_razonado`)
13. `aprobaciones_informe_razonado` (FK a `ejecuciones_informe_razonado`, `users`)
14. `exportaciones_informe_razonado` (FK a `ejecuciones_informe_razonado`, `users`)

No hay datos previos que migrar (tablas nuevas). Rollback estándar: `down()` con `dropIfExists` en orden inverso.

## Open Questions

Ninguna pendiente — el alcance y la integración con el workflow existente quedaron decididos explícitamente antes de este design.

## Context

`RequisitoDocumental.modalidad_id` tiene FK dura hacia `modalidades_adquisicion` (tabla del dominio Adquisiciones). `ResolutorChecklistDocumentalProceso::requisitosAplicables()` (`app/Services/Documentos/ResolutorChecklistDocumentalProceso.php:82-100`) ya filtra dinámicamente por `modalidad_id` (`whereNull → universal, o exacto`), pero esa columna solo se popula para `Proceso` de Adquisiciones (`ProcesoAdquisicionService`); para Pago de Proveedores, `CasoPagoProveedorImporter::importarDesdeSnapshot()` nunca la setea — queda siempre `null`.

`CasoPagoProveedorController::cargarDetalle()` (`app/Http/Controllers/PagoProveedores/CasoPagoProveedorController.php:74-104`) invoca `ResolutorChecklistDocumentalProceso::resolve()` en cada `show()`/`verificarSgf()` — el checklist se recalcula por completo (borra y regenera items) en cada carga de la página, así que clasificar el tipo de proceso y recargar la página es suficiente para ver el checklist actualizado, sin ningún paso adicional de recálculo manual.

## Goals / Non-Goals

**Goals:**
- Clasificar cada caso de Pago de Proveedores con un tipo de proceso de pago (`COMPRA`, `CONTRATO`, `CONVENIO`, `REEMBOLSO`, `ANTICIPO`, `OTRO`).
- Que esa clasificación determine qué documentos son obligatorios/opcionales en el checklist, reutilizando el mecanismo de filtrado ya existente.
- Renombrar "Registro Contable CGU" a "Traspaso" en la UI.

**Non-Goals:**
- No se toca nada del dominio Adquisiciones (`modalidades_adquisicion`, `ModalidadAdquisicion`, sus requisitos).
- No se agrega un campo/tabla "Traspaso" separado — es el mismo `RegistroContableCgu` con otra etiqueta.
- No se construye todavía el indicador "listo para Egreso" ni el botón de avance (eso es el Bloque D, en un change posterior).

## Decisions

**1. Nueva tabla `tipos_proceso_pago` en vez de reutilizar `modalidades_adquisicion`.**
`modalidades_adquisicion` es un concepto del dominio Adquisiciones ("modalidad de adquisición": licitación pública, trato directo, etc.). "Tipo de proceso o de pago" (compra, contrato, convenio, reembolso, anticipo, otro) es un concepto distinto de Pago de Proveedores. Aunque técnicamente no habría colisión de datos (el filtro de `requisitosAplicables()` ya está acotado por `definicion_workflow_id`, así que una fila de `modalidades_adquisicion` reutilizada nunca se mezclaría entre dominios), el nombre de la tabla induciría a error de lectura futuro ("¿por qué Pago de Proveedores lee de `modalidades_adquisicion`?"). Alternativa descartada: agregar las 6 filas nuevas a `modalidades_adquisicion`. Se prefiere una tabla nueva, propiamente nombrada, mismo patrón exacto que `ModalidadAdquisicion` (id, código, nombre, activo).

**2. Nueva columna `procesos.tipo_proceso_pago_id`, paralela e independiente de `modalidad_id`, con FK real.**
`modalidad_id` en `procesos` no tiene FK real a nivel de base de datos (se agregó antes de que existiera la tabla `modalidades_adquisicion`). Para `tipo_proceso_pago_id` sí se declara la FK real desde el principio, porque `tipos_proceso_pago` se crea antes de alterar `procesos`. `ResolutorChecklistDocumentalProceso::requisitosAplicables()` gana un filtro adicional, análogo y estructuralmente independiente del de `modalidad_id`.

**3. Acción de clasificación: controlador dedicado de una sola acción, mismo patrón que `VinculoAdquisicionCasoPagoProveedorController`.**
`TipoProcesoPagoCasoPagoProveedorController::store()` autoriza con `Gate::authorize('clasificarTipoProcesoPago', $caso)` → nuevo método en `CasoPagoProveedorPolicy` que reutiliza el permiso `pago_proveedores.gestionar_caso` (ya lo tiene `administrativo_finanzas`; no se crea ningún permiso nuevo). Actualiza `$caso->proceso->update(['tipo_proceso_pago_id' => ...])` dentro de una transacción, con `AuditLogger`.

**4. Matriz de requisitos: filas universales se desactivan (`activo=false`), no se borran.**
`checklist_documental_proceso_items.requisito_documental_id` tiene FK `restrictOnDelete` — borrar una fila de `requisitos_documentales` ya referenciada por un checklist histórico fallaría. El seeder reescrito desactiva las filas universales que se reemplazan por variantes específicas por tipo, y crea las nuevas filas con `tipo_proceso_pago_id` set. Se evita así que un caso clasificado vea el mismo `tipo_documento_id` dos veces (fila universal + fila específica), que generaría ítems duplicados en el checklist.

**Matriz confirmada** (Factura y Comprobante de Pago permanecen universales/obligatorios, `tipo_proceso_pago_id = null`):

| Tipo | Orden de Compra | Contrato | Acta Recepción | Cert. Vigencia | Resolución |
|---|---|---|---|---|---|
| COMPRA | obligatorio | — | obligatorio | opcional | opcional |
| CONTRATO | — | obligatorio | obligatorio | obligatorio | opcional |
| CONVENIO | — | — | opcional | opcional | obligatorio |
| REEMBOLSO | — | — | — | — | opcional |
| ANTICIPO | — | — | — | — | obligatorio |
| OTRO | opcional | opcional | obligatorio | obligatorio | obligatorio |

**Corrección respecto al plan aprobado:** el plan original excluía FACTURA de ANTICIPO ("sin Factura"). Al implementar se detectó que esto entraría en conflicto con las transiciones `aprobar_finanzas`/`aprobar_zonal` (`WorkflowPagoProveedoresSeeder.php:113,116`), que ya exigen `FACTURA` de forma incondicional vía `documentos_requeridos` para **cualquier** caso, sin excepción por tipo. El resolutor de checklist tampoco distingue "no aplica" de "opcional" — su mecanismo es unión (universal U específico), no soporta exclusión. Mostrar FACTURA como "no obligatoria" en el checklist de un ANTICIPO habría sido engañoso: el caso igual quedaría bloqueado al aprobar sin ella. Se mantiene FACTURA universal para todos los tipos, incluido ANTICIPO.

## Risks / Trade-offs

- [Riesgo] La matriz propuesta es una primera aproximación razonable, no viene de una definición formal del equipo de Finanzas — podría necesitar ajustes. → Mitigación: vive enteramente en el seeder (`RequisitosDocumentalesPagoProveedoresSeeder`), fácil de modificar en un change posterior sin tocar código de aplicación.
- [Riesgo] Reclasificar el tipo de proceso de un caso que ya tiene documentos cargados podría dejar "huérfano" un documento que era obligatorio bajo la clasificación anterior. → Mitigación: fuera de alcance de este change — el checklist se recalcula igual (el documento sigue vinculado al `Proceso`, solo cambia si aparece o no en el checklist activo); no se pierde el documento ni el vínculo.

## Migration Plan

1. Migraciones aditivas (tabla nueva + 2 columnas nullable) — sin downtime.
2. Seeder reescrito: correr `php artisan db:seed --class=TiposProcesoPagoSeeder` y re-ejecutar `RequisitosDocumentalesPagoProveedoresSeeder` en entornos existentes.
3. Rollback: revertir el commit deja `tipo_proceso_pago_id` sin usarse (columna nullable, sin impacto); las filas de requisitos desactivadas pueden reactivarse manualmente si se revierte el seeder.

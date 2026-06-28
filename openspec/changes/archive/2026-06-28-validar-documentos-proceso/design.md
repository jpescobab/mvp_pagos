## Context

`ValidacionDocumento` (`estado`, `observacion`, `validado_por`, `validado_en`, sin `updated_at`: es un log inmutable de eventos) y `Documento::estadoVigente()` (último evento, o `'pendiente'` si no hay ninguno) ya existen desde la tarea 06 y están testeados (`tests/Feature/Documentos/ValidacionDocumentoTest.php`). El cambio `subir-vincular-documentos-proceso` agregó el primer punto de entrada HTTP del dominio documental (`DocumentoProcesoController`, `routes/documentos.php`); el cambio `sincronizar-checklist-con-documentos-subidos` ya hace que el checklist lea `estadoVigente()` en cada resolución. Falta el último eslabón: crear esos eventos.

## Goals / Non-Goals

**Goals:**
- Permitir validar o rechazar un documento vinculado a un proceso, con observación opcional (obligatoria al rechazar, para que quede registrado el motivo).
- Que el checklist refleje el nuevo estado en su siguiente apertura, sin tocar `ResolutorChecklistDocumentalProceso` (ya lo hace).

**Non-Goals:**
- No se restringe por tipo de documento ni módulo quién puede validar.
- No se bloquea ninguna transición de workflow basada en el resultado de la validación (eso ya lo gobierna `ResolutorValidacionDocumental` de forma independiente, comparando contra `documentos_requeridos` de la transición, no contra `validaciones_documento` directamente).
- No se permite editar ni eliminar un evento de validación ya creado (es un log inmutable, según la spec existente: "el estado vigente es el del evento más reciente", no se reescribe historia).

## Decisions

**D1 — Permiso dedicado `documentos.validar`, no reutilizar `documentos.gestionar`.**
Subir/desvincular y validar/rechazar son responsabilidades distintas (quien sube evidencia normalmente no debería autovalidarla — separación de funciones). Mismo patrón que `pago_proveedores.vincular_adquisicion` se separó de los permisos de ciclo de vida de Adquisiciones. Alternativa descartada: reutilizar `documentos.gestionar` — mezclaría dos controles de acceso con propósitos distintos.

**D2 — Ruta anidada bajo el documento, no bajo el proceso directamente.**
`POST /procesos/{proceso}/documentos/{documento}/validaciones`. Se mantiene `{proceso}` en la URL (consistente con el resto de `routes/documentos.php`) aunque la validación en sí solo depende del `Documento`; esto simplifica la autorización (reutiliza `ProcesoPolicy`) y mantiene una sola jerarquía de rutas para todo el dominio documental.

**D3 — Form Request exige `observacion` solo cuando `estado = 'rechazado'`.**
Regla condicional (`required_if:estado,rechazado`), igual de simple que `requiere_comentario` en las transiciones de workflow, pero implementada como validación de request, no como columna de configuración (no hay "tipos de validación" configurables, son solo dos valores fijos).

**D4 — No se agrega un nuevo método al servicio `GestorDocumentoProceso`.**
Crear una `ValidacionDocumento` es una operación de una sola línea (`$documento->validaciones()->create(...)`) sin transacción multi-tabla; no amerita un service nuevo. Se hace directo en el controlador, siguiendo el mismo criterio de "controladores livianos pero sin abstraer prematuramente" que ya se aplicó en otras acciones de un solo paso de este proyecto.

## Risks / Trade-offs

- [Riesgo] Sin restricción de separación de funciones real (cualquiera con `documentos.validar` puede validar cualquier documento, incluso el que subió él mismo) → Mitigación: fuera de alcance por ahora; el permiso ya es asignable de forma granular por rol si la institución decide separar roles de "quien sube" vs "quien valida".
- [Riesgo] Cambiar el resultado de un documento ya `valido` requiere un nuevo evento `rechazado` (no se puede "deshacer"), lo cual es intencional (inmutabilidad) pero puede sorprender a un usuario que espera "editar" la validación anterior → Mitigación: ya es el comportamiento documentado en la spec existente; el frontend mostrará siempre el estado vigente (el más reciente), no una lista plana confusa.

## Migration Plan

1. Agregar `documentos.validar` en `RolesAndPermissionsSeeder`.
2. Agregar `ProcesoPolicy::validarDocumentos`.
3. Crear Form Request + `ValidacionDocumentoController@store`.
4. Agregar la ruta en `routes/documentos.php`.
5. UI: botones Validar/Rechazar en ambos `show.tsx`.
6. Tests.

Sin cambios de esquema, sin rollback especial.

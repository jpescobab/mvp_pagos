## Context

`show.tsx` ya tiene toda la lógica de subida en `subirDocumento()` (líneas 223-254), que lee `tipoDocumentoId`/`archivo` desde el estado del componente y postea a `documentos.store({ proceso: caso.proceso.id })`. El checklist (`caso.proceso.checklist.items`, líneas 653-687) solo expone `tipo_documento` (nombre), `tipo_requisito`, `estado_cumplimiento` y `documento_id` — no el `tipo_documento_id`, que es lo que el `<Select>` de subida necesita para preseleccionar el tipo correcto.

## Goals / Non-Goals

**Goals:**
- Un ítem `pendiente` del checklist (sin `documento_id`) con `documentos.gestionar` permite elegir archivo y subirlo de inmediato, sin bajar a la sección "Documentos" ni elegir el tipo manualmente.
- Reutilizar la lógica de subida existente (`subirDocumento`) en vez de duplicarla — un solo camino de código para "crear un `Documento` + `VersionDocumento` + `VinculoDocumento`".
- Quitar de esta pantalla el volcado JSON crudo de snapshots SGF y el texto de `sgf_status`, sin tocar los datos en base ni el endpoint que ya los expone (`caso.snapshots_sgf` sigue en el prop Inertia, solo deja de renderizarse).

**Non-Goals:**
- No se cambia el endpoint de subida (`procesos.documentos.store`) ni su Form Request.
- No se agrega drag-and-drop ni preview de archivo — mismo `<input type="file">` nativo que ya usa el resto de la página.
- No se toca la sección "Documentos" existente (donde se ve el historial completo, se valida/rechaza y se sube nueva versión) — sigue siendo la vista completa; el checklist solo gana un atajo para el caso más común (subir lo que falta).

## Decisions

**1. `tipo_documento_id` se agrega al ítem del checklist en el backend (`ProcesoResource::toArray()`), no se infiere en el frontend por nombre.**
El nombre (`tipo_documento`) no es un identificador estable para hacer matching contra `tiposDocumento` en el frontend (podría haber nombres duplicados o cambiar de redacción). El modelo `ChecklistDocumentalProcesoItem` ya tiene `tipo_documento_id` como columna — exponerlo es un cambio de una línea.

**2. `subirDocumento()` se generaliza para aceptar un `tipoDocumentoId`/`archivo` explícitos, en vez de leer siempre del estado del formulario principal.**
Se cambia la firma a `subirDocumento(tipoDocumentoId: string, archivo: File)`, y el formulario principal de la sección "Documentos" le pasa su propio estado (`tipoDocumentoId`, `archivo`) al hacer clic en "Subir". El atajo del checklist le pasa el `tipo_documento_id` del ítem (convertido a string) y el archivo elegido en su propio `<input type="file">` inline, disparando la subida apenas se selecciona el archivo (sin paso de confirmación extra, ya que el tipo ya está determinado por el ítem). Alternativa descartada: duplicar la función de subida para el checklist — dos caminos de código para la misma operación es más difícil de mantener y más propenso a divergir.

**3. El estado de carga/error (`subiendoDocumento`, `errorDocumento`) se comparte entre el formulario principal y los atajos del checklist.**
Ambos caminos terminan en el mismo POST; no hay necesidad de estados independientes. Un error de subida disparado desde el checklist se muestra igual que uno disparado desde la sección "Documentos" (ya visible en la página).

**4. La sección "Historial de snapshots SGF" se elimina completa (listado + JSON), pero el botón "Verificar en SGF" se conserva en su lugar actual (bajo el mismo encabezado) o se reubica junto a la cabecera del caso.**
Se conserva porque es accionable (dispara `casos.verificarSgf`), a diferencia del listado/dump que es solo lectura. Se decide reubicarlo en la cabecera del caso, junto al `EstadoBadge`, ya que es la acción de verificación relacionada con el estado mostrado ahí — evita dejar un encabezado de sección con un solo botón y nada más debajo.

## Risks / Trade-offs

- [Riesgo] Si dos ítems del checklist apuntan al mismo `tipo_documento_id` (no debería pasar según el seeder de requisitos, que es 1 requisito = 1 tipo), el atajo de ambos subiría al mismo tipo. → Mitigación: no aplica hoy (`RequisitosDocumentalesPagoProveedoresSeeder` no genera duplicados); si se necesitara en el futuro, es un cambio de datos, no de este componente.
- [Riesgo] Quitar el volcado JSON crudo reduce la capacidad de debugging manual desde la UI para quien no tiene acceso a la base de datos. → Mitigación: el dato sigue disponible vía `mcp__laravel-boost__database-query` / acceso directo a `snapshots_datos_externos`; esta pantalla es para operar el caso, no para depurar integraciones.

## Migration Plan

1. Sin migración de esquema.
2. Cambio de solo backend-resource + frontend — deploy normal, sin downtime.
3. Rollback: revertir el commit deja el checklist de solo lectura y la sección de snapshots visible de nuevo, sin pérdida de datos.

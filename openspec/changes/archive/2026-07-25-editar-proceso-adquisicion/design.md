## Context

`ProcesoAdquisicionService` solo sabe `crear()`. El `ProcesoAdquisicion` y su `Proceso` de workflow se crean juntos, copiando `modalidad_id`/`monto` al `Proceso`. El checklist documental se resuelve en `ResolutorChecklistDocumentalProceso`, que lee `modalidad_id`/`monto` **desde el `Proceso`** (no desde el sujeto). El módulo ya tiene control de acceso por permiso (`consultar_proceso`/`crear_proceso`), policy registrada y un rol operativo `administrativo_adquisiciones`. Falta la operación de edición.

## Goals / Non-Goals

**Goals:**
- Poder corregir un proceso de adquisición mientras está en `borrador`.
- Mantener consistente el checklist al cambiar modalidad/monto (sincronizando el `Proceso`).
- Reutilizar el patrón de permisos y controladores livianos ya establecido.

**Non-Goals:**
- Editar en estados posteriores a `borrador` (para eso existe `devolver_a_borrador`).
- Tocar el workflow, sus transiciones o el estado del proceso.
- Visibilidad bidireccional con Mercado Público (gap #3, separado).
- Auditoría específica de la edición (fuera de alcance; `crear()` tampoco audita hoy).

## Decisions

- **Editable solo en `borrador`.** Una vez en revisión o más allá, los datos son parte del registro/evidencia y cambiar modalidad/monto alteraría en silencio el checklist y el sentido del historial. La corrección posterior pasa por `devolver_a_borrador` (transición ya existente). Alternativa descartada: permitir editar en `en_revision` — abriría la puerta a mutar un proceso ya en curso de revisión.
- **La invariante de estado vive en el Service.** `ProcesoAdquisicionService::actualizar()` lanza `ProcesoAdquisicionException` si el `Proceso` no está en `borrador` (defensa en profundidad, independiente de la UI). La UI decide mostrar el acceso a editar según `estado_actual.codigo === 'borrador'` + `auth.permissions`. Alternativa descartada: poner la regla de estado en la Policy `update` — mezcla autorización (permiso) con invariante de dominio (estado); se prefiere Policy = permiso, Service = invariante.
- **Sincronizar el `Proceso` en la misma transacción.** `actualizar()` actualiza el `ProcesoAdquisicion` y setea `modalidad_id`/`monto` en el `Proceso` asociado. Sin esto, el checklist (que lee del `Proceso`) quedaría resuelto con la modalidad/monto viejos. No se fuerza la re-resolución: el `show` ya llama al resolutor y leerá los valores nuevos.
- **Permiso propio `adquisiciones.editar_proceso`.** Consistente con la separación consultar/crear ya introducida; permite un rol que cree pero no edite, o viceversa, sin acoplarlos. Asignado a `admin` y `administrativo_adquisiciones`.
- **`codigo` unique ignorando el propio registro.** `ActualizarProcesoAdquisicionRequest` replica las reglas de creación pero con `Rule::unique('procesos_adquisicion','codigo')->ignore($proceso)`, para poder guardar sin cambiar el código.
- **Controlador liviano.** `edit` arma el mismo payload que `create` (proceso + modalidades activas + ccostos + proveedores); `update` solo autoriza y delega en `actualizar()`. Toda la lógica (validación de modalidad, transacción, sync del `Proceso`, invariante de estado) queda en el Service.

## Risks / Trade-offs

- **[Cambiar modalidad/monto deja ítems de checklist previos huérfanos]** → El resolutor hace `updateOrCreate` del checklist y `items()->delete()` + recrea en cada resolución, así que la próxima apertura del detalle reconstruye el checklist coherente con la nueva modalidad. Riesgo acotado además a `borrador`, donde el checklist aún no gobierna transiciones.
- **[Un documento ya subido para un requisito que deja de aplicar tras cambiar la modalidad]** → El documento sigue vinculado al `Proceso` pero no aparecerá como ítem del checklist si su tipo ya no es requerido; no se borra evidencia (coherente con "no romper trazabilidad"). Aceptable en `borrador`.
- **[UI y Service podrían discrepar sobre "editable"]** → Mitigación: el Service es la fuente de verdad (rechaza fuera de `borrador`); la UI solo oculta el acceso por conveniencia. Un intento forzado fuera de `borrador` recibe error del backend.

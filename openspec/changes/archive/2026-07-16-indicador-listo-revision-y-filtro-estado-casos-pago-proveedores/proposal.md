## Why

En el listado de casos de Pago de Proveedores (`/pago-proveedores/casos`) el usuario no tiene forma de saber, sin abrir cada caso, cuáles ya cumplen los criterios de aprobación (checklist documental con todos los ítems obligatorios aprobados y totales verificados) ni de acotar la vista a los casos que todavía requieren su atención. Hoy el listado no soporta ningún filtro y siempre muestra los 14 estados de workflow mezclados, incluidos los que ya avanzaron a registro/pago/cierre. Esto obliga a abrir caso por caso para confirmar si está listo para su aprobación, y satura la vista con casos que ya no requieren revisión.

## What Changes

- Agregar al listado un indicador visual adicional ("Listo para revisar") junto al `EstadoBadge` de workflow, visible solo para casos cuyo `Proceso` está en `en_revision_finanzas` o `en_revision_zonal` y que cumplen, para la instancia correspondiente a su estado actual, el mismo criterio que ya usa `RevisionEgresoService::pagoListoParaAprobar()`: todos los documentos del checklist obligatorio aprobados (`ValidacionDocumentoInstanciaService::todosAprobados()`) y totales verificados (`RevisionPagoInstancia.totales_verificados`). Este indicador es puramente informativo: **no** dispara ni crea ninguna transición de workflow — el revisor sigue aprobando manualmente desde Revisión de Pagos, tal como hoy.
- Agregar un filtro de estado al listado (querystring, ej. `?estado=en_revision_finanzas` o `?estado=todos`), construido a partir de los códigos reales de `EstadoWorkflow` del workflow `pago_proveedores` (no hardcodeados en el frontend). Por defecto, sin que el usuario haya tocado el filtro, el listado excluye los estados avanzados/finales (`lista_para_registro_cgu`, `registrada_en_cgu`, `lista_para_pago`, `pagada_bancoestado`, `asociada_a_egreso_cgu`, `cerrada`, `rechazada`, `anulada`) y muestra el resto (`importada_desde_sgf`, `recibida_finanzas`, `en_revision_finanzas`, `en_revision_zonal`, `observada`, `subsanada`). El usuario puede cambiar el filtro para ver otros estados o todos.
- El controlador de listado (`CasoPagoProveedorController::index()`) pasa a leer el filtro desde el `Request`, aplicar el `whereHas` correspondiente sobre `proceso.estadoActual.codigo`, y preservar la paginación existente.

## Capabilities

### New Capabilities

(ninguna — este cambio extiende una página existente, no introduce un dominio nuevo)

### Modified Capabilities

- `paginas-pago-proveedores`: el requirement de listado de casos deja de decir "sin filtros ni búsqueda no soportados por el backend" — pasa a soportar un filtro de estado con valor por defecto que excluye estados avanzados/finales, y a mostrar el indicador "Listo para revisar" cuando corresponda.

## Impact

- **Backend**: `app/Http/Controllers/PagoProveedores/CasoPagoProveedorController.php` (leer filtro, aplicar `whereHas`), `app/Http/Resources/.../CasoPagoProveedorResource.php` (exponer el campo calculado `listo_para_aprobar` reutilizando `RevisionEgresoService::pagoListoParaAprobar()`, sin duplicar lógica). Ningún cambio a `TransicionWorkflowService`, a las tablas de workflow, ni a `RevisionPagoInstancia`.
- **Frontend**: `resources/js/pages/pago-proveedores/casos/index.tsx` (select de filtro de estado + badge adicional), posible componente nuevo reutilizable para el badge "Listo para revisar".
- **Specs**: delta sobre `openspec/specs/paginas-pago-proveedores/spec.md`.

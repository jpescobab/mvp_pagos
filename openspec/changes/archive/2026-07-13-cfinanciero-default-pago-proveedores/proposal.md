## Why

El guardrail de jurisdicción de `RevisionEgresoService::jurisdiccionDeterminable()` bloquea la aprobación de un pago desde la instancia Finanzas cuando `CasoPagoProveedor::cfinancieroId()` retorna `null`, y ese método hoy solo deriva el cfinanciero desde `caso->procesoAdquisicion->ccosto->cfinanciero_id`. En el entorno actual la tabla `procesos_adquisicion` está vacía y los 24 `casos_pago_proveedor` existentes tienen `proceso_adquisicion_id` en `null`, dejando bloqueado el 100% de los 17 casos en `en_revision_finanzas`. El módulo Adquisiciones no está siendo usado en paralelo a Pago de Proveedores todavía, así que exigir el vínculo manual como única fuente de cfinanciero deja la cola de revisión de Finanzas inoperable desde el primer caso.

## What Changes

- Agregar un archivo de parámetros (`config/pago-proveedores.php`) con una entrada `cfinanciero_id_default` que apunte al `cfinanciero` código `1400` ("Administracion Zonal") mediante variable de entorno con ese valor como default.
- `CasoPagoProveedor::cfinancieroId()` SHALL seguir priorizando el vínculo real (`procesoAdquisicion->ccosto->cfinanciero_id`) cuando exista, y SHALL caer al `cfinanciero_id_default` configurado solo cuando no haya `proceso_adquisicion` vinculado.
- El default configurado SHALL resolverse contra un `cfinanciero` real y activo (falla explícita en boot/validación si el código configurado no existe), para no introducir un id inventado.
- El guardrail de jurisdicción zonal (`mismaJurisdiccion()`) sigue funcionando igual, ahora recibiendo el cfinanciero por defecto en vez de `null` cuando no hay vínculo — su semántica de comparación de jurisdicción no cambia.

## Capabilities

### New Capabilities

(ninguna — este cambio modifica el comportamiento de resolución de cfinanciero dentro de una capability existente)

### Modified Capabilities

- `pago-proveedores-sgf`: se agrega una regla de resolución de cfinanciero por defecto, configurable, usada por el guardrail de jurisdicción cuando un `caso_pago_proveedor` no tiene `proceso_adquisicion` vinculado.

## Impact

- `app/Models/CasoPagoProveedor.php` (`cfinancieroId()`)
- `app/Services/PagoProveedores/RevisionEgresoService.php` (`jurisdiccionDeterminable()`, `mismaJurisdiccion()` — verificar que no dupliquen la resolución del default)
- Nuevo `config/pago-proveedores.php`
- `.env.example` / `.env` (nueva variable, ej. `PAGO_PROVEEDORES_CFINANCIERO_DEFAULT_CODIGO=1400`)
- Tests existentes de `tests/Feature/PagoProveedores/RevisionPagosTest.php` (casos que hoy esperan bloqueo por `cfinanciero` no determinable dejan de aplicar tal cual y necesitan un escenario nuevo sin vínculo pero con default activo, más un escenario con el default deliberadamente mal configurado)

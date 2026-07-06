## Context

`Cfinanciero` (`jurisdiccion_id` FK requerida, `codigo` unique, `nombre`, `activo`) y `Ccosto` (`cfinanciero_id` FK requerida, `codigo` unique, `nombre`, `cod_edificio` nullable, `activo`) son los dos niveles inferiores de la jerarquía institucional fija. A diferencia de `Item`/`Proveedor`/`ClienteMedidor`, **ninguno de los dos usa `SoftDeletes`** — no tienen columna `deleted_at`. Sus controladores hoy solo tienen `index()`, ya gateado con `Gate::authorize('viewAny', ...)` (a diferencia de Proveedor/Item/ClienteMedidor, cuyo `index()` es abierto a cualquier usuario autenticado — Cfinanciero/Ccosto están gateados desde siempre porque forman parte de la jerarquía que solo administra quien tiene `core_institucional.administrar`, igual que el resto de `estructuraInstitucionalNavItems` en el sidebar).

`ccostos` es referenciado por `clientes_medidores` (`restrictOnDelete`), `procesos_adquisicion` (restrict por defecto) y `funcionarios` (`nullOnDelete`). `cfinancieros` es referenciado por `ccostos` (`restrictOnDelete`) y `funcionarios` (`nullOnDelete`). El requirement ya existente "Mantener códigos institucionales únicos" en `core-institucional-capj` ya declara el escenario "No se puede eliminar un nivel de la jerarquía con hijos asociados" — hoy eso solo se cumple porque Postgres rechaza el `DELETE` por la restricción de FK, no porque la aplicación lo verifique de antemano.

## Goals / Non-Goals

**Goals:**
- CRUD completo (alta, edición, eliminación) de `Cfinanciero` y `Ccosto`.
- `destroy` verifica relaciones dependientes ANTES de intentar el `DELETE`, para dar un mensaje de error entendible (mismo patrón que `ItemController::destroy`) en vez de dejar pasar la excepción SQL de violación de FK.
- Reutilizar `core_institucional.administrar`.

**Non-Goals:**
- No se agrega `SoftDeletes` a ninguna de las dos tablas — es un cambio de esquema no pedido; se preserva el hard delete que ya implica la ausencia de `deleted_at`.
- No se construye CRUD para `Institucion` ni `Jurisdiccion` (los dos niveles superiores) — no tienen controlador, ruta ni UI hoy, y no fueron parte de lo acordado con el usuario.
- No se cambia la jerarquía fija en sí (`instituciones -> jurisdicciones -> cfinancieros -> ccostos`).

## Decisions

1. **Hard delete, no soft delete.** Ninguna de las dos tablas tiene `deleted_at`; agregarlo sería una migración de esquema no solicitada. El `destroy` hace `$modelo->delete()` real, protegido por la verificación de dependientes.
2. **`destroy` de `Ccosto` verifica `clientes_medidores`, `procesos_adquisicion` (bloquean) — no verifica `funcionarios`** porque esa FK usa `nullOnDelete` (se pone en null automáticamente, no bloquea ni requiere chequeo previo). Mismo criterio para `destroy` de `Cfinanciero`: verifica `ccostos` (bloquea), no verifica `funcionarios` (se anula solo).
3. **`create()`/`edit()` cargan el catálogo del nivel padre completo** (`Jurisdiccion::all()` para Cfinanciero, `Cfinanciero::all()` para Ccosto) mapeado a `{id, codigo, nombre}`, mismo patrón ya usado en `ProcesoAdquisicionController::create()` y en el change de Clientes Medidores — sin paginar ni filtrar por activo, porque el volumen de jurisdicciones/centros financieros es bajo (20 y ~6-30 respectivamente) y la UI necesita poder ver todas las opciones para asignar correctamente.
4. **`CfinancieroPolicy`/`CcostoPolicy` ya tienen `viewAny`; se agregan `view`/`create`/`update`/`delete`** sin tocar `viewAny` (el `index()` actual sigue intacto).

## Risks / Trade-offs

- **[Riesgo]** Sin soft delete, un `Ccosto`/`Cfinanciero` eliminado por error no se puede recuperar como sí ocurre con `Item`/`Proveedor`/`ClienteMedidor`. → **Mitigación**: es el comportamiento ya definido por el esquema existente (ninguna migración de este change lo cambia); la verificación de dependientes reduce el riesgo de borrados accidentales con impacto real, pero no lo elimina — es una decisión de diseño ya tomada antes de este change, no algo que se introduce ahora.
- **[Riesgo]** La verificación de dependientes en `destroy` duplica lo que la base de datos ya garantiza vía `restrictOnDelete` — si en el futuro se agrega una tabla nueva que referencia `ccostos`/`cfinancieros` sin actualizar `relacionQueImpideEliminar`, la app dejaría pasar una excepción SQL cruda en ese caso puntual (como pasaba antes de este change para todos los casos). → **Mitigación**: mismo trade-off aceptado ya en `ItemController`/`ProveedorController`; no es nuevo de este change.

## Migration Plan

Sin migraciones de base de datos. Revertir el commit es suficiente si algo sale mal.

## Open Questions

Ninguna bloqueante para implementar.

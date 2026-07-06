## Context

`ClienteMedidor` (`numero_cliente` unique, `proveedor_id` nullable FK a `proveedores`, `ccosto_id` FK requerida a `ccostos`, `tipo_suministro` string, `direccion_suministro` nullable, `activo`, soft deletes) hoy solo tiene `index()` — mismo estado en el que estaba `Item` antes de su change de CRUD. `ClienteMedidorResource` ya expone el shape correcto (incluyendo `proveedor`/`ccosto` anidados). El patrón de selects para formularios con catálogos FK ya existe en `ProcesoAdquisicionController::create()` (`Ccosto::all()`/`Proveedor::all()` mapeados a `{id, codigo/nombre, nombre}`) y su contraparte en `resources/js/pages/adquisiciones/procesos/crear.tsx` (`<Select>` + sentinela `SIN_PROVEEDOR` para el FK opcional).

## Goals / Non-Goals

**Goals:**
- CRUD completo (alta, edición, eliminación) de `ClienteMedidor`, con selects de `Ccosto` (requerido) y `Proveedor` (opcional).
- Reemplazar el placeholder "Disponible próximamente" de `ClienteMedidorActionsMenu` por acciones reales.
- Reutilizar `core_institucional.administrar`.

**Non-Goals:**
- No se modifica el esquema de `clientes_medidores`.
- No se introduce un enum para `tipo_suministro` — no existe hoy y no es parte de este change; se valida como string libre.
- No se conecta con el módulo funcional Consumo Eléctrico (sigue sin código, per CLAUDE.md/HARNESS_IA.md).

## Decisions

1. **`ClienteMedidorController::index()` no se toca.** Ya filtra, pagina y ordena correctamente; el change solo agrega los métodos de mutación.
2. **`create()`/`edit()` cargan `Ccosto::all()` y `Proveedor::all()` mapeados a listas simples**, calcando exactamente `ProcesoAdquisicionController::create()` — no se introduce un nuevo mecanismo de catálogos.
3. **`proveedor_id` opcional usa el mismo patrón de sentinela `SIN_PROVEEDOR`** ya usado en `procesos/crear.tsx`, en vez de permitir un valor vacío directo en el `<Select>` (Radix Select no soporta un `SelectItem` con `value=""`).
4. **Sin verificación de relaciones dependientes en `destroy`.** A diferencia de `Item` (que sí bloquea si tiene asignaciones/catálogos), nada en el código referencia `ClienteMedidor` desde otra tabla hoy (`grep` confirma que solo `Proveedor` lo referencia, y en sentido contrario — para bloquear el borrado del proveedor). Mismo caso que `Asignacion`/`Catalogo`: no se inventa una verificación que no aplica.
5. **`ClienteMedidorPolicy` calcada de `ItemPolicy`/`ProveedorPolicy`**, mismo permiso `core_institucional.administrar`.

## Risks / Trade-offs

- **[Riesgo]** Si en el futuro `tipo_suministro` necesita restringirse a valores fijos (ej. al conectar con Consumo Eléctrico), este change no lo anticipa — se trata como string libre porque no existe un enum definido y no corresponde inventarlo ahora sin ese contexto.
- **[Riesgo]** Ningún riesgo de integridad nuevo: `destroy` sin bloqueo es seguro hoy porque nada depende de `ClienteMedidor`; si eso cambia (ej. Consumo Eléctrico empieza a referenciarlo), ese change futuro deberá agregar la verificación correspondiente.

## Migration Plan

Sin migraciones de base de datos. Revertir el commit es suficiente si algo sale mal.

## Open Questions

Ninguna bloqueante para implementar.

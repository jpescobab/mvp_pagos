## Context

`api-adquisiciones` ya expone 5 rutas con sus Resources, pero ningún componente `.tsx` existe todavía. `paginas-pago-proveedores` ya validó el patrón completo de construir estas páginas sobre la fundación visual `fundacion-visual-layout` (tema, branding, sidebar tipo riel de íconos) y dejó componentes reutilizables (`EstadoBadge`) y convenciones (tipos a mano espejando Resources, `router.post` en vez de `useForm` para evitar condición de carrera, `Dialog` para comentario requerido). Este change replica ese patrón sin reinventarlo.

## Goals / Non-Goals

**Goals:**
- Las 3 páginas (`procesos/index`, `procesos/show`, `procesos/crear`) renderizan con datos reales de los Resources ya existentes, sin inventar campos ni lógica de negocio nueva.
- El detalle del proceso (`procesos/show`) permite ejecutar transiciones de workflow desde la UI, delegando 100% en el endpoint genérico ya construido.
- El checklist documental se renderiza dinámicamente desde lo que el backend entrega, nunca hardcodeado.
- El formulario de creación (`procesos/crear`) usa selects reales para modalidad/ccosto/proveedor — a diferencia del selector de casos de `egresos-cgu/crear` (lista con checkbox, porque cada caso necesitaba un monto independiente), aquí cada campo es una única selección simple, por lo que el primitive `Select` de shadcn/ui es la herramienta correcta (no hay precedente de usarlo todavía en el proyecto).

**Non-Goals:**
- No se agregan filtros, búsqueda ni ordenamiento en el listado — mismo criterio que `paginas-pago-proveedores`.
- No se oculta en el frontend ninguna transición por falta de permiso del usuario actual — mismo criterio.
- No se implementa edición ni eliminación de un proceso — el dominio (`ProcesoAdquisicionService`) no define esas operaciones.
- No se construye una página de detalle separada para egresos ni entidades relacionadas — Adquisiciones, a diferencia de Pago de Proveedores, no tiene una segunda entidad como `egresos_cgu` en este alcance.

## Decisions

1. **Sin brechas que cerrar en `api-adquisiciones`.** A diferencia de `paginas-pago-proveedores` (que tuvo que agregar `checklist` a `ProcesoResource` y la lista de `casos` a `EgresoCguController::create()`), `api-adquisiciones` ya se diseñó conociendo estas páginas de antemano: `ProcesoAdquisicionController::show()` ya eager-carga `proceso.checklist.items` y `ProcesoResource` (reutilizado) ya lo expone desde la corrección hecha en la tarea anterior; `ProcesoAdquisicionController::create()` ya entrega `modalidades`/`ccostos`/`proveedores`. No se modifica ningún archivo PHP en este change.

2. **Tipos en `adquisiciones.ts` reutilizan los genéricos ya definidos en `pago-proveedores.ts`** (`Proceso`, `EstadoWorkflow`, `TransicionWorkflow`, `HistorialTransicion`, `ChecklistItem`, `Paginated`) en vez de duplicarlos — esos tipos nunca fueron específicos de pago-proveedores (reflejan `ProcesoResource`, que es genérico). Solo se define un tipo nuevo, `ProcesoAdquisicion`, que sí es específico de este Resource.

3. **Selects de modalidad/ccosto/proveedor con el primitive `Select` de shadcn/ui** (`Select`/`SelectTrigger`/`SelectValue`/`SelectContent`/`SelectItem`), controlado con `useState` y `onValueChange`, no un `<select>` nativo — consistente con que el proyecto ya tiene este primitive instalado pero sin usar todavía; usarlo aquí es la primera vez, y evita reinventar estilos de un `<select>` nativo dentro del tema ya aplicado.

4. **Detalle (`procesos/show`) replica `casos/show.tsx` línea por línea en su lógica de transición** (`router.post` con `onError`/`onFinish`, `Dialog` condicional por `requiere_comentario`), cambiando solo los campos de cabecera mostrados (código/modalidad/ccosto/proveedor/monto en vez de sgf_id/proveedor/sgf_status). Mismo criterio de "no reinventar" que ya se siguió al construir `egresos-cgu/crear.tsx` reutilizando el patrón de `casos/show.tsx`.

5. **Navegación: un solo ítem "Procesos" en el grupo "Adquisiciones"**, a diferencia de "Pago de Proveedores" que tiene dos (Casos, Egresos CGU) — Adquisiciones en este alcance solo tiene una entidad navegable. El prop `label` de `NavMain` ya existe desde `paginas-pago-proveedores`, no requiere cambios.

## Risks / Trade-offs

- **[Riesgo] Primer uso del primitive `Select` de shadcn/ui en el proyecto** → **Mitigación**: es un componente ya instalado (`components.json`), solo no usado todavía; se sigue su API estándar de Radix sin wrappers adicionales.
- **[Riesgo] `checklist: null` cuando el proceso no tiene checklist generado todavía** → **Mitigación**: mismo manejo de estado vacío explícito que `casos/show.tsx`.

## Migration Plan

Sin migraciones de base de datos ni cambios de código PHP. Requiere que Wayfinder genere `resources/js/routes/adquisiciones/...` (automático en `npm run dev`/`build`) antes de que las páginas puedan importar las funciones de ruta tipadas.

## Open Questions

Ninguna pendiente.

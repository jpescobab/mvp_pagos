## Context

`tema-visual-layout` ya define el requirement "Listados tabulares densos" y `HARNESS_IA.md` ya señala `cfinancieros/index.tsx`/`ccostos/index.tsx` como implementación de referencia. Este change no agrega ni cambia requisitos: corrige dos implementaciones que quedaron desalineadas — Clientes Medidores nunca se construyó con el patrón denso (es anterior a su formalización) y Usuarios tiene funcionalidad rica (filtros, orden, paginación con selector) pero su tabla no adoptó la densidad visual ni los tokens de color.

## Goals / Non-Goals

**Goals:**
- Que ambas vistas cumplan el requirement "Listados tabulares densos" sin excepción (fixed layout, identidad visual, tokens semánticos, truncado, ocultamiento progresivo, acciones en dropdown).
- Agregar búsqueda y paginación a Clientes Medidores, que hoy carga todo sin filtro (patrón ya usado en Proveedores/Centros Financieros/Costos).

**Non-Goals:**
- No se toca la funcionalidad de Usuarios (filtros, orden, selector de por-página, diálogos de confirmación, vista de tarjetas en mobile) — solo su tabla de escritorio se ajusta a la densidad y tokens de color.
- No se agrega CRUD a Clientes Medidores; el menú de acciones queda con "Ver detalle" deshabilitado y tooltip "Disponible próximamente", igual que Proveedores hoy.

## Decisions

1. **Clientes Medidores pasa de `.get()` a `.paginate(20)->withQueryString()` con búsqueda por `q`.**
   Búsqueda por `numero_cliente` y, vía `whereHas`, por nombre del proveedor o código/nombre del centro de costo — son los campos que un usuario reconocería al buscar un cliente medidor. Mismo patrón `when($q !== '', ...)` que `ProveedorController`.

2. **`UsersTable` se edita in-place, no se reescribe.**
   La tabla de escritorio (`<table>` dentro del bloque `hidden ... md:block`) se ajusta a `table-fixed` con anchos porcentuales, se agrega `Avatar`/`AvatarFallback` con `useInitials` junto al nombre, `truncate` + `title` en las columnas secundarias, y se reduce el padding de celda a `px-2.5 py-1` (de `px-4 py-2`). La vista de tarjetas para mobile (`md:hidden`) no se toca porque no es una tabla y no aplica la convención de columnas.

3. **`user-status-badge.tsx` reemplaza los colores crudos por los tokens `success`/`danger` del tema, sin cambiar el nombre ni la firma del componente.**
   `bg-green-600/15 text-green-700 dark:bg-green-400/10 dark:text-green-400` → `bg-success-soft text-success`; `variant="secondary"` → `border-transparent bg-danger-soft text-destructive`, igual que `ProveedorStatusBadge`/`CfinancieroStatusBadge`/`CcostoStatusBadge`.

4. **`cliente-medidor-actions-menu.tsx` sigue el patrón placeholder de `proveedor-actions-menu.tsx`** (todas las acciones deshabilitadas con "Disponible próximamente"), porque no existe todavía ningún endpoint de detalle/edición para clientes medidores — inventar uno estaría fuera del alcance de esta corrección visual.

## Risks / Trade-offs

- [Riesgo] Agregar paginación a Clientes Medidores es un cambio de comportamiento (antes se veían todos los registros en una sola carga) → Mitigación: el volumen de datos real es pequeño (catálogo institucional), y el mismo patrón ya es el estándar en Proveedores/Centros Financieros/Costos; se documenta en el proposal y se cubre con tests nuevos.
- [Riesgo] Tocar `UsersTable` sin tests previos de su render visual podría introducir una regresión silenciosa en columnas ya usadas por otros flujos (ej. el diálogo de contraseña temporal no depende de la tabla, pero el orden/paginación sí) → Mitigación: los tests de Feature existentes de Usuarios (filtros, orden, paginación) no dependen del markup de la tabla, así que deberían seguir pasando sin cambios; se re-ejecuta toda la suite de `tests/Feature/Seguridad/` para confirmar.

## Migration Plan

Sin migraciones de base de datos. Cambio de controlador + frontend; rollback trivial revirtiendo el commit.

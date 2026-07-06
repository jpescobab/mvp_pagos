## Context

`app/Models/Item.php` y su migración (`create_items_table`) ya existen: `codigo` (unique), `nombre`, `descripcion` (nullable), `activo` (boolean, default true), soft deletes, y relaciones `hasMany` a `Asignacion`/`Catalogo` (esas dos tablas también ya existen — `create_asignaciones_table`, `create_catalogos_table` — pero su propio CRUD queda fuera de este change). Hoy `Item` no tiene controlador, policy, rutas, páginas ni ítem de sidebar: es un modelo sin ninguna forma de administrarlo.

En `Maestros`, hoy conviven dos patrones de CRUD:
- **Solo lectura** (`CcostoController`, `CfinancieroController`, `ClienteMedidorController`): únicamente `index()`, sin `Gate::authorize` de mutación (`CcostoController` sí llama `Gate::authorize('viewAny', ...)`).
- **CRUD completo** (`ProveedorController`, único caso hoy): `index/create/store/show/edit/update/destroy`, cada mutación con `Gate::authorize(...)`, Policy propia (`ProveedorPolicy`) y Form Requests con `authorize()` redundante al mismo permiso.

`Item` necesita el patrón completo (se puede crear, editar, desactivar y eliminar), así que sigue a `ProveedorController` como referencia — pero con un formulario plano de una sola sección (no el wizard multi-paso de Proveedor), porque solo tiene 3 campos editables.

## Goals / Non-Goals

**Goals:**
- CRUD completo (alta, edición, ver, baja) sobre el modelo `Item` ya existente, sin tocar su esquema.
- Listado en `resources/js/pages/maestros/items/index.tsx` siguiendo el patrón de "Listados tabulares densos" (`openspec/specs/tema-visual-layout/spec.md`).
- Nuevo ítem de sidebar bajo "Administración", junto a Proveedores/Clientes Medidores/Centros Financieros/Centros de Costos.
- Reutilizar el permiso `core_institucional.administrar` ya usado por el resto de catálogos de Administración — no crear un permiso nuevo.

**Non-Goals:**
- No se implementa el CRUD de `Asignacion` ni `Catalogo` (las tablas hijas de `Item`) — eso es un change futuro aparte.
- No se modifica el esquema de `items` (columnas ya correctas para lo pedido).
- No se conecta `Item` a ningún módulo funcional (Pago de Proveedores, Consumo Eléctrico) todavía — eso depende de que `Asignacion`/`Catalogo` existan primero.
- No se introduce jerarquía institucional (`cfinanciero`/`ccosto`) en `Item`; es un catálogo transversal, no ligado a un centro de costo específico (así lo definió el modelo ya existente).

## Decisions

1. **Nombre de ruta/URL: `items`, no `items-presupuestarios`.** Sigue la convención ya usada (`cfinancieros`, `ccostos`, `clientes-medidores`: el segmento de URL es el nombre de la tabla). El nombre visible en sidebar/breadcrumbs/títulos de página SHALL decir "Ítems Presupuestarios" para que no haya ambigüedad de cara al usuario; la URL (`/maestros/items`, rutas `maestros.items.*`) es un detalle interno.
2. **Formulario plano, no wizard.** Proveedor usa un wizard de 5 pasos porque tiene decenas de campos agrupados por tema. `Item` solo tiene `codigo`, `nombre`, `descripcion` y `activo` — un único formulario en `create.tsx`/`edit.tsx` es más simple y consistente con la densidad de la entidad, sin la sobre-ingeniería de un wizard para 3 campos.
3. **Reutilizar `core_institucional.administrar`, no crear un permiso nuevo.** Es el mismo permiso que ya gatea `ProveedorPolicy`, `CcostoPolicy` y `CfinancieroPolicy`, y el que ya calcula `puedeAdministrarEstructura` en `app-sidebar.tsx`. Crear un permiso nuevo (p. ej. `tablas_maestras.administrar_items`) fragmentaría innecesariamente un catálogo más de Administración que ya comparte el mismo nivel de autorización que sus vecinos.
4. **Icono de sidebar: `Tags` (lucide-react).** No está usado por ningún otro ítem del sidebar hoy; representa bien un catálogo/clasificador.
5. **`ItemController` vive en el namespace `Maestros`** (no uno nuevo tipo `Presupuesto`), porque la spec (`tablas-maestras-institucionales`) ya encuadra `items`/`asignaciones`/`catalogos` como tablas maestras, no como el módulo funcional "Presupuesto" (que sigue sin código, per CLAUDE.md/HARNESS_IA.md). Mantener el namespace evita mezclar conceptualmente el catálogo con el módulo funcional todavía inexistente.
6. **Eliminar (`destroy`) hace soft delete**, ya que el modelo usa `SoftDeletes`. A diferencia de `ProveedorController::destroy` (que bloquea el borrado si hay relaciones dependientes — facturas, casos, etc.), `Item` hoy no tiene ninguna fila real de `Asignacion`/`Catalogo` que dependa de él en producción todavía, pero el controlador SHALL igual verificar `whereHas('asignaciones')`/`whereHas('catalogos')` antes de borrar, para no dejar huérfanas esas relaciones el día que sí existan filas — mismo espíritu que la verificación de `ProveedorController::destroy`, adaptado a las dos relaciones de `Item`.

## Risks / Trade-offs

- **[Riesgo]** Si en el futuro `Asignacion`/`Catalogo` obtienen su propio CRUD, la página `show.tsx` de `Item` probablemente deba listar sus asignaciones/catálogos hijos — hoy `show.tsx` solo muestra los 4 campos propios de `Item`, sin listar relaciones (no existen filas reales de esas tablas todavía). Se documenta como extensión futura, no se implementa ahora (ver Non-Goals).
- **[Riesgo]** Reutilizar `core_institucional.administrar` significa que cualquier usuario con ese permiso amplio también administra `Item`, sin granularidad por catálogo — ya es el comportamiento aceptado para Proveedor/Ccosto/Cfinanciero, así que es consistente, no una regresión nueva.

## Migration Plan

Sin migraciones de base de datos (la tabla `items` ya existe con el esquema correcto). Cambio de código backend + frontend + tests; revertir el commit es suficiente si algo sale mal.

## Open Questions

Ninguna bloqueante para implementar.

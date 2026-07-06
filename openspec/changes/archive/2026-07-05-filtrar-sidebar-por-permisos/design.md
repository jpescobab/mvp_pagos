## Context

El sidebar (`resources/js/components/app-sidebar.tsx`) arma listas estáticas de `NavItem[]` por grupo y hoy solo une un chequeo de permiso (`puedeAdministrarEstructura`, basado en `core_institucional.administrar`) para 2 de 19 ítems (Centros Financieros, Centros de Costos). El resto se muestra a cualquier usuario con sesión. El backend, sin embargo, ya distingue tres situaciones reales:

1. **Ya gateado en Policy pero no llamado en `index()` / no reflejado en sidebar**: Proveedor, Item, ClienteMedidor (su `view`/`create`/`update`/`delete` exige `core_institucional.administrar`, pero el listado no llama `authorize`). Usuarios, Auditoría, Roles sí llaman `authorize` en `index()` pero el sidebar no lo replica.
2. **`viewAny()` intencionalmente público** (Casos, Egresos CGU, Procesos de Adquisición, Conectores Playwright, Definiciones de Workflow, Importaciones SGF, Sistemas Externos, Indicadores Económicos) — varias de estas decisiones ya están ratificadas en specs archivadas ("el sistema SHALL exponer... a cualquier usuario autenticado"). No se tocan.
3. **Sin ningún control** y sin spec que haya decidido dejarlos abiertos: Períodos de Reportabilidad, Definiciones/Ejecuciones de Informes Razonados.

## Goals / Non-Goals

**Goals:**
- El sidebar deja de mostrar un ítem si el usuario no tiene el permiso que su backend ya exige (o pasa a exigir en este cambio).
- Cerrar la brecha de autorización en `index()` para Proveedor/Item/ClienteMedidor (mismo permiso ya usado en el resto de su CRUD).
- Introducir `reportabilidad.ver` e `informes.ver` para los 3 índices que hoy no tienen ningún control y cuya apertura nunca fue una decisión de spec.
- Ocultar un `NavGroup` completo si, tras filtrar, no le queda ningún ítem visible.

**Non-Goals:**
- No se toca el diseño "ver todo, actuar con permiso" ya ratificado (Casos, Egresos CGU, Procesos de Adquisición, Conectores) ni los módulos con spec explícita de acceso abierto (Workflow, SGF, Sistemas Externos, Indicadores Económicos).
- No se rediseña el árbol de permisos existente ni se introduce un permiso granular por cada acción del sidebar — solo se llenan los huecos concretos identificados.
- No se toca la jerarquía institucional, el CRUD de Cfinanciero/Ccosto (ya correctamente gateado) ni `TransicionWorkflowService`.

## Decisions

**1. Permiso de listado = mismo permiso que gobierna el resto del CRUD, cuando ya existe.**
Para Proveedor/Item/ClienteMedidor, `viewAny()` reutiliza `core_institucional.administrar` — el mismo que ya exige `view`/`create`/`update`/`delete` en sus Policies. Alternativa descartada: crear un permiso `xxx.ver` separado del de administración — se descarta porque en este dominio nunca ha existido una distinción entre "ver" y "administrar" (a diferencia de `usuarios.ver` vs `usuarios.editar`, que sí son módulos con esa distinción ya establecida).

**2. Permisos nuevos solo donde ninguna spec archivada ya decidió dejar el acceso abierto.**
`reportabilidad.ver` e `informes.ver` se crean porque `PeriodoReportabilidadController`, `DefinicionInformeRazonadoController` y `EjecucionInformeRazonadoController` no tienen Policy ni spec que diga "abierto a cualquier autenticado". Se descarta modificar las specs de Workflow/SGF/Sistemas Externos/Indicadores Económicos (que sí dicen explícitamente "el sistema SHALL exponer... a cualquier usuario autenticado") — hacerlo sería revertir una decisión ya tomada y comunicada, no cerrar un hueco.

**3. Un permiso `informes.ver` compartido entre Definiciones y Ejecuciones de Informes Razonados**, no dos permisos separados — son el mismo submódulo en el sidebar y en la spec `gestionar-informes-razonados`, igual que `informes.aprobar`/`informes.publicar` ya son transversales a esa misma capability.

**4. Policies reales (no Gates ad-hoc) para los 3 controladores nuevos**, porque los 3 modelos Eloquent correspondientes ya existen (`PeriodoReportabilidad`, `DefinicionInformeRazonado`, `EjecucionInformeRazonado`) — consistente con el patrón de Policy+`$this->authorize('viewAny', Modelo::class)` ya usado en el resto del código, en vez de introducir un mecanismo distinto (`Gate::define` inline) solo para estos tres.

**5. Filtrado en el sidebar por función pura de permisos, no por rol.**
`app-sidebar.tsx` construye cada grupo con un helper `filtrarPorPermiso(items, permisos)` que resuelve, ítem por ítem, si requiere permiso y si el usuario lo tiene; los ítems sin `permiso` declarado (grupo "ver todo") siempre pasan. Se agrega un campo opcional `permiso?: string` a la forma en que se arman los arrays de `NavItem` de este archivo (no se toca el tipo `NavItem` compartido en `@/types`, que se usa en otros componentes sin este concepto — se resuelve el filtro antes de construir el `NavItem[]` final que reciben `NavMain`/`NavGroup`).
Alternativa descartada: pasar `auth.permissions` hacia abajo a `NavGroup`/`NavMain` y filtrar ahí — se descarta porque esos componentes son genéricos y los reutiliza el resto de la app sin noción de permisos; mantener el filtrado en `app-sidebar.tsx` (el único lugar que ya conoce `auth`) evita ensuciar componentes compartidos.

**6. Grupo se oculta si queda vacío tras filtrar**, comparando `items.length === 0` antes de renderizar cada `<NavGroup>` en `app-sidebar.tsx`.

## Risks / Trade-offs

- [Un usuario con sesión activa que hoy podía navegar a `/reportabilidad/periodos` o `/informes-razonados/*` sin restricción empezará a recibir 403] → Es el comportamiento buscado por este cambio; se asignan `reportabilidad.ver` e `informes.ver` a `superadmin` y `admin` en el seeder para no romper el acceso de quienes ya operan esos módulos hoy. Si algún otro rol los necesita, se agrega explícitamente al seeder o vía UI de Roles y Permisos (ya existente).
- [Ocultar en el sidebar sin reforzar el backend sería puramente cosmético] → Mitigado: cada ítem que se oculta en el sidebar en este cambio tiene su `authorize()` correspondiente agregado en el mismo change (Proveedor/Item/ClienteMedidor/PeriodoReportabilidad/DefinicionInformeRazonado/EjecucionInformeRazonado); los ítems que quedan sin permiso (Grupo B) siguen intencionalmente abiertos en ambos lados.
- [Confusión sobre por qué unos módulos filtran y otros no] → Se documenta explícitamente en proposal.md y en las specs modificadas cuáles quedan abiertos a propósito, para que una futura revisión no lo confunda con un descuido.

## Migration Plan

1. Backend primero (Policies + `authorize()` en los 5 controladores + permisos nuevos en el seeder) — sin esto, ocultar en el sidebar sería cosmético.
2. Ejecutar el seeder (`php artisan db:seed --class=RolesAndPermissionsSeeder`) en el entorno de desarrollo para que `superadmin`/`admin` reciban los 2 permisos nuevos de inmediato.
3. Frontend después (filtro en `app-sidebar.tsx`), una vez que el backend ya rechaza correctamente sin el permiso.
4. Sin rollback especial: son permisos aditivos y un `authorize()` adicional; revertir el commit basta si algo falla.

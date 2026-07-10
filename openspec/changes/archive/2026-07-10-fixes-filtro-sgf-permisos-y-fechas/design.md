## Context

Tres correcciones aplicadas durante la puesta en marcha de la importación real desde SGF (2026-07-10), ya implementadas y verificadas, que este change regulariza a posteriori (ver proposal). Este design registra las decisiones técnicas y su evidencia; no queda implementación pendiente.

## Goals / Non-Goals

**Goals:**
- Dejar los specs alineados con el comportamiento real y verificado del código.
- Registrar el porqué de cada decisión con su evidencia empírica.

**Non-Goals:**
- No se cambia el flujo de importación masiva (`importar_pendientes`), que nunca tuvo el filtro client-side.
- No se toca el mecanismo de gates/policies (el bypass del superadmin vía `Gate::before` queda igual); solo cambia la lista informativa compartida a la UI.
- No se altera el formato numérico global existente.

## Decisions

### 1. Confiar en el filtro nativo de la Bandeja; registrar `grupos_actuales` en vez de descartar
La Decisión original del change `2026-07-09-importar-casos-grupo-pago-operaciones-sgf` agregó un descarte defensivo por columna `grupo_actual`. La corrida real demostró que el dropdown "GRUPO" (lo que filtra la Bandeja) y la columna "Grupo Actual" (el paso donde está parado el proceso) son campos distintos: el descarte eliminaba el 100% de filas legítimas (trabajo id=3/4: `filas_procesadas: 10`, 0 importadas). Alternativa considerada — corregir el filtro para comparar contra el campo correcto: descartada, porque ese campo no está en las columnas de la fila; la fuente autoritativa es el propio filtro nativo. La trazabilidad se conserva registrando los valores distintos de `grupo_actual` observados (`grupos_actuales`) en el detalle del paso `pagina_bandeja_N`. Evidencia post-fix: trabajo id=5 `completado`, 9 filas, `grupos_actuales:["Pago Operaciones"]` — el filtro nativo filtra correctamente.

### 2. `permisosCompartidos()` en `HandleInertiaRequests`
El superadmin bypassea todos los gates (`Gate::before`) pero su lista `getAllPermissions()` no incluye permisos de módulos (solo se asignan a `admin`, `jefe_finanzas`, `administrador_zonal`), así que la UI condicionada por `auth.permissions` lo subrepresentaba. La lista compartida ahora refleja el acceso efectivo: `Permission::pluck('name')` completo para superadmin, `getAllPermissions()` para el resto. Alternativa considerada — asignar todos los permisos al rol superadmin en el seeder: descartada, duplicaría la fuente de verdad del bypass y habría que re-sincronizar en cada permiso nuevo.

### 3. Helpers de fecha deterministas en `@/lib/format`
`toLocale*()` sin locale/zona depende del ICU del runtime: Node (SSR) y el navegador producían textos distintos (`09-07-2026, 21:21:22` vs `9/7/2026, 21:21:22`), rompiendo la hidratación. `formatFechaHora` (es-CL, `America/Santiago`, componentes explícitos, hora 24h) y `formatFecha` (es-CL, `UTC` para preservar la fecha civil de columnas `date` que llegan como medianoche UTC). Barrido completo: ~30 usos en ~17 archivos reemplazados; dos helpers locales preexistentes (`users-table`, `ficha-consulta`) delegan ahora en los compartidos.

## Risks / Trade-offs

- **[Filtro nativo devuelve una fila ajena al grupo]** → ya no se descarta, se importa; mitigación: `grupos_actuales` en el paso deja evidencia inmediata para detectarlo, y el snapshot conserva el payload completo para auditar/anular sin perder trazabilidad.
- **[Superadmin ve acciones de módulos que quizá no opera]** → coherente con su bypass de gates (ya podía ejecutarlas); el control real sigue en policies/gates, no en la lista informativa.
- **[`formatFecha` en UTC]** → correcto para columnas `date`; si a futuro se formatea un timestamp real como solo-fecha cerca de medianoche, usar `formatFechaHora` o revisar el caso.

## Migration Plan

Nada que migrar: los tres cambios ya están aplicados y verificados (suite completa verde). Rollback = revertir los commits correspondientes.

## Open Questions

Ninguna.

## Why

Durante la puesta en marcha de la importación real desde SGF (2026-07-10) se aplicaron tres correcciones directamente sobre el código que alteran comportamiento especificado o introducen convenciones nuevas sin artefacto OpenSpec. Este change las regulariza: documenta el porqué, ajusta los specs afectados y deja el registro trazable, cumpliendo el flujo obligatorio del harness (toda implementación pasa por OpenSpec).

## What Changes

- **Filtro del grupo "Pago Operaciones" (conector SGF)**: la corrida real demostró que la "red de seguridad" client-side —descartar filas cuyo `grupo_actual` no coincida con "Pago Operaciones"— descartaba el 100% de las filas legítimas, porque el dropdown "GRUPO" del filtro nativo de la Bandeja y la columna "Grupo Actual" son campos distintos que no tienen por qué coincidir. Se elimina ese descarte: se confía en el filtro nativo (fuente autoritativa) y, en su lugar, se registran los valores distintos de `grupo_actual` observados en el paso `pagina_bandeja_N` (`grupos_actuales`) para trazabilidad y diagnóstico. **BREAKING respecto al escenario anterior del spec** (que exigía el descarte defensivo).
- **Permisos compartidos del superadmin**: `HandleInertiaRequests` compartía al frontend solo `getAllPermissions()` del usuario; como el superadmin bypassea los gates vía `Gate::before` pero no tiene los permisos de módulos asignados, la UI (sidebar y acciones condicionadas por `auth.permissions`) lo subrepresentaba —podía entrar a Revisión de Pagos pero no veía el ítem. Ahora la lista compartida refleja el acceso efectivo: superadmin recibe todos los permisos existentes; el resto, los de sus roles.
- **Formato de fechas determinista (SSR)**: los `toLocaleString()`/`toLocaleDateString()` sin locale/zona fijos producían texto distinto entre el render del servidor (SSR de Inertia) y el del cliente, rompiendo la hidratación de React. Se agregan `formatFecha()`/`formatFechaHora()` a `@/lib/format` (locale `es-CL`, componentes explícitos; fecha-hora en `America/Santiago`, solo-fecha en `UTC` para no correr un día las columnas `date`) y se reemplazan los ~30 usos en ~17 archivos.

## Capabilities

### New Capabilities
<!-- ninguna -->

### Modified Capabilities
- `conector-sgf-playwright`: el escenario de la importación selectiva del grupo "Pago Operaciones" deja de exigir el descarte defensivo por columna `grupo_actual` y pasa a exigir confianza en el filtro nativo + registro de `grupos_actuales` para trazabilidad.
- `seguridad-auditoria`: nuevo requirement sobre los permisos compartidos al frontend (`auth.permissions`): deben reflejar el acceso efectivo del usuario, incluido el acceso total del superadmin.
- `tema-visual-layout`: nuevo requirement de formato de fechas determinista (análogo al "Formato numérico global" existente), compatible con SSR.

## Impact

- `services/sgf-playwright/sgf-scraper.js` (`importarGrupoPagoOperaciones`): filtro client-side eliminado, diagnóstico `grupos_actuales` agregado. **Ya implementado y verificado** con corrida real (trabajo id=5: 9 casos, 9 snapshots, 47 documentos, `grupos_actuales:["Pago Operaciones"]`).
- `app/Http/Middleware/HandleInertiaRequests.php` (`permisosCompartidos()`). **Ya implementado**, con 2 tests nuevos en `tests/Feature/Seguridad/PermisosCompartidosInertiaTest.php`.
- `resources/js/lib/format.ts` + ~17 páginas/componentes. **Ya implementado**, `tsc`/ESLint verdes y sin `toLocale*` restantes en `resources/js`.
- Sin cambios de dependencias ni de esquema de base de datos.

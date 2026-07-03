## Context

Los mockups (`Login.html` 1388 líneas, `Dashboard_v2.html` 4485 líneas) usan CSS plano con variables custom (`--accent: #2563eb`, `--bg: #f4f6fb`, Manrope, radius 16px) que ya coinciden casi por completo con los tokens de `resources/css/app.css` (spec `tema-visual-layout`). El gap es de presentación de componentes, no de paleta. Los logos institucionales vienen embebidos como data-URI base64 (~1.5MB c/u, variantes clara/oscura). Hay datos reales en `indicadores_economicos` (UF, UTM, UTA, IPC, USD).

## Goals / Non-Goals

**Goals:**
- Adoptar el formato visual de los mockups en login, shell (sidebar/topbar) y dashboard, manteniendo la estructura Inertia/React/shadcn actual.
- Solo datos reales: indicadores desde BD, KPIs desde los modelos existentes.

**Non-Goals:**
- Refactor de los índices por módulo (chips de filtro, avatares de entidad, paginación pill) — change posterior ya instruido.
- Búsqueda global, campana de notificaciones, gráficos analíticos animados, ticker bursátil del login (decorativo con datos falsos → se omite).
- Módulos aspiracionales del mockup (Presupuesto, Contabilidad, Mercado Público) en el sidebar — regla convenida: solo módulos implementados.

## Decisions

- **Extracción de logos por script y no a mano**: las líneas base64 superan 1.5MB; un script Node/PHP parsea el HTML, decodifica y escribe `public/images/logo-capj-{light,dark}.png`. El script es descartable (scratchpad), los PNG se versionan.
- **Indicadores en el login vía la vista Fortify existente**: Fortify renderiza `auth/login` desde `FortifyServiceProvider` — ahí se agregan props `indicadores` (último valor por tipo, consulta simple). Si la tabla está vacía, prop `[]` y el frontend oculta los chips. Alternativa descartada: `Inertia::share` global (cargaría indicadores en todas las páginas).
- **Escena del login simplificada respecto al mockup**: se adoptan grilla + gradientes radiales + chips de indicadores reales + tarjeta glass; se omiten el gráfico SVG animado con series pseudoaleatorias y el ticker (datos inventados, contrario al principio de datos reales; además dependen de JS imperativo ajeno a React).
- **Sidebar con shadcn `Sidebar` + `Collapsible`** (ambos ya instalados): grupos por módulo con `SidebarGroup` + chevron; el estado activo del grupo se deriva de la URL actual (`useCurrentUrl` ya existe). Se mantiene `collapsible="icon"` con tooltips nativos del componente.
- **Menú de usuario al topbar**: el `NavUser` del pie del sidebar se reubica como avatar con iniciales en el header (mockup); el pie del sidebar queda libre. El dropdown existente (`user-menu-content.tsx`) se reutiliza sin cambios.
- **Dashboard con controlador dedicado**: `DashboardController@index` reemplaza `Route::inertia('dashboard')`; los KPIs son conteos directos (`Proceso`/`CasoPagoProveedor` no cerrados, `EgresoCgu` del mes corriente, `ProcesoAdquisicion` activos, `EjecucionInformeRazonado` en curso) — sin services nuevos, es lectura simple de índice permitida en controlador liviano.
- **Delta/tendencia de KPIs se omite**: el mockup muestra pills "+12%" pero no hay serie histórica comparable definida; mostrar deltas inventados violaría el principio de datos reales. Los KPI cards muestran valor + pie descriptivo.

## Risks / Trade-offs

- [Riesgo] PNGs de ~1MB c/u inflan el repo y la carga del login → Mitigación: se sirven con `loading="lazy"` donde aplique y tamaño de render fijo; si pesan demasiado tras extraer, se recomprimen (nivel PNG) antes de versionar.
- [Riesgo] Reagrupar el sidebar rompe hábitos de navegación actuales → Mitigación: los grupos inician expandidos donde está la ruta activa; el resto colapsado.
- [Riesgo] Cambiar `Route::inertia('dashboard')` a controlador puede romper tests del starter kit que esperan la ruta → Mitigación: se conserva el nombre de ruta `dashboard` y se actualizan los tests existentes.
- [Riesgo] El login carga datos de BD antes de autenticación → Mitigación: consulta mínima (5 filas, un `SELECT` con agrupación), datos públicos no sensibles (indicadores económicos oficiales).

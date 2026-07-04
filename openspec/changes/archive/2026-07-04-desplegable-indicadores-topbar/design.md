## Context

El servicio `App\Services\Indicadores\IndicadorEconomicoSelector::ultimosPorTipo(array $tipos)` ya resuelve "el último valor registrado" por tipo de indicador (`UF`, `USD`, `UTM`, `UTA`, `IPC`) y ya se usa en `DashboardController` para las tarjetas de indicadores del panel general. El formateo de esos valores (decimales, símbolo `$`/`%`, etiquetas `U.F`/`U.T.M`/`Dólar`/`I.P.C`) vive hoy como funciones privadas dentro de `dashboard.tsx`. El topbar (`app-sidebar-header.tsx`) se renderiza en todas las páginas autenticadas vía el layout compartido.

## Goals / Non-Goals

**Goals:**
- Mostrar UF, UTM, dólar e IPC desde cualquier página autenticada, con el mismo formato ya usado en el panel general.
- No duplicar la lógica de formateo entre el panel general y el nuevo desplegable.

**Non-Goals:**
- Cambiar qué indicadores muestra el panel general (sigue mostrando también UTA; el topbar no).
- Historial o gráfico de evolución del indicador — el desplegable solo muestra el último valor, igual que hoy en el panel general.
- Restringir el desplegable por permiso: los indicadores económicos ya son visibles para cualquier usuario autenticado (`consulta-catalogo-indicadores-economicos` no exige permiso especial), así que el desplegable tampoco lo exige.

## Decisions

- **Prop compartida con nombre propio (`indicadoresTopbar`), no `indicadores`**: `DashboardController` ya envía una prop `indicadores` (5 tipos, para las tarjetas del panel). Si la prop global compartida por el middleware usara el mismo nombre, Inertia la sobreescribiría con la del controlador en esa página puntual, dejando el topbar inconsistente solo en el panel general. Un nombre distinto evita la colisión sin tocar `DashboardController`.
- **Se comparte vía middleware, no vía un endpoint nuevo**: el topbar vive en el layout de todas las páginas autenticadas; pedirle sus datos a una ruta aparte solo para evitar una consulta liviana (4 filas indexadas por `tipo`) en cada request es una complejidad que no se justifica — mismo criterio ya aplicado a `auth`/`sidebarOpen` en `HandleInertiaRequests`.
- **Extracción de `resources/js/lib/indicadores.ts`**: mueve `ETIQUETAS_INDICADOR` y `formatearValor` (hoy privadas en `dashboard.tsx`) a un módulo compartido con el mismo comportamiento exacto, importado por `dashboard.tsx` y por el nuevo `topbar-indicadores.tsx`. No se reformatea ni se cambia ninguna regla existente.
- **Solo 4 tipos en el topbar** (`UF`, `UTM`, `USD`, `IPC`), tal como pidió el usuario — no se incluye `UTA` ahí, a diferencia del panel general.

## Risks / Trade-offs

- [Riesgo] La consulta de `ultimosPorTipo()` se ejecuta en cada request autenticado (antes solo corría en el panel general). → Mitigación: son 4 consultas triviales (`ORDER BY fecha_valor/periodo DESC LIMIT 1` por tipo, sobre una tabla pequeña), mismo patrón ya usado por `auth.permissions` en el propio middleware.

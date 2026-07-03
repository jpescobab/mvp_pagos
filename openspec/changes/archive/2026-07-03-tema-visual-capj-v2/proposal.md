## Why

El usuario entregó dos mockups HTML de alta fidelidad (`Login.html`, `Dashboard_v2.html`) que definen el formato visual convenido para "CAPJ +": login institucional con indicadores económicos, shell con sidebar agrupado/colapsable y topbar con avatar, y un panel general con KPIs. La app actual conserva el login y dashboard del starter kit y un sidebar de lista plana — no respetan ese formato.

## What Changes

- **Tokens**: radio base a 16px y tokens semánticos suaves (verde/rojo/ámbar + variantes soft y dark) en `app.css`, sin renombrar variables que consumen los componentes shadcn.
- **Assets**: extraer los logos institucionales (variantes clara/oscura) embebidos en base64 en los mockups hacia `public/images/`.
- **Login**: rediseño completo de la presentación (escena de fondo con grilla/gradientes/gráfico sutil, chips flotantes con indicadores económicos reales de `indicadores_economicos`, tarjeta glass "Bienvenido a CAPJ +", inputs con ícono, botón degradado, footer institucional). La lógica Fortify/Inertia existente no cambia.
- **Sidebar**: marca con logo institucional + subtítulo "Finanzas y Ppto"; ítems reagrupados en grupos colapsables por módulo (General, Administración, Pago de Proveedores, Adquisiciones, Maestros, Reportabilidad, Integraciones) con chevron e ítem activo con barra azul; se mantiene el colapso a modo ícono. **BREAKING** (visual): reemplaza el requirement de "riel de íconos" de la spec `tema-visual-layout`.
- **Topbar**: toggle de tema claro/oscuro y avatar circular con iniciales que abre el menú de usuario (se mueve desde el pie del sidebar). Sin búsqueda global ni notificaciones (sin backend — no se inventan funcionalidades muertas).
- **Dashboard**: reemplazar el placeholder por "Panel general" con datos reales: 4 KPI cards (casos de pago activos, egresos CGU del mes, procesos de adquisición activos, informes razonados en curso), chips de indicadores económicos vigentes, y tabla "Casos de pago recientes" con badge de estado y link al detalle. Nuevo `DashboardController`.

## Capabilities

### New Capabilities
(ninguna — todo es evolución de la capability visual existente)

### Modified Capabilities
- `tema-visual-layout`: el requirement de navegación cambia de "riel de íconos" a "grupos colapsables por módulo implementado"; se agregan requirements para login institucional con indicadores, topbar con tema/avatar, panel general con datos reales, y tokens semánticos suaves.

## Impact

- Frontend: `resources/css/app.css`, `resources/js/pages/auth/login.tsx`, `resources/js/layouts/auth/*`, `resources/js/components/app-sidebar.tsx`, `resources/js/components/app-sidebar-header.tsx`, `resources/js/pages/dashboard.tsx`, componentes nuevos de login/dashboard.
- Backend: nuevo `app/Http/Controllers/DashboardController.php`, props de indicadores para el login (vía `FortifyServiceProvider`/vista de login), `routes/web.php` (dashboard pasa de `Route::inertia` a controlador).
- Assets: `public/images/logo-capj-light.png`, `public/images/logo-capj-dark.png`.
- Tests: `tests/Feature/DashboardTest.php` (o equivalente) y aserciones de props de indicadores en login.
- Sin cambios de esquema de base de datos. Fuera de alcance: refactor de índices por módulo (chips de filtro, avatares, paginación pill — change posterior), búsqueda global, notificaciones, gráficos analíticos.

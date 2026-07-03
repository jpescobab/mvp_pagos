## 1. Assets y tokens

- [x] 1.1 Script descartable (scratchpad) que extraiga los dos data-URI base64 de `Dashboard_v2.html` (logo claro y oscuro) y los escriba como `public/images/logo-capj-light.png` y `public/images/logo-capj-dark.png`; verificar que abren como PNG válidos y anotar su peso.
- [x] 1.2 `resources/css/app.css`: subir `--radius` a `1rem`; agregar tokens semánticos suaves `--success`/`--success-soft`, `--warning`/`--warning-soft`, `--danger-soft` (el `--destructive` ya existe) con variantes `.dark`, y mapearlos en `@theme` como `--color-*` para poder usarlos como utilidades Tailwind.

## 2. Login institucional

- [x] 2.1 En `app/Providers/FortifyServiceProvider.php` (vista de login): agregar prop `indicadores` con el último valor por tipo (UF, UTM, UTA, IPC) desde `IndicadorEconomico` — consulta simple, array vacío si no hay datos.
- [x] 2.2 Rediseñar `resources/js/layouts/auth/auth-simple-layout.tsx` (o crear variante): escena de fondo con grilla + gradientes radiales azules (CSS puro), topbar con logo institucional (claro/oscuro según tema) y toggle de tema, footer "© 2026 Poder Judicial · República de Chile".
- [x] 2.3 Rediseñar `resources/js/pages/auth/login.tsx`: fila de chips flotantes con los indicadores recibidos (ocultos si el array está vacío), tarjeta glass (backdrop-blur, radius 22px) con eyebrow pill, "Bienvenido a CAPJ +", subtítulo "Sección Finanzas y Presupuesto - Zonal Coyhaique", inputs con ícono prefijo (mail/lock) y toggle de contraseña (reusar `password-input.tsx` si aplica), "Recordarme en este dispositivo", botón degradado azul con flecha, meta "Conexión cifrada · TLS 1.3". Mantener `useForm`/rutas Fortify existentes y la visualización de errores.

## 3. Shell — sidebar y topbar

- [x] 3.1 `resources/js/components/app-sidebar.tsx`: marca con logo institucional en tile + "CAPJ +" + subtítulo "Finanzas y Ppto"; reagrupar ítems en grupos colapsables (`Collapsible` de shadcn) por módulo: General (Panel general), Administración (Usuarios, Auditoría, Definiciones de Workflow), Pago de Proveedores (Casos, Egresos CGU, Importaciones SGF), Adquisiciones (Procesos), Maestros (Proveedores, Clientes Medidores), Reportabilidad (Períodos, Definiciones de Informes, Ejecuciones de Informes), Integraciones (Sistemas Externos, Conectores Playwright, Indicadores Económicos); grupo de la ruta activa expandido por defecto; ítem activo con fondo acentuado + barra izquierda; mantener `collapsible="icon"` con tooltips; quitar `NavUser` del pie.
- [x] 3.2 `resources/js/components/app-sidebar-header.tsx`: a la derecha de las migas agregar toggle de tema claro/oscuro (reusar `use-appearance`) y avatar circular con iniciales (`use-initials` ya existe) que abre `user-menu-content.tsx` en dropdown.

## 4. Panel general

- [x] 4.1 Crear `app/Http/Controllers/DashboardController.php` con `index()`: KPIs reales — casos de pago activos (procesos de pago no cerrados), egresos CGU del mes corriente, procesos de adquisición activos (no cerrados), informes razonados en curso; `indicadores` (último valor por tipo incluyendo USD); `casosRecientes` (últimos 6 con proveedor, monto, estado actual y ruta al detalle). Reemplazar `Route::inertia('dashboard', ...)` por el controlador en `routes/web.php` conservando el nombre de ruta `dashboard`.
- [x] 4.2 Reescribir `resources/js/pages/dashboard.tsx` como "Panel general": page-head con título; fila de 4 KPI cards (ícono en tile azul suave + número grande + pie descriptivo); fila de chips de indicadores económicos; tarjeta con tabla "Casos de pago recientes" (headers uppercase, badge de estado, fila enlaza al detalle) y estado vacío. Sin gráficos falsos ni deltas inventados.
- [x] 4.3 Regenerar Wayfinder con `php artisan wayfinder:generate --with-form`.

## 5. Pruebas

- [x] 5.1 `tests/Feature/DashboardTest.php`: requiere autenticación; renderiza componente `dashboard` con props de KPIs correctas (conteos con datos sembrados de prueba), indicadores y casos recientes; panel vacío sin errores.
- [x] 5.2 Test del login: la vista de login incluye prop `indicadores` con el último valor por tipo cuando hay datos, y array vacío sin datos; el POST de login sigue funcionando (suite Auth existente en verde).

## 6. Validación

- [x] 6.1 `vendor/bin/pint --dirty --format agent` sobre PHP tocado.
- [x] 6.2 `composer test` (lint + phpstan + Pest completo).
- [x] 6.3 `npm run lint:check` y `npm run types:check`.
- [x] 6.4 Verificación en navegador: login con chips de indicadores y tema claro/oscuro, autenticación, sidebar agrupado con grupo activo expandido y colapso a íconos, topbar con toggle de tema y avatar con menú, panel general con KPIs/indicadores/casos recientes; responsive básico.

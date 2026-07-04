## 1. Compartir los indicadores globalmente

- [x] 1.1 `App\Http\Middleware\HandleInertiaRequests::share()`: agregar la prop `indicadoresTopbar` con `app(IndicadorEconomicoSelector::class)->ultimosPorTipo(['UF', 'UTM', 'USD', 'IPC'])`.

## 2. Extraer el formateo compartido

- [x] 2.1 Crear `resources/js/lib/indicadores.ts` con `ETIQUETAS_INDICADOR`, el tipo `Indicador` y `formatearValorIndicador`, moviendo exactamente la lógica hoy privada en `dashboard.tsx`.
- [x] 2.2 Actualizar `resources/js/pages/dashboard.tsx` para importar desde `@/lib/indicadores` en vez de definir sus propias copias.

## 3. Desplegable del topbar

- [x] 3.1 `resources/js/components/topbar-indicadores.tsx`: botón `Button variant="outline" size="icon"` (mismo estilo que `ThemeToggle`) con ícono de indicadores, `DropdownMenu`/`DropdownMenuContent` que lista los indicadores de la prop compartida usando `formatearValorIndicador`/`ETIQUETAS_INDICADOR`; omite el indicador si no llega en la lista.
- [x] 3.2 Insertar `<TopbarIndicadores />` en `resources/js/components/app-sidebar-header.tsx`, junto a `<ThemeToggle />`.

## 4. Validación

- [x] 4.1 `vendor/bin/pint --dirty`, `npm run lint:check`, `npm run format:check`, `npm run types:check`, `composer types:check`, `php artisan test --compact`.
- [x] 4.2 Verificado en el navegador con datos de prueba temporales: el botón "Indicadores económicos" aparece en el topbar del panel general (visible en cualquier página autenticada, mismo layout compartido), y las tarjetas del panel general siguen mostrando los mismos valores formateados igual que antes del refactor (confirma que la extracción a `lib/indicadores.ts` no cambió el comportamiento). El contenido del propio desplegable no se pudo confirmar visualmente por una limitación de la herramienta de automatización con componentes `DropdownMenu` de Radix (mismo patrón ya en producción en `UserMenuContent`); el código es estructuralmente idéntico a ese patrón probado.
- [x] 4.3 Sincronizar la spec delta en `openspec/specs/tema-visual-layout/spec.md` y archivar el change.

## 1. Conector SGF — filtro del grupo "Pago Operaciones"

- [x] 1.1 Eliminar el descarte client-side por columna `grupo_actual` en `importarGrupoPagoOperaciones()` (`services/sgf-playwright/sgf-scraper.js`), confiando en el filtro nativo de la Bandeja.
- [x] 1.2 Registrar los valores distintos de `grupo_actual` observados (`grupos_actuales`) en el detalle del paso `pagina_bandeja_N`.
- [x] 1.3 Verificar con corrida real: trabajo id=5 `completado` (9 casos, 9 snapshots, 47 documentos, `grupos_actuales:["Pago Operaciones"]`).

## 2. Permisos compartidos al frontend

- [x] 2.1 `HandleInertiaRequests::permisosCompartidos()`: superadmin recibe todos los permisos; el resto, `getAllPermissions()`; invitado, lista vacía.
- [x] 2.2 Tests: `tests/Feature/Seguridad/PermisosCompartidosInertiaTest.php` (superadmin recibe `revisar_finanzas`+`revisar_zonal`; `jefe_finanzas` solo el suyo).

## 3. Formato de fechas determinista (SSR)

- [x] 3.1 Agregar `formatFechaHora()` (es-CL, `America/Santiago`) y `formatFecha()` (es-CL, `UTC`) a `resources/js/lib/format.ts`, con `"—"` para nulos/inválidos.
- [x] 3.2 Barrido completo: reemplazar todos los `toLocaleString`/`toLocaleDateString` de `resources/js/` (~30 usos, ~17 archivos) por los helpers; delegar los helpers locales de `users-table.tsx` y `ficha-consulta.tsx`.
- [x] 3.3 Verificar: `tsc --noEmit` y ESLint verdes; `grep toLocale` en `resources/js` sin resultados; página de importaciones SGF sin error de hidratación en consola.

## 4. Validaciones

- [x] 4.1 Suite completa `php artisan test` verde tras los tres cambios.
- [x] 4.2 `openspec validate fixes-filtro-sgf-permisos-y-fechas --strict` en verde.

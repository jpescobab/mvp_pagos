## 1. Backend: datos normalizados y vínculo al caso

- [x] 1.1 En `ImportacionSgfController::show()`, tras cargar `snapshotsDatosExternos`, resolver un mapa `sgf_id => CasoPagoProveedor` (con `proveedor` eager-loaded) para los `referencia_externa` de esos snapshots en una sola consulta (`whereIn('sgf_id', ...)`)
- [x] 1.2 Pasar ese mapa a `ImportacionSgfResource` (constructor/propiedad adicional o `additional()`)
- [x] 1.3 En `ImportacionSgfResource::mapSnapshots()`, incluir por snapshot los campos de `payload_normalizado` (proveedor — nombre si el caso relacionado tiene `Proveedor` resuelto, si no el RUT normalizado; monto; estado SGF; folio de egreso; número; período; fecha SII; observaciones), leyendo cada clave de forma defensiva (`?? null`)
- [x] 1.4 En el mismo mapeo, incluir `caso_id` y `caso_estado` (código del estado actual del workflow) cuando el mapa del paso 1.1 tenga un caso para ese `referencia_externa`; `null` si no
- [x] 1.5 Agregar al payload del detalle un bloque `resumen` con `monto_total` (suma de montos normalizados) y `proveedores_identificados` / `proveedores_no_identificados` (conteo por si el caso relacionado tiene `proveedor_id` no nulo)

## 2. Tests backend

- [x] 2.1 Test: el detalle incluye proveedor, monto, estado SGF, folio, número, período, fecha SII y observaciones de un snapshot con payload normalizado completo
- [x] 2.2 Test: un snapshot con payload normalizado incompleto (ej. solo `estado`) no rompe la respuesta y expone `null` en los campos faltantes
- [x] 2.3 Test: un snapshot cuyo `referencia_externa` coincide con un `caso_pago_proveedor.sgf_id` existente incluye `caso_id` y `caso_estado`
- [x] 2.4 Test: un snapshot sin `caso_pago_proveedor` asociado incluye `caso_id`/`caso_estado` en `null`, sin error
- [x] 2.5 Test: el resumen agregado (`monto_total`, proveedores identificados/no identificados) es correcto para una corrida con varios snapshots, algunos con proveedor identificado y otros no

## 3. Frontend

- [x] 3.1 Actualizar `resources/js/types/sgf.ts`: extender `SnapshotSgfResumen` con los nuevos campos normalizados y `caso_id`/`caso_estado`; agregar tipo `resumen` a `ImportacionSgf`
- [x] 3.2 En `resources/js/pages/sgf/importaciones/show.tsx`, agregar una sección de resumen financiero (monto total con `formatMonto`, proveedores identificados vs. no identificados)
- [x] 3.3 Enriquecer cada fila de la lista de snapshots: proveedor (o RUT si no identificado), monto (`formatMonto`), badge de estado SGF (`EstadoBadge` o equivalente), folio/número/período, y link al detalle del caso (`pago-proveedores.casos.show`) cuando `caso_id` no sea `null`
- [x] 3.4 Verificado en el navegador (Laravel Server local, `sgf/importaciones/6`, 15 snapshots reales): resumen ($39.122.320 total, 12 identificados, 3 sin identificar), filas con proveedor/monto/estado/folio/observación, y el link "Ver caso" navega correctamente al detalle del caso (probado con el caso 666, en_revision_zonal) sin errores en consola

## 4. Verificación de regresión

- [x] 4.1 Correr `composer test` (incluye `lint:check`, `types:check`, `php artisan test`) y `vendor/bin/pint --dirty --format agent` — 541 tests, 537 passed, 4 skipped preexistentes, Pint y PHPStan limpios
- [x] 4.2 `npm run build` y confirmar que no hay errores de tipos en el frontend (`npm run types:check`) — ambos limpios

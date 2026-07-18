## 1. Captura en el scraper SGF (Node/Playwright)

- [x] 1.1 En `services/sgf-playwright/selectors.js`, agregar `numero_traspaso: ['n° traspaso']` a `MAPEO_COLUMNAS_BANDEJA` (usar la forma normalizada del encabezado; el match exacto de `indiceColumna` evita colisión con `monto`).
- [x] 1.2 En `services/sgf-playwright/sgf-scraper.js`, agregar `numero_traspaso: porColumna.numero_traspaso` al objeto que retorna `extraerDatosFila()` (sin esto no se propaga al `payload_crudo`).
- [x] 1.3 En `services/sgf-playwright/server.js`, incluir `numero_traspaso` de ejemplo en las filas del stub para poder verificar la cadena PHP→UI en modo stub.
- [x] 1.4 En `services/sgf-playwright/CALIBRACION.md`, anotar brevemente que la columna "N° traspaso" ya se captura.

## 2. Datos y normalización (backend)

- [x] 2.1 Crear migración `add_sgf_numero_traspaso_to_casos_pago_proveedor_table` con `string('sgf_numero_traspaso')->nullable()->after('numero')` (molde: `2026_07_09_172914_add_observacion_egreso_to_casos_pago_proveedor_table.php`).
- [x] 2.2 Agregar `sgf_numero_traspaso` al `$fillable` de `app/Models/CasoPagoProveedor.php`.
- [x] 2.3 En `app/Services/Sgf/NormalizadorSgf.php`, dentro de `normalizar()`, agregar `'numero_traspaso' => $this->trimONull($filaSgf['numero_traspaso'] ?? null)` (reutilizar `trimONull`, igual que `folio_egreso`/`numero`).
- [x] 2.4 En `app/Services/PagoProveedores/CasoPagoProveedorImporter.php`, agregar `'sgf_numero_traspaso' => $normalizado['numero_traspaso'] ?? null` en **ambos** bloques: el `update([...])` y el `create([...])` de `importarDesdeSnapshot()`.

## 3. Criterio de listo para egreso (backend)

- [x] 3.1 En `app/Services/PagoProveedores/ListoParaEgresoResolver.php`, cambiar el guard del traspaso para aceptar SGF **o** manual: fallar solo si `registrosContablesCgu->isEmpty()` **y** `sgf_numero_traspaso === null`. Actualizar el PHPDoc del criterio.

## 4. Exposición al frontend

- [x] 4.1 En `app/Http/Resources/PagoProveedores/CasoPagoProveedorResource.php`, exponer `sgf_numero_traspaso` en `toArray()`.
- [x] 4.2 En `resources/js/types/pago-proveedores.ts`, agregar `sgf_numero_traspaso: string | null` al tipo `CasoPagoProveedor`.
- [x] 4.3 En `resources/js/pages/pago-proveedores/casos/show.tsx` (sección Traspaso): cuando `caso.sgf_numero_traspaso` tiene valor y no hay registro contable manual más reciente, mostrarlo como el Traspaso vigente en solo-lectura con etiqueta "desde SGF"; el bloque "Sin Traspaso registrado todavía." no aplica en ese caso.
- [x] 4.4 En la misma vista, re-encuadrar el formulario "Registrar Traspaso" como "Corregir traspaso", gateado por `pago_proveedores.registrar_cgu`; una corrección sigue creando un `RegistroContableCgu` manual que se muestra como vigente por encima del valor de SGF, sin borrarlo.

## 5. Tests (Pest)

- [x] 5.1 En `tests/Unit/Sgf/NormalizadorSgfTest.php`, agregar un caso que verifique que `numero_traspaso` se propaga desde la clave cruda y queda `null` cuando SGF lo entrega vacío.
- [x] 5.2 En `tests/Feature/PagoProveedores/CasoPagoProveedorImporterTest.php`, agregar aserciones de `sgf_numero_traspaso` para create, para reimport/update, y para el caso en que el payload no lo incluye (queda `null`).
- [x] 5.3 Agregar cobertura del resolver: un caso con `sgf_numero_traspaso` no nulo y sin `RegistroContableCgu` (cumpliendo los demás criterios) queda listo para egreso; sin ninguno de los dos, no.
- [x] 5.4 Agregar/extender un test de feature que verifique que `sgf_numero_traspaso` se serializa en la respuesta del `show` del caso.

## 6. Validación y cierre

- [x] 6.1 `vendor/bin/pint --dirty` sobre los archivos PHP modificados.
- [x] 6.2 `composer test` (config:clear + lint:check + types:check + Pest) en verde — 617 tests, 613 passed, 4 skipped, 0 failed; PHPStan 0 errores.
- [x] 6.3 `npm run lint:check` y `npm run types:check` en verde para el tipo TS y la página React.
- [x] 6.4 Verificación del mapeo JS con script aislado (encabezados calibrados): `n° traspaso` se extrae sin colisionar con `monto`/`numero`. La cadena PHP (normalizador→importer→persistencia→resolver→serialización) queda cubierta por la suite de tests. La corrida end-to-end con el stub Node en vivo y la captura contra SGF real quedan para verificación supervisada.
- [x] 6.5 `npx openspec validate importar-numero-traspaso-sgf --strict` en verde antes de archivar.

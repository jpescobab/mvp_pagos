## 1. Backend — nuevo Presenter

- [x] 1.1 Crear `app/Services/PagoProveedores/PreparacionEgresoPresenter.php` con un método `criterios(CasoPagoProveedor $caso): array` que devuelve una lista de 4 elementos `{criterio, etiqueta, cumplido, detalle}` (slugs `tipo_proceso`, `traspaso_cgu`, `checklist_documental`, `proveedor`).
- [x] 1.2 Criterio `tipo_proceso`: `cumplido = $caso->proceso?->tipo_proceso_pago_id !== null`, `detalle = $caso->proceso?->tipoProcesoPago?->nombre ?? 'Sin clasificar'`.
- [x] 1.3 Criterio `traspaso_cgu`: `cumplido = $caso->registrosContablesCgu->isNotEmpty() || $caso->sgf_numero_traspaso !== null`, `detalle` = número del primer registro contable, o `sgf_numero_traspaso`, o `'Sin registrar'`.
- [x] 1.4 Criterio `checklist_documental` con 3 casos: (a) `$caso->proceso?->checklist === null` → `cumplido = false`, `detalle = 'Sin checklist generado'`; (b) checklist resuelto con cero ítems `obligatorio` → `cumplido = true`, `detalle = 'Sin ítems obligatorios'`; (c) checklist resuelto con N>0 ítems `obligatorio` → `cumplido` = todos con `documento_id !== null`, `detalle = "X / N obligatorios"` (X = cantidad con documento vinculado).
- [x] 1.5 Criterio `proveedor`: `cumplido = $caso->proveedor_id !== null`, `detalle = $caso->proveedor?->nombre ?? 'No identificado'`.

## 2. Backend — refactor de `ListoParaEgresoResolver`

- [x] 2.1 `app/Services/PagoProveedores/ListoParaEgresoResolver.php` recibe `PreparacionEgresoPresenter` por constructor (inyección de dependencias).
- [x] 2.2 `resuelve(?CasoPagoProveedor $caso): bool` queda como: si `$caso === null` retorna `false`; si no, `collect($this->preparacionEgreso->criterios($caso))->every(fn ($c) => $c['cumplido'])`.
- [x] 2.3 Confirmar que no hay ningún `new ListoParaEgresoResolver(...)` manual en el código (ambos call sites — `ImportacionSgfResource`, `CasosElegiblesEgresoCguService` — deben seguir resolviendo vía el contenedor sin cambios propios).

## 3. Backend — exponer el criterio en la respuesta de la página del caso

- [x] 3.1 En `app/Http/Resources/PagoProveedores/CasoPagoProveedorResource.php`, agregar una propiedad privada `incluirPreparacionEgreso` y un wither `withPreparacionEgreso(bool $incluir = true): self` (mismo patrón que `ImportacionSgfResource::withCasos()`).
- [x] 3.2 En `toArray()`, agregar la clave `'preparacion_egreso' => $this->when($this->incluirPreparacionEgreso, fn () => app(PreparacionEgresoPresenter::class)->criterios($this->resource))`.
- [x] 3.3 En `app/Http/Controllers/PagoProveedores/CasoPagoProveedorController.php@show()`, cambiar `new CasoPagoProveedorResource($caso)` por `(new CasoPagoProveedorResource($caso))->withPreparacionEgreso()`. No tocar `cargarDetalle()` ni `index()` (el listado paginado NO debe activar este flag, evita N+1).

## 4. Frontend — pintar en vez de recalcular

- [x] 4.1 En `resources/js/types/pago-proveedores.ts`, agregar el tipo `CriterioPreparacionEgreso = { criterio: string; etiqueta: string; cumplido: boolean; detalle: string }` y el campo opcional `preparacion_egreso?: CriterioPreparacionEgreso[]` a `CasoPagoProveedor`.
- [x] 4.2 En `resources/js/components/pago-proveedores/preparacion-egreso-card.tsx`, eliminar por completo la función `calcularPreparacionEgreso()` y el tipo local `CriterioPreparacion`.
- [x] 4.3 El componente lee `caso.preparacion_egreso ?? []` directamente; `completados`, `porcentaje` y `listoParaEgreso` siguen calculándose en el cliente como aritmética pura sobre esos booleanos ya resueltos.
- [x] 4.4 Cambiar `key={criterio.etiqueta}` por `key={criterio.criterio}` en el `.map()` de las 4 tarjetas.

## 5. Tests — impacto en tests existentes

- [x] 5.1 Ejecutar y revisar (sin modificar salvo que efectivamente rompan) estos tests tras los cambios de las secciones 1-4: `tests/Feature/PagoProveedores/AccesoDirectoCrearEgresoDesdeDetalleCasoTest.php`, `ChecklistDocumentalPagoProveedoresTest.php`, `MostrarEgresosCguEnCasoTest.php`, `MostrarHistorialSnapshotsSgfTest.php`, `RegistrarEvidenciaCguYPagoBancarioTest.php`, `RegistrarFacturaCasoPagoProveedorTest.php`, `RequisitosDocumentalesMatrizTest.php`, `ApiPagoProveedoresTest.php`, `tests/Feature/Sgf/ConsultarImportacionesSgfTest.php`. Confirmado: 56/56 pasan sin modificar nada — el cambio fue aditivo tal como preveía el diseño.

## 6. Tests nuevos

- [x] 6.1 Crear `tests/Feature/PagoProveedores/PreparacionEgresoPresenterTest.php`: llama `app(PreparacionEgresoPresenter::class)->criterios($caso)` directo. Casos: checklist null; checklist con 0 obligatorios + 1 opcional (debe dar `cumplido: true`, `detalle: 'Sin ítems obligatorios'`); checklist con N>0 obligatorios (ninguno/algunos/todos con documento); traspaso vía `sgf_numero_traspaso` vs. registro manual vs. ninguno; tipo de proceso y proveedor set/unset.
- [x] 6.2 Crear `tests/Feature/PagoProveedores/ListoParaEgresoResolverTest.php`: llama `app(ListoParaEgresoResolver::class)->resuelve($caso)` directo. Caso null → `false`; los 4 criterios completos con checklist de 0 obligatorios → `true`; los 4 completos con N>0 obligatorios satisfechos → `true` (baseline); cualquier criterio individual faltante → `false`.
- [x] 6.3 Extender `tests/Feature/PagoProveedores/AccesoDirectoCrearEgresoDesdeDetalleCasoTest.php` con un caso end-to-end: sembrar los seeders de requisitos documentales, crear un `TipoProcesoPago` nuevo (p. ej. código `REMESA`), crear `RequisitoDocumental` específicos para ese tipo marcando `FACTURA`/`COMPROBANTE` como `opcional` (fila más específica que gana sobre la universal) más un requisito propio `opcional`, dejar que `ResolutorChecklistDocumentalProceso` real resuelva el checklist (sin stub), visitar `route('pago-proveedores.casos.show', $caso)`, y confirmar que `caso.preparacion_egreso` contiene el criterio `checklist_documental` con `cumplido: true`, y que el acceso directo "Crear Egreso CGU con este caso" aparece cuando corresponde.

## 7. Validación

- [x] 7.1 `php artisan test --compact --filter=PreparacionEgresoPresenter`.
- [x] 7.2 `php artisan test --compact --filter=ListoParaEgresoResolver`.
- [x] 7.3 `php artisan test --compact --filter=AccesoDirectoCrearEgresoDesdeDetalleCaso`.
- [x] 7.4 `vendor/bin/pint --dirty --format agent`.
- [x] 7.5 `composer test` completo (config:clear + lint:check + types:check + suite completa) y confirmar que ninguno de los tests listados en la sección 5 se rompió. 638/638 pasan (4 skips preexistentes), Pint y PHPStan limpios. PHPStan encontró 3 usos de `?->` redundantes en `PreparacionEgresoPresenter.php` (no anticipados en el diseño) — corregidos.
- [x] 7.6 `npm run lint:check` y `npm run types:check`.
- [ ] 7.7 Verificación manual en navegador: abrir el detalle de un caso con tipo de proceso cuyo checklist resuelva a cero obligatorios (p. ej. Remesa), confirmar que "Checklist documental" muestra check verde con "Sin ítems obligatorios" (no "Sin checklist generado"), que el panel queda "4/4 completo", y que el botón "Crear Egreso CGU con este caso" aparece cuando corresponde (sin egreso asociado + permiso `pago_proveedores.registrar_egreso`). **No realizada**: requiere sesión autenticada real; el Browser pane no puede loguearse (no manejo credenciales). Servidores (`127.0.0.1:8000`, Vite, conector SGF) siguen corriendo de la sesión — pendiente de confirmación visual por el usuario.

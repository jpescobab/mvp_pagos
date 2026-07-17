## 1. `EgresoCguController::store()` → `EgresoCguCreador`

- [x] 1.1 Crear `app/Services/PagoProveedores/EgresoCguCreador.php`, constructor inyecta `RevisionEgresoService`.
- [x] 1.2 Método `crear(array $datosValidados, User $user): EgresoCgu` que encapsula la `DB::transaction` completa hoy en `EgresoCguController::store()`: cálculo de `monto_total`, creación del `EgresoCgu`, query+`keyBy` de casos, bucle de creación de ítems + `actualizarCfinancieroSiFalta` + `iniciarRevision`.
- [x] 1.3 `EgresoCguController::store()` queda: `$request->validated()`, `try { $this->egresoCguCreador->crear($datos, $request->user()); } catch (TransicionWorkflowException $e) { ... }`, `to_route(...)`.
- [x] 1.4 Test de feature (`CrearEgresoCguTest.php`, "crear un egreso CGU con varios casos suma sus montos en monto_total") cubriendo el cálculo de `monto_total` con múltiples casos; la creación de ítems, actualización de cfinanciero e inicio de revisión ya estaban cubiertos por tests existentes del mismo archivo (se ejercen a través de `EgresoCguCreador` sin cambio de comportamiento).
- [x] 1.5 Confirmado: los tests de feature existentes de `EgresoCguController::store()` (`CrearEgresoCguTest.php`) siguen pasando sin modificar sus aserciones.

## 2. `RequisitoDocumentalController` → `RequisitoDocumentalPagoProveedorService`

- [x] 2.1 Crear `app/Services/PagoProveedores/RequisitoDocumentalPagoProveedorService.php` con: `conjunto(): ConjuntoRequisitosDocumentales` (el `firstOrCreate` de `conjuntoPagoProveedores()`), `vigentes(): Collection` (el query de `index()`), `actualizar(TipoDocumento $tipoDocumento, ?int $tipoProcesoPagoId, ?string $tipoRequisito): void` (las tres ramas de `update()`).
- [x] 2.2 `RequisitoDocumentalController::index()` y `::update()` quedan reducidos a: autorizar, llamar al Service, renderizar/redirigir.
- [x] 2.3 Las tres ramas de `actualizar()` (borrar, actualizar, crear "Todos los tipos") ya están cubiertas end-to-end por `RequisitosDocumentalesMatrizTest.php`, que ejerce el Service a través del controller — no se duplicó con un test unitario adicional (proyecto: no testear dos veces lo mismo).
- [x] 2.4 Confirmado: los tests de feature existentes de `RequisitoDocumentalController` (`RequisitosDocumentalesMatrizTest.php`, `RequisitosDocumentalesPagoProveedoresMatrizTest.php`) siguen pasando sin modificar sus aserciones.

## 3. `RevisionPagosController::egresosEnRevision()` → `RevisionEgresoPresenter::listadoEnRevision()`

- [x] 3.1 Agregar `listadoEnRevision(User $user): Collection` a `RevisionEgresoPresenter`, moviendo la constante `ESTADOS_EN_REVISION`, el query `whereHas`, el filtro por Gate y el `map()` a `detalle()`.
- [x] 3.2 `RevisionPagosController::index()` y `::show()` llaman a `$this->presenter->listadoEnRevision($user)` en vez de al método privado eliminado.
- [x] 3.3 El filtro por estado y por permiso (Gate) ya está cubierto end-to-end por `RevisionPagosTest.php` (incluye el caso de jurisdicción/Gate) — no se duplicó con un test unitario adicional.
- [x] 3.4 Confirmado: los tests de feature existentes de `RevisionPagosController` (`RevisionPagosTest.php`) siguen pasando sin modificar sus aserciones.

## 4. Validación final

- [x] 4.1 `vendor/bin/pint --dirty --format agent` sobre los archivos tocados — passed.
- [x] 4.2 `composer test` (config:clear + lint:check + types:check + `php artisan test`) en verde — 597 tests (593 passed, 4 skipped preexistentes), Pint y PHPStan limpios.
- [x] 4.3 Revisión manual: ningún controller de la lista (`EgresoCguController`, `RequisitoDocumentalController`, `RevisionPagosController`) conserva `DB::transaction`, listas de códigos de estado hardcodeadas, o `app(...)` dentro de un método público.

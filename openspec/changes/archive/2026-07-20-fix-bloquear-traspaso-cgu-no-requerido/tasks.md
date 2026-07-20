## 1. Migración y modelo

- [x] 1.1 Crear migración `add_requiere_traspaso_cgu_to_tipos_proceso_pago_table`: `$table->boolean('requiere_traspaso_cgu')->default(true)->after('activo');` en `up()`, más `DB::table('tipos_proceso_pago')->where('codigo', 'REMESA')->update(['requiere_traspaso_cgu' => false]);` (vía query builder, no el modelo Eloquent). `down()` elimina la columna.
- [x] 1.2 `app/Models/TipoProcesoPago.php`: agregar `requiere_traspaso_cgu` a `$fillable` y a `casts()` como `'boolean'`.
- [x] 1.3 `app/Models/CasoPagoProveedor.php`: agregar método `requiereTraspasoCgu(): bool` (mismo idiom que `cfinancieroId()`): `return $this->proceso?->tipoProcesoPago->requiere_traspaso_cgu ?? true;`
- [x] 1.4 Correr `php artisan migrate` localmente y confirmar que el tipo `REMESA` real queda con `requiere_traspaso_cgu = false` (vía `database-query` de Laravel Boost).

## 2. Admin CRUD de Maestros (TipoProcesoPago)

- [x] 2.1 `app/Http/Requests/Maestros/StoreTipoProcesoPagoRequest.php` y `UpdateTipoProcesoPagoRequest.php`: agregar `'requiere_traspaso_cgu' => ['boolean']` a `rules()`.
- [x] 2.2 `app/Http/Resources/Maestros/TipoProcesoPagoResource.php`: agregar `'requiere_traspaso_cgu' => $this->requiere_traspaso_cgu` a `toArray()`.
- [x] 2.3 `resources/js/types/maestros.ts`: agregar `requiere_traspaso_cgu: boolean` a `TipoProcesoPagoMaestro`.
- [x] 2.4 `resources/js/pages/maestros/tipos-proceso-pago/create.tsx`: agregar `useState(true)` y un segundo `<Switch>` (mismo bloque que `activo`) con label "Requiere Traspaso (CGU)" y texto de ayuda "Desactiva esta opción para tipos de proceso que nunca generan un Traspaso (CGU), como Remesa. El formulario de registro quedará oculto en el detalle de esos casos."; incluir el campo en el payload de `router.post`.
- [x] 2.5 `resources/js/pages/maestros/tipos-proceso-pago/edit.tsx`: mismo `<Switch>` inicializado desde `tipoProcesoPago.requiere_traspaso_cgu`; incluir el campo en el payload de `router.patch`.
- [x] 2.6 `resources/js/pages/maestros/tipos-proceso-pago/show.tsx`: agregar fila `<dt>Requiere Traspaso (CGU)</dt><dd>{tipoProcesoPago.requiere_traspaso_cgu ? 'Sí' : 'No'}</dd>` en el `<dl>` junto a "Estado".

## 3. Backend: criterio de preparación y autorización

- [x] 3.1 `app/Services/PagoProveedores/PreparacionEgresoPresenter.php::traspasoCgu()`: agregar early-return cuando `! $caso->requiereTraspasoCgu()` retornando `cumplido: true`, `detalle: 'No requiere traspaso'`, antes del cálculo existente basado en `registrosContablesCgu`/`sgf_numero_traspaso`.
- [x] 3.2 `app/Policies/CasoPagoProveedorPolicy.php::registrarCgu()`: `return $user->can('pago_proveedores.registrar_cgu') && $caso->requiereTraspasoCgu();`. **Desviación del diseño original**: se descartó `Response::deny()` — `Gate::after()` en `AppServiceProvider::configureAuthorization()` (usado por las 17+ Policies de la app para auditar `acceso_denegado`) está tipado a `?bool $result`; un `Response` ahí produce un `TypeError` (500) en cada intento denegado. Ampliar ese hook global por un mensaje de UX en un solo endpoint no valía el riesgo sobre infraestructura compartida — se mantiene `bool` plano, igual que el resto de las Policies del repo.
- [x] 3.3 `app/Http/Resources/PagoProveedores/ProcesoResource.php`: dentro del array de `whenLoaded('tipoProcesoPago', ...)`, agregar `'requiere_traspaso_cgu' => $this->tipoProcesoPago->requiere_traspaso_cgu`.

## 4. Frontend: detalle del caso

- [x] 4.1 `resources/js/types/pago-proveedores.ts`: agregar `requiere_traspaso_cgu: boolean` al tipo `TipoProcesoPago` (el anidado en `Proceso.tipo_proceso_pago`); crear `TipoProcesoPagoSeleccionable` (`{id, codigo, nombre}`, sin el campo nuevo) para el array del dropdown de clasificación.
- [x] 4.2 `resources/js/pages/pago-proveedores/casos/show.tsx`: cambiar la anotación `PageProps.tiposProcesoPago` a `TipoProcesoPagoSeleccionable[]`; agregar `const requiereTraspasoCgu = caso.proceso.tipo_proceso_pago?.requiere_traspaso_cgu ?? true;` junto a `hayTraspaso`.
- [x] 4.3 Condicionar el bloque del formulario de corrección (hoy `{puedeRegistrarCgu && (...)}`) a `{puedeRegistrarCgu && requiereTraspasoCgu && (...)}`.
- [x] 4.4 Reestructurar el bloque de estado vacío/lista: cuando `!requiereTraspasoCgu`, mostrar siempre el mensaje "Este tipo de proceso no requiere Traspaso (CGU)." y, si existen registros previos, seguir mostrando la `<ul>` existente debajo sin cambios; cuando `requiereTraspasoCgu` es `true`, comportamiento actual intacto.

## 5. Tests

- [x] 5.1 `tests/Feature/Maestros/TipoProcesoPagoCrudTest.php`: crear sin especificar el campo → `requiere_traspaso_cgu = true`; crear con `requiere_traspaso_cgu: false` → persiste así; editar de `true` a `false` sin afectar código/nombre.
- [x] 5.2 `tests/Feature/PagoProveedores/PreparacionEgresoPresenterTest.php`: agregar helper `crearTipoProcesoPagoSinTraspaso(string $codigo): TipoProcesoPago`; test con tipo `requiere_traspaso_cgu = false` → `cumplido: true`, `detalle: 'No requiere traspaso'`, sin ningún registro; test de caso sin tipo clasificado sigue exigiendo traspaso (`?? true`).
- [x] 5.3 `tests/Feature/PagoProveedores/ListoParaEgresoResolverTest.php`: los 4 criterios completos con un tipo que no requiere traspaso y cero registros → `true`.
- [x] 5.4 `tests/Feature/PagoProveedores/RegistrarEvidenciaCguYPagoBancarioTest.php`: usuario con permiso `registrar_cgu` pero caso con tipo `requiere_traspaso_cgu = false` → `assertForbidden()`, sin registro creado, `SecurityAuditLog` con `acceso_denegado`.
- [x] 5.5 `tests/Feature/PagoProveedores/AccesoDirectoCrearEgresoDesdeDetalleCasoTest.php`: detalle de un caso con tipo `requiere_traspaso_cgu = false` — `assertInertia` confirma `proceso.tipo_proceso_pago.requiere_traspaso_cgu === false` y `preparacion_egreso` con `traspaso_cgu.cumplido === true`.

## 6. Validación final

- [x] 6.1 `vendor/bin/pint --dirty --format agent` sobre los archivos PHP tocados.
- [x] 6.2 `composer test` completo.
- [x] 6.3 `npm run lint:check` y `npm run types:check`.
- [ ] 6.4 Verificación manual en navegador: caso `sgf_id=779` (Remesa) sin formulario, con mensaje explicativo, con los 2 registros existentes visibles, y panel de preparación en 4/4; admin de Maestros del tipo Remesa con el switch nuevo desmarcado. **Omitida a pedido del usuario**: `pagos.test` no resuelve desde el panel del navegador (mismo problema de DNS/adaptadores de red diagnosticado antes en esta sesión, ajeno a este cambio). Cubierto en su lugar por el test `AccesoDirectoCrearEgresoDesdeDetalleCasoTest` que verifica contra el HTML/JSON real de la misma ruta (`proceso.tipo_proceso_pago.requiere_traspaso_cgu === false`, `preparacion_egreso.traspaso_cgu.cumplido === true`).

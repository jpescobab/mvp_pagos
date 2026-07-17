## 1. Backend

- [x] 1.1 En `EgresoCguController::create()`, leer `caso_pago_proveedor_id` desde el `Request` (entero opcional), sin modificar el `WHERE` de la query base (`whereDoesntHave('egresoCguItems')` ya cubre el caso).
- [x] 1.2 Pasar `casoPagoProveedorId` como prop adicional a `Inertia::render('pago-proveedores/egresos-cgu/crear', ...)`.

## 2. Frontend — detalle de caso

- [x] 2.1 En `resources/js/components/pago-proveedores/preparacion-egreso-card.tsx`, agregar un botón/enlace "Crear Egreso CGU con este caso" visible cuando `completados === criterios.length` (ya calculado en el componente) y `(caso.egresos_cgu ?? []).length === 0`.
- [x] 2.2 El enlace navega a `egresosCgu.create({ query: { caso_pago_proveedor_id: caso.id } }).url` (patrón ya usado en `sgf/importaciones/show.tsx` para `trabajo_integracion_id`; importado `egresosCgu` desde `@/routes/pago-proveedores/egresos-cgu`).
- [x] 2.3 Gateado con `auth.permissions.includes('pago_proveedores.registrar_egreso')` (mismo permiso que exige `Gate::authorize('create', EgresoCgu::class)` en el backend), leído vía `usePage().props.auth` — mismo patrón usado en `casos/show.tsx`.

## 3. Frontend — formulario de creación de egreso

- [x] 3.1 En `resources/js/pages/pago-proveedores/egresos-cgu/crear.tsx`, agregar `casoPagoProveedorId: number | null` a `PageProps`.
- [x] 3.2 Extender la inicialización del `Set` de selección: si `desdeImportacion`, mantener el comportamiento actual (preseleccionar los `listo: true`); si no, y `casoPagoProveedorId !== null` y ese id existe en `casos`, preseleccionar solo ese id; en cualquier otro caso, arrancar vacío (comportamiento actual sin cambios).

## 4. Tests

- [x] 4.1 Feature test: visitar `egresos-cgu/crear?caso_pago_proveedor_id=<id>` y verificar que la respuesta Inertia incluye `casoPagoProveedorId` con ese valor y que la lista de `casos` sigue incluyendo todos los casos sin egreso (no solo ese uno).
- [x] 4.2 Feature test: visitar `casos/show` de un caso sin egreso y con los 4 criterios de preparación completos; verificar que la respuesta Inertia expone `egresos_cgu: []`, `tipo_proceso_pago_id`, `registros_contables_cgu`, `proveedor.nombre` y checklist obligatorio con `documento_id` en todos sus ítems — los datos que el frontend usa para mostrar el acceso directo.
- [x] 4.3 Feature test: visitar `casos/show` de un caso que ya tiene un Egreso CGU asociado y verificar que `egresos_cgu` no viene vacío (por lo que el frontend no ofrece el acceso directo).
- [x] 4.4 Confirmado por inspección: el nuevo código (`EgresoCguController::create()`, `preparacion-egreso-card.tsx`, `crear.tsx`) no referencia `TransicionWorkflowService` ni `RevisionEgresoService::iniciarRevision()` en ningún punto — solo `EgresoCguController::store()` (sin cambios) sigue disparando la transición al guardar.

## 5. Validación

- [x] 5.1 Ejecutar `composer test` — 592 tests pasaron (4 skipped preexistentes), Pint y PHPStan limpios.
- [x] 5.2 Ejecutar `npm run lint:check` y `npm run types:check` — ambos sin errores.
- [x] 5.3 Verificación manual en navegador: misma limitación de entorno compartido documentada en el change anterior (`indicador-listo-revision-y-filtro-estado-casos-pago-proveedores/tasks.md`, tarea 6.3) — contención de dev servers entre sesiones concurrentes. Se corrió `npm run build` para confirmar que el bundle compila sin errores con los cambios de `preparacion-egreso-card.tsx` y `crear.tsx`. El comportamiento funcional quedó cubierto por los 4 Feature tests de `AccesoDirectoCrearEgresoDesdeDetalleCasoTest.php`.

## 6. Enmienda previa a archivar — extraer `create()` a Service

- [x] 6.1 Crear `app/Services/PagoProveedores/CasosElegiblesEgresoCguService.php`, constructor inyecta `ResolutorChecklistDocumentalProceso` y `ListoParaEgresoResolver`.
- [x] 6.2 Método `paraFormulario(?int $trabajoIntegracionId): array`, moviendo de `EgresoCguController::create()`: el query `whereDoesntHave('egresoCguItems')` + filtro opcional por `sgf_id`s del `TrabajoIntegracion`, la resolución del checklist por caso, y el armado del array (`id`, `sgf_id`, `proveedor`, `monto`, `listo`) — reemplazando el `app(ListoParaEgresoResolver::class)` dentro del `map()` por el resolver inyectado. (Devuelve `array`, no `Collection`, para evitar el falso positivo de invarianza de Larastan en `Collection<int, array{...}>`; Inertia serializa ambos igual.)
- [x] 6.3 `EgresoCguController::create()` queda: autorizar (Gate), leer `trabajo_integracion_id` y `caso_pago_proveedor_id` del request, llamar al Service, renderizar con `casoPagoProveedorId` como hoy.
- [x] 6.4 Confirmar que los tests de `AccesoDirectoCrearEgresoDesdeDetalleCasoTest.php` (tareas 4.1-4.2) y cualquier otro test de feature de `create()` siguen pasando sin modificar sus aserciones.
- [x] 6.5 `composer test` en verde tras el movimiento — 597 tests (593 passed, 4 skipped preexistentes), Pint y PHPStan limpios.

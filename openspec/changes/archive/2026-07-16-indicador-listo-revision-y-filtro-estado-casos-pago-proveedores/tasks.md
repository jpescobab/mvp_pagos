## 1. Backend — filtro de estado en el listado

- [x] 1.1 En `CasoPagoProveedorController::index()`, leer `estado` desde el `Request` (string opcional).
- [x] 1.2 Definir la constante de estados avanzados/finales excluidos por defecto (`lista_para_registro_cgu`, `registrada_en_cgu`, `lista_para_pago`, `pagada_bancoestado`, `asociada_a_egreso_cgu`, `cerrada`, `rechazada`, `anulada`) en el propio controlador o en un método estático de `CasoPagoProveedor` (según lo que quede más legible), evitando duplicarla en el frontend.
- [x] 1.3 Aplicar al query de `index()`: si no viene `estado` (o viene vacío), `whereHas('proceso.estadoActual', fn ($q) => $q->whereNotIn('codigo', [...]))`; si viene un código puntual, `whereHas('proceso.estadoActual', fn ($q) => $q->where('codigo', $estado))`; si viene el valor "todos", no aplicar filtro. Preservar `paginate(20)` y el resto del query existente (`with(['proveedor', 'proceso.estadoActual', ...])`).
- [x] 1.4 Pasar a la vista Inertia la lista de estados disponibles del workflow `pago_proveedores` (código + nombre, desde `DefinicionWorkflow`/`EstadoWorkflow`) y el filtro actualmente aplicado, para poblar el `<select>` sin hardcodear valores en TS.

## 2. Backend — indicador "Listo para revisar"

- [x] 2.1 En `CasoPagoProveedorResource`, agregar el campo `listo_para_aprobar` (booleano): `false` si el `Proceso` no está en `en_revision_finanzas`/`en_revision_zonal`; en caso contrario, resultado de `RevisionEgresoService::pagoListoParaAprobar($caso)` (resolver el servicio vía el container, p. ej. `app(RevisionEgresoService::class)`, siguiendo el patrón ya usado para servicios inyectados en resources si existe alguno, o inyectándolo si el resource ya recibe dependencias en este proyecto — revisar convención existente en otros Resources antes de decidir).
- [x] 2.2 Confirmar que `CasoPagoProveedorController::index()` sigue cargando las relaciones necesarias para que `pagoListoParaAprobar()` no dispare N+1 evidente (revisar qué relaciones toca `ValidacionDocumentoInstanciaService::todosAprobados()` y `RevisionEgresoService::totalesVerificados()`); si hace falta eager loading adicional, añadirlo al `with()` de `index()`.
- [x] 2.3 No tocar `TransicionWorkflowService`, `RevisionPagoInstancia`, `RevisionTotalesController` ni `revision/index.tsx` — el campo es de solo lectura derivado de servicios ya existentes.

## 3. Frontend — listado de casos

- [x] 3.1 En `resources/js/pages/pago-proveedores/casos/index.tsx`, agregar un `<select>` de filtro de estado poblado con los estados recibidos por props (incluida la opción "Todos"), reflejando el filtro actual desde la URL.
- [x] 3.2 Al cambiar el filtro, navegar con `router.get(casos.index.url(), { estado: valor }, { preserveState: true, preserveScroll: true, only: ['casos'] })` (o el patrón equivalente ya usado en el proyecto para filtros con Inertia), sin perder la página actual salvo que cambie el filtro.
- [x] 3.3 Agregar el indicador "Listo para revisar" junto a `<EstadoBadge estado={caso.proceso.estado_actual} compact />` cuando `caso.listoParaAprobar` (o el nombre serializado que use `CasoPagoProveedorResource`) sea `true`; usar un badge/token visual existente en el sistema de diseño (ver `tema-visual-layout` — badges semánticos `success`/`danger`), no un color ad-hoc.
- [x] 3.4 Actualizar el tipo TypeScript del caso (donde esté tipado `CasoPagoProveedor`/`Paginated<CasoPagoProveedor>`) para incluir el nuevo campo del resource.

## 4. Wayfinder y tipos

- [x] 4.1 No aplica: el controlador no agregó parámetros de ruta nuevos (la URL sigue siendo `/pago-proveedores/casos`, solo cambió qué hace el controlador con el querystring), y `resources/js/routes/pago-proveedores/casos/index.ts` ya acepta `RouteQueryOptions` genéricos — se verificó que `casos.index.url({ query: { estado } })`/`router.get` siguen funcionando sin regenerar.

## 5. Tests

- [x] 5.1 Feature test: visitar `/pago-proveedores/casos` sin filtro y verificar que la respuesta Inertia solo incluye casos en los estados no avanzados por defecto.
- [x] 5.2 Feature test: visitar `/pago-proveedores/casos?estado=todos` (o el valor elegido para "todos") y verificar que incluye también casos en estados avanzados/finales.
- [x] 5.3 Feature test: visitar `/pago-proveedores/casos?estado=en_revision_finanzas` y verificar que solo devuelve casos en ese estado.
- [x] 5.4 Feature/Unit test: un caso en `en_revision_finanzas` con checklist obligatorio aprobado y totales verificados para la instancia Finanzas expone `listo_para_aprobar: true` en el resource/página; un caso que falte cualquiera de las dos condiciones expone `false`.
- [x] 5.5 Feature/Unit test: un caso fuera de `en_revision_finanzas`/`en_revision_zonal` (p. ej. `importada_desde_sgf` o `lista_para_pago`) siempre expone `listo_para_aprobar: false`, sin ejecutar innecesariamente `pagoListoParaAprobar()` si se decide hacer ese guard explícito.
- [x] 5.6 Confirmar (test o inspección manual) que ningún test ni código de este change invoca `TransicionWorkflowService::execute()` — el indicador no debe cambiar `estado_actual_id`. Verificado por inspección: `CasoPagoProveedorController::index()` y `CasoPagoProveedorResource::listoParaAprobar()` no referencian `TransicionWorkflowService` en ningún punto.

## 6. Validación

- [x] 6.1 Ejecutar `composer test` (incluye `config:clear`, `lint:check`, `types:check`, `php artisan test`) — 588 tests pasaron (4 skipped preexistentes), Pint y PHPStan limpios.
- [x] 6.2 Ejecutar `npm run lint:check` y `npm run types:check` — ambos sin errores.
- [x] 6.3 Verificación manual en navegador: bloqueada por el entorno compartido (4 sesiones concurrentes ya usando los 5 slots de dev server de la carpeta, y un `public/hot` de otra sesión que redirige los assets a un Vite dev server inalcanzable desde este navegador). Se corrió `npm run build` para confirmar que el bundle de producción compila sin errores con los cambios de `casos/index.tsx` y el nuevo componente `ListoParaRevisarBadge`. El comportamiento funcional (filtro por defecto, `estado=todos`, filtro puntual, y `listo_para_aprobar` en sus tres casos: verdadero, falso por totales sin verificar, falso por estar fuera de revisión) quedó cubierto por los 6 Feature tests de `FiltroYListoParaRevisarCasosTest.php`, que inspeccionan las props Inertia reales devueltas por el controlador — no se pudo complementar con una captura de pantalla por la contención de entorno.

## 7. Enmienda previa a archivar — extraer a Service

- [x] 7.1 Crear `app/Services/PagoProveedores/ListadoCasoPagoProveedorService.php` con `paginar(?string $estadoFiltro, int $porPagina = 20): LengthAwarePaginator`, moviendo la constante de estados excluidos por defecto, el `with([...])` y los dos `whereHas` de `CasoPagoProveedorController::index()`.
- [x] 7.2 `CasoPagoProveedorController::index()` queda: autorizar (Gate), leer `estado` del request, llamar al Service, pasar el resultado al Resource y a la vista Inertia.
- [x] 7.3 Confirmar que los tests de `FiltroYListoParaRevisarCasosTest.php` (tareas 5.1-5.3) siguen pasando sin modificar sus aserciones.
- [x] 7.4 `composer test` en verde tras el movimiento — 597 tests (593 passed, 4 skipped preexistentes), Pint y PHPStan limpios.

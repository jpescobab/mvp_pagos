## 1. Refactor del formulario de alta

- [x] 1.1 Extraer `resources/js/components/maestros/proveedor-formulario.tsx` desde `create.tsx`: mismo JSX/estado, parametrizado por `modo: 'crear' | 'editar'`, `catalogos`, `valoresIniciales?`, `accionUrl`, `metodoHttp: 'post' | 'patch'`.
- [x] 1.2 Reescribir `create.tsx` como wrapper delgado que usa `ProveedorFormulario` con `modo="crear"`.
- [x] 1.3 Confirmar que `StoreProveedorTest` sigue pasando sin cambios tras el refactor.

## 2. Backend: ver y editar

- [x] 2.1 Extender `ProveedorPolicy` con `view(User $user, Proveedor $proveedor): bool` y `update(User $user, Proveedor $proveedor): bool`, ambas sobre `core_institucional.administrar`.
- [x] 2.2 `ProveedorController::show(Proveedor $proveedor)`: `Gate::authorize('view', ...)`, retorna Inertia con el proveedor completo (`ProveedorResource`) y si tiene documento de respaldo (`documento_respaldo_path !== null`, sin exponer la ruta).
- [x] 2.3 `App\Http\Requests\Maestros\UpdateProveedorRequest`: mismas reglas que `StoreProveedorRequest`, con `rutproveedor` único excluyendo el propio registro.
- [x] 2.4 `ProveedorController::edit(Proveedor $proveedor)`: `Gate::authorize('update', ...)`, retorna Inertia con el proveedor, los mismos catálogos que `create()` y si tiene documento de respaldo.
- [x] 2.5 `ProveedorController::update(UpdateProveedorRequest $request, Proveedor $proveedor)`: valida, si llega un documento nuevo borra el anterior (si existía) antes de guardar el nuevo, actualiza el proveedor en una transacción, redirige al detalle con mensaje flash de éxito.
- [x] 2.6 Rutas `GET /maestros/proveedores/{proveedor}/editar` y `PATCH /maestros/proveedores/{proveedor}` en `routes/maestros.php`.

## 3. Backend: eliminar

- [x] 3.1 Extender `ProveedorPolicy` con `delete(User $user, Proveedor $proveedor): bool` sobre `core_institucional.administrar`.
- [x] 3.2 `ProveedorController::destroy(Proveedor $proveedor)`: `Gate::authorize('delete', ...)`, verifica existencia en `clientes_medidores`, `casos_pago_proveedor`, `facturas` y `procesos_adquisicion` por `proveedor_id`; si existe alguna, redirige de vuelta con un error indicando cuál lo impide; si no, hace soft delete y redirige al índice con mensaje flash de éxito.
- [x] 3.3 Ruta `DELETE /maestros/proveedores/{proveedor}` en `routes/maestros.php`.

## 4. Frontend

- [x] 4.1 Rutas GET/PATCH/DELETE en `routes/maestros.php` regeneradas en Wayfinder (`php artisan wayfinder:generate --with-form`).
- [x] 4.2 `resources/js/pages/maestros/proveedores/show.tsx`: secciones de solo lectura (Identificación, Clasificación, Contacto, Domicilio, Datos bancarios, Notas internas), indicador de documento de respaldo, breadcrumbs, botones Editar/Volver.
- [x] 4.3 `resources/js/pages/maestros/proveedores/edit.tsx`: usa `ProveedorFormulario` con `modo="editar"` y los valores iniciales del proveedor.
- [x] 4.4 Reescribir `resources/js/components/maestros/proveedor-actions-menu.tsx`: recibe el `Proveedor`, habilita "Ver detalle" (navega a `show`) y "Editar" (navega a `edit`), agrega "Eliminar" con `Dialog` de confirmación y `router.delete()` (patrón de `seguridad/roles/index.tsx`); el error de relaciones activas llega vía el mecanismo de toast (`Inertia::flash('toast', ...)`) ya usado en el resto de la app, no como texto embebido en el diálogo.
- [x] 4.5 Actualizar `resources/js/pages/maestros/proveedores/index.tsx` para pasar el proveedor completo a `ProveedorActionsMenu`.

## 5. Validación y documentación

- [x] 5.1 Tests Feature: `ShowProveedorTest` (ver detalle, sin permiso), `UpdateProveedorTest` (edición exitosa, RUT en conflicto, RUT propio sin conflicto, reemplazo de documento, sin permiso), `DestroyProveedorTest` (eliminación exitosa, rechazo por cada tipo de relación, sin permiso).
- [x] 5.2 `vendor/bin/pint --dirty`, `npm run lint:check`, `npm run format:check`, `npm run types:check`, `composer types:check`, `php artisan test --compact`.
- [x] 5.3 Verificado en el navegador: detalle completo, edición precargada (incluye corrección de un bug real encontrado durante la verificación — ver Nota), envío de edición con toast de éxito. La eliminación vía UI se validó por equivalencia de patrón (mismo `DropdownMenu`+`Dialog` que `seguridad/roles/index.tsx`, ya en producción) más la cobertura completa de `DestroyProveedorTest`.
- [x] 5.4 Sincronizar la spec delta en `openspec/specs/registrar-proveedor/spec.md` y archivar el change.

**Nota (bug encontrado y corregido durante 5.3):** el campo `banco` no descartaba el valor centinela "sin seleccionar" al enviar el formulario (a diferencia de `tipo_contribuyente`/`tipo_cuenta`, que sí lo hacían), guardando literalmente el string interno en vez de `null` cuando el usuario no tocaba el selector de banco. Corregido en `proveedor-formulario.tsx` (`bancoFinal`).

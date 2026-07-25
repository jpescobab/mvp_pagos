## 1. Service (lógica de negocio)

- [x] 1.1 En `app/Services/InformesRazonados/InformeRazonadoService.php`, agregar `editarSeccion(SeccionInformeRazonado $seccion, string $titulo, int $orden): SeccionInformeRazonado` (actualiza `titulo` y `orden`, retorna la sección refrescada).
- [x] 1.2 En el mismo service, agregar `eliminarSeccion(SeccionInformeRazonado $seccion): void` (elimina la sección; el `cascadeOnDelete` del esquema borra su contenido asignado). Confirmar que `agregarSeccion()` existente cubre crear sin cambios.

## 2. Autorización (Policy)

- [x] 2.1 Crear `app/Policies/SeccionInformeRazonadoPolicy.php` gemela de `NarrativaInformeRazonadoPolicy`: `create(User, EjecucionInformeRazonado): bool` → `informes.elaborar` **y** `$ejecucion->estaEnElaboracion()`; `update(User, SeccionInformeRazonado): bool` y `delete(User, SeccionInformeRazonado): bool` → `informes.elaborar` **y** la ejecución de la sección en `en_elaboracion` (helper privado que lee `seccion->ejecucionInformeRazonado?->estaEnElaboracion()`).
- [x] 2.2 Registrar la policy en `app/Providers/AppServiceProvider.php::configureAuthorization()` con `Gate::policy(SeccionInformeRazonado::class, SeccionInformeRazonadoPolicy::class)` (importar modelo y policy en orden alfabético con los demás `use`).

## 3. Form Request (validación + authorize)

- [x] 3.1 Crear `app/Http/Requests/InformesRazonados/GuardarSeccionInformeRazonadoRequest.php`: `authorize()` exige `informes.elaborar`; `rules()` valida `codigo` (required, string, max:255), `titulo` (required, string, max:255) y `orden` (nullable, integer, min:0). Reutilizable para `store` y `update`.

## 4. Controlador (liviano) + rutas

- [x] 4.1 Crear `app/Http/Controllers/InformesRazonados/SeccionInformeRazonadoController.php` con `store(EjecucionInformeRazonado $ejecucion, GuardarSeccionInformeRazonadoRequest $request)`, `update(SeccionInformeRazonado $seccion, GuardarSeccionInformeRazonadoRequest $request)`, `destroy(SeccionInformeRazonado $seccion)`. Cada método llama `Gate::authorize(...)` con la Policy y delega en `InformeRazonadoService` (`agregarSeccion`/`editarSeccion`/`eliminarSeccion`); retorna `back()`. Sin lógica de negocio en el controlador (extraer `orden` con default 0: `$request->integer('orden')`).
- [x] 4.2 En `routes/informes-razonados.php`, agregar: `POST ejecuciones/{ejecucion}/secciones` → `store` (`ejecuciones.secciones.store`); `PATCH secciones/{seccion}` → `update` (`secciones.update`); `DELETE secciones/{seccion}` → `destroy` (`secciones.destroy`).
- [x] 4.3 Regenerar Wayfinder: `php artisan wayfinder:generate --with-form`.

## 5. Tipos TS

- [x] 5.1 En `resources/js/types/informes-razonados.ts`, verificar el tipo `SeccionInformeRazonado` (`id`, `codigo`, `titulo`, `orden`) y extenderlo si falta algún campo que la UI use. `EjecucionInformeRazonado.secciones?` ya existe.

## 6. UI (gestión de secciones + narrativa por sección + agrupación)

- [x] 6.1 En `resources/js/pages/informes-razonados/ejecuciones/show.tsx`, agregar una sección "Secciones": cuando `ejecucion.editable` y el usuario tiene `informes.elaborar`, listar las secciones (ordenadas por `orden`) con botones editar (inline: `titulo` + `orden`, `PATCH secciones.update`) y eliminar (`DELETE secciones.destroy` con `window.confirm` que ADVIERTE que también se eliminan las narrativas/métricas/gráficos de la sección), y un formulario para agregar sección (`codigo`, `titulo`, `orden` → `POST ejecuciones.secciones.store`).
- [x] 6.2 En el formulario de "agregar narrativa" existente, agregar un `<select>` opcional de sección (opciones = `ejecucion.secciones`), enviando `seccion_informe_razonado_id` en el `router.post` de crear narrativa.
- [x] 6.3 Reemplazar la lista plana de narrativas por una agrupación por sección: por cada sección (ordenada por `orden`) su encabezado (`titulo`) y sus narrativas (`narrativas` con `seccion_informe_razonado_id === seccion.id`), más un grupo "Sin sección" para las de `seccion_informe_razonado_id` nulo. Conservar los controles de autoría/revisión de narrativa ya existentes en cada ítem.

## 7. Tests

- [x] 7.1 Crear `tests/Feature/InformesRazonados/ElaborarSeccionesInformeRazonadoTest.php`, reusando los helpers globales del directorio (`corteReportabilidadDePrueba`, `definicionInformeRazonadoDePrueba`, `ejecucionEnElaboracionDePrueba`).
- [x] 7.2 Casos de autoría de sección: crear con `informes.elaborar` en `en_elaboracion` funciona; sin permiso 403; editar y eliminar con permiso funcionan; crear/editar/eliminar cuando la ejecución NO está `en_elaboracion` (mover a `en_revision`) da 403 y no altera datos.
- [x] 7.3 Caso cascada: crear una sección + una narrativa asignada a ella (`agregarNarrativa` con la sección), eliminar la sección vía la ruta `destroy`, y afirmar que la sección Y la narrativa asignada se eliminaron (`NarrativaInformeRazonado` count 0), mientras una narrativa sin sección de la misma ejecución permanece.
- [x] 7.4 Caso narrativa→sección: crear una narrativa vía la ruta `ejecuciones.narrativas.store` con `seccion_informe_razonado_id` de una sección de la misma ejecución la asocia; con una sección de OTRA ejecución el `exists` scoped la rechaza (422).

## 8. Validación final

- [x] 8.1 `vendor/bin/pint --dirty --format agent`.
- [x] 8.2 `php artisan test --compact tests/Feature/InformesRazonados/` (suite del dominio completa, por el gotcha de helpers cross-archivo de Pest).
- [x] 8.3 `npm run types:check` y `npm run lint:check`.
- [x] 8.4 Revisar el controlador contra la regla de "controladores livianos": ningún `where`/`whereHas`, `DB::transaction` ni ramas de negocio en el controlador — todo en el service.

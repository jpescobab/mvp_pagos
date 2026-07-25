## 1. Service (lógica de negocio)

- [x] 1.1 En `app/Services/InformesRazonados/InformeRazonadoService.php`, agregar `editarExcepcion(ExcepcionInformeRazonado $excepcion, string $descripcion, string $severidad): ExcepcionInformeRazonado` (actualiza `descripcion` y `severidad`, retorna la excepción refrescada).
- [x] 1.2 En el mismo service, agregar `eliminarExcepcion(ExcepcionInformeRazonado $excepcion): void`. Confirmar que `agregarExcepcion()` existente cubre crear sin cambios.

## 2. Autorización (Policy)

- [x] 2.1 Crear `app/Policies/ExcepcionInformeRazonadoPolicy.php` gemela de `NarrativaInformeRazonadoPolicy`: `create(User, EjecucionInformeRazonado): bool` → `informes.elaborar` **y** `$ejecucion->estaEnElaboracion()`; `update`/`delete(User, ExcepcionInformeRazonado): bool` → `informes.elaborar` **y** la ejecución de la excepción en `en_elaboracion` (helper privado que lee `excepcion->ejecucionInformeRazonado?->estaEnElaboracion()`).
- [x] 2.2 Registrar la policy en `app/Providers/AppServiceProvider.php::configureAuthorization()` con `Gate::policy(ExcepcionInformeRazonado::class, ExcepcionInformeRazonadoPolicy::class)` (importar modelo y policy en orden alfabético con los demás `use`).

## 3. Form Request (validación + authorize)

- [x] 3.1 Crear `app/Http/Requests/InformesRazonados/GuardarExcepcionInformeRazonadoRequest.php`: `authorize()` exige `informes.elaborar`; `rules()`: `codigo` `required` al crear / `sometimes` al editar (detectar por `$this->route('excepcion')`, como en secciones), `descripcion` (required, string), `severidad` (required, `in:info,advertencia,critico`). Reutilizable para `store` y `update`.

## 4. Controlador (liviano) + rutas

- [x] 4.1 Crear `app/Http/Controllers/InformesRazonados/ExcepcionInformeRazonadoController.php` con `store(EjecucionInformeRazonado $ejecucion, GuardarExcepcionInformeRazonadoRequest $request)`, `update(ExcepcionInformeRazonado $excepcion, GuardarExcepcionInformeRazonadoRequest $request)`, `destroy(ExcepcionInformeRazonado $excepcion)`. Cada método llama `Gate::authorize(...)` con la Policy y delega en `InformeRazonadoService` (`agregarExcepcion`/`editarExcepcion`/`eliminarExcepcion`); retorna `back()`. Sin lógica de negocio en el controlador.
- [x] 4.2 En `routes/informes-razonados.php`, agregar: `POST ejecuciones/{ejecucion}/excepciones` → `store` (`ejecuciones.excepciones.store`); `PATCH excepciones/{excepcion}` → `update` (`excepciones.update`); `DELETE excepciones/{excepcion}` → `destroy` (`excepciones.destroy`).
- [x] 4.3 Regenerar Wayfinder: `php artisan wayfinder:generate --with-form`.

## 5. Tipos TS

- [x] 5.1 En `resources/js/types/informes-razonados.ts`, verificar el tipo `ExcepcionInformeRazonado` (`id`, `codigo`, `descripcion`, `severidad`) y extenderlo si falta algún campo que la UI use. `EjecucionInformeRazonado.excepciones?` ya existe.

## 6. UI (sección Excepciones editable)

- [x] 6.1 En `resources/js/pages/informes-razonados/ejecuciones/show.tsx`, hacer editable la sección "Excepciones": cuando `ejecucion.editable` y el usuario tiene `informes.elaborar`, permitir editar (inline: `descripcion` + `severidad`, `PATCH excepciones.update`) y eliminar (`DELETE excepciones.destroy` con `window.confirm`) cada excepción, y un formulario para agregar (`codigo`, `descripcion`, `severidad` → `POST ejecuciones.excepciones.store`). Reusar el patrón de la sección de narrativas (Button `size="sm"` `variant="outline"`, inputs/textarea/select con las clases ya usadas, `router.post/patch/delete` con `preserveScroll`).
- [x] 6.2 Mostrar la severidad con un badge por nivel (`info` neutro, `advertencia` ámbar, `critico` rojo) con tokens semánticos del tema.

## 7. Tests

- [x] 7.1 Crear `tests/Feature/InformesRazonados/ElaborarExcepcionesInformeRazonadoTest.php`, reusando los helpers globales del directorio (`corteReportabilidadDePrueba`, `definicionInformeRazonadoDePrueba`, `ejecucionEnElaboracionDePrueba`) — NO redeclararlos.
- [x] 7.2 Casos de autoría: crear con `informes.elaborar` en `en_elaboracion` funciona; sin permiso 403; editar y eliminar con permiso funcionan; crear/editar/eliminar cuando la ejecución NO está `en_elaboracion` (mover a `en_revision`) da 403 y no altera datos.
- [x] 7.3 Caso validación: crear/editar con una `severidad` inválida es rechazado (`assertSessionHasErrors('severidad')`) y no crea/modifica; crear con severidad válida distinta del default (`critico`) la persiste.

## 8. Validación final

- [x] 8.1 `vendor/bin/pint --dirty --format agent`.
- [x] 8.2 `php artisan test --compact tests/Feature/InformesRazonados/` (suite del dominio completa, por el gotcha de helpers cross-archivo de Pest).
- [x] 8.3 `npm run types:check` y `npm run lint:check`.
- [x] 8.4 Revisar el controlador contra la regla de "controladores livianos": ningún `where`/`whereHas`, `DB::transaction` ni ramas de negocio — todo en el service.

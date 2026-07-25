## 1. Service (lógica de negocio)

- [x] 1.1 En `app/Services/InformesRazonados/InformeRazonadoService.php`, agregar `editarNarrativa(NarrativaInformeRazonado $narrativa, string $contenido): NarrativaInformeRazonado` (actualiza `contenido`, retorna la narrativa refrescada).
- [x] 1.2 En el mismo service, agregar `eliminarNarrativa(NarrativaInformeRazonado $narrativa): void` (elimina la narrativa). Confirmar que `agregarNarrativa()` y `revisarNarrativa()` existentes cubren crear y revisar sin cambios (la firma de `agregarNarrativa` ya acepta `contenido`, `generado_por_ia`, `seccion`).

## 2. Autorización (Policy)

- [x] 2.1 Crear `app/Policies/NarrativaInformeRazonadoPolicy.php` con: `create(User $user, EjecucionInformeRazonado $ejecucion): bool` → `$user->can('informes.elaborar')` **y** ejecución en estado `en_elaboracion`; `update(User, NarrativaInformeRazonado): bool` y `delete(User, NarrativaInformeRazonado): bool` → `informes.elaborar` **y** la ejecución de la narrativa en `en_elaboracion`; `revisar(User, NarrativaInformeRazonado): bool` → `informes.aprobar`. Encapsular la comprobación de estado en un helper privado que lea `narrativa->ejecucionInformeRazonado->proceso->estadoActual->codigo === 'en_elaboracion'` (cargando la relación si hace falta).
- [x] 2.2 Registrar la policy a mano en `app/Providers/AppServiceProvider.php::configureAuthorization()` con `Gate::policy(NarrativaInformeRazonado::class, NarrativaInformeRazonadoPolicy::class)` (junto a las policies del módulo ya registradas).

## 3. Form Requests (validación + authorize)

- [x] 3.1 Crear `app/Http/Requests/InformesRazonados/GuardarNarrativaInformeRazonadoRequest.php`: `authorize()` exige `informes.elaborar`; `rules()` valida `contenido` (required, string, min:1), `generado_por_ia` (boolean, opcional, default false) y `seccion_informe_razonado_id` (nullable, integer, `exists` contra secciones de esa ejecución). Reutilizable para `store` y `update` (en `update`, `contenido` sigue required).
- [x] 3.2 Crear `app/Http/Requests/InformesRazonados/RevisarNarrativaInformeRazonadoRequest.php`: `authorize()` exige `informes.aprobar`; `rules()` vacío (no hay body).

## 4. Controlador (liviano) + rutas

- [x] 4.1 Crear `app/Http/Controllers/InformesRazonados/NarrativaInformeRazonadoController.php` con `store(EjecucionInformeRazonado $ejecucion, GuardarNarrativaInformeRazonadoRequest $request)`, `update(NarrativaInformeRazonado $narrativa, GuardarNarrativaInformeRazonadoRequest $request)`, `destroy(NarrativaInformeRazonado $narrativa)`, `revisar(NarrativaInformeRazonado $narrativa, RevisarNarrativaInformeRazonadoRequest $request)`. Cada método llama `Gate::authorize(...)` con la Policy y delega en `InformeRazonadoService` (`agregarNarrativa`/`editarNarrativa`/`eliminarNarrativa`/`revisarNarrativa`); retorna `back()`. Sin lógica de negocio en el controlador.
- [x] 4.2 En `routes/informes-razonados.php`, dentro del grupo existente, agregar: `POST ejecuciones/{ejecucion}/narrativas` → `store` (`ejecuciones.narrativas.store`); `PATCH narrativas/{narrativa}` → `update` (`narrativas.update`); `DELETE narrativas/{narrativa}` → `destroy` (`narrativas.destroy`); `POST narrativas/{narrativa}/revisar` → `revisar` (`narrativas.revisar`).
- [x] 4.3 Regenerar Wayfinder: `php artisan wayfinder:generate --with-form`.

## 5. Recurso + tipos (detalle enriquecido)

- [x] 5.1 En `app/Http/Resources/InformesRazonados/EjecucionInformeRazonadoResource.php`: agregar `'editable' => $this->whenLoaded('proceso', fn () => $this->proceso->estadoActual?->codigo === 'en_elaboracion')`; en `mapNarrativas()` agregar `seccion_informe_razonado_id`, `revisado_en`, y `revisado_por` (nombre, vía relación `revisadoPor`).
- [x] 5.2 En `EjecucionInformeRazonadoController::show`, agregar `narrativas.revisadoPor` al `load(...)` para que el nombre del revisor esté disponible.
- [x] 5.3 En `resources/js/types/informes-razonados.ts`: extender `NarrativaInformeRazonado` con `seccion_informe_razonado_id: number | null`, `revisado_por: string | null` y agregar `editable: boolean` al tipo de la ejecución.

## 6. UI (autoría + revisión en el detalle)

- [x] 6.1 En `resources/js/pages/informes-razonados/ejecuciones/show.tsx`, extender la sección "Narrativas": cuando `ejecucion.editable` y el usuario tiene `informes.elaborar` (leído de `auth.permissions` vía `usePage`), mostrar un formulario para agregar narrativa (textarea + guardar → `POST` a `ejecuciones.narrativas.store`), y por cada narrativa botones editar (inline, `PATCH narrativas.update`) y eliminar (`DELETE narrativas.destroy`, con confirmación).
- [x] 6.2 En la misma sección, cuando el usuario tiene `informes.aprobar`, mostrar por cada narrativa no revisada un control "Marcar revisada" (`POST narrativas.revisar`); las revisadas muestran `revisado_por` y `revisado_en`. Usar los helpers Wayfinder con named imports y `router.post/patch/delete` con `preserveScroll`.

## 7. Tests

- [x] 7.1 Crear `tests/Feature/InformesRazonados/ElaborarNarrativasInformeRazonadoTest.php`. Helper local para crear una ejecución en un estado dado (definición + corte publicado + `iniciarEjecucion`, moviendo el proceso si el test necesita otro estado).
- [x] 7.2 Casos de autoría: agregar narrativa con `informes.elaborar` en `en_elaboracion` crea la fila (200/redirect); sin el permiso da 403; editar y eliminar con permiso funcionan; agregar/editar/eliminar cuando la ejecución NO está `en_elaboracion` (p. ej. `en_revision`) da 403 y no altera datos.
- [x] 7.3 Casos de revisión: marcar revisada con `informes.aprobar` setea `revisado_por` (usuario) y `revisado_en`; sin el permiso da 403 y la narrativa queda sin revisar.
- [x] 7.4 Caso de recurso: el `show` expone `editable=true` para una ejecución en `en_elaboracion` y `editable=false` para una en otro estado, y las narrativas incluyen `revisado_en`/`revisado_por`.

## 8. Validación final

- [x] 8.1 `vendor/bin/pint --dirty --format agent` (formato PHP).
- [x] 8.2 `php artisan test --compact tests/Feature/InformesRazonados/` (suite del dominio, por el gotcha de helpers cross-archivo de Pest).
- [x] 8.3 `npm run types:check` y `npm run lint:check` (TS/ESLint tras tocar la página y los tipos).
- [x] 8.4 Revisar el controlador contra la regla de "controladores livianos": ningún `where`/`whereHas` de negocio, `DB::transaction`, ni ramas de negocio en el controlador — todo en el service.

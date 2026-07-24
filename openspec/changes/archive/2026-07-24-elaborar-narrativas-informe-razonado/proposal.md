## Why

Una `ejecucion_informe_razonado` nace vacía en el estado inicial `en_elaboracion` (`InformeRazonadoService::iniciarEjecucion` solo crea la ejecución y su `Proceso`, sin contenido) y **no existe forma de elaborar su contenido desde la app**: los métodos `agregarNarrativa()` y `revisarNarrativa()` ya viven en el service pero no están expuestos por ninguna ruta, controlador ni UI (solo se usan en tests). El estado se llama `en_elaboracion` pero no hay nada con qué elaborar, y el permiso `informes.elaborar` —recién creado en el change `2026-07-24-autorizar-elaboracion-informes-razonados`— hoy solo gatea *iniciar* una ejecución vacía. La narrativa es la capa claramente humana del informe razonado (el "razonamiento" que acompaña las cifras del corte), y `revisarNarrativa` es la pieza de "revisión humana antes de publicar" que exige el harness. Este change cierra ese hueco para la narrativa, dejándola como el primer contenido elaborable de una ejecución.

## What Changes

- **Autoría de narrativas de una ejecución** desde la UI: crear, editar y eliminar `narrativa_informe_razonado` (`contenido`, `generado_por_ia`, `seccion_informe_razonado_id` opcional) **únicamente mientras la ejecución está en `en_elaboracion`**. Una vez enviada a revisión / aprobada / publicada, el contenido no se toca (al publicar se congela vía `snapshot_informe_razonado`). Gated por el permiso ya existente `informes.elaborar`.
- **Revisión humana de una narrativa** como acción separada de la autoría: marcar una narrativa como revisada (`revisarNarrativa` setea `revisado_por` + `revisado_en`). Gated por el permiso ya existente `informes.aprobar` (es parte de la revisión, gemelo de `aprobar`/`rechazar`).
- **`InformeRazonadoService`**: se agregan `editarNarrativa(NarrativaInformeRazonado, string $contenido)` y `eliminarNarrativa(NarrativaInformeRazonado)`; se reutilizan los ya existentes `agregarNarrativa()` y `revisarNarrativa()`. El controlador nuevo es liviano y delega toda la lógica en el service.
- **Autorización**: nueva `NarrativaInformeRazonadoPolicy` (o gates equivalentes) registrada a mano en `AppServiceProvider::configureAuthorization()` con `Gate::policy(...)` (no hay auto-discovery); `create`/`update`/`delete` exigen `informes.elaborar` **y** que la ejecución esté `en_elaboracion`; `revisar` exige `informes.aprobar`. Validación vía Form Requests (`authorize()` + `rules()`), no en el controlador.
- **Rutas** nuevas en `routes/informes-razonados.php` bajo el prefijo/nombre `ejecuciones` (crear narrativa colgada de la ejecución; editar/eliminar/revisar por narrativa); Wayfinder regenerado.
- **UI**: se extiende `resources/js/pages/informes-razonados/ejecuciones/show.tsx` (hoy la sección "Narrativas" es de solo lectura) con controles para agregar/editar/eliminar narrativas cuando la ejecución está `en_elaboracion` y el usuario tiene `informes.elaborar`, y un control "marcar revisada" cuando tiene `informes.aprobar`. Gating de UI con `auth.permissions`. Se enriquece `EjecucionInformeRazonadoResource` (campos de narrativa: `id`, `contenido`, `generado_por_ia`, `seccion_informe_razonado_id`, `revisado_en`, `revisado_por`) y se indica al frontend si la ejecución es editable (estado `en_elaboracion`). Tipos TS actualizados.
- **NO se crean permisos nuevos**: se reutilizan `informes.elaborar` e `informes.aprobar` que ya siembra `WorkflowInformesRazonadosSeeder`. **NO se toca** `RolesAndPermissionsSeeder` ni su test (permisos core).

**No cambia**: el workflow de la ejecución (transiciones, `TransicionWorkflowService`, snapshots al publicar), los permisos `informes.ver`/`informes.administrar`/`informes.publicar`, la naturaleza activable del módulo, ni las demás piezas de contenido (secciones, métricas, gráficos, excepciones) que quedan para changes posteriores. Marcar una narrativa como revisada **no** es una transición de workflow: es una anotación de contenido, por eso no pasa por `TransicionWorkflowService`.

## Capabilities

### New Capabilities

_(ninguna)_

### Modified Capabilities

- `gestionar-informes-razonados`: se agrega el requirement "Elaborar las narrativas de una ejecución de informe razonado" (crear/editar/eliminar narrativas en estado `en_elaboracion` con `informes.elaborar`) y el requirement "Marcar como revisada una narrativa de una ejecución" (con `informes.aprobar`). El requirement existente "Mostrar el detalle completo de una ejecución de informe razonado" pasa a documentar que el detalle indica si la ejecución es editable (estado `en_elaboracion`) y expone, por cada narrativa, si ya fue revisada y por quién.

## Impact

**Código modificado**

- `app/Services/InformesRazonados/InformeRazonadoService.php`: nuevos `editarNarrativa()` y `eliminarNarrativa()`.
- `app/Http/Resources/InformesRazonados/EjecucionInformeRazonadoResource.php`: `mapNarrativas()` enriquecido + flag `editable` de la ejecución.
- `routes/informes-razonados.php`: rutas de narrativas.
- `app/Providers/AppServiceProvider.php`: `Gate::policy(NarrativaInformeRazonado::class, NarrativaInformeRazonadoPolicy::class)`.
- `resources/js/pages/informes-razonados/ejecuciones/show.tsx`: controles de autoría/revisión de narrativas.
- `resources/js/types/informes-razonados.ts`: tipos de narrativa.
- `resources/js/routes/informes-razonados/**` / `resources/js/actions/**`: regenerados por Wayfinder.

**Código nuevo**

- `app/Http/Controllers/InformesRazonados/NarrativaInformeRazonadoController.php` (liviano; `store`, `update`, `destroy`, `revisar`).
- `app/Http/Requests/InformesRazonados/{GuardarNarrativaInformeRazonadoRequest, RevisarNarrativaInformeRazonadoRequest}.php`.
- `app/Policies/NarrativaInformeRazonadoPolicy.php`.
- `tests/Feature/InformesRazonados/ElaborarNarrativasInformeRazonadoTest.php` (o equivalente).

**Sin impacto en**: el workflow de ejecuciones (`TransicionWorkflowService`, transiciones, aprobaciones, snapshots al publicar), los permisos core y su seeder/test, el resto de dominios, ni la naturaleza activable del módulo de informes razonados.

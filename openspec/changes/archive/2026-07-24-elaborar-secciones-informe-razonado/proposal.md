## Why

Es el gemelo del change `elaborar-narrativas-informe-razonado`. El modelo `SeccionInformeRazonado` (`codigo`, `titulo`, `orden`) y `InformeRazonadoService::agregarSeccion()` ya existen, pero **no están expuestos por ninguna ruta, controlador ni UI** (`agregarSeccion` no se usa ni en tests). Las secciones son la capa organizativa del informe razonado: agrupan métricas, gráficos y narrativas. Además, el change de narrativas dejó un **cabo suelto**: agregó soporte backend para `narrativa.seccion_informe_razonado_id` (el Form Request ya valida un `exists` scoped a las secciones de la ejecución), pero como no se puede crear ninguna sección desde la app, ese campo es hoy inservible. Este change cierra ambos huecos: expone el CRUD de secciones y permite ubicar una narrativa dentro de una sección.

## What Changes

- **CRUD de secciones de una ejecución** desde la UI: crear (`codigo`, `titulo`, `orden`), editar (`titulo`, `orden`) y eliminar `seccion_informe_razonado`, **únicamente mientras la ejecución está en `en_elaboracion`** (igual que las narrativas). Gated por el permiso ya existente `informes.elaborar`.
- **Borrado en cascada respetando el esquema**: la FK `seccion_informe_razonado_id` en `metricas`/`graficos`/`narrativas` es `nullable()->constrained()->cascadeOnDelete()`, así que **eliminar una sección elimina también su contenido asignado** (el contenido sin sección, `seccion_id = null`, no se toca). **No se altera ninguna migración**: se respeta ese comportamiento tal cual. La UI SHALL confirmar el borrado con una advertencia explícita que nombre la consecuencia, y la spec lo documenta.
- **Asignar una narrativa a una sección al crearla**: se agrega un selector de sección (opcional) al formulario de "agregar narrativa" ya existente en `ejecuciones/show.tsx`. El backend (Form Request + controlador de narrativas) ya soporta `seccion_informe_razonado_id`. **No se cambia `editarNarrativa`** (mover una narrativa entre secciones queda para un change posterior).
- **Narrativas agrupadas por sección** en el detalle: encabezado de sección + sus narrativas, con un grupo "Sin sección" para las de `seccion_id` nulo. Agrupación en el cliente usando `secciones[]` y `narrativas[].seccion_informe_razonado_id` que el `EjecucionInformeRazonadoResource` ya expone.
- **`InformeRazonadoService`**: se agregan `editarSeccion(SeccionInformeRazonado, string $titulo, int $orden)` y `eliminarSeccion(SeccionInformeRazonado)`; se reutiliza `agregarSeccion()`. El controlador es liviano y delega en el service.
- **Autorización**: nueva `SeccionInformeRazonadoPolicy` (gemela de `NarrativaInformeRazonadoPolicy`), registrada a mano en `AppServiceProvider::configureAuthorization()`; `create`/`update`/`delete` exigen `informes.elaborar` **y** que la ejecución esté `en_elaboracion`. Validación vía `GuardarSeccionInformeRazonadoRequest`.
- **Rutas** nuevas en `routes/informes-razonados.php`; Wayfinder regenerado.
- **NO se crean permisos nuevos** (se reutiliza `informes.elaborar`), **no se toca** `RolesAndPermissionsSeeder` ni su test.

**No cambia**: el workflow de la ejecución (transiciones, `TransicionWorkflowService`, snapshots), la naturaleza activable del módulo, ni `editarNarrativa`. Crear/editar/eliminar una sección **no** es una transición de workflow: es contenido, por lo que no pasa por `TransicionWorkflowService` ni altera el estado del `Proceso`.

## Capabilities

### New Capabilities

_(ninguna)_

### Modified Capabilities

- `gestionar-informes-razonados`: se agrega el requirement "Elaborar las secciones de una ejecución de informe razonado" (crear/editar/eliminar secciones en `en_elaboracion` con `informes.elaborar`, borrado en cascada del contenido asignado). El requirement existente "Elaborar las narrativas de una ejecución de informe razonado" pasa a documentar que, al crear una narrativa, se le puede asignar opcionalmente una sección de la misma ejecución.

## Impact

**Código modificado**

- `app/Services/InformesRazonados/InformeRazonadoService.php`: nuevos `editarSeccion()` y `eliminarSeccion()`.
- `routes/informes-razonados.php`: rutas de secciones.
- `app/Providers/AppServiceProvider.php`: `Gate::policy(SeccionInformeRazonado::class, SeccionInformeRazonadoPolicy::class)`.
- `resources/js/pages/informes-razonados/ejecuciones/show.tsx`: gestión de secciones, selector de sección en el form de narrativa, narrativas agrupadas por sección.
- `resources/js/types/informes-razonados.ts`: verificar/extender el tipo de sección si falta.
- `resources/js/routes/informes-razonados/**` / `resources/js/actions/**`: regenerados por Wayfinder.

**Código nuevo**

- `app/Http/Controllers/InformesRazonados/SeccionInformeRazonadoController.php` (liviano; `store`, `update`, `destroy`).
- `app/Http/Requests/InformesRazonados/GuardarSeccionInformeRazonadoRequest.php`.
- `app/Policies/SeccionInformeRazonadoPolicy.php`.
- `tests/Feature/InformesRazonados/ElaborarSeccionesInformeRazonadoTest.php`.

**Sin impacto en**: el workflow de ejecuciones, los permisos core y su seeder/test, `editarNarrativa`, el resto de dominios, ni la naturaleza activable del módulo.

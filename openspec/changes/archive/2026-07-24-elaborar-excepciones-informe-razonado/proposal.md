## Why

Tercer mirror de la serie de elaboración (después de `elaborar-narrativas-informe-razonado` y `elaborar-secciones-informe-razonado`). El modelo `ExcepcionInformeRazonado` (`codigo`, `descripcion`, `severidad`, `vinculable` morph nullable) y `InformeRazonadoService::agregarExcepcion()` ya existen, pero **no están expuestos por ninguna ruta, controlador ni UI** (`agregarExcepcion` no se usa fuera de tests) y la sección "Excepciones" del detalle es de solo lectura. Las excepciones son las anomalías/salvedades que el elaborador marca sobre el corte ("el caso X quedó excluido por falta de documento", "brecha de datos en Y") — contenido humano, no datos vivos, consistente con el harness. Es el mirror más simple: a diferencia de las narrativas, las excepciones **no** tienen sección ni revisión.

## What Changes

- **CRUD de excepciones de una ejecución** desde la UI: crear (`codigo`, `descripcion`, `severidad`), editar (`descripcion`, `severidad`) y eliminar `excepcion_informe_razonado`, **únicamente mientras la ejecución está en `en_elaboracion`**. Gated por el permiso ya existente `informes.elaborar`.
- **`severidad`**: se establece la convención de valores de dominio en español `info` | `advertencia` | `critico` (no existía enum previo; se valida en el Form Request con `in:info,advertencia,critico`; default `info`).
- **`vinculable`** (morph a un registro concreto) queda **nulo** en este slice; vincular una excepción a un registro específico es un refinamiento posterior y no se expone en la UI.
- **`InformeRazonadoService`**: se agregan `editarExcepcion(ExcepcionInformeRazonado, string $descripcion, string $severidad)` y `eliminarExcepcion(ExcepcionInformeRazonado)`; se reutiliza `agregarExcepcion()`. El controlador es liviano y delega en el service.
- **Autorización**: nueva `ExcepcionInformeRazonadoPolicy` (gemela exacta de `NarrativaInformeRazonadoPolicy`/`SeccionInformeRazonadoPolicy`), registrada a mano en `AppServiceProvider::configureAuthorization()`; `create`/`update`/`delete` exigen `informes.elaborar` **y** que la ejecución esté `en_elaboracion`. Validación vía `GuardarExcepcionInformeRazonadoRequest`.
- **Rutas** nuevas en `routes/informes-razonados.php`; Wayfinder regenerado.
- **UI**: se hace editable la sección "Excepciones" de `ejecuciones/show.tsx` (agregar/editar/eliminar cuando la ejecución es editable y el usuario tiene `informes.elaborar`), mostrando la severidad con un badge por nivel. Gating de UI con `auth.permissions`.
- **NO se crean permisos nuevos** (se reutiliza `informes.elaborar`), **no se toca** el core ni su seeder/test.

**No cambia**: el workflow de la ejecución (transiciones, `TransicionWorkflowService`, snapshots), la naturaleza activable del módulo, ni las demás piezas de contenido. Crear/editar/eliminar una excepción **no** es una transición de workflow: es contenido, por lo que no pasa por `TransicionWorkflowService` ni altera el estado del `Proceso`.

## Capabilities

### New Capabilities

_(ninguna)_

### Modified Capabilities

- `gestionar-informes-razonados`: se agrega el requirement "Elaborar las excepciones de una ejecución de informe razonado" (crear/editar/eliminar excepciones en `en_elaboracion` con `informes.elaborar`, con severidad `info`/`advertencia`/`critico`).

## Impact

**Código modificado**

- `app/Services/InformesRazonados/InformeRazonadoService.php`: nuevos `editarExcepcion()` y `eliminarExcepcion()`.
- `routes/informes-razonados.php`: rutas de excepciones.
- `app/Providers/AppServiceProvider.php`: `Gate::policy(ExcepcionInformeRazonado::class, ExcepcionInformeRazonadoPolicy::class)`.
- `resources/js/pages/informes-razonados/ejecuciones/show.tsx`: sección "Excepciones" editable con badge de severidad.
- `resources/js/types/informes-razonados.ts`: verificar/extender el tipo de excepción si falta.
- `resources/js/routes/informes-razonados/**` / `resources/js/actions/**`: regenerados por Wayfinder.

**Código nuevo**

- `app/Http/Controllers/InformesRazonados/ExcepcionInformeRazonadoController.php` (liviano; `store`, `update`, `destroy`).
- `app/Http/Requests/InformesRazonados/GuardarExcepcionInformeRazonadoRequest.php`.
- `app/Policies/ExcepcionInformeRazonadoPolicy.php`.
- `tests/Feature/InformesRazonados/ElaborarExcepcionesInformeRazonadoTest.php`.

**Sin impacto en**: el workflow de ejecuciones, los permisos core y su seeder/test, el resto de dominios, ni la naturaleza activable del módulo.

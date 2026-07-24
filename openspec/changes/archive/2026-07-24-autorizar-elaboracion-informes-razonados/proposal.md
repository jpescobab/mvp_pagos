## Why

Iniciar una ejecución de informe razonado (`EjecucionInformeRazonadoController::store`) —elaborar un informe a partir de un corte publicado, creando su `Proceso` de workflow en el estado inicial `en_elaboracion`— **no está autorizado**: el controlador no llama a `Gate::authorize` y su Form Request `IniciarEjecucionInformeRazonadoRequest` no define `authorize()` (default `true`). La `EjecucionInformeRazonadoPolicy` solo tiene `viewAny`/`view` (con `informes.ver`), sin `create`. El resultado es que **cualquier usuario autenticado puede iniciar un informe razonado**, inconsistente con el resto del módulo y del core, donde la escritura siempre pasa por una policy. Es el gemelo exacto del hueco que se cerró para las definiciones en el change `2026-07-24-administrar-definiciones-informes-razonados`, que lo dejó explícitamente marcado como el siguiente gap del módulo, con la nota de que iniciar una ejecución **merece su propio permiso**, no reutilizar `informes.administrar`.

A ese hueco se suman dos huecos simétricos en el mismo workflow: las transiciones `enviar_a_revision` (segunda acción del elaborador: envía su propio borrador a revisión) y `rechazar` (veredicto de revisión, gemelo de `aprobar`) se siembran **sin** `permiso_requerido`, mientras `aprobar`/`publicar` sí lo exigen. Sin gatear `enviar_a_revision`, cualquier autenticado podría empujar a revisión un informe que no elaboró; sin gatear `rechazar`, cualquiera podría rechazar un informe en revisión.

## What Changes

- **Nuevo permiso `informes.elaborar`** (convención `modulo_accion.verbo`), sembrado en `WorkflowInformesRazonadosSeeder` junto a `informes.administrar`/`informes.aprobar`/`informes.publicar` y otorgado al rol `admin`. **No se toca** `RolesAndPermissionsSeeder` (así no cambia `RolesAndPermissionsSeederTest`, que afirma la lista EXACTA de permisos core): `informes.elaborar` es un permiso del módulo activable, no core.
- **Cerrar el hueco de autorización de iniciar ejecución**: `EjecucionInformeRazonadoPolicy::create` exige `informes.elaborar`; `IniciarEjecucionInformeRazonadoRequest::authorize()` exige `informes.elaborar`; `EjecucionInformeRazonadoController::store` llama a `Gate::authorize('create', EjecucionInformeRazonado::class)`. **BREAKING** (a propósito): iniciar una ejecución pasa a requerir `informes.elaborar`; el comportamiento anterior (cualquier autenticado podía iniciar) era el defecto que se corrige.
- **Cerrar los huecos simétricos del workflow** en el mismo seeder: la transición `enviar_a_revision` pasa a exigir `permiso_requerido = informes.elaborar`; la transición `rechazar` pasa a exigir `permiso_requerido = informes.aprobar` (gemelo de `aprobar`). **BREAKING** (a propósito): `enviar_a_revision` y `rechazar` pasan de "cualquier autenticado" a requerir permiso. Se siembran con `firstOrCreate` por `codigo`, así que en instalaciones existentes el `permiso_requerido` nuevo no se actualiza salvo re-seed / `migrate:fresh --seed` — se documenta en `design.md`.
- **UI**: en `resources/js/pages/informes-razonados/ejecuciones/index.tsx` se oculta el control de "Iniciar ejecución" cuando el usuario no tiene `informes.elaborar`, usando `auth.permissions` (compartido por `HandleInertiaRequests`), siguiendo el patrón de gating de UI del resto del proyecto.
- **Tests** Feature que cubran: iniciar con permiso funciona, iniciar sin `informes.elaborar` da 403, `enviar_a_revision` exige `informes.elaborar`, `rechazar` exige `informes.aprobar`.

No cambia el workflow (`InformeRazonadoService`, `TransicionWorkflowService`, snapshots, aprobaciones), ni los permisos `informes.ver`/`informes.aprobar`/`informes.publicar`, ni la naturaleza activable del módulo.

## Capabilities

### New Capabilities

_(ninguna)_

### Modified Capabilities

- `gestionar-informes-razonados`: el requirement "Iniciar una ejecución de informe razonado sobre un corte publicado" pasa a exigir el permiso `informes.elaborar` (además de que el corte esté publicado). El requirement "Mover una ejecución de informe razonado por su workflow" pasa a documentar que `enviar_a_revision` exige `informes.elaborar` y `rechazar` exige `informes.aprobar`, además de `aprobar`/`publicar` que ya los exigen.

## Impact

**Código modificado**

- `app/Http/Controllers/InformesRazonados/EjecucionInformeRazonadoController.php`: `Gate::authorize('create', ...)` en `store`.
- `app/Http/Requests/InformesRazonados/IniciarEjecucionInformeRazonadoRequest.php`: `authorize()` → `informes.elaborar`.
- `app/Policies/EjecucionInformeRazonadoPolicy.php`: método `create`.
- `database/seeders/WorkflowInformesRazonadosSeeder.php`: permiso `informes.elaborar` → rol `admin`; `permiso_requerido` en las transiciones `enviar_a_revision` (`informes.elaborar`) y `rechazar` (`informes.aprobar`).
- `resources/js/pages/informes-razonados/ejecuciones/index.tsx`: gating del control de iniciar ejecución con `auth.permissions`.

**Código nuevo**

- `tests/Feature/InformesRazonados/IniciarEjecucionInformeRazonadoAutorizacionTest.php` (o equivalente): autorización de iniciar y de las transiciones `enviar_a_revision`/`rechazar`.

**Sin impacto en**: el workflow de ejecuciones (`InformeRazonadoService`, transiciones, snapshots, aprobaciones), los permisos `informes.ver`/`informes.aprobar`/`informes.publicar`, `RolesAndPermissionsSeeder`/su test, ni el resto de dominios. El módulo de informes razonados sigue siendo activable sin cambios.

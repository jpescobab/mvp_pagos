## Why

Todos los permisos del flujo de informes razonados y reportabilidad (`reportabilidad.ver`, `reportabilidad.generar_corte`, `reportabilidad.publicar_corte`, `informes.ver`, `informes.administrar`, `informes.elaborar`, `informes.aprobar`, `informes.publicar`, `informes.exportar`) recaen hoy en un único rol operativo `admin` (`superadmin` accede vía `Gate::before`). Ningún otro rol sembrado recibe uno solo de ellos. El workflow modela separación de deberes (elaborar ≠ aprobar ≠ publicar), pero los roles sembrados no la materializan: el mismo `admin` elabora, aprueba y publica un informe razonado, que por definición requiere revisión humana independiente.

## What Changes

- Se siembran **tres roles nuevos** que bundlean permisos **ya existentes** (no se crean permisos nuevos), materializando la separación de deberes:
  - `gestor_reportabilidad`: `reportabilidad.ver`, `reportabilidad.generar_corte`, `reportabilidad.publicar_corte`, `informes.ver`.
  - `elaborador_informes`: `informes.ver`, `informes.elaborar`, `informes.exportar`.
  - `revisor_informes`: `informes.ver`, `informes.aprobar`, `informes.publicar`, `informes.exportar`.
- Garantía de separación de deberes, afirmada por test: `elaborador_informes` **no** tiene `informes.aprobar` ni `informes.publicar`; `revisor_informes` **no** tiene `informes.elaborar`.
- `informes.administrar` (gestión de plantillas/definiciones, tarea de configuración) se mantiene **solo en `admin`**, no en un rol operativo.
- Los roles `admin` y `superadmin` conservan el superset completo — los roles nuevos delegan deberes acotados, no reemplazan a `admin`.
- Siembra idempotente y aditiva (`Role::firstOrCreate` + `givePermissionTo`) en `WorkflowInformesRazonadosSeeder`, co-locada con los permisos que usan.

## Capabilities

### New Capabilities
<!-- Ninguna capability nueva. -->

### Modified Capabilities
- `reportabilidad-informes-razonados`: agrega un requirement que fija los roles dedicados y la separación de deberes del flujo (elaborar, aprobar/publicar y preparar cortes recaen en roles distintos).

## Impact

- **Seeder**: `database/seeders/WorkflowInformesRazonadosSeeder.php` (crea los tres roles y les asigna permisos existentes).
- **Test nuevo/extendido**: afirma el conjunto exacto de permisos de cada rol y la ausencia explícita de los permisos de las otras etapas (separación de deberes).
- **Sin permisos nuevos**: no se toca la lista exacta que afirma `RolesAndPermissionsSeederTest` (solo se agregan roles).
- **Sin cambios en policies, controllers ni frontend**: los roles solo agrupan permisos que ya gatean el flujo (FormRequests + `Gate`). Al asignar un rol a un usuario en runtime hay que invalidar la caché de `PermisosCompartidosResolver` para que la UI lo refleje (comportamiento existente, no se modifica).
- **Sin migraciones ni tablas nuevas.**

### Decisiones abiertas para revisión humana

Se implementa la opción recomendada; estas alternativas se dejan explícitas por si se prefiere ajustar antes de aplicar:

- **(a) `informes.exportar` en `gestor_reportabilidad`**: se deja fuera (exportar es propio de quien elabora/revisa el informe). Alternativa: incluirlo si el gestor de reportabilidad también entrega el informe final.
- **(b) Separar `publicar` de `aprobar`**: van juntos en `revisor_informes`. Alternativa: un cuarto rol `publicador_informes` con solo `informes.publicar` si la publicación debe ser un acto formal distinto de la aprobación.
- **(c) `informes.ver` en `gestor_reportabilidad`**: se incluye para que vea los informes derivados de sus cortes. Alternativa: excluirlo y dejar al gestor limitado a reportabilidad.

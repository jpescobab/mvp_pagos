## Context

Los permisos del flujo ya existen y ya gatean cada etapa: los FormRequests (`IniciarEjecucionInformeRazonadoRequest` → `informes.elaborar`, etc.) y las transiciones del workflow (`WorkflowInformesRazonadosSeeder`: `enviar_a_revision`→`informes.elaborar`, `aprobar`/`rechazar`→`informes.aprobar`, `publicar`→`informes.publicar`) los verifican. Lo único ausente es un conjunto de roles que agrupe esos permisos por deber. Hoy `admin` los tiene todos (core seeder `syncPermissions` con `informes.ver`/`reportabilidad.ver`, más `WorkflowInformesRazonadosSeeder` que le agrega el resto con `givePermissionTo` aditivo); `superadmin` accede vía `Gate::before`.

## Goals / Non-Goals

**Goals:**
- Materializar la separación de deberes del flujo con tres roles dedicados que bundlean permisos existentes.
- Afirmar por test la separación (un rol no tiene los permisos de la etapa que no le corresponde).
- Siembra idempotente y aditiva, sin regresiones en `admin`/`superadmin`.

**Non-Goals:**
- No se crean permisos nuevos (no se toca la lista exacta que afirma `RolesAndPermissionsSeederTest`).
- No se tocan policies, controllers, FormRequests ni frontend: el gating ya existe.
- No se asignan estos roles a usuarios concretos (eso es operación, no siembra).
- No se cambia el mecanismo de caché de permisos.

## Decisions

**1. Sembrar en `WorkflowInformesRazonadosSeeder`, co-locado con los permisos.**
Ese seeder ya crea los permisos `informes.*`/`reportabilidad.*` y ya corre después de `RolesAndPermissionsSeeder` (línea 36 vs 30 de `DatabaseSeeder`), así que los permisos existen al momento de asignarlos. Se agrega un bloque que crea los tres roles con `Role::firstOrCreate` y les asigna sus permisos con `syncPermissions` (para que el conjunto sea exactamente el esperado, idempotente incluso si se corre dos veces). Usar `syncPermissions` en los roles NUEVOS es seguro porque nadie más gobierna su conjunto; `admin` se sigue tratando con `givePermissionTo` aditivo como hoy.

**2. `syncPermissions` en los roles nuevos, no `givePermissionTo`.**
El test afirma el conjunto EXACTO de cada rol; `syncPermissions` garantiza que una segunda corrida no deje permisos de más y que el estado sea determinista. `admin`/`superadmin` no se tocan en este change.

**3. Composición de roles (opción recomendada).**
- `gestor_reportabilidad` = deberes de preparación del corte + lectura de informes.
- `elaborador_informes` = construir el contenido y exportar borradores.
- `revisor_informes` = aprobar, publicar y exportar el final.
`informes.administrar` queda fuera (config de plantillas, propia de `admin`). Las alternativas (exportar en gestor, separar publicar de aprobar, quitar `informes.ver` al gestor) quedan documentadas en el proposal para decisión humana; el default es el más conservador respecto a separación de deberes.

**4. Test de separación explícita.**
Un test nuevo siembra los roles y afirma, por cada rol, `hasPermissionTo(...)` verdadero para los suyos y falso para los de otras etapas (p. ej. `elaborador_informes` no tiene `informes.aprobar`). Esto convierte la separación de deberes en una invariante verificada, no en una convención.

## Risks / Trade-offs

- **`RolesAndPermissionsSeederTest`**: afirma la lista exacta de PERMISOS core, no de roles. Como no se agregan permisos, no debería romperse; se corre para confirmar. Si existiera además un test que afirme el catálogo exacto de ROLES, habría que extenderlo con los tres nuevos.
- **Orden de seeders**: la siembra depende de que los permisos ya existan; se ubica después de su creación en el mismo seeder, eliminando el riesgo de orden.
- **Caché de permisos**: sembrar roles no invalida nada en runtime; recién al asignar un rol a un usuario hay que invalidar `PermisosCompartidosResolver` (comportamiento existente, se menciona pero no se altera).
- **Separación de deberes sin usuarios asignados**: este change habilita la separación pero no la fuerza — un operador podría asignar los tres roles a una misma persona. Eso es decisión de administración de usuarios, fuera de alcance.

## Context

El módulo de informes razonados está implementado y en uso. La `definicion_informe_razonado` es el catálogo de tipos de informe: cada ejecución (`ejecucion_informe_razonado`) nace de una definición aplicada a un corte publicado, y luego recorre un workflow (`en_elaboracion → en_revision → aprobado → publicado`) gobernado por `InformeRazonadoService`. Las definiciones son la configuración estable; las ejecuciones son el trabajo operacional.

Estado actual de las definiciones: `DefinicionInformeRazonadoController` tiene solo `index` (con `Gate::authorize('viewAny')` → `informes.ver`) y `store`. El `store` **no autoriza** y su `CrearDefinicionInformeRazonadoRequest` **no define `authorize()`** — cualquier autenticado puede crear. El `codigo` no valida unicidad. La `DefinicionInformeRazonadoPolicy` solo tiene `viewAny`/`view`. La creación ocurre desde un formulario incrustado en el índice (`router.post` directo), y el índice no sigue el patrón de listado denso (`text-green-600` hardcodeado, sin búsqueda ni acciones).

Permisos del módulo: `informes.ver` (sembrado en `RolesAndPermissionsSeeder`, core, con test de lista exacta), `informes.aprobar` e `informes.publicar` (sembrados en `WorkflowInformesRazonadosSeeder`, otorgados al rol `admin`). No existe un permiso para administrar el catálogo de definiciones.

Volumen: las definiciones son pocas por naturaleza (un puñado de tipos de informe institucional); las ejecuciones crecen con cada corte.

## Goals / Non-Goals

**Goals:**

- Que crear/editar/eliminar una definición exija un permiso, cerrando el hueco por el que hoy cualquier autenticado crea definiciones.
- Que el catálogo de definiciones sea administrable y navegable como el resto de las entidades del sistema: detalle (con sus ejecuciones), edición, desactivación/eliminación, listado denso con búsqueda.
- Que el `codigo` sea único y que una definición con ejecuciones no se pueda borrar (preservar trazabilidad).
- Que las mutaciones queden auditadas.

**Non-Goals:**

- **No** se toca el workflow de ejecuciones ni `InformeRazonadoService`, ni los permisos `informes.ver`/`aprobar`/`publicar`.
- **No** se corrige el hueco de autorización gemelo de `EjecucionInformeRazonadoController::store` (iniciar ejecución) — es una acción operacional distinta que merece su propio permiso y análisis; queda documentado como el siguiente gap.
- **No** se agrega soft delete a las definiciones (la protección por dependencias ya cubre el caso peligroso).
- **No** se modifica `RolesAndPermissionsSeeder` (el permiso nuevo va en el seeder del módulo).
- **No** se desactivan ejecuciones en cascada al desactivar una definición; `activo` es una marca que solo condiciona qué definiciones se ofrecen al iniciar una ejecución nueva (comportamiento ya existente: `EjecucionController` filtra `where('activo', true)`).

## Decisions

### Permiso nuevo `informes.administrar`, sembrado en el seeder del módulo

Se introduce `informes.administrar` para gobernar la gestión del catálogo de definiciones (crear/editar/eliminar). Se siembra en `WorkflowInformesRazonadosSeeder` —junto a `informes.aprobar`/`informes.publicar`— y se otorga al rol `admin`.

*Por qué un permiso nuevo y no reutilizar uno existente*: `informes.ver` es de solo lectura; `informes.aprobar`/`informes.publicar` gobiernan transiciones de workflow de una fase distinta (revisión/publicación de una ejecución). Administrar el catálogo de tipos de informe es una acción de configuración propia, y la convención del harness es `modulo_accion.verbo`. Un permiso dedicado la modela bien.

*Por qué en `WorkflowInformesRazonadosSeeder` y no en `RolesAndPermissionsSeeder`*: `RolesAndPermissionsSeederTest` afirma la lista EXACTA de permisos core; agregar ahí obligaría a tocar ese test y mezclaría un permiso de módulo funcional con los del core. `informes.aprobar`/`publicar` ya sientan el precedente de vivir en el seeder del módulo.

### Autorización por policy, como el resto del core

Se agregan `create`/`update`/`delete` a `DefinicionInformeRazonadoPolicy` (todos contra `informes.administrar`), el controlador llama `Gate::authorize` en cada acción de escritura, y los Form Requests definen `authorize()`. Doble control (policy en el controlador + `authorize()` en el request) idéntico al de las tablas maestras.

*Por qué también en el request y no solo en el controlador*: es la convención establecida (`Store*Request`/`Update*Request` de Maestros autorizan en el request). Deja la regla de autorización junto a la de validación y protege aunque una acción olvide el `Gate::authorize`.

### Eliminación física con bloqueo previo por ejecuciones

`destroy` consulta `->ejecuciones()->exists()` antes de borrar; si hay ejecuciones, flash de error y `back()` sin tocar nada. Igual que `CfinancieroController::relacionQueImpideEliminar()`.

*Por qué bloquear y no cascada ni soft delete*: una ejecución es evidencia de un informe generado sobre un corte; borrar su definición rompería la trazabilidad que el módulo existe para dar. El bloqueo explícito da un mensaje en español y es verificable igual en PostgreSQL y SQLite (a diferencia de dejar reventar la FK). No se agrega `deleted_at` porque sería un cambio de esquema innecesario: el bloqueo ya impide el escenario peligroso.

### La creación se mueve a su propia pantalla

El formulario incrustado en el índice se reemplaza por una página `create` dedicada, y el índice pasa a listado denso con búsqueda, badge de estado y dropdown de acciones (referencia: `maestros/cfinancieros/index.tsx`).

*Por qué*: el índice denso no tiene lugar para un formulario incrustado, y separar creación de listado es el patrón uniforme del resto de la app. El detalle muestra la definición y la tabla de sus ejecuciones (sin paginar: son pocas por definición; si alguna supera el centenar, se paginaría esa sección — mismo criterio y umbral que en el resto del sistema).

### Auditoría por trait

`DefinicionInformeRazonado` usa `RegistraAuditoria` (ya en `master`), registrando `crear_/editar_/eliminar_definicion_informe_razonado` con diff y solo con usuario autenticado. Consistente con las tablas maestras; los seeders no auditan.

## Risks / Trade-offs

- **Cambio de comportamiento observable**: crear una definición pasa a requerir `informes.administrar`. Cualquier usuario que hoy dependa del hueco (crear sin permiso) dejará de poder. Es el defecto que se corrige, no una regresión; el rol `admin` —que ya administra el módulo— recibe el permiso, así que el flujo legítimo sigue igual. Mitigación operacional: el permiso se siembra de forma aditiva e idempotente al re-correr el seeder.

- **El hueco gemelo de iniciar ejecución queda abierto** tras este change. Se deja constancia explícita en el proposal y aquí para que sea el siguiente gap, no un olvido. Riesgo acotado: una ejecución iniciada sin permiso sigue sin poder publicarse sin `informes.publicar`, y su listado exige `informes.ver`.

- **Desactivar una definición no afecta ejecuciones en curso**: solo la excluye de la lista de definiciones ofrecidas al iniciar una ejecución nueva. Coherente con el `activo` del resto del sistema; puede sorprender a quien espere una baja en cascada. Fuera de alcance por diseño.

## Migration Plan

Sin migración de esquema ni de datos. Despliegue: código + re-correr `WorkflowInformesRazonadosSeeder` (idempotente, otorga `informes.administrar` al rol `admin`) + `php artisan wayfinder:generate --with-form` + `npm run build`. Tras sembrar, invalidar la caché de permisos si aplica (el resolver cachea por usuario 5 min). Rollback: revertir el commit; las definiciones y ejecuciones quedan intactas (no se tocan sus tablas). El permiso `informes.administrar` sembrado quedaría huérfano tras un rollback de código, sin efecto.

## Open Questions

Ninguna bloqueante. Pendiente para un change futuro (fuera de este): definir el permiso y el flujo de autorización de "iniciar ejecución" (`EjecucionInformeRazonadoController::store`).

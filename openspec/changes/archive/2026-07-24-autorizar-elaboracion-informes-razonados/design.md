## Context

El módulo de Informes Razonados ya tiene un ciclo de vida completo: se define un tipo de informe (`definicion_informe_razonado`), se **inicia una ejecución** sobre un corte publicado (`EjecucionInformeRazonadoController::store` → `InformeRazonadoService::iniciarEjecucion`), y esa ejecución avanza por un workflow `informes_razonados` (`en_elaboracion → en_revision → aprobado → publicado`, con `rechazado` como salida). El change previo (`2026-07-24-administrar-definiciones-informes-razonados`) cerró el hueco de autorización de escritura en las **definiciones** e introdujo el permiso `informes.administrar`, dejando explícitamente marcado como siguiente gap el hueco gemelo en **iniciar ejecución**.

Estado actual del hueco:
- `IniciarEjecucionInformeRazonadoRequest` no define `authorize()` → default `true`.
- `EjecucionInformeRazonadoController::store` no llama a `Gate::authorize`.
- `EjecucionInformeRazonadoPolicy` solo tiene `viewAny`/`view`.
- En `WorkflowInformesRazonadosSeeder`, las transiciones `enviar_a_revision` y `rechazar` se siembran sin `permiso_requerido`; `aprobar` exige `informes.aprobar` y `publicar` exige `informes.publicar`.

## Goals / Non-Goals

**Goals:**
- Que iniciar una ejecución de informe razonado exija un permiso propio (`informes.elaborar`), consistente con el patrón de autorización del resto del módulo/core.
- Cerrar los dos huecos simétricos del workflow (`enviar_a_revision`, `rechazar`) en la misma pasada, para que todo el ciclo de vida quede gobernado por permisos.
- No romper `RolesAndPermissionsSeederTest` (afirma la lista EXACTA de permisos core).

**Non-Goals:**
- No se toca el motor de workflow (`TransicionWorkflowService`), ni `InformeRazonadoService`, ni los snapshots/aprobaciones.
- No se crea un rol nuevo "elaborador/redactor" ni se reasigna el mapeo cargo→rol; el permiso se otorga al rol `admin` (y `superadmin` lo obtiene vía `Gate::before`). Definir un rol operacional dedicado es una decisión de negocio aparte.
- No se cambia `informes.ver`/`informes.aprobar`/`informes.publicar` ni su ubicación de siembra.

## Decisions

**1. Permiso nuevo `informes.elaborar`, sembrado en `WorkflowInformesRazonadosSeeder` (no en `RolesAndPermissionsSeeder`).**
`informes.elaborar` es un permiso del módulo activable Informes Razonados, no del core. Sembrarlo junto a `informes.administrar`/`informes.aprobar`/`informes.publicar` mantiene la coherencia con cómo se reparten los permisos por dominio y evita tocar `RolesAndPermissionsSeederTest`, que valida la lista exacta de permisos core. Alternativa descartada: reutilizar `informes.administrar` — el change previo ya argumentó que iniciar una ejecución es una acción operacional distinta del CRUD de definiciones y modelar ambas con el mismo permiso sería incorrecto.

**2. Autorización vía Policy `create` + `Gate::authorize` en el controlador + `authorize()` en el Form Request.**
Se replica exactamente el patrón que el change previo estableció para las definiciones: `EjecucionInformeRazonadoPolicy::create(User): bool` devuelve `$user->can('informes.elaborar')`; el controlador llama `Gate::authorize('create', EjecucionInformeRazonado::class)`; el Form Request devuelve el mismo `can` en `authorize()`. La doble comprobación (controller + request) es intencional y consistente con el resto del proyecto: el Form Request corta antes de validar, el `Gate::authorize` del controlador documenta la intención y cubre cualquier llamada futura. La policy ya está registrada en `AppServiceProvider::configureAuthorization()` (no hay auto-discovery); solo se le agrega el método `create`, no requiere registro nuevo.

**3. Gating de las transiciones en el seeder, no en el controlador de transiciones.**
`enviar_a_revision` y `rechazar` se gatean agregando `permiso_requerido` en la definición de la transición sembrada. Es el mecanismo correcto: `TransicionWorkflowService::execute()` ya valida `permiso_requerido` contra los permisos del usuario para `aprobar`/`publicar`. Poner la verificación en `TransicionEjecucionInformeRazonadoController` en vez del dato de la transición duplicaría la regla y rompería el principio "Workflow antes que CRUD". Asignación semántica: `enviar_a_revision → informes.elaborar` (es la segunda acción del elaborador sobre su propio borrador); `rechazar → informes.aprobar` (gemelo de `aprobar`: ambas son el veredicto de la revisión, por lo que comparten permiso).

**4. Gating de UI con `auth.permissions`.**
El control de "Iniciar ejecución" en `ejecuciones/index.tsx` se muestra solo si `auth.permissions` incluye `informes.elaborar`, leyendo el prop compartido por `HandleInertiaRequests` (resuelto por `PermisosCompartidosResolver` con caché de 5 min por usuario). Es UX, no seguridad: el backend sigue siendo la autoridad. No hay que invalidar caché adicional aquí; la caché de permisos ya se invalida al cambiar roles/permisos.

## Risks / Trade-offs

- **Instalaciones existentes no reflejan el `permiso_requerido` nuevo** → las transiciones se siembran con `firstOrCreate` por `codigo`, que no actualiza filas existentes. En dev/CI se resuelve con `migrate:fresh --seed`; en un entorno ya sembrado habría que re-sembrar o actualizar la fila a mano. Se acepta porque el módulo está en construcción y el flujo estándar del proyecto es `migrate:fresh --seed`. Se documenta explícitamente para quien opere un entorno persistente. (No se agrega un `updateOrCreate` para no alterar el patrón idempotente-aditivo del resto del seeder.)
- **BREAKING intencional** → iniciar/enviar-a-revisión/rechazar pasan de "cualquier autenticado" a requerir permiso. Es exactamente el defecto que se corrige; el rol `admin` y `superadmin` conservan la capacidad. Cubierto por tests.
- **Cambio en el texto de escenarios existentes de la spec** (los que decían "usuario autenticado") → se actualizan a "usuario con el permiso …". Los tests existentes en `InformeRazonadoServiceTest`/`GestionarInformesRazonadosTest` que ejerciten estas transiciones podrían necesitar otorgar el permiso al usuario de prueba; se revisan y ajustan durante `apply`.

## Migration Plan

1. Agregar `informes.elaborar` al arreglo de permisos de `WorkflowInformesRazonadosSeeder` y otorgarlo a `admin`; agregar `permiso_requerido` a `enviar_a_revision` (`informes.elaborar`) y `rechazar` (`informes.aprobar`).
2. Agregar `create` a la policy, `authorize()` al Form Request, `Gate::authorize` al controlador.
3. Gating de UI.
4. Tests nuevos + ajuste de tests existentes que ejerzan estas rutas.
5. Re-sembrar (`migrate:fresh --seed` en dev; los tests usan sqlite en memoria y siembran lo necesario vía factories/seeders).

Rollback: revertir el commit; no hay migraciones de esquema (solo datos de seeder y código de autorización), así que no hay estado de BD que deshacer más allá de re-sembrar.

## Open Questions

_(ninguna)_ — el modelo de permisos del módulo ya está establecido; este change solo completa la pieza que faltaba.

## Context

El módulo Informes Razonados ya tiene el modelo de datos completo (`ejecucion_informe_razonado`, `seccion_informe_razonado`, `metrica_informe_razonado`, `grafico_informe_razonado`, `narrativa_informe_razonado`, `excepcion_informe_razonado`, `snapshot_informe_razonado`, `aprobacion_informe_razonado`, `exportacion_informe_razonado`) y un `InformeRazonadoService` con métodos para poblar y mover ejecuciones. Pero la única escritura expuesta hoy es **iniciar** una ejecución (vacía) y **mover su workflow** (`enviar_a_revision`/`aprobar`/`rechazar`/`publicar`). El contenido —incluida la narrativa— solo se crea desde tests. Este change expone el primer contenido elaborable: la narrativa.

Restricciones del harness relevantes:
- Controladores livianos; la lógica vive en `InformeRazonadoService`.
- Los cambios de estado del `Proceso` pasan **exclusivamente** por `TransicionWorkflowService`. Elaborar/revisar narrativas **no** es un cambio de estado, así que **no** toca ese service ni el `Proceso`.
- Permisos con convención `modulo_accion.verbo`, sembrados en el seeder del módulo (`WorkflowInformesRazonadosSeeder`), nunca en el de core.
- Policies registradas a mano en `AppServiceProvider::configureAuthorization()` (no hay auto-discovery).
- Informes razonados terminan con revisión humana antes de publicar.

## Goals / Non-Goals

**Goals:**
- Permitir a un elaborador (`informes.elaborar`) crear/editar/eliminar narrativas de una ejecución mientras está `en_elaboracion`.
- Permitir a un revisor (`informes.aprobar`) marcar una narrativa como revisada (revisión humana), como acción separada de la autoría.
- Mantener el controlador liviano delegando en el service; validar en Form Requests; autorizar en Policy + Form Request.
- Que el detalle (`show`) exponga los datos que la UI necesita para condicionar los controles (ejecución editable; narrativa revisada/por quién).

**Non-Goals:**
- Exponer la autoría de secciones, métricas, gráficos o excepciones (changes posteriores).
- Crear permisos nuevos, tocar el core (`RolesAndPermissionsSeeder` / su test) o el workflow de la ejecución.
- Cambiar cómo se ensambla/congela el contenido al publicar (`snapshot_informe_razonado`).
- Editar contenido de narrativas por IA / generación automática: `generado_por_ia` se persiste como bandera, pero la generación queda fuera de alcance.

## Decisions

### 1. La narrativa es contenido, no workflow → no pasa por `TransicionWorkflowService`
Crear/editar/eliminar/revisar una narrativa **no** cambia el estado del `Proceso`. Se persiste directo por `InformeRazonadoService` dentro de sus propios métodos. Esto respeta el harness: `TransicionWorkflowService` sigue siendo la única puerta para cambiar estados, y aquí no hay cambio de estado.
_Alternativa descartada_: modelar "revisar narrativa" como una transición de workflow. Rechazada porque la revisión de una narrativa individual es granular y repetible; el veredicto de la ejecución completa ya es la transición `aprobar`/`rechazar`.

### 2. La editabilidad se ata al estado `en_elaboracion`, verificada en el backend
`create`/`update`/`delete` de narrativa solo se permiten si la ejecución está `en_elaboracion`. La verificación es autoritativa en el backend (Policy + Form Request), no solo en la UI. Una vez que la ejecución sale de `en_elaboracion`, su contenido queda estable de cara a la revisión y se congela al publicar.
_Alternativa descartada_: permitir editar en cualquier estado previo a `publicado`. Rechazada porque rompería la premisa de que la revisión juzga un contenido fijo.

### 3. Autorización combinada estado + permiso en la Policy
Nueva `NarrativaInformeRazonadoPolicy`, registrada a mano en `AppServiceProvider::configureAuthorization()`:
- `create(User, EjecucionInformeRazonado)`: `informes.elaborar` **y** ejecución `en_elaboracion`.
- `update(User, NarrativaInformeRazonado)` / `delete(...)`: `informes.elaborar` **y** la ejecución de la narrativa `en_elaboracion`.
- `revisar(User, NarrativaInformeRazonado)`: `informes.aprobar` (independiente del estado de elaboración; la revisión ocurre típicamente en `en_revision`).

Como `create` cuelga de la ejecución (aún no hay narrativa), se autoriza con `Gate::authorize('create', [NarrativaInformeRazonado::class, $ejecucion])`. Las Form Requests replican la verificación de permiso en `authorize()` para un 403 temprano y coherente con el resto del módulo.
_Alternativa descartada_: gates sueltos por acción. Rechazada por consistencia con `EjecucionInformeRazonadoPolicy`/`DefinicionInformeRazonadoPolicy` ya existentes.

### 4. Forma de las rutas: la narrativa cuelga de la ejecución para crear; por id para el resto
- `POST   ejecuciones/{ejecucion}/narrativas` → `store` (crea; necesita la ejecución para autorizar contra su estado).
- `PATCH  narrativas/{narrativa}` → `update`.
- `DELETE narrativas/{narrativa}` → `destroy`.
- `POST   narrativas/{narrativa}/revisar` → `revisar` (no es PATCH del recurso: es una acción de dominio con su propia autorización).

Se agregan al grupo existente `informes-razonados.` de `routes/informes-razonados.php`. Wayfinder se regenera con `php artisan wayfinder:generate --with-form`.

### 5. `revisar` como endpoint de acción separado
`revisar` no se modela como un `PATCH` del campo `revisado_en` dentro de `update`, sino como su propio endpoint `POST .../revisar`, porque tiene un permiso distinto (`informes.aprobar` vs `informes.elaborar`) y una semántica distinta (sellar revisión humana, no editar contenido). Es idempotente: re-revisar actualiza `revisado_por`/`revisado_en` sin efectos adicionales.

### 6. Recurso `show`: flag `editable` + narrativa enriquecida
`EjecucionInformeRazonadoResource` agrega `editable` (bool = `proceso.estadoActual.codigo === 'en_elaboracion'`) y, en `mapNarrativas()`, `seccion_informe_razonado_id`, `revisado_en` y `revisado_por` (nombre). La UI usa `editable` + `auth.permissions` para mostrar los controles de autoría, y `revisado_en` + `informes.aprobar` para el control de revisión. Se carga la relación `narrativas.revisadoPor` en el `show`.

## Risks / Trade-offs

- **[Race: la ejecución cambia de estado mientras se edita una narrativa]** → La Policy verifica el estado `en_elaboracion` en cada request (server-side), así que un envío a revisión concurrente hace que la siguiente escritura de narrativa falle con 403 en vez de corromper contenido bajo revisión. La UI puede quedar momentáneamente desincronizada, pero el backend es autoritativo.
- **[La UI podría mostrar controles de edición a quien no puede editar]** → El gating de UI (`editable` + `auth.permissions`) es solo cosmético; la autorización real está en Policy + Form Request. Peor caso: un botón visible que devuelve 403.
- **[Marcar revisada no exige que la ejecución esté en revisión]** → Decisión deliberada: `informes.aprobar` es suficiente y no se acopla `revisar` a un estado puntual, para no bloquear flujos legítimos (p. ej. revisar en `en_elaboracion` antes de enviar). El veredicto formal de la ejecución sigue siendo la transición `aprobar`/`rechazar`, intacta.
- **[Consistencia de permisos en instalaciones existentes]** → No aplica: no se agregan permisos ni transiciones nuevas; `informes.elaborar` e `informes.aprobar` ya están sembrados. No hay re-seed requerido.

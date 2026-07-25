## Context

Change gemelo de `elaborar-narrativas-informe-razonado`. El modelo `SeccionInformeRazonado` y `InformeRazonadoService::agregarSeccion()` existen pero no tienen ruta/controlador/UI. Las secciones organizan el contenido de la ejecución (métricas, gráficos, narrativas cuelgan opcionalmente de una sección vía `seccion_informe_razonado_id` nullable). El change de narrativas ya habilitó el backend para asignar una narrativa a una sección, pero sin secciones creables ese soporte quedó inerte.

Restricciones del harness ya aplicadas en el gemelo: controladores livianos con la lógica en `InformeRazonadoService`; los cambios de estado del `Proceso` van solo por `TransicionWorkflowService` (esto es contenido, no workflow); permisos del módulo, no core; policies registradas a mano en `AppServiceProvider`.

Dato de esquema (migraciones `2026_06_27_1000{07,08,09,11}`): la FK `seccion_informe_razonado_id` en métricas/gráficos/narrativas es `nullable()->constrained('secciones_informe_razonado')->cascadeOnDelete()`.

## Goals / Non-Goals

**Goals:**
- CRUD de secciones desde la UI, gated por `informes.elaborar`, solo en `en_elaboracion`.
- Asignar una narrativa a una sección al crearla (reusar el soporte backend ya existente).
- Mostrar las narrativas agrupadas por sección en el detalle.
- Reusar exactamente los patrones del gemelo de narrativas (policy con chequeo de estado, Form Request con `authorize`, controlador que llama `Gate::authorize` y delega, tests con los helpers globales del directorio).

**Non-Goals:**
- Alterar migraciones o cambiar el comportamiento `cascadeOnDelete` del esquema.
- Cambiar `editarNarrativa` para mover una narrativa entre secciones (change posterior).
- Exponer autoría de métricas, gráficos o excepciones (changes posteriores).
- Reordenar secciones por drag-and-drop (el `orden` se edita como número).

## Decisions

### 1. Se respeta el `cascadeOnDelete` del esquema; la UI advierte
Eliminar una sección borra en cascada su contenido asignado (métricas/gráficos/narrativas con esa `seccion_id`). **No se toca la migración**: reinterpretarla y cambiarla a `nullOnDelete` sería imponer una decisión de datos no acordada. En su lugar, el borrado es honesto: la UI muestra una confirmación que nombra la consecuencia ("Esto también eliminará las narrativas, métricas y gráficos de esta sección") y la spec lo documenta como escenario.
_Alternativa descartada_: alterar la FK a `nullOnDelete` para orfanar el contenido. Rechazada en este change por ser una decisión de esquema que corresponde acordar explícitamente; se deja marcada como revisable.

### 2. Autorización idéntica al gemelo de narrativas
Nueva `SeccionInformeRazonadoPolicy` registrada a mano en `AppServiceProvider`:
- `create(User, EjecucionInformeRazonado)`: `informes.elaborar` **y** ejecución `en_elaboracion`.
- `update`/`delete(User, SeccionInformeRazonado)`: `informes.elaborar` **y** la ejecución de la sección `en_elaboracion` (helper privado que lee `seccion->ejecucionInformeRazonado->estaEnElaboracion()`).
`create` cuelga de la ejecución, así que se autoriza con `Gate::authorize('create', [SeccionInformeRazonado::class, $ejecucion])`. El Form Request replica el chequeo de permiso en `authorize()`, igual que en narrativas.

### 3. Forma de rutas idéntica al gemelo
- `POST   ejecuciones/{ejecucion}/secciones` → `store`.
- `PATCH  secciones/{seccion}` → `update`.
- `DELETE secciones/{seccion}` → `destroy`.
Bajo el grupo `informes-razonados.` existente. Wayfinder regenerado.

### 4. Asignación narrativa→sección: solo al crear, reusando el backend existente
El `GuardarNarrativaInformeRazonadoRequest` ya valida `seccion_informe_razonado_id` con un `exists` scoped a las secciones de la ejecución, y el controlador de narrativas ya lo pasa a `agregarNarrativa`. Solo falta la UI: un `<select>` opcional de sección en el formulario de agregar narrativa. No se toca `editarNarrativa`.

### 5. Agrupación por sección en el cliente
El `EjecucionInformeRazonadoResource` ya expone `secciones[]` (id, codigo, titulo, orden) y `narrativas[].seccion_informe_razonado_id`. La página agrupa en el cliente: por cada sección (ordenada por `orden`), sus narrativas; más un grupo "Sin sección" para `seccion_id` nulo. Sin cambios de backend para el display.

## Risks / Trade-offs

- **[Borrado en cascada sorpresivo]** → La confirmación de la UI nombra explícitamente la consecuencia y la spec la documenta. Aun así, es un borrado destructivo: se deja marcado como revisable por si se prefiere `nullOnDelete`.
- **[Race: la ejecución cambia de estado mientras se edita una sección]** → La Policy verifica `en_elaboracion` en cada request (server-side); un envío a revisión concurrente hace fallar la siguiente escritura de sección con 403.
- **[La UI muestra controles a quien no puede]** → Gating de UI (`editable` + `auth.permissions`) es cosmético; la autorización real está en Policy + Form Request. Peor caso: un botón que devuelve 403.
- **[Consistencia de permisos en instalaciones existentes]** → No aplica: no se agregan permisos ni transiciones; `informes.elaborar` ya está sembrado.

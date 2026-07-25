## Context

Tercer mirror de la serie de elaboración de contenido (`elaborar-narrativas-*`, `elaborar-secciones-*`). `ExcepcionInformeRazonado` y `InformeRazonadoService::agregarExcepcion()` existen sin ruta/controlador/UI; la sección "Excepciones" del detalle es de solo lectura. Las excepciones son anotaciones humanas (anomalías/salvedades sobre el corte), no datos vivos. Es el mirror más simple: la tabla `excepciones_informe_razonado` no tiene `seccion_informe_razonado_id` (sin sección) ni campos de revisión.

Se reutilizan tal cual los patrones ya establecidos: policy con chequeo de estado `en_elaboracion`, Form Request con `authorize` por permiso, controlador liviano que llama `Gate::authorize` y delega en el service, tests con los helpers globales del directorio.

## Goals / Non-Goals

**Goals:**
- CRUD de excepciones desde la UI, gated por `informes.elaborar`, solo en `en_elaboracion`.
- Establecer la convención de `severidad` (`info`/`advertencia`/`critico`) y validarla.
- Hacer editable la sección "Excepciones" del detalle, con badge de severidad.

**Non-Goals:**
- Exponer o setear `vinculable` (morph a un registro concreto): queda nulo, refinamiento posterior.
- Auto-detección de excepciones a partir del corte (fuera de alcance; esto es autoría manual).
- Cualquier flujo de revisión de excepciones (no existe en el modelo).
- Métricas/gráficos (siguen sin exponerse; su origen ideal es el corte, no autoría manual).

## Decisions

### 1. Convención de `severidad` en el Form Request
No hay enum previo (la columna es `string default 'info'`). Se fija `in:info,advertencia,critico` en `GuardarExcepcionInformeRazonadoRequest`. Valores en español, coherentes con el resto del dominio. Si a futuro se necesitan más niveles, se amplía la regla (y el badge de la UI).
_Alternativa descartada_: un enum PHP o una tabla catálogo. Sobredimensionado para tres valores estables; la validación en el request basta.

### 2. Autorización y forma de rutas idénticas a los mirrors previos
Nueva `ExcepcionInformeRazonadoPolicy` registrada en `AppServiceProvider`:
- `create(User, EjecucionInformeRazonado)`: `informes.elaborar` **y** ejecución `en_elaboracion`.
- `update`/`delete(User, ExcepcionInformeRazonado)`: `informes.elaborar` **y** la ejecución de la excepción `en_elaboracion` (helper privado que lee `excepcion->ejecucionInformeRazonado?->estaEnElaboracion()`).
`create` cuelga de la ejecución: `Gate::authorize('create', [ExcepcionInformeRazonado::class, $ejecucion])`. Rutas: `POST ejecuciones/{ejecucion}/excepciones`, `PATCH excepciones/{excepcion}`, `DELETE excepciones/{excepcion}`. Wayfinder regenerado.

### 3. `codigo` requerido solo al crear
Como en secciones, `editarExcepcion` ajusta solo `descripcion` y `severidad`; el `codigo` no se reescribe y la UI de edición no lo envía. El Form Request exige `codigo` en `store` (`required`) y no en `update` (`sometimes`), detectando el caso por la presencia del route param `excepcion`.

### 4. Badge de severidad en la UI
La sección "Excepciones" (hoy solo lectura) pasa a editable. Cada excepción muestra su severidad con un color por nivel (`info` neutro, `advertencia` ámbar, `critico` rojo) usando tokens semánticos del tema. Los controles de autoría se gatean con `editable` (`en_elaboracion`) + `auth.permissions`.

## Risks / Trade-offs

- **[Race: la ejecución cambia de estado mientras se edita una excepción]** → La Policy verifica `en_elaboracion` en cada request (server-side); un envío a revisión concurrente hace fallar la siguiente escritura con 403.
- **[La UI muestra controles a quien no puede]** → Gating de UI es cosmético; la autorización real está en Policy + Form Request. Peor caso: un botón que devuelve 403.
- **[`vinculable` sin exponer]** → Las excepciones quedan como salvedades de texto libre a nivel de ejecución; vincularlas a un registro concreto es un refinamiento posterior, no un bloqueo.
- **[Consistencia de permisos en instalaciones existentes]** → No aplica: no se agregan permisos ni transiciones; `informes.elaborar` ya está sembrado.

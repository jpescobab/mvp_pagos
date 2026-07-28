## Context

`CorteReportabilidadService` ya expone las primitivas de contenido de un corte —`agregarItem(corte, vinculable, etiqueta)` y `capturarSnapshot(corte, payloadCrudo, item)`— ambas con guarda `corteYaPublicado()` y con tests unitarios. Lo que falta es un orquestador que, dado un corte en `borrador`, recolecte las entidades del período y llame a esas primitivas por cada una; hoy no existe controlador, ruta, job ni UI que lo dispare, así que los cortes se crean y publican vacíos.

Un `corte_reportabilidad` pertenece a un `periodo_reportabilidad` (con `codigo` y rango de fechas). Los `corte_reportabilidad_item` son polimórficos (`vinculable` + `etiqueta`); los `snapshot_corte_reportabilidad` guardan `payload_crudo` + `hash` + `capturado_en`, opcionalmente ligados a un item. La entidad reportable del módulo implementado es `casos_pago_proveedor`, que ya trae un campo `periodo` (texto, proveniente de la importación SGF).

## Goals / Non-Goals

**Goals:**
- Una acción "Generar corte" que puebla un corte en `borrador` con un ítem + snapshot por cada entidad reportable del período, en una transacción, gated por `reportabilidad.generar_corte`.
- Regeneración reemplazante e idempotente en `borrador`.
- Arquitectura extensible a más entidades reportables sin reabrir el orquestador.
- UI para disparar la generación y ver los ítems resultantes.

**Non-Goals:**
- Implementar más de una entidad reportable (`egresos_cgu` y otras quedan como extensión futura; solo se deja la interfaz).
- Tocar el workflow, la publicación del corte o el flujo de informes razonados.
- Migraciones o cambios de esquema (las tablas existen).
- Programar la generación (jobs/scheduler): la acción es síncrona y disparada por el usuario.

## Decisions

### 1. `GeneradorCorteReportabilidadService` separado, con estrategias por entidad reportable
Un service nuevo orquesta; `CorteReportabilidadService` conserva sus primitivas intactas. El generador expone `generar(CorteReportabilidad $corte): void` y, dentro de una `DB::transaction`, itera un conjunto de **fuentes reportables** (una interfaz/estrategia por tipo de entidad). Cada fuente sabe: (a) consultar sus entidades para un período, (b) construir la etiqueta del ítem, (c) serializar el `payload_crudo` de la entidad. En este alcance se implementa una sola fuente: `FuenteReportableCasosPagoProveedor`.
**Por qué:** separa "qué entidades entran al corte" (fuentes, que crecerán) de "cómo se materializa un corte" (orquestador, estable). Alternativa descartada: un `match` sobre tipos dentro del generador — obligaría a editar el orquestador por cada entidad nueva. Alternativa descartada: meter la lógica en `CorteReportabilidadService` — mezcla primitivas con política de recolección y engorda un service ya usado por el controlador de cortes.

### 2. Regeneración = reemplazo transaccional
`generar()` primero elimina los `items` y `snapshots` existentes del corte (solo posible en `borrador`; la guarda ya lo impide en `publicado`) y luego los vuelve a capturar. Así el corte refleja el estado actual de las entidades sin duplicar, y la acción es idempotente respecto al estado de los datos.
**Por qué:** un corte en borrador es un "trabajo en progreso"; regenerar tras cambios en los casos es el caso de uso real. Alternativa descartada: append (acumularía duplicados); alternativa descartada: no-op si ya tiene contenido (obligaría a un "limpiar" separado).

### 3. Emparejamiento período↔entidad por `codigo`, verificado en apply
La fuente de casos filtra `CasoPagoProveedor::where('periodo', $corte->periodoReportabilidad->codigo)`. El campo `periodo` del caso viene de SGF como texto; **en apply se verifica el formato real** (p. ej. `'2026-06'`) contra el `codigo` del período y, si difiere, se normaliza el match en la fuente (una sola función, aislada). No se asume ciegamente igualdad de strings.
**Por qué:** mantener el criterio de pertenencia dentro de la fuente lo hace verificable y ajustable sin tocar el orquestador.

### 4. `payload_crudo` = estado serializado de la entidad al momento del corte
Cada snapshot guarda `$entidad->toArray()` (atributos + relaciones cargadas que interesen) como `payload_crudo`, con `hash = hash('sha256', json_encode($payload))`. Es "evidencia inmutable de datos internos en un corte", el análogo interno del snapshot de datos externos.
**Por qué:** cumple la regla de snapshot obligatorio (payload/fuente/fecha/hash) reutilizando la primitiva `capturarSnapshot` existente. La "fuente" queda implícita en el tipo del ítem vinculado.

### 5. Controlador liviano + policy
`CorteReportabilidadController@generar` (o `GenerarContenidoCorteController`) autoriza `generar` y delega en el generador; sin lógica de negocio. `CorteReportabilidadPolicy@generar` → `reportabilidad.generar_corte`. Si la policy es nueva, se registra a mano en `AppServiceProvider::configureAuthorization()`. El permiso se siembra en el seeder del dominio de reportabilidad y, si algún test afirma la lista exacta, se actualiza.

### 6. Frontend: extender `cortes/show.tsx` y su Resource
Botón "Generar corte" visible solo con el corte en `borrador` y `auth.permissions` incluyendo `reportabilidad.generar_corte`. El `CorteReportabilidadResource` expone la lista de `items` con un resumen de la entidad vinculada (tipo legible, identificador, etiqueta); el conteo de snapshots ya existe. Ruta consumida por helper Wayfinder (import con nombre), regenerado tras agregar la ruta.

## Risks / Trade-offs

- **[El match por `periodo` string podría no coincidir con el `codigo` del período]** → Se verifica el formato real en apply y se normaliza dentro de la fuente; se cubre con un feature test que crea casos con el `periodo` esperado. Si el dato real resulta inconsistente, la fuente es el único punto a ajustar.
- **[Serializar `toArray()` como payload podría incluir campos ruidosos o faltar relaciones clave]** → La fuente define explícitamente qué serializa (no un `toArray()` ciego), acotando el payload a los campos relevantes del caso; documentado en la fuente.
- **[Regenerar borra snapshots previos]** → Es intencional y solo en `borrador`; un corte publicado es inmutable (guarda existente) y su evidencia queda congelada. Se cubre con test de que publicar tras generar preserva el contenido.
- **[Un período con muchos casos genera muchas filas en una transacción]** → Aceptable para los volúmenes institucionales esperados; si creciera, la fuente puede paginar/insertar por lotes sin cambiar la interfaz. Se mide en apply si el volumen real lo amerita (no se optimiza a ciegas).
- **[Agregar `reportabilidad.generar_corte` puede romper un test de lista de permisos]** → Se actualiza en el mismo change; aditivo e idempotente. Invalidar la caché de permisos tras sembrar.

## Migration Plan

Sin migraciones. Despliegue estándar: agregar service/fuente/controlador/ruta/policy, sembrar el permiso (`db:seed` idempotente) e invalidar caché de permisos, regenerar Wayfinder en build. Rollback: revertir el commit; el contenido generado en cortes en `borrador` es inerte y regenerable.

## Open Questions

- ¿Debe `egresos_cgu` entrar como segunda fuente reportable en este change o quedar para uno posterior? Propuesta: posterior (una sola fuente ahora mantiene el change acotado y prueba la arquitectura de fuentes).

## Context

`CasoPagoProveedor::cfinancieroId()` (`app/Models/CasoPagoProveedor.php:122-125`) hoy es `return $this->procesoAdquisicion?->ccosto?->cfinanciero_id;` — sin vínculo a `proceso_adquisicion`, retorna `null`. Dos guardrails distintos consumen ese valor, en dos momentos distintos del ciclo de vida:

1. **`RevisionEgresoService::jurisdiccionDeterminable()`** (`app/Services/PagoProveedores/RevisionEgresoService.php:187-200`) llama `caso->cfinancieroId()` en vivo, en el momento de `aprobarPago()` desde la instancia Finanzas. Si es `null`, bloquea la aprobación.
2. **`EgresoCgu::actualizarCfinancieroSiFalta()`** (`app/Models/EgresoCgu.php:72-83`) persiste `egreso.cfinanciero_id` una sola vez ("si falta"), al momento en que un `caso_pago_proveedor` se agrupa a un `EgresoCgu` (`EgresoCguController.php:86`). Ese valor persistido es el que después usa `EgresoCguPolicy::mismaJurisdiccion()` (`app/Policies/EgresoCguPolicy.php:52-60`, vía `EgresoCgu::jurisdiccionId()`) para filtrar la revisión Zonal por jurisdicción del usuario.

En el entorno actual los 2 `egresos_cgu` existentes ya tienen `cfinanciero_id = null` (se agruparon antes de que existiera cualquier vínculo a adquisición), así que arreglar solo `cfinancieroId()` desbloquea la aprobación desde Finanzas pero deja el guardrail de Zonal con el mismo problema un paso después, porque `actualizarCfinancieroSiFalta()` ya "gastó" su única oportunidad de escribir con un valor `null`.

## Goals / Non-Goals

**Goals:**
- Un `cfinanciero` por defecto, configurable vía `.env`/`config/pago-proveedores.php`, usado solo cuando no hay `proceso_adquisicion` vinculado.
- El vínculo real a `proceso_adquisicion` sigue teniendo prioridad absoluta sobre el default.
- El guardrail de Finanzas (`jurisdiccionDeterminable`) y el de Zonal (`mismaJurisdiccion`, vía `egreso.cfinanciero_id`) quedan ambos resueltos con el mismo cambio — no solo el primero.
- El código configurado (`1400`) se resuelve contra un `cfinanciero` real y activo, no un id inventado.

**Non-Goals:**
- No se automatiza la creación de `procesos_adquisicion` ni se backfillea el vínculo en casos existentes.
- No se cambia la lógica de comparación de jurisdicción en sí (`mismaJurisdiccion`), solo se asegura que reciba un valor no nulo.
- No se extiende este default al módulo Adquisiciones en este change — si ese módulo lo necesita, es una propuesta aparte que puede reusar el mismo config.

## Decisions

**1. El default se configura por código institucional (`1400`), no por id interno, y se resuelve con cache corto.**
Se agrega `config/pago-proveedores.php` con `'cfinanciero_default_codigo' => env('PAGO_PROVEEDORES_CFINANCIERO_DEFAULT_CODIGO', '1400')`. Un servicio nuevo `App\Services\PagoProveedores\CfinancieroPorDefectoResolver` resuelve ese código a `cfinanciero_id` con `Cache::remember(..., now()->addHour())`. Se prefiere código sobre id interno porque las tablas maestras usan el código institucional como clave estable entre entornos (el `id` interno de un seed puede variar); alternativa descartada: guardar el `id` numérico directamente en `.env`, frágil entre entornos con seeds distintos.

**2. La resolución del default vive en un servicio, no en el modelo `CasoPagoProveedor`.**
`cfinancieroId()` sigue siendo `procesoAdquisicion?->ccosto?->cfinanciero_id ?? app(CfinancieroPorDefectoResolver::class)->resolver()`. Alternativa descartada: resolver el config/cache directamente dentro del modelo Eloquent — se prefiere un servicio inyectable para mantener el modelo sin dependencias de infraestructura (cache/config) y para que sea testeable de forma aislada.

**3. `aprobarPago()` persiste el default en el `EgresoCgu` antes de avanzar a Zonal, reusando `actualizarCfinancieroSiFalta()`.**
Justo después de pasar `jurisdiccionDeterminable()` y antes de ejecutar la transición, `RevisionEgresoService::aprobarPago()` llama `$caso->egresoCguItems->first()?->egreso?->actualizarCfinancieroSiFalta($caso)`. Como el método ya es idempotente ("si falta"), esto cierra el hueco para los `egresos_cgu` que quedaron con `cfinanciero_id = null` porque se agruparon antes de que existiera cualquier vínculo o default. Alternativa descartada: hacer que `EgresoCguPolicy::mismaJurisdiccion()` llame `caso->cfinancieroId()` en vivo en lugar de leer `egreso.cfinanciero_id` — se descarta porque un `EgresoCgu` agrupa múltiples `casos_pago_proveedor` (`egresos_cgu_items`), y su jurisdicción debe seguir siendo un valor único persistido a nivel de grupo, no recalculado por caso.

**4. Si el código configurado no resuelve a un `cfinanciero` activo, se loguea un warning y se retorna `null` — no se lanza excepción.**
Preferido sobre fallar duro en boot/resolución: un typo en la variable de entorno no debe convertir un flujo de aprobación en un error 500 opaco para el usuario de Finanzas; debe degradar al comportamiento actual (bloqueo con el mensaje ya existente), quedando visible solo en logs para quien opera el entorno. Se agrega un test de config/seeder que verifique en CI que el código por defecto existe como `cfinanciero` activo, para atrapar el typo antes de producción en vez de en runtime.

## Risks / Trade-offs

- [Riesgo] Todo caso sin `proceso_adquisicion` vinculado queda agrupado bajo el mismo `cfinanciero` "1400" (Administración Zonal), aunque en la realidad pertenezca a otro centro financiero, afectando a qué Administrador Zonal le llega la revisión. → Mitigación: decisión de negocio explícita del usuario para este momento operativo (mientras Adquisiciones no esté en uso en paralelo); el vínculo manual real sigue disponible y sigue teniendo prioridad total sobre el default en cuanto se cargue.
- [Riesgo] Cache de 1h del resolver queda desactualizado si alguien desactiva o cambia el código `1400` en caliente. → Mitigación: TTL corto y dato administrado manualmente con baja frecuencia de cambio; aceptable para este caso de uso.
- [Riesgo] Un typo en `PAGO_PROVEEDORES_CFINANCIERO_DEFAULT_CODIGO` reintroduce el bloqueo original silenciosamente. → Mitigación: log de warning explícito + test que valida el código default contra la tabla `cfinancieros` real (seeder/CI), no solo contra config.

## Migration Plan

1. Sin migración de esquema (no hay columnas ni tablas nuevas).
2. Agregar `config/pago-proveedores.php`, variable en `.env.example` y en `.env` local.
3. Deploy normal, sin downtime.
4. Rollback: revertir el commit vuelve `cfinancieroId()` a su comportamiento anterior (siempre `null` sin vínculo) — no hay pérdida de datos ni de vínculos reales ya cargados.

## Context

Esta es la primera tarea con una integración a una API externa real. Se verificó contra la API de la CMF (`api.cmfchile.cl`) con una key real del usuario:
- `dolar`, `uf/{año}/{mes}`, `utm/{año}` funcionan y devuelven datos reales.
- `uta` devuelve HTTP 302 — no existe como endpoint; se confirmó por fuente externa que UTA SHALL calcularse como `UTM(diciembre del año comercial) × 12`.
- Los valores vienen en formato chileno (`40.809,44`), requieren parseo explícito.
- `uf/{año}/{mes}` devuelve el mes calendario completo, no el tramo día-10-a-día-9 que pide el harness — hay que combinar dos meses calendario.
- `ipc` se publica con ~1 mes de rezago.

## Goals / Non-Goals

**Goals:**
- Importar UF/UTM/IPC mensualmente (día 10), USD diariamente, y calcular UTA cuando corresponda.
- Snapshot completo: cada importación y cada valor conservan su payload de origen y hash.
- Selector de indicadores reutilizable por cálculos futuros, con fallback parametrizado para USD.
- Tests sin red real (`Http::fake()`).

**Non-Goals:**
- No se integra SII en esta tarea — la CMF cubre los 5 indicadores requeridos (UF, USD, UTM, IPC directos; UTA calculada). SII queda disponible como fuente futura si algún indicador lo requiriera.
- No se modela una tabla `parameters` institucional — la regla de fallback de USD vive en `config/indicadores.php` hasta que esa tabla exista en una tarea futura.
- No se construye UI — sigue el patrón backend-only de las tareas 1-3.

## Decisions

- **UTA se calcula, no se importa.** Confirmado que la CMF no expone UTA (HTTP 302) y que la fórmula legal es `UTM(diciembre del año comercial) × 12`. Se guarda con `fuente = calculado_utm`, referenciando en `source_payload` qué registro UTM se usó — mantiene trazabilidad sin necesitar una segunda fuente externa.
- **El job diario de USD no aplica fallback al importar — solo lo registra como advertencia.** El fallback (`config('indicadores.usd_fallback')`) se aplica en `IndicadorEconomicoSelector` al CONSULTAR un valor que no existe para una fecha exacta, no al importar. Esto separa "qué pasó realmente" (lo que importa el job) de "qué valor usar si falta uno" (decisión de consumo). Evita que el job invente datos para una fecha que la CMF nunca publicó.
- **Tramo mensual de UF combinando dos meses calendario.** `uf/{año}/{mes}` solo devuelve el mes calendario completo; el job mensual pide el mes actual y el siguiente, filtra días >= 10 del actual y <= 9 del siguiente, y guarda cada fila con `vigente_desde`/`vigente_hasta` iguales a los límites del tramo.
- **Unicidad por `(tipo, fecha_valor)` y `(tipo, periodo)` como índices únicos separados** (no una sola columna). PostgreSQL no considera iguales los `NULL` en restricciones unique, así que conviven sin conflicto: UF/USD siempre tienen `periodo = null`, UTM/UTA/IPC siempre tienen `fecha_valor = null`.
- **`indicadores_economicos` es append-only** (`created_at` sin `updated_at`) — un valor económico ya capturado no se edita; si la CMF publicara una corrección, eso es un caso fuera de alcance de esta tarea (se trataría como una nueva importación, no una edición retroactiva).
- **`CMF_API_KEY` solo en `.env`** (real) y `.env.example` (vacío) — nunca en código ni en artefactos de OpenSpec versionados.

## Risks / Trade-offs

- **[Riesgo] La key de la CMF podría cambiar de plan/cuota** → Mitigación: el cliente HTTP queda centralizado en `CmfClient`, un solo punto de cambio si la URL/autenticación cambia.
- **[Riesgo] El cálculo de UTA depende de que la UTM de diciembre ya esté publicada** → Mitigación: el job simplemente no crea el registro UTA si esa UTM todavía no existe (no es un error, es esperado la mayor parte del año); se reintenta automáticamente cada vez que corre el job mensual.
- **[Riesgo] Tests dependientes de fechas reales (`Carbon::now()`)** → Mitigación: se usa `Carbon::setTestNow()` en los tests para fijar la fecha y hacer las aserciones deterministas.

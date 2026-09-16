## Context

`ClienteMedidor` (`app/Models/ClienteMedidor.php`) ya existe como catálogo maestro (`numero_cliente`, `proveedor_id`, `ccosto_id`, `tipo_suministro`, `direccion_suministro`) pero sin ninguna relación hacia el flujo de pagos. `CasoPagoProveedor` recibe desde SGF la cabecera de toda factura/boleta (RUT del proveedor, monto, período, folio) sin detalle de consumo. Se calibró este diseño con 8 boletas eléctricas reales de EDELAYSEN (proveedor único en la muestra, Región de Aysén — el Poder Judicial tiene tribunales en otras regiones con otros proveedores eléctricos), leídas y sintetizadas con evidencia real, no supuestos. Hallazgos que determinan las decisiones de abajo:

- El estado de pago **nunca** aparece impreso en la boleta. El único dato relacionado a pago ("Último pago: [fecha] por [monto]") corresponde sistemáticamente a la boleta **anterior**, nunca a la actual — inferir estado de pago de ese campo sería un error real, no solo un riesgo teórico.
- El código de tarifa real impreso (BT1, BLANCA I, ECO_AYRE, BT43, BT3PP) **no coincide** con las clasificaciones informales que la institución usa hoy para nombrar carpetas ("NORMAL"/"CALEFACCIÓN") — dos medidores "NORMAL" tenían tarifas distintas (BT43 y BT3PP).
- El "historial de consumo" de la boleta es un **gráfico de barras**, no una tabla de texto estructurada, con inconsistencias reales (boletas con 0 kWh en los 13 meses igual imprimen "consumiste 100% más energía").
- Un medidor físico puede compartirse entre 2+ centros de costo (caso real: carpeta "GARANTIA Y TOP COMPARTEN MISMOS MEDIDORES"), pero esa relación no consta en ningún documento — es conocimiento externo del usuario.
- El RUT del cliente (la institución) es el mismo en las 8 boletas — el RUT que identifica es el del **proveedor**, y la clave real que distingue cada punto de suministro es `numero_cliente`.
- No existe ninguna capacidad de extracción de texto/datos de PDF en el proyecto hoy (verificado exhaustivamente: sin librería, sin código).

## Goals / Non-Goals

**Goals:**
- Registrar el detalle de consumo de un servicio básico (medidor, período, consumo, tarifa, monto) vinculado a la cabecera ya importada desde SGF (`CasoPagoProveedor`).
- Reconocer automáticamente (por consulta simple a BD, no por lectura de PDF) cuándo un `CasoPagoProveedor` corresponde a un proveedor de servicios básicos ya conocido.
- Adjuntar el PDF de la boleta como evidencia, reutilizando el modelo `Documento`/`VersionDocumento` ya existente (hash, metadata, trazabilidad) en vez de duplicar ese mecanismo.
- Construir el historial de consumo acumulando los registros que el propio sistema va guardando mes a mes — nunca parseándolo de un PDF.
- Permitir crear un `ClienteMedidor` al vuelo si el número de cliente de una boleta todavía no está en el catálogo.

**Non-Goals:**
- Extracción automática de datos desde el PDF (OCR/parsing) — no hay evidencia de que valga la pena el esfuerzo/riesgo todavía; se retoma como change futuro si aparece esa evidencia.
- Modelar el medidor físico compartido entre múltiples centros de costo — caso real confirmado pero de baja frecuencia aparente en la muestra (1 de 8), y no consta en ningún documento; se deja como limitación conocida.
- Desglose de líneas de cargo (demanda máxima, fondo de estabilización, arriendo de medidor, etc.) — varían mucho según tarifa y tienen bajo valor de negocio para el objetivo de este change (seguimiento de consumo y gasto total, no auditoría de facturación eléctrica línea por línea). Se guardan los montos agregados (neto/IVA/exento/total), no el detalle de cada cargo.
- Importar las boletas históricas reales usadas para calibrar este diseño — queda para una fase de datos posterior, separada de este change (mismo orden que Contratos: primero el módulo, después el import).
- Inferir o gobernar el estado de pago desde `ConsumoBasico` — el estado de pago sigue siendo exclusivamente del workflow de `CasoPagoProveedor`, sin cambios.
- Backfill/importación masiva del historial de consumo impreso en una boleta — el historial se construye solo hacia adelante.

## Decisions

### 1. `ConsumoBasico` vinculado 1:1 y obligatoriamente a `CasoPagoProveedor`
A diferencia de `ContratoCuota` ↔ `CasoPagoProveedor` (vínculo opcional, se puede generar el calendario sin que exista todavía un caso de pago), aquí el flujo real es el inverso: el `CasoPagoProveedor` ya existe (SGF lo importó primero) y completar el detalle de consumo es una acción sobre ese caso puntual — decisión explícita del usuario. `caso_pago_proveedor_id` es `NOT NULL` y `UNIQUE` (un caso de pago tiene a lo sumo un detalle de consumo).

Alternativa descartada: vínculo opcional como en `ContratoCuota` — se descarta porque no refleja el flujo real confirmado por el usuario (la cabecera SGF siempre existe antes del detalle).

### 2. Reconocimiento de "proveedor de servicios básicos" vía `ClienteMedidor.proveedor_id`, sin campo nuevo en `Proveedor`
La pregunta "¿este `CasoPagoProveedor` corresponde a un servicio básico?" se resuelve con `ClienteMedidor::where('proveedor_id', $caso->proveedor_id)->exists()` — reutiliza un dato que ya existe, sin agregar ninguna columna de clasificación a `Proveedor` (que hoy no tiene ninguna, y `giro`/`rubros` son texto libre sin uso real).

Alternativa descartada: agregar un campo `es_servicio_basico`/`categoria` a `Proveedor` — se descarta porque duplicaría información que `ClienteMedidor.proveedor_id` ya expresa, y porque el equipo de Contratos/CDP repetidamente prefirió reutilizar datos existentes antes que agregar clasificaciones nuevas sin necesidad confirmada.

### 3. El PDF se adjunta reutilizando `Documento`/`VersionDocumento`/`VinculoDocumento`, con un controlador propio (no se toca `GestorDocumentoProceso`)
`VinculoDocumento` ya es polimórfico a nivel de esquema y modelo (`vinculable_type`/`vinculable_id`, sin FK dura a `procesos`) pero hoy el único consumidor es `Proceso` (`GestorDocumentoProceso::subirYVincular(Proceso $vinculable, ...)` tiene el tipo fijado, y las rutas están anidadas bajo `procesos/{proceso}/documentos/...`). Se agrega a `ConsumoBasico` la misma relación `vinculosDocumento(): MorphMany` que ya tiene `Proceso`, y un controlador nuevo y acotado (`ConsumoBasicoDocumentoController`, con un único endpoint de subida — sin versiones ni validaciones, que `ConsumoBasico` no necesita) que reutiliza los modelos `Documento`/`VersionDocumento` para heredar hash/metadata/trazabilidad gratis.

Alternativas descartadas:
- Ampliar `GestorDocumentoProceso` para aceptar cualquier `Model` — se descarta por ahora porque toca código compartido y ya probado del árbol de `Proceso`, con más riesgo que beneficio para una necesidad mucho más simple (`ConsumoBasico` no necesita checklist ni validación documental, solo adjuntar un PDF de evidencia).
- Guardar el PDF como una columna simple (`ruta_archivo`) directamente en `ConsumoBasico`, sin pasar por `Documento` — se descarta porque duplicaría manualmente el hash/metadata/trazabilidad que `VersionDocumento` ya resuelve, e iría contra la convención ya establecida en todo el proyecto de que la evidencia subida por el usuario pasa por el módulo de Documentos.

### 4. Sin tabla de líneas de cargo — solo montos agregados
Se guardan `monto_neto`, `iva`, `monto_exento` (puede ser negativo, visto en boletas reales), `saldo_anterior`, `monto_total` — el desglose de cargos (demanda máxima, fondo de estabilización, arriendo de medidor, etc., que varía según tarifa) no se modela como tabla hija en este change.

Alternativa descartada: tabla `consumo_basico_cargos` (línea/monto) — se descarta por bajo valor de negocio para el objetivo de este change (seguimiento de consumo/gasto agregado, no auditoría de facturación eléctrica) y para no construir una estructura más compleja de la necesaria sin un caso de uso confirmado que la requiera.

### 5. `tarifa` como texto libre (el código real impreso), no un catálogo/enum
Se confirmó con evidencia real que el código de tarifa (BT1, BLANCA I, ECO_AYRE, BT43, BT3PP...) no es una lista cerrada conocida de antemano, y no coincide con la clasificación informal que la institución usa hoy. Se guarda tal como aparece impreso, texto libre — mismo criterio ya usado para `materia`/`submateria` en Contratos (sin catálogo dedicado hasta que exista necesidad real).

### 6. `lectura_estimada` como columna booleana explícita
La boleta real trae un flag propio ("consumo estimado por ausencia de lectura") independiente de que el consumo resulte en 0 — no se puede inferir de `consumo == 0`, así que se modela como columna separada en vez de derivarla.

### 7. El historial de consumo es una consulta ordenada, no una tabla/columna separada
Mismo patrón que `ContratoCuota` (ordenada por `numero_cuota`): el "historial" de un `ClienteMedidor` es simplemente sus `ConsumoBasico` ordenados por `fecha_inicio_lectura` (o el campo de período equivalente) — no se persiste ningún resumen/serie aparte.

## Risks / Trade-offs

- [Riesgo] Sin tabla de líneas de cargo, se pierde el desglose fino de por qué varió el monto entre dos boletas (ej. cargo por demanda máxima que solo aparece algunos meses) → Mitigación: aceptado como no-goal explícito; el PDF adjunto sigue siendo la fuente de verdad para ese detalle si alguna vez se necesita, y agregar la tabla de líneas después no requiere rediseñar `ConsumoBasico`.
- [Riesgo] El medidor compartido entre centros de costo no modelado puede llevar a que un usuario registre el mismo consumo dos veces (una por cada centro de costo) o a que un centro de costo quede sin su gasto de electricidad reflejado → Mitigación: aceptado como limitación conocida; si aparece con más frecuencia de la vista en esta muestra (1 de 8), se aborda en un change de seguimiento con evidencia real de cuántos casos hay.
- [Riesgo] El controlador de documentos propio para `ConsumoBasico` (en vez de extender `GestorDocumentoProceso`) puede generar código parcialmente duplicado si en el futuro aparecen más entidades no-`Proceso` que necesiten adjuntar documentos → Mitigación: aceptado por ahora (una sola instancia no justifica una abstracción); si aparece una tercera entidad con la misma necesidad, ahí sí conviene generalizar `GestorDocumentoProceso` con la evidencia de 2-3 casos reales en vez de 1.

## Migration Plan

1. Migración nueva `create_consumos_basicos_table`: FKs a `clientes_medidores` (`cliente_medidor_id`, restrict/cascade a definir en tasks) y `casos_pago_proveedor` (`caso_pago_proveedor_id`, `unique`, `cascadeOnDelete` o `restrictOnDelete` a definir), más las columnas de la sección Decisions. Aditiva, sin tocar datos existentes.
2. `ClienteMedidor` gana la relación `consumos(): HasMany` y `vinculosDocumento` no aplica a él (el documento se adjunta a `ConsumoBasico`, no al medidor).
3. `ConsumoBasico` gana `vinculosDocumento(): MorphMany` (mismo patrón que `Proceso`).
4. Permisos nuevos `consumo_basico.crear`/`consumo_basico.editar`/`consumo_basico.ver`, seedeados de forma aditiva (idempotente) — a confirmar en qué seeder viven (probablemente uno propio, `WorkflowPagoProveedoresSeeder` o el que se use para Contratos como referencia, ya que este dominio vive conceptualmente cerca de Pago de Proveedores).
5. Sin cambios al workflow de `CasoPagoProveedor` ni a la importación SGF — el vínculo es puramente informativo.

## Open Questions

- ¿En qué seeder de permisos exactamente deben vivir `consumo_basico.*`, y qué roles los reciben? A confirmar en `/opsx:apply` contra `FuncionariosCapjSeeder`, mismo criterio que se usó para Contratos.
- ¿`ConsumoBasico` vive en `app/Services/ConsumoBasico/`/`app/Http/Controllers/ConsumoBasico/` como dominio propio, o dentro de `PagoProveedores`? Se asume dominio propio (mismo criterio que Contratos, que también bridgea Adquisiciones/Pago de Proveedores) — a confirmar en tasks.md.

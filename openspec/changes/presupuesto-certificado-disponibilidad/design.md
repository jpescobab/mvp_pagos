## Context

`presupuesto-importacion-cgu` (archivado) ya modela `presupuestos` (línea `cfinanciero × catalogo
× plan_tarea × año`, `monto_asignado`), `planes_tarea`, `importaciones_presupuesto`, y amplió
`catalogos`/`items`/`asignaciones` a Subtítulo 22/29/31. Ese change deliberadamente NO calculó
saldo comprometido — dejó explícito que eso era trabajo del CDP.

El diseño del CDP se construyó con evidencia real, no teórica: 6 PDF de CDP reales emitidos por
CAPJ Coyhaique (folios 066, 112, 115, 123, 125, 134, 141-2026, cubriendo Gasto Operacional e
Iniciativa, CLP/UF/USD, borrador y firmado, y una anulación real) más la planilla de control que
la Corporación usa hoy. Varias hipótesis de diseño previas se corrigieron al contrastarlas con
estos documentos (ver sección Decisiones).

Este change reutiliza patrones ya establecidos en el dominio Adquisiciones —
`ProcesoAdquisicion` + `ProcesoAdquisicionService::crear()` + `WorkflowAdquisicionesSeeder` +
`TransicionProcesoAdquisicionController` — en vez de inventar un mecanismo propio de estados.

## Goals / Non-Goals

**Goals:**
- Modelar el CDP con su ciclo de vida real: Borrador (reserva folio, no compromete) → Firmado
  (compromiso real, inmutable desde ahí).
- Calcular y validar saldo disponible de una línea de `presupuesto` al firmar, alertando (no
  bloqueando) en caso de sobregiro.
- Replicar la plantilla PDF exacta que CAPJ usa hoy, incluida su nota legal configurable por año.
- Modelar la anulación como un documento nuevo (nunca editar un CDP firmado), 100% del monto,
  referenciado al original.
- Dejar un vínculo de datos, no gobernante, hacia Adquisiciones y Mercado Público.

**Non-Goals:**
- No se calcula "ejecutado" ni se descuenta el compromiso al pagar — eso es
  `presupuesto-ejecucion-desde-pago` (change 3, futuro), que se disparará desde
  `marcar_pagada_bancoestado`.
- No se modifica `WorkflowAdquisicionesSeeder` ni se agrega ningún `documentos_requeridos` a una
  transición de Adquisiciones — el proceso de compras todavía no está redefinido por el usuario;
  este change deja el mecanismo disponible (vía `Documento`/`VinculoDocumento`) pero no lo activa.
- No se modela `Requerimiento N°` como entidad propia — solo se guarda el número.
- No se soporta cabecera+líneas múltiples por CDP — un CDP siempre tiene una sola cuenta
  presupuestaria (confirmado con evidencia real, ver Decisiones).

## Decisions

### 1. Una cuenta presupuestaria por CDP, sin líneas múltiples

Evidencia: los 6 PDF reales revisados no tienen fila repetible en el bloque "El presupuesto
disponible ha sido reservado, de acuerdo al siguiente detalle" — es un único set de campos fijos.
La Nota 2 impresa (*"si hay montos en distintos subtítulo/ítem/asignación presupuestarios, se
deben identificar cada uno de éstos por separado"*) significa **emitir un CDP distinto por
cuenta**, no una tabla multi-línea dentro del mismo folio. Confirmado también por el usuario
("es un cdp por proceso no tiene líneas"). **Alternativa descartada**: cabecera + N líneas — se
había considerado por una lectura inicial ambigua de la Nota 2, pero ningún PDF real la respalda.

### 2. `Programa Presupuestario` es dato de control, no campo del PDF

Ninguno de los 6 PDF reales lo imprime. Confirmado explícitamente por el usuario: "el campo
Programa Presupuestario no lo utiliza el formulario CDP, solo es de control". Se modela como
columna en la tabla (para reportabilidad futura) pero se excluye de la plantilla dompdf.

### 3. El folio se asigna al crear el Borrador

Evidencia: el PDF de CDP 066-2026 (confirmado por el usuario como borrador sin firmar) ya trae el
folio impreso pero no tiene bloque de firma electrónica, código de verificación ni QR — a
diferencia de los folios firmados (115, 125, 134, 141), que sí los traen. `folio` se genera al
ejecutar `CrearBorradorCertificadoDisponibilidadService`, no al firmar.

**Corrección post-implementación**: el correlativo del folio es una secuencia **global, única y
autonumérica** — el año es solo el sufijo de display (`CDP {correlativo}-{año}`), no reinicia el
contador. Confirmado por el usuario tras observar en pruebas que un contador por año calendario
repetía "001" entre 2024/2025/2026, lo cual contradice que el número deba ser único.

### 4. El CDP pasa por `TransicionWorkflowService` — no un estado ad-hoc

`App\Models\Proceso` es un wrapper de workflow genérico: `sujeto()` es `MorphTo`, y sus columnas
domain-specific (`modalidad_id`, `tipo_proceso_pago_id`) son nullable — ya está construido para
que un dominio nuevo le cuelgue su propio `sujeto`. `CLAUDE.md` es explícito: *"todo cambio de
estado pasa exclusivamente por `TransicionWorkflowService::execute()`... Detenerse si: se pide
saltarse `TransicionWorkflowService` para cambiar un estado."* El CDP tiene una firma electrónica
real de por medio (compromiso presupuestario) — exactamente el tipo de cambio de estado que la
regla protege.

Se sigue el patrón exacto de `ProcesoAdquisicion`:
- `CertificadoDisponibilidadPresupuestaria` es un modelo de datos plano, **sin columna `estado`
  propia** — `MorphOne proceso()` hacia `Proceso`.
- Nueva `DefinicionWorkflow` (`codigo: presupuesto_cdp`), 2 `EstadoWorkflow` (`borrador`
  `es_inicial`, `firmado` `es_final`), 1 `TransicionWorkflow` (`firmar`,
  `permiso_requerido: presupuesto.firmar_cdp`) — sembrados en `WorkflowPresupuestoCdpSeeder`,
  mismo molde que `WorkflowAdquisicionesSeeder`.
- Crear el Borrador es **creación**, no cambio de estado: `CrearBorradorCertificadoDisponibilidadService`
  hace `CertificadoDisponibilidadPresupuestaria::create()` + `Proceso::create()` directo en la
  misma transacción, igual que `ProcesoAdquisicionService::crear()` — no pasa por
  `TransicionWorkflowService::execute()` porque no hay estado previo que transicionar.
- Firmar sí es un cambio de estado real: `FirmarCertificadoDisponibilidadService` llama a
  `TransicionWorkflowService::execute($cdp->proceso, 'firmar', ...)`, envolviendo la lógica de
  saldo/movimiento/PDF en la misma transacción.

**Alternativa descartada**: una columna `estado` propia en la tabla del CDP, actualizada
directamente por un service (sin pasar por el motor de workflow). Se descartó por violar
`CLAUDE.md` directamente — una versión anterior de este diseño lo proponía por error.

### 5. Anulación = CDP nuevo, 100% del monto, sin campo estructurado de referencia en el PDF

Confirmado por el usuario: la anulación siempre es el 100% del monto original, nunca parcial. El
PDF real de una anulación (CDP 112-2026, en estado borrador) solo trae texto libre en el campo
`Nombre` ("ANULA COMPRA DE TONER...") — sin ningún campo estructurado que referencie el folio
original. La vinculación (`cdp_original_id`) es entonces una decisión de UI (quien anula busca y
selecciona el CDP a anular), no algo parseable del documento. `AnularCertificadoDisponibilidadService`
crea y firma un CDP nuevo con `monto` negativo y mismo `requerimiento_numero`, siguiendo el mismo
ciclo borrador→firmado que cualquier CDP.

### 6. El CDP es standalone; el vínculo con Mercado Público es de datos, sin resolución automática

La Orden de Compra real de Mercado Público (`2182-130-CM26`) no referencia el CDP en ningún campo,
y viceversa — coinciden solo por monto y materia, hoy vinculados a mano por quien administra el
presupuesto. `mercado_publico_tipo`/`mercado_publico_id` quedan como referencia opcional
(`OrdenCompraMercadoPublico` o `LicitacionMercadoPublico` ya importadas), sin intentar resolverla
automáticamente por texto.

### 7. Vínculo con Adquisiciones: FK de datos, sin gate de workflow

El usuario confirmó: el CDP financia una compra que "sigue su curso normal" en Adquisiciones —
existe relación, pero **no se toca `WorkflowAdquisicionesSeeder`** en este change, ya que el
proceso de compras todavía no está redefinido. Se agrega `proceso_adquisicion_id` nullable en el
CDP (mismo patrón que `OrdenCompraMercadoPublico.proceso_adquisicion_id` /
`LicitacionMercadoPublico.proceso_adquisicion_id`) y `HasMany cdps()` en `ProcesoAdquisicion`. El
`Documento` tipo `CDP` generado al firmar se vincula (`VinculoDocumento`) al `Proceso` propio del
CDP y, si corresponde, también al `Proceso` de la adquisición vinculada — esto deja el mecanismo
de `documentos_requeridos => ['CDP']` disponible para una transición de Adquisiciones sin escribir
ese gate ahora, y sin tocar `ResolutorValidacionDocumental`.

### 8. Resolución de la línea de presupuesto: por `cfinanciero_id` directo, no por `ccosto`

`presupuestos` no tiene `ccosto`, solo `cfinanciero` (grano ya cerrado en el change 1). Una
`ProcesoAdquisicion` cuelga de `ccosto_id`, no de `cfinanciero_id` directamente. En vez de forzar
una resolución `ccosto → cfinanciero` (que además puede ser ambigua si el `ccosto` cambia), el CDP
lleva su propio `cfinanciero_id` explícito — coincide 1:1 con los campos "Unidad Ejecutora"/"N° UE"
que ya trae el PDF real. Si el CDP tiene `proceso_adquisicion_id`, la UI puede sugerir el
`cfinanciero_id` derivado de `ccosto.cfinanciero_id` como valor por defecto editable, pero el
campo siempre es explícito en el CDP.

## Risks / Trade-offs

- **[Riesgo] Multi-moneda con paridad manual** → la paridad (UF/USD del día) se ingresa a mano al
  emitir; un valor incorrecto desalinea `monto` (CLP) del real. Mitigación: mostrar el cálculo
  (`total_moneda_compra × paridad`) en la UI antes de confirmar, sin bloquear (igual que el resto
  del módulo prioriza evidencia sobre bloqueo).
- **[Riesgo] Concurrencia al firmar dos CDP contra la misma línea casi simultáneamente** →
  ambos podrían leer el mismo saldo disponible antes de comprometer. Mitigación: bloqueo
  pesimista (`lockForUpdate()`) sobre la línea de `presupuesto` dentro de la transacción de
  `FirmarCertificadoDisponibilidadService`, consistente con "alertar, no bloquear" en el resultado
  pero sin permitir una condición de carrera en la lectura del saldo.
- **[Trade-off] `firmado_por`/`firmado_en` denormalizados en el CDP** en vez de consultarlos desde
  `historial_transiciones_workflow` en cada render → duplica un dato que ya vive en el historial,
  pero evita un join extra en listados/PDF; se aceptan por ser de solo lectura y coherentes con lo
  que ya escribe `TransicionWorkflowService` en la misma transacción.
- **[Riesgo] El vínculo opcional a Adquisiciones queda sin ningún control hasta que se redefina el
  proceso de compras** → un CDP firmado podría no vincularse nunca a la compra que financia, sin
  que nada lo detecte. Aceptado deliberadamente (ver Non-Goals) — es una decisión explícita del
  usuario, no un descuido.

## Addendum — formulario real de captura (post-implementación)

El usuario compartió el prototipo HTML del formulario que usará el personal de finanzas para crear
un CDP (`CDP - Standalone.html`), con lógica JS de referencia para paridad/monto. Contrastarlo con
el diseño ya implementado reveló campos de captura reales que faltaban y una oportunidad de
integración real (no mock) para la paridad:

- **Campos nuevos** (confirmados con el usuario, ninguno se imprime en el PDF — sin evidencia de
  dónde irían en la plantilla real): `nombre_iniciativa` (nullable, requerido si `tipo_gasto=INI`
  — separado de `nombre`/detalle del requerimiento, a diferencia de los CDP reales que solo
  imprimen un "Nombre" único), `medio_solicitud` (`Requerimiento`\|`Oficio`\|`Otro`, nullable),
  `fecha_solicitud` (date, nullable).
- **Moneda**: se descartó agregar EUR (mock la incluía) — sin evidencia real de uso ni fuente de
  paridad; se mantiene `CLP`\|`UF`\|`USD`.
- **Paridad ya no es input del usuario.** El mock la calculaba con un mock JS
  (`mockUFValue`, solo UF, USD quedaba hardcodeado a 1 — claramente un placeholder de prototipo).
  Este sistema ya tiene una fuente real: `App\Services\Indicadores\IndicadorEconomicoSelector`
  (dominio `indicadores-economicos-cmf-sii`, UF/USD/UTM/UTA/IPC ya importados). Se agregó
  `fecha_paridad` (nullable, requerido si `moneda_compra≠CLP`) como único input del usuario;
  `CrearBorradorCertificadoDisponibilidadService::resolverParidadYMonto()` resuelve `paridad` vía
  `IndicadorEconomicoSelector::paraFecha()` y calcula `monto = total_moneda_compra × paridad`
  siempre server-side — el cliente ya no puede enviar `paridad`/`monto` directamente (se
  ignoran/sobrescriben). Sin indicador para la fecha → `CertificadoDisponibilidadPresupuestariaException::sinIndicadorParaFecha()`,
  no se crea el CDP.
- **Endpoint de previsualización** (`GET presupuesto/cdps/paridad?moneda=&fecha=`,
  `ParidadCdpController`): permite mostrar la paridad en vivo en el formulario antes de guardar,
  llamando al mismo selector — puramente informativo, el valor real se vuelve a resolver al
  guardar.
- **Denominación/Unidad Ejecutora/N° UE** ahora se muestran de solo lectura en el formulario al
  elegir la línea de presupuesto (el mock las mostraba como feedback, aunque editables — acá se
  mantienen no editables porque ya se derivan de datos institucionales reales).
- **Bug de concurrencia real encontrado en pruebas** (no viene del mock): el correlativo del folio
  se decidió inicialmente con `SELECT ... ORDER BY id DESC LIMIT 1 FOR UPDATE`, que no serializa de
  forma segura contra inserciones concurrentes bajo READ COMMITTED en PostgreSQL — producía folios
  duplicados. Se reemplazó por usar el `id` autoincremental de la propia fila (atómico de forma
  nativa): se inserta con un folio temporal único y se fija el definitivo una vez conocido el
  `id`, dentro de la misma transacción. También se corrigió que el correlativo es una secuencia
  **global** (no se reinicia por año — el año es solo el sufijo del folio).

## Migration Plan

Migraciones aditivas puras (dos tablas nuevas: `certificados_disponibilidad_presupuestaria`,
`movimientos_presupuestarios`; una columna nueva `HasMany cdps()` no requiere migración en
`procesos_adquisicion`). Sin cambios sobre tablas existentes de Adquisiciones, Pago de Proveedores
ni Workflow — riesgo de romper esos dominios es bajo. Reversible con `php artisan migrate:rollback`
sin pérdida de datos de otros módulos. No requiere backfill (feature nueva, sin CDP previos en el
sistema).

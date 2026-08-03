## Context

Adquisiciones (`ProcesoAdquisicion`, `LicitacionMercadoPublico`, `OrdenCompraMercadoPublico`) y Presupuesto (`CertificadoDisponibilidadPresupuestaria`) ya siguen un patrón consolidado: entidad de dominio propia + `Proceso` genérico de workflow vinculado vía `sujeto` polimórfico (`sujeto_type`/`sujeto_id`), transiciones exclusivamente por `TransicionWorkflowService::execute()`, y vínculos entre entidades como FKs opcionales `nullOnDelete` (evidencia/referencia, nunca disparadores de transición). `Contrato` debe encajar en ese mismo patrón, no inventar uno nuevo.

El dato de negocio real (aportado por el usuario) muestra contratos con o sin convenio de precios; cuando hay convenio, la lista de precios condiciona el costo de las órdenes de compra emitidas contra ese contrato. Hoy ninguna entidad de Adquisiciones puede referenciar un contrato.

Además, muchos contratos (arriendos, mantenciones, servicios recurrentes) se pagan en cuotas periódicas según un calendario propio del contrato — mensual, semestral u otra periodicidad — y no como un pago único. Pago de Proveedores ya tiene un patrón de vínculo manual y opcional entre `caso_pago_proveedor` y `proceso_adquisicion` (`caso_pago_proveedor.proceso_adquisicion_id`, permiso `pago_proveedores.vincular_adquisicion`, ver `openspec/specs/pago-proveedores-sgf/spec.md`); el calendario de un contrato debe seguir el mismo patrón de vínculo con `caso_pago_proveedor`, no crear un mecanismo de pago paralelo.

## Goals / Non-Goals

**Goals:**
- Modelar `Contrato`, `ContratoItemConvenioPrecio` y `ContratoCuota` reutilizando el patrón entidad+workflow ya validado.
- Permitir vincular (opcionalmente, sin obligatoriedad retroactiva) un `ProcesoAdquisicion`, una `LicitacionMercadoPublico` o una `OrdenCompraMercadoPublico` a un `Contrato`, y un `CasoPagoProveedor` a una `ContratoCuota`.
- Generar automáticamente el calendario de cuotas de un contrato a partir de su vigencia y periodicidad, dejando cada cuota editable mientras el contrato esté en `borrador`.
- Dejar el checklist documental y los permisos siguiendo las convenciones existentes.
- Dejar la base de datos preparada (vínculo transitivo `Contrato → ProcesoAdquisicion → CDP`) para que un change futuro de Presupuesto pueda planificar sobre contratos vigentes, sin construir esa planificación ahora.

**Non-Goals:**
- No se valida ni bloquea el monto de una OC contra la lista de precios de un `ContratoItemConvenioPrecio` (queda como referencia visual/manual).
- No se construye importación automática/masiva de contratos desde ninguna fuente externa.
- No se modifica `CertificadoDisponibilidadPresupuestaria` ni se agrega planificación recurrente en Presupuesto.
- No se dispara ninguna transición de workflow de Adquisiciones/Presupuesto/Pago de Proveedores a partir de eventos de `Contrato` o `ContratoCuota`.
- No se crea automáticamente un `caso_pago_proveedor` cuando una cuota vence — el vínculo cuota↔pago es siempre una acción manual del usuario, igual que el resto de los vínculos del módulo.
- No se envían recordatorios/notificaciones de cuotas próximas a vencer en este change.

## Decisions

### 1. `Contrato` es una entidad de workflow propia, no un catálogo simple
Alternativa descartada: modelar Contrato como tabla simple sin workflow (solo CRUD). Se descarta porque la muestra de datos trae un `Estado` real (PENDIENTE/APROBADO) que gobierna si el contrato es utilizable — igual que `ProcesoAdquisicion`/CDP, este estado debe pasar por `TransicionWorkflowService::execute()` y no por un campo `estado` actualizado directo, por la regla "workflow antes que CRUD".

### 2. Estado terminal `aprobado`: inmutable, con vigencia editable solo vía nuevo registro
Se decide que `aprobado` es terminal e inmutable (igual que `firmado` en CDP), y que **no se agrega** un mecanismo de addendum/renovación en este change — si un contrato real tiene un addendum, se registra como un `Contrato` nuevo, opcionalmente enlazado por una futura relación (fuera de alcance). Alternativa descartada: replicar `cdp_original_id` para "contrato reemplazo" — se descarta por ahora porque el usuario no confirmó que los addendums sean un caso frecuente; se prefiere el modelo mínimo y extenderlo cuando aparezca la necesidad real, evitando construir para un caso hipotético.

### 3. Ítems de convenio de precio: carga manual en este change
Se decide que `ContratoItemConvenioPrecio` se crea/edita manualmente (formulario CRUD estándar mientras el `Contrato` esté en `borrador`), sin importador. Alternativa descartada: importador de Excel desde el arranque — se descarta porque no hay una fuente/formato de archivo confirmado por el usuario todavía; agregarlo después sigue el mismo patrón que los importadores existentes de OC/licitaciones 2182 y CGU sin retrabajo del modelo base.

### 4. Vínculos con Adquisiciones/Mercado Público: FK opcional `nullOnDelete`, sin gobierno
Igual patrón que `cdp.proceso_adquisicion_id` y `orden_compra.proceso_adquisicion_id`: FK nullable, vincular/desvincular es una acción explícita y auditada (vía `AuditLogger::log()`), nunca implícita ni obligatoria. `Contrato` no participa en `Proceso.sujeto` de Adquisiciones ni viceversa — son procesos de workflow independientes vinculados por referencia, no por jerarquía.

### 5. `materia`/`submateria` como texto libre
Se confirmó que no existe catálogo dedicado en el sistema (ni siquiera en CDP, que tiene un campo `materia` libre). Se mantiene como texto libre en `Contrato` por consistencia; si se necesita catálogo más adelante, es una migración de tabla maestra independiente que no bloquea este change.

### 6. `id_institucional`: identificador propio de la institución, separado del `id` interno y del `id_proceso_mp`
El usuario confirmó que el "ID" de su planilla de origen (ej. `26417`) es el identificador que la institución usa "para todo efecto" sobre el contrato — no es lo mismo que `id_proceso_mp` (referencia a Mercado Público, string, nullable, formato `2182-5-LE26`) ni que el `id` autoincremental interno de Laravel (nunca expuesto como identificador de negocio). Se agrega como columna `id_institucional` (`unsignedBigInteger`, `unique`, indexado automáticamente por la restricción `unique`), obligatoria en creación y edición. Alternativa descartada: reutilizar `id_proceso_mp` para este propósito — se descarta porque son conceptos distintos (uno es la referencia externa a Mercado Público, el otro es la clave de negocio interna de la institución) y varios registros `FUERA_DE_PORTAL` de la muestra no tienen proceso MP pero sí tienen este identificador institucional.

### 8. Calendario de pago: sub-entidad `ContratoCuota` generada automáticamente desde vigencia + periodicidad
Se agrega `periodicidad_pago` (`mensual`, `bimestral`, `trimestral`, `semestral`, `anual`, `unica`) y `tiene_calendario_pago` (boolean) a `Contrato`, más un `monto_total` (nullable) usado para distribuir el monto entre cuotas cuando corresponde. Al marcar `tiene_calendario_pago = true` con `periodicidad_pago` distinta de `unica`, el sistema genera automáticamente las `ContratoCuota` (fecha de vencimiento espaciada según periodicidad entre `fecha_inicio_vigencia` y `fecha_fin_vigencia`, monto = `monto_total` dividido entre el número de cuotas, ajustando la última cuota por el resto) — evita que el usuario tenga que calcular manualmente cada fecha. Las cuotas siguen siendo editables individualmente (fecha/monto) mientras el `Contrato` esté en `borrador`, para cubrir calendarios irregulares que no calzan con una división exacta. Alternativa descartada: carga 100% manual de cada cuota sin generación automática — se descarta porque el caso más común (mensual/semestral sobre un rango de vigencia) es mecánico y generar automáticamente reduce error humano; se mantiene la edición manual posterior como escape hatch.

### 9. Estado de una cuota: `pendiente`/`pagada` es un atributo simple, no un sub-workflow
Una `ContratoCuota` no es un sujeto de `Proceso`/`TransicionWorkflowService` propio — es un dato hijo del `Contrato`. Su `estado` (`pendiente`, `pagada`) se actualiza mediante una acción explícita y auditada (vincular/desvincular un `caso_pago_proveedor`), igual patrón que `caso_pago_proveedor.proceso_adquisicion_id` en Pago de Proveedores, no como una transición de workflow. "Vencida" es un estado **derivado** (calculado en el momento de la consulta comparando `fecha_vencimiento` contra la fecha actual cuando `estado = pendiente`), no una columna persistida ni un job programado — evita mantener un estado que puede desincronizarse. Alternativa descartada: modelar cada cuota como su propio `Proceso` de workflow — se descarta por sobre-ingeniería frente a un dato que solo tiene dos transiciones posibles (pendiente→pagada) gobernadas por un vínculo, no por reglas de aprobación.

### 10. Permisos nuevos en el seeder de Adquisiciones
`contratos.crear`, `contratos.editar`, `contratos.aprobar`, `contratos.rechazar`, `contratos.ver`, `contratos.vincular_pago` se agregan a `WorkflowAdquisicionesSeeder` (o el seeder de permisos de Adquisiciones vigente) en vez de crear un seeder nuevo — Contratos es funcionalmente parte del dominio de Adquisiciones, igual que Presupuesto/CDP reutiliza roles existentes donde aplica. Roles concretos que reciben cada permiso se confirman durante `/opsx:apply` revisando `FuncionariosCapjSeeder` (mapeo cargo→rol) contra quién gestiona contratos hoy en la operación real.

## Risks / Trade-offs

- [Riesgo] Sin importador desde el inicio, la carga inicial de contratos históricos (la planilla de muestra) requiere trabajo manual repetitivo → Mitigación: aceptado como no-goal explícito; si el volumen resulta alto, se prioriza un importador en un change de seguimiento inmediato en vez de bloquear este change.
- [Riesgo] Al no validar el monto de la OC contra la lista de precios del convenio, un usuario puede registrar una OC con precio distinto al pactado sin ninguna alerta → Mitigación: aceptado como no-goal en este change; el vínculo `contrato_id` en la OC ya deja la evidencia consultable manualmente, y la validación automática puede agregarse después sin cambiar el modelo de datos.
- [Riesgo] `aprobado` inmutable sin mecanismo de addendum puede quedar corto si en la práctica los contratos se modifican con frecuencia (ej. extensión de vigencia) → Mitigación: si aparece el caso real durante `/opsx:apply` o en uso temprano, se registra como decisión pendiente para un change de seguimiento en vez de expandir el alcance ahora.
- [Riesgo] La generación automática de cuotas por división exacta de `monto_total` puede no calzar con acuerdos reales de pago (montos desiguales por cuota, cuotas que caen en fin de semana/feriado, etc.) → Mitigación: cada cuota queda editable individualmente mientras el contrato esté en `borrador`, así que la generación automática es un punto de partida, no un valor final obligatorio.
- [Riesgo] Sin recordatorio de cuotas próximas a vencer, el seguimiento del calendario depende de que un usuario revise el listado manualmente → Mitigación: aceptado como no-goal explícito; si se confirma la necesidad, se agrega una notificación programada en un change de seguimiento reutilizando la infraestructura de notificaciones ya existente en el workflow.

## Migration Plan

1. Migraciones nuevas (orden): `create_contratos_table`, `create_contrato_items_convenio_precio_table`, `create_contrato_cuotas_table`, `add_contrato_id_to_ordenes_compra_mercado_publico_table`. Todas aditivas, sin tocar datos existentes — sin plan de rollback especial más allá de `migrate:rollback` estándar.
2. Seed de `DefinicionWorkflow` código `contratos` con sus transiciones (`borrador→pendiente`, `pendiente→aprobado`, `pendiente→rechazado`), siguiendo el seeder de workflow de Adquisiciones como plantilla.
3. Seed de permisos nuevos, aditivo vía `givePermissionTo` (idempotente).
4. Sin cambios en datos ni comportamiento de módulos existentes (Adquisiciones/Presupuesto/Pago de Proveedores) más allá de la nueva columna opcional `contrato_id` en `ordenes_compra_mercado_publico`.

## Open Questions

- ¿Qué roles reciben `contratos.crear`/`contratos.aprobar`? (a confirmar en `/opsx:apply` contra `FuncionariosCapjSeeder`).
- ¿Se requiere una vista de "contratos vigentes por vencer" o similar en este change, o queda para el change de planificación de Presupuesto? (asumido: fuera de alcance, solo listado + detalle estándar).
- ¿Se requiere una vista de "cuotas próximas a vencer" (dashboard/alerta) en este change, o basta con el listado de cuotas dentro del detalle del contrato? (asumido: solo listado dentro del detalle, sin dashboard dedicado).
- ¿`monto_total` del contrato es obligatorio cuando `tiene_calendario_pago = true`, o puede generarse el calendario solo con fechas y completar el monto de cada cuota manualmente después? (asumido: `monto_total` obligatorio para la generación automática, pero editable cuota por cuota después).

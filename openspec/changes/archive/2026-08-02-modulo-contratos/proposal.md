## Why

No existe hoy una entidad "Contrato" en el sistema: el concepto solo aparece indirectamente (tipo de documento del expediente de Adquisiciones, transición de workflow `formalizar_contrato`, valor de catálogo en `tipos_proceso_pago`). Sin embargo, la operación real de Adquisiciones sí gira en torno a contratos y convenios de precio: cuando un contrato tiene convenio de precios, ese convenio determina el precio de las órdenes de compra que se emiten contra él, y hoy no hay forma de decir "esta orden de compra / este proceso de adquisición corresponde a este contrato". Además, muchos contratos (arriendos, mantenciones, servicios recurrentes) se pagan en cuotas periódicas (mensual, semestral, etc.) según un calendario propio del contrato, y hoy Pago de Proveedores no tiene forma de anticipar ni relacionar esos pagos recurrentes con el contrato que los origina. Se necesita esta entidad para que Adquisiciones pueda identificar el contrato aplicable a un proceso de compra y para que los pagos derivados de un contrato queden planificados y trazables, dejando además la base de datos lista para que Presupuesto pueda, en un change futuro, planificar a partir de contratos vigentes.

## What Changes

- Nueva entidad `Contrato` con workflow propio (`borrador → pendiente → aprobado` / `rechazado`), gobernada exclusivamente por `TransicionWorkflowService::execute()`, siguiendo el mismo patrón que `ProcesoAdquisicion` y el CDP (entidad de dominio + `Proceso` propio vía `sujeto` polimórfico). Incluye `id_institucional`: el identificador propio que la institución usa "para todo efecto" (distinto del `id` interno autoincremental y del `id_proceso_mp` de Mercado Público), persistido con índice único para búsquedas rápidas.
- Nueva sub-entidad `ContratoItemConvenioPrecio` para modelar la paleta de precios de un contrato con convenio de precios (`tiene_convenio_precio = true`).
- Nueva sub-entidad `ContratoCuota` para modelar el calendario de pago de un contrato (`tiene_calendario_pago = true`): cuotas generadas según `periodicidad_pago` (mensual, bimestral, trimestral, semestral, anual, única) entre la vigencia del contrato, cada una vinculable manualmente a un `caso_pago_proveedor` cuando se paga.
- Vínculos opcionales, puramente informativos (evidencia, no gobierno), entre `Contrato` y `ProcesoAdquisicion`, `LicitacionMercadoPublico` y `OrdenCompraMercadoPublico` (nueva FK `contrato_id` en `ordenes_compra_mercado_publico`), y entre `ContratoCuota` y `CasoPagoProveedor` (patrón ya existente de `caso_pago_proveedor.proceso_adquisicion_id`/`pago_proveedores.vincular_adquisicion`).
- Checklist documental del `Contrato` resuelto vía `requisitos_documentales`/`ResolutorChecklistDocumentalProceso`, reutilizando el `tipo_documento` `CONTRATO` ya existente en el catálogo.
- Nuevos permisos `contratos.crear`, `contratos.editar`, `contratos.aprobar`, `contratos.rechazar`, `contratos.ver`, `contratos.vincular_pago` (convención `modulo_accion.verbo`), agregados al seeder correspondiente.
- **Fuera de alcance** (explícito, para un change posterior): planificación de Presupuesto a partir de contratos vigentes ("plan de tareas"), e importación masiva/automática de contratos desde fuente externa. Este change deja el vínculo transitivo `Contrato → ProcesoAdquisicion → CertificadoDisponibilidadPresupuestaria` disponible sin modificar el modelo del CDP. Tampoco se automatiza la creación de un `caso_pago_proveedor` al vencer una cuota — el vínculo es manual, igual que el resto de los vínculos del módulo.

## Capabilities

### New Capabilities
- `contratos`: gestión de contratos y convenios de precio — entidad `Contrato` con workflow propio, sub-entidad de ítems de convenio de precio, sub-entidad de calendario de pago (cuotas), vínculos informativos con Adquisiciones/Mercado Público/Pago de Proveedores, checklist documental y permisos.

### Modified Capabilities
- `ordenes-compra-mercado-publico`: se agrega la posibilidad de vincular opcionalmente una orden de compra a un `Contrato` (nueva FK `contrato_id`, informativa, sin efecto en el workflow existente).

## Impact

- Nuevas migraciones: `contratos`, `contrato_items_convenio_precio`, `contrato_cuotas`, `add_contrato_id_to_ordenes_compra_mercado_publico_table`, `add_id_institucional_to_contratos_table`.
- Nuevos modelos: `app/Models/Contrato.php`, `app/Models/ContratoItemConvenioPrecio.php`, `app/Models/ContratoCuota.php`; modificación de `app/Models/OrdenCompraMercadoPublico.php` (nueva relación `contrato()`).
- Nuevo service `app/Services/Contratos/ContratoService.php` (creación, resolución/dedup de proveedor reutilizando `Proveedor::normalizarRut()`, transición de workflow, generación del calendario de cuotas).
- Nueva `DefinicionWorkflow` código `contratos`, seed de transiciones/estados.
- Registro de policy nueva en `AppServiceProvider::configureAuthorization()`.
- Nuevos permisos en el seeder de Adquisiciones (o uno nuevo dedicado si no hay uno reutilizable).
- Frontend: páginas React nuevas bajo `resources/js/pages/contratos/` (listado denso siguiendo el patrón de `resources/js/pages/maestros/cfinancieros/index.tsx`), y ajuste menor en el flujo de OC/Adquisiciones para mostrar/seleccionar el contrato vinculado.
- Sin impacto en integraciones externas (SGF, Mercado Público API) — los vínculos son manuales, no se dispara ninguna consulta externa nueva.

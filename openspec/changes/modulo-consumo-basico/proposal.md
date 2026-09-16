## Why

El sistema hoy importa desde SGF la cabecera de todas las facturas/boletas (incluidas las de servicios básicos: electricidad, agua, gas, telefonía) — RUT del proveedor, monto, período, fecha — pero no tiene forma de registrar ni consultar el detalle real de consumo (número de cliente/medidor, kWh o m³ consumidos, tarifa, historial mes a mes). El catálogo `ClienteMedidor` ya existe (número de cliente, proveedor, centro de costo, dirección) pero está completamente desconectado del flujo de pagos — no hay ningún vínculo hacia `CasoPagoProveedor`. Se calibró el diseño con 8 boletas eléctricas reales de EDELAYSEN (Corporación Administrativa del Poder Judicial, Región de Aysén), lo que reveló varios hallazgos que determinan el modelo: el estado de pago nunca aparece impreso en la boleta, el código de tarifa real no coincide con las clasificaciones informales que hoy usa la institución, el "historial de consumo" de la boleta es un gráfico de barras poco confiable (no una fuente de datos parseable), y un medidor puede compartirse entre más de un centro de costo sin que eso conste en el documento.

## What Changes

- Nueva entidad `ConsumoBasico`: el detalle de consumo de un servicio básico para un período, vinculada obligatoriamente a un `ClienteMedidor` (el punto de suministro) y a un `CasoPagoProveedor` existente (la cabecera ya importada desde SGF) — un registro 1:1 por caso de pago de un proveedor de servicios básicos.
- El registro es manual: cuando un `CasoPagoProveedor` corresponde a un proveedor que ya tiene algún `ClienteMedidor` asociado, el sistema lo señala como candidato a completar el detalle de consumo (consulta simple contra el catálogo existente — sin leer el contenido del PDF).
- El PDF de la boleta se adjunta como evidencia (documento), reutilizando el mecanismo de `Documento`/`VinculoDocumento` — primera vez que se usa fuera del árbol de `Proceso`, ver design.md para el enfoque.
- Si el número de cliente de una boleta todavía no tiene un `ClienteMedidor` creado, el formulario de registro permite crearlo al vuelo (mismo patrón "resolver o crear" ya usado para `Proveedor`).
- El "historial de consumo" de un `ClienteMedidor` se construye acumulando los `ConsumoBasico` que el propio sistema va registrando mes a mes — no se parsea ningún historial impreso en el PDF.
- **Fuera de alcance explícito de este change**: extracción automática de datos desde el PDF (OCR/parsing) — no existe ninguna capacidad de este tipo hoy en el proyecto y construirla sin evidencia de que vale la pena contradice el criterio ya usado en cada módulo anterior (CDP, Contratos, Presupuesto). Tampoco se modela en este change el caso de un medidor físico compartido entre dos o más centros de costo (confirmado real mediante evidencia, pero no impreso en ningún documento — requeriría una relación adicional que no hay evidencia de que sea frecuente). Tampoco se importan las boletas históricas de la carpeta real que sirvió de calibración — esa importación de datos queda para una fase posterior, separada de este change (mismo orden que se usó en Contratos: primero el módulo, después el import de datos reales).

## Capabilities

### New Capabilities
- `consumo-basico`: registro y consulta del detalle de consumo (medidor, período, consumo, tarifa, monto) de un servicio básico, vinculado a su `ClienteMedidor` y a su `CasoPagoProveedor`, con el PDF de la boleta adjunto como evidencia y el historial de consumo acumulado a partir de los registros del propio sistema.

### Modified Capabilities
(ninguna — `ClienteMedidor` gana una relación nueva hacia `ConsumoBasico`, pero su comportamiento de catálogo/CRUD ya documentado en `consultar-catalogo-clientes-medidores` no cambia)

## Impact

- Nuevas migraciones: tabla `consumos_basicos` (o el nombre que se confirme en design.md) con FKs a `clientes_medidores` y `casos_pago_proveedor`.
- Nuevo modelo `app/Models/ConsumoBasico.php`, nuevo service en `app/Services/PagoProveedores/` o dominio equivalente (a confirmar en design.md), nuevo controller, policy, form requests, resource.
- Modificación de `app/Models/ClienteMedidor.php` (nueva relación `consumos()`) y de su página `resources/js/pages/maestros/clientes-medidores/show.tsx` (sección de historial).
- Modificación del detalle de `CasoPagoProveedor` (página React) para ofrecer completar el detalle de consumo cuando aplique.
- Posible extensión de `GestorDocumentoProceso`/`VinculoDocumento` para aceptar un `vinculable` distinto de `Proceso` — a definir el enfoque exacto en design.md.
- Nuevos permisos `consumo_basico.crear`/`consumo_basico.editar`/`consumo_basico.ver` (convención `modulo_accion.verbo`).
- Sin cambios al workflow de `CasoPagoProveedor` ni a la importación SGF existente — el vínculo es informativo, nunca gobierna estados.

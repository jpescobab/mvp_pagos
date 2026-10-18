## Why

"Servicios contratados" (aseo, vigilancia, mantención, arriendos) figura como módulo funcional previsto pero sin código. Hoy esos servicios solo existen como contratos sueltos y casos de pago importados desde SGF; nadie ve de un vistazo qué servicios recurrentes están vigentes, cuáles vencen pronto ni qué meses ya se pagaron.

## What Changes

- Nueva entidad `ServicioContratado`: un servicio recurrente vinculado a un `Contrato` (del que hereda proveedor y vigencia máxima), con nombre, categoría, monto mensual esperado, moneda, vigencia propia, centro de costo opcional y administrador (funcionario) opcional.
- Listado denso, detalle, alta y edición de servicios.
- Estado **derivado** de la vigencia (`vigente`, `por_vencer`, `vencido`) más una marca manual de `terminado`; no hay workflow propio ni estado persistido.
- Alerta visible de vencimiento próximo (ventana configurable, 60 días por defecto) en listado y detalle.
- Vista de períodos mes a mes (derivada, no persistida): cada mes de la vigencia se cruza con los `CasoPagoProveedor` del mismo proveedor cuyo `periodo` coincide, e indica `pagado`, `en_tramite` o `sin_caso`. El vínculo es informativo: no bloquea ni gobierna el pago (SGF es origen, CGU gobierna el presupuesto).
- Permisos `servicios_contratados.{ver,crear,editar,terminar}`, política registrada en `AppServiceProvider`, ítem de sidebar.
- Fuera de alcance: recepción conforme mensual, Mantenimiento, RR.HH., y cualquier cambio al workflow de Pago de Proveedores.

## Capabilities

### New Capabilities
- `servicios-contratados`: catálogo de servicios recurrentes vinculados a un contrato, con estado derivado de vigencia, alerta de vencimiento y vista de períodos cruzada con los casos de pago.

### Modified Capabilities
<!-- Ninguna: Contratos y Pago de Proveedores solo se leen. -->

## Impact

- Backend: migración `servicios_contratados`, modelo `ServicioContratado`, `ServicioContratadoService` + `PeriodosServicioContratadoService`, controlador, Form Requests, Resources, `ServicioContratadoPolicy`, `routes/servicios-contratados.php`, `ServiciosContratadosSeeder`.
- Frontend: páginas `servicios-contratados/{index,show,create,edit}`, tipos TS, ítem de sidebar.
- Tests: Feature en `tests/Feature/ServiciosContratados/`.
- Sin dependencias nuevas ni cambios a tablas existentes.

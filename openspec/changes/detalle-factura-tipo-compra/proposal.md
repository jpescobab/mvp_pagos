## Why

Hoy `Factura` (registro manual de folio/monto/fecha de emisión ligado a un `CasoPagoProveedor`) no captura qué se compró. El detalle real de una factura varía según el tipo de compra (bienes, servicios generales, obras, etc.) y no existe ningún lugar para registrarlo. El usuario necesita capturar ese detalle, pero **sin que su ausencia bloquee el pago**: hoy ya es posible pagar un caso sin haber registrado ninguna factura (`CasoPagoProveedorPolicy::registrarFactura` solo chequea el permiso, nunca el estado del `Proceso`) — el detalle debe preservar exactamente esa misma independencia.

## What Changes

- Nueva entidad `DetalleFactura`: vinculada 1:1 a una `Factura` existente (`factura_id` único, `cascadeOnDelete`) — no obligatoria, una `Factura` puede no tener detalle sin que eso afecte el pago del caso.
- Campos del detalle: `tipo_compra_id` (catálogo nuevo), `ccosto_id` (selección manual desde el catálogo `Ccosto` existente, sin herencia automática de ninguna relación), `cantidad`, `unidad_medida` (texto libre, mismo criterio que `ContratoItemConvenioPrecio.unidad_medida`), `monto`.
- Nuevo catálogo abierto `TipoCompra` (`codigo`/`nombre`/`activo`), con CRUD propio en Maestros (mismo patrón que `TipoProcesoPago`), sembrado con una base genérica: `BIENES`, `SERVICIOS_GENERALES`, `OBRAS` — editable después desde la UI sin tocar código.
- `FacturaController` gana un endpoint `show` (hoy solo tiene `store`) para poder ver/gestionar el detalle de una factura puntual.
- Nueva sección "Facturas" en el detalle de un `CasoPagoProveedor` (hoy `caso.facturas` no se renderiza en ningún lado del frontend), con indicador "con detalle"/"sin detalle" por factura.
- Registrar o editar un `DetalleFactura` **nunca** dispara ninguna transición de `TransicionWorkflowService` ni depende del estado del `Proceso` — es puramente informativo, igual que la `Factura` misma.
- **Fuera de alcance explícito**: no se modifica `ConsumoBasico` (servicios básicos electricidad/agua/gas) en absoluto — es un módulo separado que ya resuelve un caso distinto (1:1 con `CasoPagoProveedor`, no con `Factura`). No se importa ni migra ningún detalle histórico. No se modela más de un detalle por factura (sin líneas/ítems múltiples) — si aparece esa necesidad se aborda en un change futuro con evidencia real.

## Capabilities

### New Capabilities
- `detalle-factura`: registro y consulta del detalle (tipo de compra, centro de costo, cantidad, unidad de medida, monto) de una `Factura` individual, independiente del workflow de pago del `CasoPagoProveedor` al que pertenece.
- `tipos-compra`: catálogo administrable de tipos de compra (código/nombre/activo), con CRUD en Maestros, usado por `detalle-factura`.

### Modified Capabilities
(ninguna — `Factura` gana una relación nueva `detalle()` y un endpoint `show`, pero su comportamiento de registro ya documentado en `registrar-factura-caso-pago-proveedor` no cambia)

## Impact

- Nuevas migraciones: `tipos_compra` y `detalles_factura` (FK única a `facturas`, FK a `tipos_compra` y `ccostos`).
- Nuevos modelos `TipoCompra`, `DetalleFactura`; `Factura` gana `detalle(): HasOne`.
- Nuevo `DetalleFacturaService` (`app/Services/PagoProveedores/`), nuevo `DetalleFacturaController`, `TipoCompraController` (Maestros), policies, form requests, resources.
- Modificación de `FacturaController` (agrega `show`), `CasoPagoProveedorResource::mapFacturas()` (agrega `tiene_detalle`), `CasoPagoProveedorController::show()` (eager-load `facturas.detalle`).
- Nuevos permisos `pago_proveedores.registrar_detalle_factura`, `pago_proveedores.administrar_tipos_compra` (convención `modulo_accion.verbo`).
- Frontend: nueva sección "Facturas" en `casos/show.tsx`, páginas nuevas `pago-proveedores/detalle-factura/{create,edit}.tsx` y `maestros/tipos-compra/*.tsx`.
- Sin cambios al workflow de `CasoPagoProveedor` ni a `ConsumoBasico`.

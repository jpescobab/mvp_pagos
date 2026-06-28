## 1. Matriz de requisitos documentales

- [x] 1.1 Crear `database/seeders/RequisitosDocumentalesPagoProveedoresSeeder.php` que cree (idempotente) un `ConjuntoRequisitosDocumentales` propio para el workflow "pago_proveedores" (`definicion_workflow_id` resuelto desde `DefinicionWorkflow::where('codigo', 'pago_proveedores')`).
- [x] 1.2 En ese mismo seeder, crear los `RequisitoDocumental` (sin `modalidad_id`, según D2 del design): `FACTURA`, `ACTA_RECEP`, `CERT_VIGENCIA`, `RESOLUCION`, `COMPROBANTE` (obligatorios); `ORDEN_COMPRA`, `CONTRATO` (opcionales).
- [x] 1.3 Registrar `RequisitosDocumentalesPagoProveedoresSeeder` en `DatabaseSeeder` después de `WorkflowPagoProveedoresSeeder`.

## 2. Wiring en el controlador

- [x] 2.1 Inyectar `ResolutorChecklistDocumentalProceso` en `App\Http\Controllers\PagoProveedores\CasoPagoProveedorController`.
- [x] 2.2 En `show()`, resolver el `ConjuntoRequisitosDocumentales` de Pago de Proveedores por código y llamar a `resolve()` (con fallback seguro si no existe el conjunto, igual que en Adquisiciones), recargando `proceso.checklist.items` antes de construir el Resource.

## 3. Tests

- [x] 3.1 Feature test: el seeder crea el `conjunto_requisitos_documentales` de Pago de Proveedores con `FACTURA` obligatorio.
- [x] 3.2 Feature test: `GET` al detalle de un `caso_pago_proveedor` devuelve un checklist no vacío que incluye `Factura`.
- [x] 3.3 Feature test: abrir el detalle dos veces no duplica items del checklist.

## 4. Validación

- [x] 4.1 Ejecutar `composer test`.
- [x] 4.2 Probar manualmente en el navegador: abrir el detalle de `DEMO-1001` (u otro caso de demo) y verificar que "Checklist documental" ya no muestra "Sin checklist generado aún".

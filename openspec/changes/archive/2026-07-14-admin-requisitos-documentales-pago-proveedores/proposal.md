## Why

La matriz de qué `TipoDocumento` es obligatorio u opcional según el `TipoProcesoPago` de un caso de Pago de Proveedores vive hoy hardcodeada en `database/seeders/RequisitosDocumentalesPagoProveedoresSeeder.php`. Agregar un nuevo tipo de proceso (ej. "Consumos básicos") o un nuevo tipo de documento (ej. "FURBS"), o simplemente ajustar si un documento es obligatorio, requiere tocar código PHP y volver a correr un seeder — no hay ninguna vista donde un administrador funcional pueda hacer este ajuste por su cuenta.

## What Changes

- Nuevo CRUD administrable para `TipoProcesoPago` (hoy 6 valores fijos: Compra, Contrato, Convenio, Reembolso, Anticipo, Otro) — crear, editar, activar/desactivar.
- Nuevo CRUD administrable para `TipoDocumento` (catálogo general compartido con Adquisiciones, hoy 13 valores fijos) — crear, editar, activar/desactivar.
- Nueva pantalla de matriz/grilla para asignar, por cada combinación `TipoDocumento` × `TipoProcesoPago` del conjunto `pago_proveedores`, uno de tres estados: obligatorio, opcional, o no aplica — incluyendo una columna especial "Todos los tipos" para requisitos universales (equivalente a `tipo_proceso_pago_id = null`, usado hoy por Factura y Comprobante). Cambios en la matriz se reflejan de inmediato en el checklist documental de cualquier caso, sin seeder ni deploy.
- Nuevo permiso `pago_proveedores.administrar_requisitos_documentales`, que gatea el CRUD de `TipoProcesoPago` y la matriz de asignación.
- El CRUD de `TipoDocumento` usa el permiso `core_institucional.administrar` ya existente, por ser un catálogo general compartido con Adquisiciones, consistente con el resto de maestros del sistema.
- **No** se modifica `RequisitosDocumentalesPagoProveedoresSeeder.php` (sigue siendo el seed inicial para entornos nuevos) ni se agrega UI para las dimensiones `estado_workflow_id` o `monto_desde`/`monto_hasta` de `RequisitoDocumental` — quedan fuera de alcance de este change.

## Capabilities

### New Capabilities
- `administracion-requisitos-documentales-pago-proveedores`: CRUD de `TipoProcesoPago` y `TipoDocumento`, y la matriz de asignación de obligatoriedad documental por tipo de proceso de pago.

### Modified Capabilities

(ninguna — el comportamiento de resolución del checklist en tiempo de ejecución, ya cubierto por `documentos-expediente-variable`, no cambia: esta funcionalidad solo agrega una forma de editar los datos que ese resolutor ya lee)

## Impact

- **Backend nuevo**: controladores, Form Requests y Policies para `TipoProcesoPago`, `TipoDocumento` y la matriz de `RequisitoDocumental` (scoping explícito al conjunto `pago_proveedores` / definición de workflow `pago_proveedores`, sin tocar las filas de Adquisiciones).
- **Frontend nuevo**: páginas de administración bajo `resources/js/pages/` siguiendo el patrón de maestros existente (`maestros/cfinancieros/*` como referencia) para los dos CRUD, y una página nueva tipo grilla para la matriz.
- **Rutas y permisos**: nuevas rutas en `routes/maestros.php` (o `routes/pago-proveedores.php` según corresponda por dominio) y el permiso `pago_proveedores.administrar_requisitos_documentales` en `RolesAndPermissionsSeeder`.
- **Sin cambios** en `ResolutorChecklistDocumentalProceso`, en la resolución del checklist de casos existentes, ni en `RequisitosDocumentalesPagoProveedoresSeeder.php`.
- Tests: Pest feature tests para ambos CRUD (permiso, validación, bloqueo de eliminación con filas relacionadas) y para la matriz (crear/actualizar/quitar un requisito por celda, scoping correcto al conjunto `pago_proveedores`, columna universal `tipo_proceso_pago_id = null`).

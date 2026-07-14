## Why

Hoy la matriz de requisitos documentales de Pago de Proveedores es fija: los mismos 7 tipos de documento (Factura, Acta de Recepción, Certificado de Vigencia, Resolución de Pago, Comprobante de Pago obligatorios; Orden de Compra, Contrato opcionales) aplican a todo caso, sin distinguir si el pago corresponde a una compra, un contrato, un convenio, un reembolso o un anticipo. `administrativo_finanzas` necesita poder clasificar el "tipo de proceso o de pago" de cada caso, para que el checklist documental refleje realmente qué se exige en cada situación — un anticipo no tiene factura todavía, un convenio no siempre requiere acta de recepción, etc.

Además, el "Registro Contable CGU" que ya existe (número, fecha, monto, observación) es exactamente el documento que el equipo de Finanzas conoce como "Traspaso" — hoy la UI no usa esa terminología, generando fricción de comunicación entre la app y el proceso real.

## What Changes

- Nueva clasificación "tipo de proceso o de pago" (`COMPRA`, `CONTRATO`, `CONVENIO`, `REEMBOLSO`, `ANTICIPO`, `OTRO`) asignable a cada caso de Pago de Proveedores por un usuario con `pago_proveedores.gestionar_caso`.
- El motor de resolución del checklist documental (`ResolutorChecklistDocumentalProceso`) SHALL considerar esta clasificación para determinar qué documentos son obligatorios y cuáles opcionales, siguiendo el mismo patrón ya usado para `modalidad_id` en Adquisiciones — pero como un concepto propio y paralelo (nueva tabla `tipos_proceso_pago`), no reutilizando `modalidades_adquisicion` (dominio semánticamente distinto).
- La matriz de `requisitos_documentales` de Pago de Proveedores se reescribe para variar por tipo de proceso de pago (Factura y Comprobante de Pago siguen siendo universales/obligatorios siempre; el resto varía según el tipo).
- La sección "Registro Contable CGU" se renombra en la UI a "Traspaso" — sin cambios de datos ni de backend, son los mismos 4 campos ya existentes.

## Capabilities

### New Capabilities

(ninguna)

### Modified Capabilities

- `pago-proveedores-sgf`: se agrega la clasificación de tipo de proceso de pago y su efecto en la resolución del checklist documental.
- `documentos-expediente-variable`: el motor de resolución del checklist gana un segundo eje de filtrado (`tipo_proceso_pago_id`), independiente de `modalidad_id`.

## Impact

- `database/migrations/*` — 3 migraciones nuevas (tabla `tipos_proceso_pago`, columna en `procesos`, columna en `requisitos_documentales`).
- `app/Models/TipoProcesoPago.php`, `app/Models/Proceso.php`
- `app/Services/Documentos/ResolutorChecklistDocumentalProceso.php`
- `app/Http/Controllers/PagoProveedores/TipoProcesoPagoCasoPagoProveedorController.php`, `app/Http/Requests/PagoProveedores/ClasificarTipoProcesoPagoRequest.php`
- `app/Policies/CasoPagoProveedorPolicy.php`
- `routes/pago-proveedores.php`
- `app/Http/Resources/PagoProveedores/ProcesoResource.php`
- `app/Http/Controllers/PagoProveedores/CasoPagoProveedorController.php`
- `database/seeders/RequisitosDocumentalesPagoProveedoresSeeder.php`, nuevo `database/seeders/TiposProcesoPagoSeeder.php`
- `resources/js/pages/pago-proveedores/casos/show.tsx`, `resources/js/types/pago-proveedores.ts`
- Tests: `tests/Feature/PagoProveedores/*`, `tests/Feature/Documentos/*`

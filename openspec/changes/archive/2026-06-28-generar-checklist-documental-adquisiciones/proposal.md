## Why

El flujo de negocio de Adquisiciones (licitar, adjudicar, contratar) ya está gobernado y soportado externamente por Mercado Público; este sistema no necesita replicarlo con un workflow interno rico. Lo que sí necesita CAPJ App Pagos es su propia capa de control documental: actualmente `ResolutorChecklistDocumentalProceso::resolve()` existe y está testeado de forma aislada, pero **no se invoca desde ningún controlador de ningún módulo**, y no existe ningún `requisito_documental` ni `tipo_documento` cargado en seeders. Como resultado, el detalle de un `ProcesoAdquisicion` siempre muestra "Sin checklist generado aún", contradiciendo el requisito ya especificado en `documentos-expediente-variable` ("Generar checklist documental" al abrir el expediente de un proceso). Sin esto, Adquisiciones no aporta la evidencia documental institucional que es la razón principal de tenerlo dentro de este sistema.

## What Changes

- Catálogo de `tipos_documento` necesarios para el expediente de Adquisiciones (bases/TDR, resolución de adjudicación, contrato u orden de compra, garantías, certificado de recepción conforme), reutilizable a futuro por otros módulos.
- Un `conjunto_requisitos_documentales` para el workflow "adquisiciones" con reglas `requisitos_documentales` por modalidad (licitación pública, licitación privada, trato directo, convenio marco), marcando cada documento como obligatorio u opcional según corresponda.
- Wiring de `ResolutorChecklistDocumentalProceso::resolve()` en `ProcesoAdquisicionController::show()`, de modo que el checklist se genere/actualice automáticamente al abrir el expediente, tal como exige el escenario "Generar checklist documental" ya definido en la spec `documentos-expediente-variable`.
- Tests que verifiquen que cada modalidad resuelve los documentos correctos y que el detalle ya no muestra el checklist vacío.

Fuera de alcance (explícitamente, por decisión del usuario): no se modifica el workflow interno de Adquisiciones (estados/transiciones), no se integra con Mercado Público, y no se toca el mismo gap de wiring en Pago de Proveedores (queda con su comportamiento actual, sin regresión).

## Capabilities

### New Capabilities

(ninguna — este cambio no introduce un dominio nuevo, sino que activa comportamiento ya definido en una spec existente)

### Modified Capabilities

- `adquisiciones`: se agrega el requisito de que el detalle de un proceso de adquisición exponga un checklist documental realmente poblado según su modalidad (no solo la capacidad de mostrarlo vacío), y que exista una matriz de reglas documentales concreta para el workflow de Adquisiciones. No se toca `documentos-expediente-variable`: su comportamiento genérico de resolución ya está especificado y no cambia; este cambio solo lo activa y le da datos reales para Adquisiciones.

## Impact

- `app/Http/Controllers/Adquisiciones/ProcesoAdquisicionController.php` (inyecta y llama al resolutor en `show()`).
- Nuevo seeder de `tipos_documento`, `conjuntos_requisitos_documentales` y `requisitos_documentales` para Adquisiciones (posiblemente `database/seeders/RequisitosDocumentalesAdquisicionesSeeder.php`).
- Tests nuevos en `tests/Feature/Adquisiciones/`.
- Sin cambios de esquema (las tablas `tipos_documento`, `conjuntos_requisitos_documentales`, `requisitos_documentales`, `checklists_documentales_proceso` ya existen desde la tarea 06).

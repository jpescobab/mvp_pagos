## 1. Catálogo de tipos de documento

- [x] 1.1 Extender `database/seeders/TiposDocumentoSeeder.php` agregando (con `firstOrCreate` por `codigo`, igual al patrón existente): `BASES_LICITACION`, `RESOLUCION_ADJUDICACION`, `GARANTIA`. Reutilizar `CONTRATO` y `ACTA_RECEP` ya existentes, sin duplicarlos.

## 2. Matriz de requisitos documentales para Adquisiciones

- [x] 2.1 Crear `database/seeders/RequisitosDocumentalesAdquisicionesSeeder.php` que cree (idempotente) un `ConjuntoRequisitosDocumentales` propio para el workflow "adquisiciones" (`definicion_workflow_id` resuelto desde `DefinicionWorkflow::where('codigo', 'adquisiciones')`).
- [x] 2.2 En ese mismo seeder, crear los `RequisitoDocumental` por modalidad según D4 del design: licitación pública, licitación privada, trato directo, convenio marco, usando `modalidad_id` resuelto desde `ModalidadAdquisicion` y `tipo_requisito` (`obligatorio`/`opcional`).
- [x] 2.3 Registrar `RequisitosDocumentalesAdquisicionesSeeder` en `DatabaseSeeder` después de `TiposDocumentoSeeder`, `ModalidadesAdquisicionSeeder` y `WorkflowAdquisicionesSeeder`.

## 3. Wiring del resolutor en el detalle del proceso

- [x] 3.1 Inyectar `ResolutorChecklistDocumentalProceso` en `App\Http\Controllers\Adquisiciones\ProcesoAdquisicionController` (constructor con `readonly`).
- [x] 3.2 En `show()`, después de cargar el proceso, resolver el `ConjuntoRequisitosDocumentales` de Adquisiciones por código y llamar a `resolve($proceso->proceso, $conjunto, $request->user())` antes de recargar `proceso.checklist.items` para la respuesta (o recargar la relación después de resolver, para que el Resource sirva el checklist actualizado).
- [x] 3.3 Si no existe el `ConjuntoRequisitosDocumentales` (entorno sin seed), omitir la resolución sin error — el checklist simplemente queda vacío como hoy.

## 4. Tests

- [x] 4.1 Feature test: el seeder de requisitos documentales crea los `tipos_documento` esperados (incluyendo `CONTRATO` reutilizado) y el `conjunto_requisitos_documentales` de Adquisiciones con reglas por modalidad.
- [x] 4.2 Feature test: `GET` al detalle de un `proceso_adquisicion` con modalidad "licitación pública" devuelve un checklist con los items esperados (incluye `BASES_LICITACION`, `GARANTIA` obligatorios).
- [x] 4.3 Feature test: `GET` al detalle de un `proceso_adquisicion` con modalidad "trato directo" devuelve un checklist sin `BASES_LICITACION`.
- [x] 4.4 Feature test: abrir el detalle dos veces no duplica items del checklist (regenera, no acumula), consistente con el comportamiento ya existente de `resolve()`.

## 5. Validación

- [x] 5.1 Ejecutar `composer test` (incluye `lint:check`, `types:check` y la suite Pest).
- [x] 5.2 Probar manualmente en el navegador: abrir el detalle de un `ADQ-DEMO-001` (u otro proceso de demo) y verificar que la sección "Checklist documental" ya no muestra "Sin checklist generado aún".

## Why

El formulario actual de "Crear proceso de adquisición" es mínimo (código, modalidad, centro de costo, proveedor opcional, monto, objeto en texto libre) y no refleja cómo la institución realmente levanta una solicitud de compra menor a 1.000 UTM. El usuario proporcionó un mockup ("Solicitud de Compra") que define el formulario y los datos reales que necesita: antecedentes generales completos, verificación de Plan Anual de Compras y Convenio Marco, y aprobación de la jefatura de la unidad requirente. Además, este cambio desbloquea el módulo Presupuesto/CDP, pausado porque `certificados_disponibilidad_presupuestaria.proceso_adquisicion_id` depende de que Adquisiciones esté redefinido.

## What Changes

- Se agregan nuevos campos a la solicitud de compra: fecha de inicio, nombre de la compra, ID de requerimiento (referencia libre), funcionario requirente (seleccionado desde `funcionarios`, filtrado por la unidad/centro de costo elegido), características del bien o servicio, motivo de contratación, indicador de Plan Anual de Compras (con ID del PAC condicional), código BIP y monto estimado.
- **BREAKING**: `modalidad_id` deja de seleccionarse manualmente en el formulario de creación. Se deriva de una pregunta Sí/No sobre Convenio Marco: "Sí" fija la modalidad `CONVENIO_MARCO`; "No" fija `TRATO_DIRECTO` y exige adjuntar un informe de justificación (nuevo documento obligatorio del expediente para esa modalidad).
- **BREAKING**: el código/correlativo de la solicitud pasa a ser autogenerado por el sistema; deja de ser un campo editable por el usuario en el formulario de creación.
- **BREAKING**: se elimina la captura de "nombre/cargo de quien aprueba" como texto libre. La aprobación de la jefatura de la unidad requirente pasa a ser una transición de workflow real (reutiliza la transición existente `en_revision → publicada`), quedando registrada en el historial de transiciones con el usuario y la fecha reales.
- El campo `monto` se renombra a `monto_estimado` y se valida contra el valor UTM vigente: este formulario es específico para compras menores a 1.000 UTM (Licitación Pública para montos mayores queda fuera de este cambio).
- El campo `objeto` deja de pedirse al usuario (se reemplaza por "características del bien o servicio" + "motivo de contratación"); se mantiene internamente sincronizado por compatibilidad con lo que ya lo consume.
- Se agrega exportación a PDF de la solicitud, descargable bajo demanda desde el detalle del proceso.

## Capabilities

### New Capabilities

(ninguna — este cambio rediseña capacidades existentes, no introduce un dominio nuevo)

### Modified Capabilities

- `adquisiciones`: nuevos campos y reglas de negocio del proceso de adquisición (funcionario requirente, plan de compras, derivación de modalidad desde Convenio Marco, código autogenerado, nuevo documento obligatorio condicionado para `TRATO_DIRECTO`, aprobación vía transición de workflow, exportación a PDF).
- `api-adquisiciones`: cambia el contrato de creación/edición (nuevos campos, `convenio_marco` reemplaza a `modalidad_id` como input), nuevo endpoint de descarga de PDF.
- `paginas-adquisiciones`: rediseño del formulario de creación/edición (secciones, selects dependientes, campos condicionales, validación cliente); se retira el bloque de aprobación de la página de creación.

## Impact

- Backend: migración sobre `procesos_adquisicion` (columnas nuevas + rename `monto`→`monto_estimado` + `objeto` nullable); `ProcesoAdquisicion` (modelo y relación nueva a `Funcionario`); `CrearProcesoAdquisicionRequest`/`ActualizarProcesoAdquisicionRequest`; `ProcesoAdquisicionService` (generación de código, derivación de modalidad, validación UTM); `ProcesoAdquisicionController`; `ProcesoAdquisicionResource`; seeders `RequisitosDocumentalesAdquisicionesSeeder` y `WorkflowAdquisicionesSeeder`; nuevo servicio de exportación a PDF (reutiliza dompdf, ya usado por `ExportadorInformeRazonadoService`).
- Frontend: `resources/js/pages/adquisiciones/procesos/crear.tsx` y `editar.tsx`; `resources/js/types/adquisiciones.ts`.
- Tests: `tests/Feature/Adquisiciones/*` existentes requieren actualización por el cambio de contrato; se agregan tests nuevos para generación de código, derivación de modalidad, validación de umbral UTM y el nuevo documento condicional.
- No afecta: Mercado Público (OC/licitaciones), CasoPagoProveedor, ni el módulo Presupuesto/CDP directamente (solo los desbloquea).

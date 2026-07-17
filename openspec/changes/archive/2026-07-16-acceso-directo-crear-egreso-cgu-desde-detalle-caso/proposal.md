## Why

Cuando un caso de pago de proveedores cumple los 4 criterios del panel "Preparación para Asignar Egreso" (tipo de proceso clasificado, traspaso CGU registrado, checklist obligatorio con documento vinculado, proveedor identificado), el detalle del caso no ofrece ningún acceso directo para avanzarlo: el usuario tiene que saber por su cuenta que debe navegar a Egresos CGU → Nuevo Egreso y buscar el caso ahí. Esto genera la percepción de que el caso "no cambia de estado" pese a estar listo, cuando en realidad el paso que falta (crear el Egreso CGU) ya existe pero está desconectado del detalle del caso.

## What Changes

- En el detalle de un caso (`casos/show.tsx`), cuando el caso no tiene ningún Egreso CGU asignado todavía y el panel de preparación muestra sus 4 criterios completos, se agrega un botón/enlace "Crear Egreso CGU con este caso".
- Ese enlace navega al formulario de creación de Egreso CGU (`egresos-cgu/crear`) con este caso preseleccionado, sin restringir la lista a solo ese caso — el usuario puede seguir agregando otros casos al mismo egreso antes de guardar, igual que hoy.
- `EgresoCguController::create()` pasa a aceptar un query param adicional (`caso_pago_proveedor_id`) para indicar cuál caso preseleccionar; `egresos-cgu/crear.tsx` extiende su lógica de selección inicial para cubrir este caso además del existente por `trabajo_integracion_id`.
- No se agrega ninguna transición de workflow nueva ni se modifica el criterio de "listo para egreso": la única acción que cambia el estado del caso sigue siendo `EgresoCguController::store()` → `RevisionEgresoService::iniciarRevision()` → `TransicionWorkflowService::execute()`, exactamente como hoy. Este change es puramente de navegación/UX.

## Capabilities

### New Capabilities

(ninguna — extiende páginas existentes, no introduce un dominio nuevo)

### Modified Capabilities

- `paginas-pago-proveedores`: el requirement de detalle de caso agrega el acceso directo a creación de Egreso CGU cuando el caso está listo y sin egreso asignado; el requirement de formulario de creación de egreso agrega el soporte para preseleccionar un caso puntual llegado por su id (además del ya existente por corrida de importación SGF).

## Impact

- **Backend**: `app/Http/Controllers/PagoProveedores/EgresoCguController.php` (`create()` lee y propaga `caso_pago_proveedor_id`). Ningún cambio a `store()`, `RevisionEgresoService`, `TransicionWorkflowService` ni a las tablas de workflow.
- **Frontend**: `resources/js/pages/pago-proveedores/casos/show.tsx` (botón condicional), `resources/js/pages/pago-proveedores/egresos-cgu/crear.tsx` (preselección por id de caso).
- **Specs**: delta sobre `openspec/specs/paginas-pago-proveedores/spec.md`.

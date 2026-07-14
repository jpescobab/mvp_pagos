## Why

La página de detalle de un caso de Pago de Proveedores (`resources/js/pages/pago-proveedores/casos/show.tsx`) creció orgánicamente a lo largo de varios changes hasta convertirse en una lista plana de 10 secciones con idéntico tratamiento visual (mismo borde, mismo padding, mismo tamaño de título), sin distinguir qué requiere acción del usuario ahora mismo de lo que es solo historial de referencia. En particular, el criterio "listo para Asignar Egreso" (tipo de proceso clasificado + Traspaso registrado + checklist obligatorio completo + proveedor identificado), que ya se calcula en el backend para el detalle de una importación SGF (`ImportacionSgfResource::listoParaEgreso`) y para el formulario de creación de Egreso CGU, no tiene ninguna representación visual en el detalle del caso individual — el usuario tiene que inferirlo leyendo cuatro secciones distintas.

## What Changes

- Agregar un panel de "Preparación para Asignar Egreso" al inicio de la página, con una barra de progreso y 4 indicadores de estado (tipo de proceso, Traspaso, checklist obligatorio, proveedor identificado), calculado en el frontend a partir de datos que Inertia ya envía (`caso.proceso.tipo_proceso_pago_id`, `caso.registros_contables_cgu`, `caso.proceso.checklist.items`, `caso.proveedor`) — sin nuevos campos de backend, es el mismo criterio que `ListoParaEgresoResolver` ya aplica del lado del servidor para otras vistas.
- Reagrupar las secciones existentes en 3 bloques visuales con encabezado: "Clasificación y expediente" (Tipo de proceso, Checklist documental, Documentos), "Financiero" (Traspaso/CGU, Pago bancario, Facturas) y "Actividad" (Historial de transiciones, Egresos CGU asociados) — mismo contenido y lógica de cada sección, solo reorganizadas.
- Rediseñar cada ítem del checklist documental con un ícono circular de estado (check verde / círculo pendiente) en vez de solo texto, conservando exactamente los mismos controles (subida directa, vinculación de huérfanos) y su gating por permisos.
- Mover "Transiciones disponibles" y un resumen de datos del caso a una barra lateral sticky en pantallas anchas, que colapsa a una columna en mobile.
- **BREAKING**: ninguno — es un rediseño puro de presentación; no cambia rutas, props, endpoints, permisos ni comportamiento funcional.

## Capabilities

### New Capabilities

(ninguna — este change no introduce un dominio funcional nuevo)

### Modified Capabilities

- `paginas-pago-proveedores`: el requirement "Página de detalle de un caso con acciones de workflow" gana un nuevo SHALL sobre mostrar un panel de preparación para Asignar Egreso con los 4 criterios de `listo_para_egreso`, además de la reorganización visual en grupos (sin cambiar ningún SHALL de comportamiento existente: mismas transiciones, mismo checklist, mismos documentos).

## Impact

- **Frontend únicamente**: `resources/js/pages/pago-proveedores/casos/show.tsx`, posiblemente extraído en subcomponentes dentro de `resources/js/components/pago-proveedores/` (ej. `preparacion-egreso-card.tsx`, `checklist-documental-card.tsx`) para mantener el archivo legible.
- Sin cambios en `app/Http/Controllers`, `app/Http/Resources`, rutas, policies, migraciones ni tests de backend (Pest).
- Sin cambios en `resources/js/types/pago-proveedores.ts` (no se agregan campos nuevos al payload de Inertia).
- Tests afectados: ninguno de backend; verificación manual en navegador de todos los flujos existentes de la página (clasificar tipo de proceso, subir/vincular documento, vincular huérfano, registrar Traspaso/pago/factura, ejecutar transición, vincular/desvincular adquisición) tras el rediseño, más revisión de modo claro/oscuro y responsive.

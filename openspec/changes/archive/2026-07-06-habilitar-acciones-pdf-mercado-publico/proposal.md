## Why

El change `2026-07-06-actualizar-ficha-oc-mercado-publico` dejó las acciones "Ver PDF" y "Mercado Público" del encabezado de la ficha de OC deshabilitadas ("Disponible próximamente") porque en ese momento no había un enlace externo verificado hacia el detalle público de una Orden de Compra. Se investigó ahora (API real de Mercado Público, ingeniería inversa del buscador público, y una URL verificada por el usuario) y se confirmó un enlace oficial y estable por código de OC, sin token opaco. Ya no hay razón para mantener ambas acciones deshabilitadas.

## What Changes

- El botón "Mercado Público" del encabezado de la ficha deja de estar deshabilitado: abre en una pestaña nueva `https://www.mercadopublico.cl/PurchaseOrder/Modules/PO/DetailsPurchaseOrder.aspx?codigoOC=<código>` con el código de la OC actual.
- El botón "Ver PDF" descarga el PDF directamente: en vez de solo enlazar a la página HTML de Mercado Público (donde el usuario tendría que hacer clic de nuevo en el botón nativo "Descargar PDF"), un endpoint propio del backend resuelve esa página, extrae el token de descarga real y redirige el navegador al PDF binario de Mercado Público — se confirmó que ese endpoint (`PDFReport.aspx?qs=<token>`) responde el archivo PDF directamente (`Content-Disposition: attachment`), sin requerir sesión ni cookies previas.
- `AccionesEncabezadoFichaMercadoPublico` (resources/js/components/mercado-publico/ficha-consulta.tsx) pasa a requerir el código de la OC como prop, además del `payloadCrudo` ya existente: "Mercado Público" sigue siendo un enlace externo directo, "Ver PDF" ahora apunta al endpoint propio de resolución de PDF.
- Se actualizan los tres usos del componente en `show.tsx` y `buscar.tsx` (OC guardada, OC local desde búsqueda, y vista previa antes de guardar) para pasar el código.
- Nuevo endpoint de backend (dominio `ordenes-compra-mercado-publico`) que, dado un código de OC, consulta la página pública de Mercado Público, extrae el enlace real de descarga de PDF y redirige a él, registrando la solicitud como evidencia trazable igual que las demás consultas a Mercado Público de este dominio.
- "Ver JSON" no cambia.

## Capabilities

### Modified Capabilities
- `ordenes-compra-mercado-publico`: se agrega la resolución del enlace directo de descarga de PDF de una OC a partir de su código, consultando la página pública de Mercado Público y registrando la solicitud como evidencia trazable (reutilizando `IntegracionExternaService`).
- `paginas-ordenes-compra-mercado-publico`: la acción "Acciones de encabezado para ver el JSON, el PDF y el enlace a Mercado Público" deja de describir "Ver PDF" y "Ver en Mercado Público" como deshabilitadas; "Mercado Público" abre el enlace oficial en una pestaña nueva, y "Ver PDF" descarga el PDF directamente a través del endpoint propio de resolución.

## Impact

- Backend: nueva ruta/acción de controlador y método de servicio en el dominio `ordenes-compra-mercado-publico` (`app/Services/Adquisiciones/OrdenCompraMercadoPublicoService.php`, `app/Http/Controllers/Adquisiciones/OrdenCompraMercadoPublicoController.php`, `routes/adquisiciones.php` o equivalente).
- Frontend: `resources/js/components/mercado-publico/ficha-consulta.tsx`, `resources/js/pages/adquisiciones/ordenes-compra-mercado-publico/show.tsx`, `resources/js/pages/adquisiciones/ordenes-compra-mercado-publico/buscar.tsx`.
- Tests: nuevos tests de feature/servicio para la resolución del PDF (éxito, OC no encontrada, botón de PDF ausente en la página de Mercado Público).

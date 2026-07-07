## 0. Backend: resolver el enlace directo de descarga de PDF

- [x] 0.1 En `app/Services/Adquisiciones/OrdenCompraMercadoPublicoService.php`, agregar `resolverUrlPdf(string $codigo): ?string`: hace `Http::get()` a `https://www.mercadopublico.cl/PurchaseOrder/Modules/PO/DetailsPurchaseOrder.aspx?codigoOC=<código>`, extrae el token del atributo `onclick` de `#imgPDF` con una regex estricta (`qs=([A-Za-z0-9+/=]+)`), y arma la URL final concatenando con la constante base propia. Retorna `null` si no matchea o si la petición falla.
- [x] 0.2 Registrar la consulta como `solicitud_api_externa` (reutilizando `IntegracionExternaService::iniciarTrabajo`/`registrarSolicitud`/`finalizarTrabajo`), sin crear `snapshot_datos_externos`.
- [x] 0.3 Agregar la ruta `GET ordenes-compra-mercado-publico/pdf` en `routes/adquisiciones.php`, registrada ANTES de la ruta `{orden}` para evitar que "pdf" se intente resolver como binding de modelo.
- [x] 0.4 Agregar `OrdenCompraMercadoPublicoController@pdf`: exige el permiso `adquisiciones.consultar_orden_compra_mp` (mismo gate `viewAny` que `index`/`buscar`), llama a `resolverUrlPdf`, y redirige (`302`) a la URL resuelta o vuelve atrás con un error si no se pudo resolver.
- [x] 0.5 Tests de servicio (`Http::fake` simulando el HTML con y sin el botón `imgPDF`) y de feature HTTP (redirección exitosa, error cuando no se resuelve, y rechazo sin el permiso requerido).

## 1. Componente compartido

- [x] 1.1 En `resources/js/components/mercado-publico/ficha-consulta.tsx`, agregar la constante de URL oficial `https://www.mercadopublico.cl/PurchaseOrder/Modules/PO/DetailsPurchaseOrder.aspx?codigoOC=` y una función que la arme con `encodeURIComponent(codigo)`.
- [x] 1.2 Agregar `codigo: string` como prop obligatoria de `AccionesEncabezadoFichaMercadoPublico`.
- [x] 1.3 Reemplazar los botones deshabilitados "Ver PDF" y "Mercado Público" (y sus `Tooltip`/"Disponible próximamente"): "Mercado Público" queda como `<a target="_blank" rel="noopener noreferrer">` a la URL armada en 1.1; "Ver PDF" pasa a ser un `<a>` (sin `target="_blank"`, para que el navegador maneje la descarga/redirección) hacia el nuevo endpoint propio `GET /adquisiciones/ordenes-compra-mercado-publico/pdf?codigo=<código>`.

## 2. Páginas que usan el componente

- [x] 2.1 En `show.tsx`, pasar `codigo={orden.codigo}` a `AccionesEncabezadoFichaMercadoPublico`.
- [x] 2.2 En `buscar.tsx`, pasar `codigo={ordenLocal.codigo}` en la ficha de OC local y `codigo={vistaPrevia.payload_normalizado.codigo}` en la ficha de vista previa.

## 3. Validación

- [x] 3.1 `vendor/bin/pint --dirty --format agent`, `npm run lint:check` y `npm run types:check`.
- [x] 3.2 `php artisan test --compact --filter=OrdenCompraMercadoPublico`.
- [x] 3.3 Verificación manual en navegador: cargar una OC real y confirmar que "Mercado Público" abre la página oficial en una pestaña nueva, y que "Ver PDF" descarga el PDF directamente (sin pasos intermedios); confirmar también que "Ver JSON" sigue funcionando igual.

## Why

Adquisiciones ya puede traer y trazar Órdenes de Compra desde Mercado Público, pero no tiene visibilidad de las Licitaciones que las originan. Sin esto, un usuario que quiere revisar el proceso completo de una compra (licitación → adjudicación → OC) debe salir del sistema y consultar mercadopublico.cl directamente, perdiendo la evidencia trazable (snapshot, solicitud) que sí se genera para las OC.

## What Changes

- Nuevo dominio `licitaciones-mercado-publico`: búsqueda local-primero por código, consulta a la API pública de Mercado Público (`licitaciones.json`) solo cuando no existe localmente, snapshot obligatorio de todo payload recibido, comparación campo a campo contra la API bajo confirmación explícita del usuario, y guardado de una licitación nueva solo tras confirmación explícita.
- A diferencia de una OC, una licitación **no tiene un único proveedor emisor**: puede tener cero o varios proveedores adjudicados, uno por ítem (`Items[].Adjudicacion`). Por eso esta capability **no** replica la resolución/creación automática de un `Proveedor` al guardar — solo conserva el RUT y nombre del proveedor adjudicado de cada ítem como dato informativo del payload, sin tocar el catálogo de proveedores.
- Vínculo opcional y manual (sin disparar workflow) entre una `licitacion_mercado_publico` y un `proceso_adquisicion` existente, igual que con las OC.
- Nuevas páginas Inertia (listado, búsqueda/vista previa, ficha de detalle) que reutilizan el componente ya genérico `FichaConsultaMercadoPublico` y el patrón de listado tabular denso ya usado por `ordenes-compra-mercado-publico`.
- El componente compartido `AccionesEncabezadoFichaMercadoPublico` se generaliza para recibir la URL de detalle público y la URL de PDF (o `null`) como props en vez de tenerlas hardcodeadas a Órdenes de Compra — pequeño ajuste no-breaking a su firma, consumido por ambas páginas de detalle (OC y Licitación).
- **Fuera de alcance en este cambio**: la acción "Ver PDF" para una licitación queda deshabilitada ("Disponible próximamente"). A diferencia del PDF de una OC (patrón de extracción verificado sobre `DetailsPurchaseOrder.aspx`), Mercado Público no expone un botón de descarga equivalente y verificable en la página pública de detalle de una licitación; no se implementa una extracción no verificada.

## Capabilities

### New Capabilities
- `licitaciones-mercado-publico`: servicio de integración que trae Licitaciones desde la API de Mercado Público (búsqueda local-primero, consulta a la API, snapshot obligatorio, comparación/actualización bajo confirmación, guardado bajo confirmación, vínculo opcional con `proceso_adquisicion`).
- `paginas-licitaciones-mercado-publico`: capa HTTP/Inertia (listado, búsqueda por código, ficha de detalle, comparación de diferencias, vista previa antes de guardar) que expone la capability anterior al usuario autenticado.

### Modified Capabilities
- (ninguna: la generalización de `AccionesEncabezadoFichaMercadoPublico` es un detalle de implementación del componente compartido, no un cambio de requisito de `paginas-ordenes-compra-mercado-publico`)

## Impact

- Nuevas tablas `licitaciones_mercado_publico` y `licitaciones_mercado_publico_items`.
- Nuevo modelo `LicitacionMercadoPublico`, `LicitacionMercadoPublicoItem`, servicio `LicitacionMercadoPublicoService`, controlador `LicitacionMercadoPublicoController`, controlador de vínculo `VinculoProcesoAdquisicionLicitacionMercadoPublicoController`, policy `LicitacionMercadoPublicoPolicy`, permiso `adquisiciones.consultar_licitacion_mp`.
- Nuevas rutas bajo `adquisiciones/licitaciones-mercado-publico` en `routes/adquisiciones.php`.
- Nuevas páginas React en `resources/js/pages/adquisiciones/licitaciones-mercado-publico/` y ajuste de firma en `resources/js/components/mercado-publico/ficha-consulta.tsx`.
- Reutiliza `App\Services\Integraciones\IntegracionExternaService` y el `sistema_externo` `MERCADO_PUBLICO` ya existente (no se crea uno nuevo).
- Nuevos tests Feature en `tests/Feature/Adquisiciones/*LicitacionMercadoPublico*Test.php`.

## Why

Hoy no existe ninguna forma de traer una Orden de Compra (OC) emitida en Mercado Público hacia el sistema propio: los procesos de adquisición se crean íntegramente a mano, sin ningún vínculo con la evidencia real que produce la plataforma oficial. Esto obliga a copiar datos de OC manualmente (proveedor, ítems, montos) y no deja registro verificable de que lo cargado coincide con lo que Mercado Público realmente emitió. Se necesita un mecanismo de consulta que traiga la OC desde la API oficial, la deje como evidencia trazable (snapshot + log de la llamada) y permita compararla contra lo que ya existe localmente antes de guardar o actualizar nada — sin que esa evidencia externa gobierne el workflow interno.

## What Changes

- Nueva entidad `orden_compra_mercado_publico` (+ sus ítems) que representa la OC tal como la devuelve Mercado Público, buscable localmente por código de OC.
- Nuevo servicio de dominio que, dado un código de OC:
  - Busca primero en base de datos local.
  - Si existe y el usuario lo pide, consulta la API de Mercado Público, compara campo a campo contra el registro local y devuelve las diferencias (sin aplicar ningún cambio automáticamente).
  - Si no existe localmente, consulta la API; si la API no la encuentra, registra el intento fallido vía `solicitudes_api_externas` sin crear nada; si la API la encuentra, arma una vista previa (OC + ítems + datos del proveedor emisor).
- Reutilización estricta de la capa transversal de integraciones ya existente (`SistemaExterno`, `IntegracionExternaService`, `SolicitudApiExterna`, `SnapshotDatosExterno`) para registrar la llamada HTTP y el snapshot con hash del payload crudo — nada de tablas ni servicios paralelos.
- Activación del `sistema_externo` `MERCADO_PUBLICO` (ya sembrado, hoy `activo: false`) y nueva configuración de credenciales (`config/services.php` + `.env`) siguiendo el mismo patrón que `CMF_API_KEY` / `CMF_API_BASE_URL`.
- Verificación del proveedor emisor de la OC contra el catálogo de proveedores existente; si no existe, el usuario decide crear/actualizarlo reutilizando el flujo ya existente de alta de proveedores (`ProveedorController`) — no se duplica esa lógica.
- Guardado de la OC solo tras confirmación explícita del usuario (tanto para la creación inicial como para aplicar una actualización detectada por diferencias), guardando en la misma operación: la OC, sus ítems, el proveedor si corresponde, el snapshot y el log de la solicitud.
- Vínculo manual y opcional entre una `orden_compra_mercado_publico` y un `proceso_adquisicion` existente, siguiendo el mismo patrón ya usado para vincular `caso_pago_proveedor` a una adquisición. Este vínculo es solo informativo: no dispara transiciones de workflow.
- Nuevo permiso `adquisiciones.consultar_orden_compra_mp` para autorizar esta funcionalidad.
- Nueva página/flujo Inertia: ingreso de código de OC, resultado local, comparación de diferencias, vista previa de OC nueva, confirmación de guardado.
- **BREAKING (spec, no runtime)**: se reemplaza el requirement "No modelar integración externa todavía" de la capability `adquisiciones`, que hoy prohíbe expresamente cualquier integración con Mercado Público como origen de datos para Adquisiciones.

## Capabilities

### New Capabilities
- `ordenes-compra-mercado-publico`: dominio y servicio de integración que busca, compara y persiste Órdenes de Compra de Mercado Público como evidencia externa, con snapshot y log obligatorios, y vínculo opcional a un proceso de adquisición.
- `paginas-ordenes-compra-mercado-publico`: capa HTTP/Inertia que expone el flujo de búsqueda/consulta/comparación/confirmación de OC al usuario autenticado con el permiso correspondiente.

### Modified Capabilities
- `adquisiciones`: se reemplaza el requirement "No modelar integración externa todavía" — Mercado Público pasa a ser un origen de datos externo válido (vía snapshot) para las Órdenes de Compra vinculables a un `proceso_adquisicion`, sin que esto implique que Mercado Público gobierne el workflow ni los estados del proceso.

## Impact

- Nuevas migraciones: `ordenes_compra_mercado_publico`, `orden_compra_mercado_publico_items`, tabla de vínculo con `procesos_adquisicion`.
- Nuevos modelos, un servicio de dominio (`Services/Adquisiciones` o dominio propio `Services/OrdenesCompraMercadoPublico`), un Form Request, un controlador HTTP y páginas React nuevas.
- `config/services.php` y `.env(.example)`: nuevas claves `MERCADOPUBLICO_API_KEY` / `MERCADOPUBLICO_API_BASE_URL`.
- `database/seeders/IntegracionesSeeder.php`: activar `MERCADO_PUBLICO` (`activo: true`).
- Nuevo seeder o extensión de seeder existente para el permiso `adquisiciones.consultar_orden_compra_mp`.
- `openspec/specs/adquisiciones/spec.md`: reemplazo del requirement de no-integración externa.

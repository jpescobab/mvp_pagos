## Why

Al guardar una Orden de Compra de Mercado Público que no existe localmente, el proveedor emisor se persiste **incompleto**: solo con RUT y nombre, descartando dirección, comuna, región, giro/actividad, correo y datos de contacto que el payload de Mercado Público **sí entrega** y para los que el modelo `Proveedor` ya tiene columnas. Además, el manejo de una OC sin RUT de proveedor es frágil: crea un proveedor con `rutproveedor = ''` (basura), y por el unique constraint `proveedores_rutproveedor_unique` una segunda OC sin RUT aborta todo el guardado (unique violation → rollback → no se guarda ni la OC ni el proveedor).

## What Changes

- Al resolver el proveedor emisor de una OC, poblar **todos los campos disponibles** del payload de Mercado Público, no solo RUT y nombre: `direccion`, `comuna`, `region`, `giro` (desde `Actividad`), `correo` (desde `MailContacto`), `contacto` (desde `NombreContacto`), `contacto_cargo` (desde `CargoContacto`) y `contacto_telefono` (desde `FonoContacto`), normalizando valores vacíos/espacios a `null`.
- Aplicar esos campos tanto al **crear** un proveedor nuevo como al **completar campos vacíos** de un proveedor existente (sin sobrescribir los que ya tienen valor).
- **Salvaguarda de RUT ausente**: cuando el payload no trae un RUT de proveedor identificable, no crear un proveedor con `rutproveedor` vacío ni abortar el guardado por la violación del unique; en su lugar, rechazar el guardado de esa OC con un mensaje claro.
- Sin cambios en el modelo de datos (columnas ya existen) ni en el frontend.

## Capabilities

### New Capabilities

_(ninguna)_

### Modified Capabilities

- `ordenes-compra-mercado-publico`: el requirement de resolución del proveedor emisor de una OC SHALL poblar y completar todos los campos disponibles del payload (no solo RUT y nombre), y SHALL manejar de forma segura el caso de una OC sin RUT de proveedor identificable.

## Impact

- **Backend**: `app/Services/Adquisiciones/OrdenCompraMercadoPublicoService.php` (`normalizarPayload()` amplía el bloque `proveedor`; `resolverProveedor()` usa los campos nuevos al crear y al completar; nueva salvaguarda de RUT ausente). Posible ajuste menor en `OrdenCompraMercadoPublicoController::guardar()` para transmitir el mensaje de rechazo.
- **Tests (Pest)**: cobertura de creación completa del proveedor, completado de campos vacíos, y del rechazo por RUT ausente.
- **Datos**: los proveedores ya creados con solo RUT+nombre no se modifican retroactivamente; se completan la próxima vez que se guarde/actualice una OC de ese proveedor (vía el completado de campos vacíos).
- **Sin cambios** en migraciones, rutas, dependencias ni frontend.

## Why

Hoy, al guardar una Orden de Compra (OC) nueva traída desde la API de Mercado Público, si el proveedor emisor no existe en el catálogo local el sistema **bloquea** el guardado de la OC: obliga al usuario a abandonar el flujo, ir al formulario aparte de alta de proveedores, crearlo manualmente, y solo entonces volver a intentar guardar la OC. Esto interrumpe innecesariamente un flujo que ya tiene toda la información del proveedor emisor disponible en el mismo payload de Mercado Público (RUT y nombre). Además, tras guardar, el sistema redirige al detalle de la OC recién creada, cuando el punto de entrada natural del usuario a este flujo es el listado de OC.

## What Changes

- El guardado de una OC nueva deja de bloquearse por proveedor inexistente: el proveedor se resuelve automáticamente como parte de la misma operación de guardado, en una sola transacción, antes de crear la OC.
  - Si el proveedor no existe en el catálogo (por RUT), se crea con los datos del payload (RUT normalizado, nombre).
  - Si el proveedor ya existe, se completan únicamente los campos vacíos del registro local con los datos disponibles del payload (hoy, en la práctica, solo `nombre` si estuviera vacío), sin sobreescribir datos ya cargados.
- Se elimina de la vista previa de OC nueva el bloqueo "el proveedor no existe, créalo antes de guardar" con su enlace al formulario de alta de proveedores: la acción "Guardar OC" queda siempre habilitada, exista o no el proveedor previamente.
- Tras guardar, el sistema informa (vía flash/toast) el resultado de la operación sobre el proveedor: creado, actualizado (con qué se completó) o sin cambios.
- Tras guardar, el sistema redirige al **listado** de Órdenes de Compra (`adquisiciones.ordenes_compra_mp.index`) en vez de al detalle de la OC recién guardada.
- **BREAKING (spec, no runtime)**: reemplaza el requirement "Verificar y vincular al proveedor emisor de la OC" de la capability `ordenes-compra-mercado-publico`, que hoy exige bloquear el guardado hasta que el proveedor exista; y el requirement "Página de vista previa de una OC nueva antes de guardar" de `paginas-ordenes-compra-mercado-publico` en su escenario "Proveedor inexistente" y "Confirmar guardado" (cambia el destino de la navegación tras guardar).

## Capabilities

### Modified Capabilities
- `ordenes-compra-mercado-publico`: el guardado de una OC nueva ya no depende de que el proveedor exista de antemano; el servicio de dominio crea o completa el proveedor automáticamente dentro de la misma transacción de guardado.
- `paginas-ordenes-compra-mercado-publico`: la vista previa de OC nueva ya no bloquea el guardado por proveedor inexistente, y la confirmación de guardado navega al listado de OC en vez del detalle.

## Impact

- `app/Services/Adquisiciones/OrdenCompraMercadoPublicoService.php`: nueva lógica de resolución de proveedor (crear/completar) integrada a `guardarDesdeApi()`, dentro de la misma transacción.
- `app/Http/Controllers/Adquisiciones/OrdenCompraMercadoPublicoController.php`: `guardar()` deja de exigir `proveedor_id`/rechazar por proveedor inexistente; cambia el redirect final a `index`; agrega el mensaje de resultado del proveedor al flash.
- `app/Http/Requests/Adquisiciones/GuardarOrdenCompraMercadoPublicoRequest.php`: revisar si `proveedor_id` sigue teniendo sentido como override manual opcional (p. ej. para vincular a un proveedor existente distinto al detectado por RUT) o se elimina.
- `resources/js/pages/adquisiciones/ordenes-compra-mercado-publico/buscar.tsx`: la vista previa de OC nueva elimina el bloqueo de proveedor inexistente y su enlace al alta manual; el botón "Guardar OC" queda siempre habilitado.
- Tests de Feature existentes que verifican el bloqueo por proveedor inexistente y el redirect a `show` deben actualizarse para reflejar el nuevo comportamiento.

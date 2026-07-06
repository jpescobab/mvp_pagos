## Context

`OrdenCompraMercadoPublicoService::guardarDesdeApi()` hoy recibe un `?Proveedor $proveedor` ya resuelto por el controlador (`verificarProveedor()` + `proveedor_id` opcional del request) y lo asigna tal cual a la OC; si es `null`, `OrdenCompraMercadoPublicoController::guardar()` rechaza la operación con un error de validación (`proveedor_id`) antes de siquiera invocar al servicio de guardado. El payload normalizado de Mercado Público solo expone `proveedor.rut` y `proveedor.nombre` — no hay más campos de proveedor disponibles desde esta API.

## Goals / Non-Goals

**Goals:**
- Que guardar una OC nueva nunca se bloquee por proveedor inexistente: el proveedor se resuelve (crea o completa) automáticamente, dentro de la misma transacción que crea la OC.
- Que el usuario reciba una confirmación clara de qué pasó con el proveedor (creado / actualizado / sin cambios) sin que sea un paso bloqueante previo.
- Que tras guardar, la navegación vuelva al listado de OC.

**Non-Goals:**
- No se agrega ningún campo nuevo de proveedor al payload de Mercado Público (la API de OC solo entrega RUT y nombre); "completar campos faltantes" queda acotado a lo que ese payload realmente aporta.
- No se cambia el flujo de verificación/comparación de una OC ya guardada contra la API (`compararConApi`/`aplicarActualizacion`), solo el guardado inicial de una OC nueva.
- No se toca el formulario manual de alta/edición de proveedores (`Maestros\ProveedorController`) ni su validación; sigue existiendo para el mantenimiento general del catálogo, solo deja de ser un paso obligatorio dentro de este flujo específico.

## Decisions

- **La resolución del proveedor se mueve al servicio, dentro de la transacción de `guardarDesdeApi()`**, en vez de resolverse antes en el controlador. Esto garantiza atomicidad: si el guardado de la OC falla después de crear/actualizar el proveedor, todo el cambio se revierte (no queda un proveedor huérfano). Se reemplaza la firma de `guardarDesdeApi(array $payloadNormalizado, ?Proveedor $proveedor, ...)` por `guardarDesdeApi(array $payloadNormalizado, SnapshotDatosExterno $snapshot, ?int $procesoAdquisicionId = null)`, resolviendo el proveedor internamente vía un nuevo método privado `resolverProveedor()`.
- **`resolverProveedor()` reutiliza `verificarProveedor()` para buscar por RUT normalizado**; si no existe, crea uno nuevo (`rutproveedor`, `nombre`, `activo => true`); si existe, completa con `fill()` únicamente los campos que estén `null`/vacíos hoy (en la práctica, solo `nombre`, dado lo que aporta el payload) y guarda solo si hubo cambios reales.
- **Se conserva `proveedor_id` en el request como override manual opcional**: si el usuario lo envía explícitamente (caso: el proveedor detectado por RUT no es el correcto, o quiere vincular a uno distinto), se usa ese proveedor tal cual sin pasar por la lógica de creación/completado. Si no se envía, se resuelve automáticamente por RUT del payload.
- **El resultado de la operación de proveedor se modela como un enum/string simple** (`creado` | `actualizado` | `sin_cambios`) devuelto junto con la OC desde el servicio (p. ej. como parte de un array de retorno o un objeto ligero), para que el controlador arme el mensaje flash sin tener que re-derivar el estado.
- **El redirect final cambia de `show` a `index`**: es un cambio de una línea en el controlador, sin impacto en el resto del flujo (verificar/actualizar/vincular siguen redirigiendo a `show` como corresponde, ya que ahí el usuario sí quiere ver el detalle de una OC que está revisando).
- **El frontend elimina la sección condicional de "proveedor inexistente"** en la vista previa (`vistaPrevia.proveedor_existente === null`): ya no hay nada que bloquear, así que el botón "Guardar OC" se muestra siempre habilitado en la vista previa, sin importar si el proveedor existe o no.

## Risks / Trade-offs

- [Crear un proveedor solo con RUT y nombre puede dejar un registro incompleto (sin dirección, contacto, etc.)] → Es una mejora respecto al bloqueo actual (que de todas formas terminaba creando un proveedor igual de incompleto vía el formulario manual, solo que con un paso extra); el catálogo de proveedores sigue siendo editable después para completar el resto de los datos.
- [Actualizar automáticamente el proveedor sin confirmación explícita del usuario podría sorprender si el nombre en Mercado Público difiere del nombre local] → Se limita estrictamente a completar campos **vacíos**, nunca a sobreescribir un valor ya cargado; y el resultado se comunica siempre en el mensaje de confirmación.

## 1. Servicio de dominio

- [x] 1.1 En `OrdenCompraMercadoPublicoService`, cambiar la firma de `guardarDesdeApi()` de `(array $payloadNormalizado, ?Proveedor $proveedor, SnapshotDatosExterno $snapshot, ?int $procesoAdquisicionId = null)` a `(array $payloadNormalizado, SnapshotDatosExterno $snapshot, ?int $procesoAdquisicionId = null, ?int $proveedorIdOverride = null)`.
- [x] 1.2 Crear un método privado `resolverProveedor(array $payloadNormalizado, ?int $proveedorIdOverride): array{proveedor: Proveedor, resultado: string}` donde `resultado` es `'creado'`, `'actualizado'` o `'sin_cambios'`:
  - Si `$proveedorIdOverride` viene informado, usar `Proveedor::findOrFail($proveedorIdOverride)` tal cual, resultado `'sin_cambios'`.
  - Si no, buscar por RUT normalizado (reutilizando `verificarProveedor()`); si no existe, crear uno nuevo con `rutproveedor`, `nombre` y `activo => true`, resultado `'creado'`.
  - Si existe, completar con `fill()` únicamente los campos vacíos (`null` o cadena vacía) que el payload sí aporte (en la práctica, `nombre`); guardar solo si hubo cambios reales, resultado `'actualizado'` o `'sin_cambios'` según corresponda.
- [x] 1.3 Mover la llamada a `resolverProveedor()` dentro de la transacción de `guardarDesdeApi()`, antes de `OrdenCompraMercadoPublico::create()`, y devolver junto con la OC el resultado de la operación sobre el proveedor (p. ej. como array `['orden' => OrdenCompraMercadoPublico, 'proveedor_resultado' => string]`).
- [x] 1.4 Tests de Feature/Unit: guardar con proveedor inexistente (se crea), guardar con proveedor existente con `nombre` vacío (se completa sin tocar otros campos), guardar con proveedor existente y completo (sin cambios), guardar con `proveedor_id` override explícito (se usa tal cual, sin tocar sus campos), y un test que fuerce un fallo posterior al crear el proveedor (p. ej. mockeando `crearItems` o el guardado de la OC) para confirmar que la transacción revierte también la creación del proveedor. 13/13 tests verdes.

## 2. Capa HTTP

- [x] 2.1 En `OrdenCompraMercadoPublicoController::guardar()`, eliminar la resolución previa de `$proveedor` vía `verificarProveedor()`/`Proveedor::find()` y el rechazo con `withErrors(['proveedor_id' => ...])`; delegar toda la resolución al servicio, pasando `proveedor_id` del request como override opcional.
- [x] 2.2 Cambiar el redirect final de `to_route('adquisiciones.ordenes_compra_mp.show', $orden)` a `to_route('adquisiciones.ordenes_compra_mp.index')`.
- [x] 2.3 Ajustar el mensaje flash de éxito para incluir el resultado de la operación sobre el proveedor (p. ej. "OC \"{codigo}\" guardada. Proveedor {creado/actualizado/sin cambios}.").
- [x] 2.4 Revisar si `GuardarOrdenCompraMercadoPublicoRequest` necesita ajustes en la regla de `proveedor_id` (sigue `nullable`/`exists:proveedores,id`, ahora como override manual opcional, no como requisito de guardado). Sin cambios necesarios.
- [x] 2.5 Tests de Feature HTTP: guardar sin `proveedor_id` y sin proveedor existente ya no falla y crea el proveedor; la respuesta redirige al índice; el flash incluye el resultado del proveedor. 49/49 tests de Adquisiciones verdes.

## 3. Frontend

- [x] 3.1 En `resources/js/pages/adquisiciones/ordenes-compra-mercado-publico/buscar.tsx`, eliminar la rama condicional de "proveedor inexistente" que muestra el enlace a crear proveedor y bloquea la acción de guardado; la sección "Proveedor emisor" de la vista previa debe mostrar el proveedor existente o un aviso informativo ("Se creará un proveedor nuevo con estos datos al guardar") pero el botón "Guardar OC" queda siempre visible y habilitado.
- [x] 3.2 Ajustar la llamada `guardar()` para no depender de `vistaPrevia.proveedor_existente` al construir el payload (ya no hace falta enviar `proveedor_id` salvo que se agregue en el futuro un override manual explícito desde la UI, fuera de alcance de este change).
- [x] 3.3 Verificar que tras el guardado exitoso la navegación aterrice en el listado de OC (comportamiento ya delegado al backend vía redirect, sin lógica adicional en el frontend).
- [x] 3.4 Regenerar tipos de Wayfinder si cambia la forma de props (`php artisan wayfinder:generate --with-form`). Ejecutado; sin cambios generados.

## 4. Verificación end-to-end

- [x] 4.1 Verificar en el navegador (dev server): buscar un código de OC nueva con proveedor inexistente, confirmar guardado, verificar que no aparece ningún bloqueo previo, que el proveedor queda creado en el catálogo, y que la navegación final llega al listado de OC mostrando la OC recién guardada. Verificado el flujo de búsqueda (sin errores de consola); el guardado con proveedor inexistente contra la API real de Mercado Público no se pudo forzar de forma fiable sin un ticket configurado (misma limitación ya documentada en el change `integrar-ordenes-compra-mercado-publico`, tarea 7.8) — cubierto en cambio por los tests de Feature con `Http::fake` (tarea 1.4 y 2.5).

## 5. Especificación y cierre

- [x] 5.1 Ejecutar `openspec validate guardado-automatico-proveedor-oc-mercado-publico --strict` y corregir lo que señale. Válido sin hallazgos.
- [x] 5.2 Ejecutar `composer test` (lint, types, test) y corregir hallazgos. Pint, PHPStan (0 errores tras corregir una comparación `=== null` siempre falsa sobre `Proveedor::$nombre`, no nullable) y Pest (422 passed, 4 skipped, 0 fallos) en verde.
- [x] 5.3 Ejecutar `vendor/bin/pint --dirty --format agent` sobre los archivos PHP modificados. Pasa sin cambios.
- [ ] 5.4 Tras aprobación, archivar el change con `/opsx:archive` para fusionar los spec delta en `openspec/specs/ordenes-compra-mercado-publico/spec.md` y `openspec/specs/paginas-ordenes-compra-mercado-publico/spec.md`.

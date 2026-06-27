## 1. Brechas en la capa HTTP existente

- [x] 1.1 En `App\Http\Resources\PagoProveedores\ProcesoResource`, agregar `checklist`: si `$this->checklist` existe, `['items' => $this->checklist->items->map(fn ($item) => ['tipo_documento' => $item->tipoDocumento?->nombre, 'tipo_requisito' => $item->tipo_requisito, 'estado_cumplimiento' => $item->estado_cumplimiento])]`; si no existe, `null` (implementado con `whenLoaded('checklist', ...)` para no forzar un lazy-load en `casos.index`, que no eager-carga esa relación)
- [x] 1.2 En `App\Http\Controllers\PagoProveedores\EgresoCguController::create()`, autorizar `create` (ya existente), cargar `CasoPagoProveedor::with('proveedor')->get()` y pasarlo como `casos` — **ajuste sobre el diseño**: en vez de `CasoPagoProveedorResource::collection(...)` se mapea a un arreglo plano `{id, sgf_id, proveedor.nombre, monto}`, porque ese Resource siempre anida `ProcesoResource` (estado, transiciones disponibles, definicionWorkflow), forzando N+1 innecesario para un simple selector de casos en el formulario
- [x] 1.3 Confirmar que `tests/Feature/PagoProveedores/ApiPagoProveedoresTest.php` sigue pasando sin cambios (los nuevos campos son aditivos, no rompen aserciones existentes) — 7/7 passed

## 2. Tipos y componentes compartidos

- [x] 2.1 Crear `resources/js/types/pago-proveedores.ts` con los tipos `CasoPagoProveedor`, `Proceso`, `EstadoWorkflow`, `TransicionWorkflow`, `HistorialTransicion`, `ChecklistItem`, `EgresoCgu`, espejando exactamente las claves de los Resources PHP (incluyendo el `checklist` de la tarea 1.1) — formas verificadas en vivo contra los endpoints reales (no inferidas), más `Paginated<T>`/`CasoSeleccionable` adicionales
- [x] 2.2 Crear `resources/js/components/pago-proveedores/estado-badge.tsx`: recibe `codigo` y `nombre` de un estado, devuelve un `Badge` (de `@/components/ui/badge`) con variante/clase de color según el código (usar `es_final`/`es_inicial` si están disponibles para distinguir estados terminales)

## 3. Página: listado de casos

- [x] 3.1 Crear `resources/js/pages/pago-proveedores/casos/index.tsx`: tabla con columnas Proveedor, RUT (fuente `JetBrains Mono` vía clase `font-mono`), Monto, Estado SGF, Estado (badge del `Proceso`), enlace a la fila vía Wayfinder hacia `casos.show`; paginación con los `links`/`meta` del paginador de Laravel
- [x] 3.2 Layout: usar `AppLayout` (heredado automáticamente vía `app.tsx`), breadcrumb "Casos de pago de proveedores"

## 4. Página: detalle de un caso

- [x] 4.1 Crear `resources/js/pages/pago-proveedores/casos/show.tsx`: cabecera con sgf_id/proveedor/monto/badges de estado SGF y estado del Proceso
- [x] 4.2 Sección de transiciones disponibles: un botón por cada `transiciones_disponibles`; si `requiere_comentario`, abrir `Dialog` (de `@/components/ui/dialog`) pidiendo comentario antes de enviar; el envío hace `POST` vía `router.post` (no `useForm`, para evitar la condición de carrera entre `setData` y `post` inmediato) a la ruta Wayfinder `casos.transiciones.store` con `{codigo, comentario?}`
- [x] 4.3 Sección de historial de transiciones: lista cronológica (más reciente primero) con transición, estados origen→destino, usuario, comentario y fecha
- [x] 4.4 Sección de checklist documental: si `caso.proceso.checklist` es `null`, mostrar estado vacío explícito; si existe, listar cada item con tipo de documento, tipo de requisito y estado de cumplimiento
- [x] 4.5 Mostrar errores de transición (`errors.transicion` del response de Inertia) de forma visible junto a las acciones

## 5. Páginas: egresos CGU

- [x] 5.1 Crear `resources/js/pages/pago-proveedores/egresos-cgu/index.tsx`: tabla con número de egreso, fecha, monto total, y los `sgf_id` de los casos cubiertos (`items[].caso.sgf_id`); botón "Nuevo egreso" hacia `egresos-cgu.create`
- [x] 5.2 Crear `resources/js/pages/pago-proveedores/egresos-cgu/crear.tsx`: formulario con `numero_egreso`, `fecha`, `observaciones`, y una lista de los `casos` recibidos como prop con checkbox + input de monto por fila; al enviar, construir `casos` como arreglo de `{caso_pago_proveedor_id, monto}` solo con los casos marcados, vía `router.post` hacia `egresos-cgu.store`
- [x] 5.3 Mostrar errores de validación por campo (incluyendo `casos.*.monto` si el backend los devuelve) sin perder los valores ya ingresados

## 6. Navegación

- [x] 6.1 En `resources/js/components/app-sidebar.tsx`, agregar un grupo de navegación "Pago de Proveedores" con dos ítems: "Casos" (→ `casos.index`) y "Egresos CGU" (→ `egresos-cgu.index`) — primeros ítems reales de un módulo funcional en el sidebar, ya que las páginas existen y son navegables (requirió agregar un prop `label` opcional a `NavMain` para soportar un segundo grupo con etiqueta distinta a "General")

## 7. Validación

- [x] 7.1 `npm run lint:check`
- [x] 7.2 `npm run types:check`
- [x] 7.3 `npm run build` (confirma que Wayfinder genera las funciones de ruta `pago-proveedores.*` y que los imports en las páginas resuelven)
- [x] 7.4 `composer test` (confirma que los cambios en `ProcesoResource`/`EgresoCguController` no rompen `ApiPagoProveedoresTest`) — 116 tests, 0 fallos
- [x] 7.5 Verificación visual vía Claude Preview: iniciar sesión, navegar a "Casos" y "Egresos CGU" desde el sidebar, abrir el detalle de un caso, ejecutar al menos una transición real y confirmar que el estado se actualiza en pantalla — verificado con un caso de prueba real (sembrado vía tinker, no parte de ningún seeder/migración): listado, detalle, transición sin comentario (estado actualizado reactivamente sin reload), transición con comentario (diálogo bloqueado hasta llenar el campo, estado pasó a "Observada"), y creación completa de un egreso CGU cubriendo el caso (redirección y aparición en el listado confirmadas)

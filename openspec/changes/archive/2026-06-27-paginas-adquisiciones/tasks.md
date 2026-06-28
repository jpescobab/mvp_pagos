## 1. Tipos y componentes compartidos

- [x] 1.1 Crear `resources/js/types/adquisiciones.ts` con el tipo `ProcesoAdquisicion` (espejando `ProcesoAdquisicionResource`: `id`, `codigo`, `modalidad.codigo`/`nombre`, `ccosto.codigo`/`nombre`, `proveedor.nombre`/`rutproveedor`, `monto`, `objeto`, `proceso`), reutilizando `Proceso`/`EstadoWorkflow`/`TransicionWorkflow`/`HistorialTransicion`/`ChecklistItem`/`Paginated` desde `@/types/pago-proveedores` (no duplicarlos)
- [x] 1.2 Confirmar que `EstadoBadge` (`resources/js/components/pago-proveedores/estado-badge.tsx`) funciona sin cambios para estados del workflow "adquisiciones" (ya es genérico, recibe `codigo`/`nombre`)

## 2. Página: listado de procesos

- [x] 2.1 Crear `resources/js/pages/adquisiciones/procesos/index.tsx`: tabla con columnas Código (font-mono), Modalidad, Centro de costo, Proveedor, Monto, Estado (badge del `Proceso`), enlace a la fila vía Wayfinder hacia `procesos.show`; botón "Nuevo proceso" hacia `procesos.create`; paginación con `links`/`meta`
- [x] 2.2 Layout: breadcrumb "Procesos de adquisición"

## 3. Página: detalle de un proceso

- [x] 3.1 Crear `resources/js/pages/adquisiciones/procesos/show.tsx`: cabecera con código/modalidad/ccosto/proveedor/monto y badge de estado del `Proceso`
- [x] 3.2 Sección de transiciones disponibles: un botón por cada `transiciones_disponibles`; si `requiere_comentario`, abrir `Dialog` pidiendo comentario antes de enviar; el envío hace `POST` vía `router.post` (no `useForm`) a la ruta Wayfinder `procesos.transiciones.store` con `{codigo, comentario?}`
- [x] 3.3 Sección de historial de transiciones: lista cronológica (más reciente primero) con transición, estados origen→destino, usuario, comentario y fecha
- [x] 3.4 Sección de checklist documental: si `proceso.proceso.checklist` es `null`, mostrar estado vacío explícito; si existe, listar cada item con tipo de documento, tipo de requisito y estado de cumplimiento
- [x] 3.5 Mostrar errores de transición (`errors.transicion`) de forma visible junto a las acciones

## 4. Página: formulario de creación

- [x] 4.1 Crear `resources/js/pages/adquisiciones/procesos/crear.tsx`: campos `codigo` (font-mono), `objeto` (textarea), `monto` (opcional); selects (`Select`/`SelectTrigger`/`SelectValue`/`SelectContent`/`SelectItem` de `@/components/ui/select`) para `modalidad_id` (de `modalidades` recibidas), `ccosto_id` (de `ccostos`), `proveedor_id` (opcional, de `proveedores`)
- [x] 4.2 Al enviar, `router.post` hacia `procesos.store` con los valores seleccionados; `onSuccess` navega al detalle (ya lo hace la redirección del controlador); mostrar errores de validación por campo (incluyendo `modalidad_id` si el backend rechaza la modalidad) sin perder los valores ya ingresados

## 5. Navegación

- [x] 5.1 En `resources/js/components/app-sidebar.tsx`, agregar un grupo de navegación "Adquisiciones" con un ítem "Procesos" (→ `procesos.index`), siguiendo el mismo patrón que el grupo "Pago de Proveedores" ya existente (usa el prop `label` de `NavMain`, ya soportado)

## 6. Validación

- [x] 6.1 `npm run lint:check`
- [x] 6.2 `npm run types:check`
- [x] 6.3 `npm run build` (confirma que Wayfinder genera las funciones de ruta `adquisiciones.*` y que los imports en las páginas resuelven)
- [x] 6.4 `composer test` (confirma que no se rompió `ApiAdquisicionesTest` ni `ProcesoAdquisicionServiceTest` — no se tocó código PHP en este change)
- [x] 6.5 Verificación visual vía Claude Preview: iniciar sesión, navegar a "Procesos" desde el sidebar, crear un proceso real vía el formulario, abrir su detalle, ejecutar al menos una transición real y confirmar que el estado se actualiza en pantalla — verificado con un proceso de prueba real (creado vía el formulario, no seeder/migración): listado, formulario de creación (selects de modalidad/ccosto/proveedor con datos reales), detalle, transición sin comentario (Borrador → En revisión, actualización reactiva sin reload), transición con comentario requerido (`Rechazar`: diálogo bloqueado hasta llenar el campo, estado pasó a "Rechazada"), listado reflejando el estado actualizado, y enlace "Procesos" visible en el sidebar bajo el grupo "Adquisiciones"

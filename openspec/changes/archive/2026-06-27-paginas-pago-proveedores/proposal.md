## Why

La tarea ad-hoc `api-pago-proveedores` construyó los controladores/Resources/rutas para `casos_pago_proveedor` y `egresos_cgu`, pero deliberadamente sin páginas `.tsx` ("paso explícitamente posterior y separado"). Hoy esas rutas devuelven `Inertia::render()` apuntando a componentes que no existen — cualquier navegador real fallaría. Con la fundación visual ya aplicada (`fundacion-visual-layout`: marca "CAPJ +", paleta azul/Manrope, sidebar tipo riel de íconos), corresponde construir las páginas reales, usando como referencia los tres diseños del usuario (formulario "Registrar Proveedor", listado "Proveedores" con drawer, chrome de "Dashboard") para los patrones de tabla, badges de estado, formulario y navegación.

## What Changes

- Crear 4 páginas Inertia en `resources/js/pages/pago-proveedores/`:
  - `casos/index.tsx`: tabla paginada de casos (sgf_id, proveedor, monto, estado SGF, estado del `Proceso` como badge), siguiendo el patrón de tabla+badges del diseño "Proveedores".
  - `casos/show.tsx`: detalle de un caso — datos del caso, badge de estado actual, botones de transición disponibles (con diálogo de comentario cuando la transición lo requiere), historial de transiciones (timeline, inspirado en la sección "Historial de actividad" del drawer de "Proveedores"), y checklist documental del proceso.
  - `egresos-cgu/index.tsx`: tabla paginada de egresos CGU con sus casos cubiertos.
  - `egresos-cgu/crear.tsx`: formulario para registrar un egreso CGU cubriendo uno o más casos, siguiendo el patrón de formulario en grilla del diseño "Registrar Proveedor".
- Pequeños ajustes a la capa HTTP ya existente para que las páginas tengan los datos que necesitan (brecha encontrada al diseñar las páginas, no cambio de alcance de negocio):
  - `ProcesoResource` agrega `checklist` (items con tipo de documento, tipo de requisito y estado de cumplimiento) — el controlador ya eager-carga `proceso.checklist.items` pero el Resource nunca lo expone.
  - `EgresoCguController::create()` pasa la lista de `casos_pago_proveedor` disponibles (vía `CasoPagoProveedorResource`) para que el formulario pueda elegir cuáles cubre el egreso.
- Regenerar las funciones tipadas de Wayfinder (`resources/js/routes/pago-proveedores/...`) para las rutas nombradas `pago-proveedores.*` ya existentes — ocurre automáticamente al correr Vite, no requiere código nuevo.

**Fuera de alcance (decisión explícita):** filtros/búsqueda en los listados (el backend no los soporta hoy); ocultar en el frontend transiciones para las que el usuario no tiene permiso (el backend ya rechaza con mensaje claro; duplicar ese chequeo en React no aporta y requeriría exponer permisos del usuario al cliente); cualquier lógica de elegibilidad de qué casos pueden incluirse en un egreso (no existe esa regla de negocio en ningún servicio).

## Capabilities

### New Capabilities
- `paginas-pago-proveedores`: páginas React/Inertia para listar/ver casos de pago de proveedores y listar/crear egresos CGU, consumiendo exclusivamente los datos que ya entregan los controladores de `api-pago-proveedores`.

### Modified Capabilities
- `api-pago-proveedores`: `ProcesoResource` expone `checklist`; `EgresoCguController::create()` entrega la lista de casos disponibles para el formulario. No cambia ningún Requirement existente de esa spec (son datos adicionales en una respuesta ya especificada como "incluye el caso, su Proceso..."), pero se documenta como modificación porque toca código ya archivado.

## Impact

- Archivos nuevos: 4 páginas `.tsx` en `resources/js/pages/pago-proveedores/`, posibles componentes compartidos (badge de estado, fila de historial) en `resources/js/components/pago-proveedores/` si se repiten entre páginas.
- Archivos modificados: `app/Http/Resources/PagoProveedores/ProcesoResource.php`, `app/Http/Controllers/PagoProveedores/EgresoCguController.php`.
- Sin migraciones ni cambios de rutas/permisos — los endpoints HTTP ya existen tal cual.
- Requiere `npm run build`/`composer dev` para generar las funciones Wayfinder y ver las páginas; tests Pest existentes de `api-pago-proveedores` no deberían romperse (siguen usando `assertInertia()` con `shouldExist: false` donde aplica, aunque ahora los componentes sí existirán).

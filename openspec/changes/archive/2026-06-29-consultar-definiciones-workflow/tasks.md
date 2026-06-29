## 1. Backend

- [x] 1.1 Crear `App\Http\Resources\Workflow\DefinicionWorkflowResource`: `id`, `codigo`, `nombre`, `descripcion`, `activo`, `estados_count`/`transiciones_count` (`whenCounted`), `estados`/`transiciones` (`whenLoaded`, mapeados a arrays planos).
- [x] 1.2 Crear `App\Http\Controllers\Workflow\DefinicionWorkflowController`: `index()` con `withCount(['estados', 'transiciones'])`, `show()` con `load(['estados', 'transiciones.estadoOrigen', 'transiciones.estadoDestino'])`.
- [x] 1.3 Crear `routes/workflow.php` con `GET workflow/definiciones` y `GET workflow/definiciones/{definicionWorkflow}`, ambas solo bajo middleware `auth` (sin Policy).
- [x] 1.4 Agregar `require __DIR__.'/workflow.php';` en `routes/web.php`.

## 2. Frontend

- [x] 2.1 Crear `resources/js/types/workflow.ts`: tipos `DefinicionWorkflow`, `EstadoWorkflowResumen`, `TransicionWorkflowResumen`.
- [x] 2.2 Crear `resources/js/pages/workflow/definiciones/index.tsx`: tabla con código, nombre, badge activo/inactivo, cantidad de estados/transiciones, fila clickeable hacia el detalle.
- [x] 2.3 Crear `resources/js/pages/workflow/definiciones/show.tsx`: sección "Estados" (lista con badges `es_inicial`/`es_final`) y sección "Transiciones" (tabla: código, nombre, origen → destino, permiso requerido, documentos requeridos, requiere comentario).
- [x] 2.4 Agregar ítem "Definiciones de Workflow" en `resources/js/components/app-sidebar.tsx`.

## 3. Tests y spec

- [x] 3.1 Test Feature: el listado incluye las 3 definiciones sembradas con sus conteos correctos.
- [x] 3.2 Test Feature: el detalle de `pago_proveedores` incluye sus 13 estados y 13 transiciones, con los flags correctos en al menos una transición con `permiso_requerido` y una con `documentos_requeridos`.
- [x] 3.3 Test Feature: un usuario no autenticado es redirigido al login.
- [x] 3.4 `vendor/bin/pint --dirty --format agent`, `npm run lint:check`, `npm run types:check`, `php artisan test --compact`.

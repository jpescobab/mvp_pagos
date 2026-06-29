## 1. Backend

- [x] 1.1 Crear `App\Http\Resources\Sgf\ImportacionSgfResource`: `id`, `fuente`, `iniciado_por` (nombre), `iniciado_en`, `finalizado_en`, `total_filas`, `estado`, `snapshots` (`whenLoaded`, cada uno con `sgf_id`, `hash`, `capturado_en`).
- [x] 1.2 Crear `App\Http\Controllers\Sgf\ImportacionSgfController`: `index()` con `with('iniciadoPor')->latest('iniciado_en')->paginate(20)`, `show()` con `load('snapshots')`.
- [x] 1.3 Crear `routes/sgf.php` con `GET sgf/importaciones` y `GET sgf/importaciones/{importacionSgf}`, ambas solo bajo middleware `auth`.
- [x] 1.4 Agregar `require __DIR__.'/sgf.php';` en `routes/web.php`.

## 2. Frontend

- [x] 2.1 Crear `resources/js/types/sgf.ts`: tipos `ImportacionSgf`, `SnapshotSgfResumen`.
- [x] 2.2 Crear `resources/js/pages/sgf/importaciones/index.tsx`: tabla paginada con fuente, iniciado por, fechas, total de filas, estado, fila clickeable hacia el detalle.
- [x] 2.3 Crear `resources/js/pages/sgf/importaciones/show.tsx`: datos de la corrida + lista de snapshots producidos.
- [x] 2.4 Agregar ítem "Importaciones SGF" bajo "Pago de Proveedores" en `resources/js/components/app-sidebar.tsx`.

## 3. Tests y spec

- [x] 3.1 Test Feature: el listado incluye las importaciones existentes ordenadas de la más reciente a la más antigua, con sus campos correctos.
- [x] 3.2 Test Feature: el detalle de una importación incluye los snapshots que produjo.
- [x] 3.3 Test Feature: un usuario no autenticado es redirigido al login.
- [x] 3.4 `vendor/bin/pint --dirty --format agent`, `npm run lint:check`, `npm run types:check`, `php artisan test --compact`.

## 1. Backend

- [x] 1.1 Agregar `snapshotsSgf(): HasMany` a `CasoPagoProveedor`: `hasMany(SnapshotSgf::class, 'sgf_id', 'sgf_id')->orderByDesc('id')`.
- [x] 1.2 En `CasoPagoProveedorController::show()`, eager-load `snapshotsSgf.importacion.iniciadoPor`.
- [x] 1.3 En `CasoPagoProveedorResource`, agregar `snapshots_sgf` (`whenLoaded`, cada item con id, capturado_en, hash, fuente (de `importacion.fuente`), payload_crudo, payload_normalizado).

## 2. Frontend

- [x] 2.1 Agregar tipo `SnapshotSgf` en `resources/js/types/pago-proveedores.ts`; extender `CasoPagoProveedor` con `snapshots_sgf?`.
- [x] 2.2 En `resources/js/pages/pago-proveedores/casos/show.tsx`, agregar sección "Historial de snapshots SGF": lista con fecha/hash/fuente y botón "Ver detalle"/"Ocultar" que expande un `<pre>` con `payload_crudo`/`payload_normalizado`, mismo patrón que `resources/js/pages/auditoria/index.tsx`.

## 3. Tests y spec

- [x] 3.1 Test Feature: el detalle del caso incluye los snapshots de su `sgf_id` ordenados del más reciente al más antiguo, con más de un snapshot.
- [x] 3.2 `vendor/bin/pint --dirty --format agent`, `npm run lint:check`, `npm run types:check`, `php artisan test --compact`.

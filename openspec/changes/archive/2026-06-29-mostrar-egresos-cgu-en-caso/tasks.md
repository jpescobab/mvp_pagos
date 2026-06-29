## 1. Backend

- [x] 1.1 Agregar `egresoCguItems(): HasMany` a `CasoPagoProveedor` (`hasMany(EgresoCguItem::class)`).
- [x] 1.2 En `CasoPagoProveedorController::show()`, eager-load `egresoCguItems.egreso`.
- [x] 1.3 En `CasoPagoProveedorResource`, agregar `egresos_cgu` (`whenLoaded`, cada item con id del egreso, numero_egreso, fecha, monto del item).

## 2. Frontend

- [x] 2.1 Agregar tipo `EgresoCguAsociado` en `resources/js/types/pago-proveedores.ts`; extender `CasoPagoProveedor` con `egresos_cgu?`.
- [x] 2.2 En `resources/js/pages/pago-proveedores/casos/show.tsx`, agregar sección "Egresos CGU asociados": lista con número/fecha/monto y enlace a `egresosCgu.show(egreso.id)`.

## 3. Tests y spec

- [x] 3.1 Test Feature: el detalle del caso incluye los egresos CGU asociados con su monto correcto, cuando el caso tiene uno o más `egresos_cgu_items`.
- [x] 3.2 `vendor/bin/pint --dirty --format agent`, `npm run lint:check`, `npm run types:check`, `php artisan test --compact`.

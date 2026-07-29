## 1. Modelo y backend

- [x] 1.1 Agregar `licitacionesMercadoPublico(): HasMany` a `app/Models/ProcesoAdquisicion.php`, simétrica con `ordenesCompraMercadoPublico()`.
- [x] 1.2 En `ProcesoAdquisicionController::show`, agregar `ordenesCompraMercadoPublico` y `licitacionesMercadoPublico` al `load()` del proceso.
- [x] 1.3 En `ProcesoAdquisicionResource`, exponer `ordenes_compra_mercado_publico` y `licitaciones_mercado_publico` con `whenLoaded(...)->map(...)`, cada ítem con `id`, `codigo`, `estado_mercado_publico`, `organismo_comprador` y su monto (`monto_total` para OC, `monto_estimado` para licitación). Acceso defensivo a `organismo_comprador` (array).

## 2. Frontend

- [x] 2.1 Tipos en `resources/js/types/adquisiciones.ts`: agregar los shapes de OC y licitación vinculadas y las dos colecciones en `ProcesoAdquisicion`.
- [x] 2.2 En `adquisiciones/procesos/show.tsx`, agregar dos secciones ("Órdenes de compra (Mercado Público)" y "Licitaciones (Mercado Público)") que listan las vinculadas con datos clave + enlace a su detalle (`ordenes_compra_mp.show` / `licitaciones_mp.show`), con vacío explícito cuando no hay.

## 3. Tests

- [x] 3.1 Feature: un proceso con una OC y una licitación vinculadas expone ambas en el payload del detalle (`ProcesoAdquisicionResource`), con sus campos clave; un proceso sin vínculos expone colecciones vacías. Extender `ProcesoAdquisicionServiceTest` o el test de show si existe, o crear uno nuevo en `tests/Feature/Adquisiciones/`.
- [x] 3.2 Unit/relación: afirmar que `ProcesoAdquisicion::licitacionesMercadoPublico` devuelve las licitaciones con su `proceso_adquisicion_id`.

## 4. Validación y cierre

- [x] 4.1 `vendor/bin/pint --dirty --format agent`, `php artisan test` (suite Adquisiciones), `npm run types:check`, `npm run lint:check`.
- [x] 4.2 Regenerar Wayfinder si hiciera falta (no se agregan rutas); `npm run build` sin errores.

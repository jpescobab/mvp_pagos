## Why

El panel "Preparación para Asignar Egreso" del detalle de un caso de pago a proveedor calcula sus 4 criterios en el frontend, en una réplica manual del servicio backend `ListoParaEgresoResolver` que diverge de él para el criterio "Checklist documental": exige al menos un ítem obligatorio para considerar el checklist completo, mientras que el backend no tiene ese resguardo. Como resultado, cualquier caso cuyo checklist documental resuelva a cero ítems obligatorios (situación real y correcta para el tipo de proceso "Remesa", ya configurado así en la matriz de requisitos documentales) queda atascado mostrando "Sin checklist generado" y oculta el acceso directo para crear un Egreso CGU, aunque el caso ya esté listo.

## What Changes

- Se introduce `PreparacionEgresoPresenter` como única fuente de los 4 criterios del panel (Tipo de proceso, Traspaso CGU, Checklist documental, Proveedor identificado), eliminando la réplica manual del frontend.
- El criterio "Checklist documental" distingue explícitamente tres estados: checklist nunca resuelto, checklist resuelto sin ítems obligatorios (ahora se considera cumplido), y checklist resuelto con ítems obligatorios (comportamiento sin cambios).
- `ListoParaEgresoResolver` pasa a delegar en el nuevo Presenter en vez de mantener su propio cálculo paralelo.
- El backend (`CasoPagoProveedorResource`, vía un wither opt-in) envía los 4 criterios ya resueltos; el frontend (`PreparacionEgresoCard`) deja de recalcularlos y solo los pinta.

## Capabilities

### New Capabilities

Ninguna — este cambio no introduce una capability nueva, corrige el comportamiento de una existente.

### Modified Capabilities

- `paginas-pago-proveedores`: el Requirement "Página de detalle de un caso con acciones de workflow" incorpora un escenario adicional que documenta que el criterio "Checklist documental" se considera cumplido cuando el checklist resuelto no tiene ítems obligatorios.

## Impact

- `app/Services/PagoProveedores/PreparacionEgresoPresenter.php` (nuevo).
- `app/Services/PagoProveedores/ListoParaEgresoResolver.php`: refactor para delegar en el Presenter (sin cambio de comportamiento en los casos ya cubiertos).
- `app/Http/Resources/PagoProveedores/CasoPagoProveedorResource.php` y `app/Http/Controllers/PagoProveedores/CasoPagoProveedorController.php`: nueva clave `preparacion_egreso`, opt-in vía wither, solo en el detalle del caso (no en el listado paginado, para evitar N+1).
- `resources/js/components/pago-proveedores/preparacion-egreso-card.tsx` y `resources/js/types/pago-proveedores.ts`: el componente deja de recalcular los criterios y solo pinta lo que llega del backend.
- Tests nuevos: `PreparacionEgresoPresenterTest`, `ListoParaEgresoResolverTest`; extensión de `AccesoDirectoCrearEgresoDesdeDetalleCasoTest` con un caso end-to-end del tipo de proceso sin documentos obligatorios.
- Sin impacto en `ResolutorChecklistDocumentalProceso`, `RequisitoDocumental`, seeders de requisitos documentales, `ImportacionSgfResource`, ni `CasosElegiblesEgresoCguService` (ya usan `ListoParaEgresoResolver` correctamente y se benefician del fix sin cambios propios).

## Why

En el detalle de un caso de pago a proveedor, la sección Financiero → "Registro contable CGU (Traspaso)" siempre muestra el formulario de corrección manual a cualquier usuario con el permiso `pago_proveedores.registrar_cgu`, incluso cuando el `TipoProcesoPago` del caso nunca tendrá un Traspaso que registrar — hoy, el caso real de tipo "Remesa": no llega desde SGF ni existe ningún mecanismo para generarlo. Como el criterio "Traspaso (CGU)" del panel de preparación para Asignar Egreso exige un registro para considerarse cumplido, el formulario habilitado invita a intentar satisfacerlo con valores inventados: el caso `sgf_id=779` (Remesa, sin traspaso SGF) tiene hoy dos registros de corrección con el mismo monto y la misma fecha, cargados por dos usuarios distintos, cada uno intentando sin éxito completar un dato que esa Remesa nunca puede tener.

Es el mismo patrón de bug corregido en la tarea inmediatamente anterior (`fix-checklist-completo-sin-obligatorios`) para el criterio "Checklist documental" del mismo panel: si solo se bloquea el formulario sin también ajustar el criterio "Traspaso (CGU)", el caso quedaría varado para siempre en "incompleto". `TipoProcesoPago` ya es 100% administrable desde Maestros sin nada hardcodeado en código, así que la solución debe ser igual de genérica.

## What Changes

- `TipoProcesoPago` incorpora un campo administrable `requiere_traspaso_cgu` (boolean, `true` por defecto), editable desde el mismo CRUD de Maestros que ya administra código/nombre/activo. El tipo real `REMESA` se corrige a `false` como dato de arranque de la migración.
- `CasoPagoProveedor` gana un método derivado `requiereTraspasoCgu(): bool`, única fuente de esta pregunta (fallback `true` cuando el caso aún no tiene tipo de proceso clasificado).
- El detalle de un caso oculta el formulario de registro/corrección de Traspaso (CGU) cuando `requiereTraspasoCgu()` es `false`, mostrando un mensaje explicativo; los registros ya existentes se siguen mostrando como referencia (no se ocultan).
- `CasoPagoProveedorPolicy::registrarCgu()` rechaza la acción a nivel de autorización — no solo de UI — cuando el tipo de proceso no requiere traspaso, aunque el usuario tenga el permiso `pago_proveedores.registrar_cgu` (defensa en profundidad).
- `PreparacionEgresoPresenter` (criterio `traspaso_cgu`) considera el criterio cumplido automáticamente para casos cuyo tipo de proceso no requiere traspaso — mismo mecanismo aplicado al criterio de checklist documental en el cambio anterior; sin este ajuste, un caso de un tipo sin traspaso quedaría atascado para siempre en el panel de preparación y en el flag `listo` del formulario de creación de Egreso CGU.

## Capabilities

### New Capabilities

(ninguna)

### Modified Capabilities

- `paginas-pago-proveedores`: el detalle de un caso oculta el formulario de Traspaso (CGU) y el criterio de preparación para Asignar Egreso se cumple automáticamente cuando el tipo de proceso del caso no requiere traspaso; los registros existentes se conservan.
- `administracion-requisitos-documentales-pago-proveedores`: el catálogo de `TipoProcesoPago` administrado desde Maestros incorpora el campo `requiere_traspaso_cgu`.

## Impact

- Backend: migración nueva sobre `tipos_proceso_pago`; `app/Models/{TipoProcesoPago,CasoPagoProveedor}.php`; `app/Services/PagoProveedores/PreparacionEgresoPresenter.php`; `app/Policies/CasoPagoProveedorPolicy.php`; `app/Http/Resources/{PagoProveedores/ProcesoResource,Maestros/TipoProcesoPagoResource}.php`; `app/Http/Requests/Maestros/{Store,Update}TipoProcesoPagoRequest.php`.
- Frontend: `resources/js/pages/pago-proveedores/casos/show.tsx`; `resources/js/pages/maestros/tipos-proceso-pago/{create,edit,show}.tsx`; `resources/js/types/{pago-proveedores,maestros}.ts`.
- Sin impacto en `RegistroContableCguController`, `RegistrarRegistroContableCguRequest`, `ResolutorChecklistDocumentalProceso`, `RequisitoDocumental`, Wayfinder, `ListoParaEgresoResolver` ni `CasosElegiblesEgresoCguService` (estos dos últimos se benefician automáticamente sin cambio propio).
- Tests: `tests/Feature/Maestros/TipoProcesoPagoCrudTest.php`; `tests/Feature/PagoProveedores/{PreparacionEgresoPresenterTest,ListoParaEgresoResolverTest,RegistrarEvidenciaCguYPagoBancarioTest,AccesoDirectoCrearEgresoDesdeDetalleCasoTest}.php`.

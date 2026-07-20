## Context

`RegistroContableCgu` es el mecanismo de corrección manual del Traspaso (CGU) de un caso de pago a proveedor; convive con `sgf_numero_traspaso` (importado desde SGF, gobernado por harness como evidencia, no como workflow interno). El formulario de corrección en el detalle del caso (`resources/js/pages/pago-proveedores/casos/show.tsx`) hoy solo se gatea por el permiso `pago_proveedores.registrar_cgu` — no contempla que algunos `TipoProcesoPago` (hoy: Remesa) nunca generan un Traspaso real. El criterio homónimo del panel "Preparación para Asignar Egreso" (`PreparacionEgresoPresenter::traspasoCgu()`) exige un registro (manual o SGF) para marcarse cumplido, sin excepción. El resultado observado en producción: dos usuarios distintos registraron valores inventados en el caso Remesa `sgf_id=779` intentando satisfacer un criterio irresoluble.

Este mismo patrón (criterio de preparación irresoluble para un tipo de proceso) ya se corrigió una vez en `fix-checklist-completo-sin-obligatorios` para el criterio "Checklist documental", centralizando el cálculo en `PreparacionEgresoPresenter`. Este cambio extiende el mismo Presenter con el criterio hermano.

## Goals / Non-Goals

**Goals:**
- Un `TipoProcesoPago` administrable (sin código hardcodeado) puede declarar que no requiere Traspaso (CGU).
- El detalle de un caso de ese tipo oculta el formulario de registro/corrección, sin ocultar el historial de registros ya existentes.
- El backend rechaza por autorización cualquier intento de registrar un Traspaso para ese tipo, incluso con el permiso adecuado (defensa en profundidad — el frontend no es la única barrera).
- El criterio `traspaso_cgu` del panel de preparación se cumple automáticamente para esos casos, evitando que queden varados en "incompleto" igual que ya se corrigió para el checklist documental.

**Non-Goals:**
- No se modifica `ResolutorChecklistDocumentalProceso`, `RequisitoDocumental` ni ningún seeder de requisitos documentales (mecanismo independiente).
- No se introduce ninguna condición hardcodeada para el código `REMESA` en `app/` — solo como dato de arranque en la migración.
- No se añade ningún indicador nuevo al índice/listado de casos (el indicador "Listo para revisar" de esa pantalla no depende de Traspaso/CGU).

## Decisions

**Campo booleano administrable en `TipoProcesoPago`, no una tabla/enum separado.** `requiere_traspaso_cgu` sigue exactamente el mismo patrón que el campo `activo` ya existente (migración con default, `$fillable`/`casts()`, Form Requests, Resource, dos páginas React) — no se introduce ningún concepto nuevo de administración. Alternativa descartada: modelar "requisitos de traspaso" dentro del mecanismo genérico de `RequisitoDocumental` (que ya resuelve obligatoriedad documental por tipo de proceso) — se descarta porque Traspaso (CGU) no es un documento del checklist, es un registro contable con su propio modelo (`RegistroContableCgu`) y su propia Policy; forzarlo dentro de `RequisitoDocumental` mezclaría dos conceptos de dominio distintos.

**Un único método derivado `CasoPagoProveedor::requiereTraspasoCgu()`, no la expresión repetida en cada consumidor.** Tanto `PreparacionEgresoPresenter::traspasoCgu()` como `CasoPagoProveedorPolicy::registrarCgu()` necesitan la misma pregunta. Centralizarla en el modelo (mismo idiom ya usado por `cfinancieroId()` en ese archivo) evita que la lógica de fallback (`?? true` cuando el caso no tiene tipo clasificado) diverja entre los dos consumidores — exactamente el tipo de divergencia que causó el bug original del checklist documental (frontend vs. backend calculando la misma pregunta con reglas distintas).

**Bloqueo en la Policy con `bool` plano, no solo ocultando el formulario en React.** El frontend puede quedar con datos obsoletos (un admin desactiva `requiere_traspaso_cgu` mientras alguien tiene el formulario abierto); sin un guard en el backend, ese POST igual crearía un `RegistroContableCgu` que nunca debió existir. Se evaluó `Response::deny()` para un mensaje explicativo en el 403, pero se descartó: `Gate::after()` en `AppServiceProvider::configureAuthorization()` — el hook que audita `acceso_denegado` para las 17+ Policies de toda la app — está tipado a `?bool $result`; un `Response` ahí produce un `TypeError` (500) en cada intento denegado (confirmado por el test de este mismo cambio). Ampliar ese hook compartido por un mensaje de UX en un solo endpoint no valía el riesgo; `registrarCgu()` devuelve `bool` plano, igual que el resto de las Policies del repo.

**Los registros de Traspaso ya existentes nunca se ocultan.** `RegistroContableCgu` es append-only (sin `update()`/`destroy()` en todo el codebase); ocultar la lista para un caso que ya tiene registros (como `sgf_id=779`, con dos) borraría de la vista un dato real de auditoría aunque siga en la base de datos. Solo se oculta el formulario de captura; el historial se sigue mostrando siempre.

**Fallback `?? true` cuando el caso no tiene tipo de proceso clasificado.** Preserva el comportamiento histórico — un caso "sin clasificar" sigue exigiendo Traspaso hasta que alguien lo clasifique explícitamente como un tipo que no lo requiere. Evita que la ausencia de clasificación se lea accidentalmente como "no requiere".

## Risks / Trade-offs

[Un admin cambia `requiere_traspaso_cgu` mientras un usuario tiene el formulario de un caso de ese tipo abierto en el navegador] → Mitigación: la Policy (fuente de verdad real) rechaza el POST en el momento del submit sin importar qué mostraba la página stale; el único costo es un modal de error genérico de Inertia en una carrera poco frecuente, sin pérdida de integridad de datos.

[Migración con dato de arranque (`UPDATE ... WHERE codigo = 'REMESA'`) queda acoplada a un valor de producción específico] → Mitigación: es una corrección de datos de una sola vez sobre un registro ya confirmado contra la base de datos real, vía query builder (no el modelo Eloquent) para no acoplarse a la forma futura del modelo; mismo patrón ya usado en migraciones previas de este repo (seed de jurisdicciones/cfinancieros). Si el `codigo` no existe en un entorno dado (tests, otro ambiente), el `UPDATE` afecta cero filas sin error.

## Migration Plan

1. Migración agrega la columna con default `true` (no rompe ningún tipo existente) y corrige `REMESA` a `false` en el mismo `up()`.
2. Deploy estándar (`php artisan migrate`); sin pasos de rollback especiales — `down()` elimina la columna.
3. Sin cambios de configuración ni variables de entorno.

## Open Questions

Ninguna — diseño validado contra el código real y la base de datos de producción antes de escribir este documento.

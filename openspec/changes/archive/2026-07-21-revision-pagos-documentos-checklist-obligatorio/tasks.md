## 1. Clasificación documental en el dominio (backend Service)

- [x] 1.1 En `app/Services/PagoProveedores/ValidacionDocumentoInstanciaService.php`, agregar un helper privado `obligatoriosDelProceso(Proceso $proceso): array` (o método equivalente) que lea el `ChecklistDocumentalProceso` del proceso y devuelva: el conjunto de `tipo_documento_id` obligatorios (`ChecklistDocumentalProcesoItem` con `tipo_requisito = 'obligatorio'`) y los ítems obligatorios sin `documento_id` (faltantes, con su `tipo_documento`). Degradar con gracia si el proceso no tiene checklist (sin obligatorios → todo opcional).
- [x] 1.2 Reescribir `ValidacionDocumentoInstanciaService::documentosDelCaso()` para devolver una estructura clasificada `{ obligatorios: Collection<Documento>, opcionales: Collection<Documento>, faltantes: array<{tipo_documento, tipo_documento_id}> }` en vez de una `Collection<Documento>` plana. Obligatorio = documento vinculado cuyo `tipo_documento_id` está en el conjunto obligatorio; opcional = el resto de documentos vinculados activos; faltante = ítem obligatorio del checklist sin documento vinculado.
- [x] 1.3 Actualizar `ValidacionDocumentoInstanciaService::todosAprobados(CasoPagoProveedor $caso, InstanciaRevision $instancia)` para evaluar el gating SOLO sobre obligatorios: `true` únicamente si no hay ningún obligatorio faltante Y todo documento obligatorio presente tiene `estadoVigente(instancia) = 'valido'`. Conservar el comportamiento defensivo de retornar `false` cuando no hay obligatorios definidos.

## 2. Gating y payload de la revisión (backend Presenter/Service)

- [x] 2.1 En `app/Services/PagoProveedores/RevisionEgresoService.php`, ajustar `pagoListoParaAprobar()` para que la parte documental use el nuevo `todosAprobados` basado en obligatorios (la verificación de totales queda igual). Verificar que ningún otro consumidor de `pagoListoParaAprobar`/`todosAprobados` quede con la semántica vieja.
- [x] 2.2 En `app/Services/PagoProveedores/RevisionEgresoPresenter.php`, adaptar `pago()` para consumir la estructura clasificada: cada documento entregado en `documentos` gana `clasificacion: 'obligatorio' | 'opcional'`; agregar `faltantes: array<{tipo_documento, tipo_documento_id, clasificacion:'faltante'}>` (sin `id`); agregar contadores `obligatorios_ok` y `obligatorios_total` para el indicador de avance. Obligatorios (y faltantes) antes que opcionales en el orden entregado.
- [x] 2.3 Confirmar que `RevisionPagosController` (index/show) permanece liviano: sigue delegando en el Presenter, sin lógica de cruce checklist↔documentos.

## 3. Render de la pantalla (frontend)

- [x] 3.1 En `resources/js/pages/pago-proveedores/revision/index.tsx`, actualizar los tipos (`Documento` con `clasificacion`; nuevo tipo para faltantes; `Pago` con `faltantes`, `obligatorios_ok`, `obligatorios_total`).
- [x] 3.2 Seccionar la columna "Documentos del pago": primero obligatorios (con las filas faltantes como placeholders no accionables que identifican el tipo esperado), luego una sección/etiqueta "Opcionales" con el resto. Las filas faltantes no abren visor ni ofrecen aprobar/rechazar.
- [x] 3.3 Calcular la barra "docs OK" y el `pct` a partir de `obligatorios_ok`/`obligatorios_total` que entrega el backend (no recalcular sobre todos los documentos en el cliente). Los documentos opcionales aprobables/rechazables siguen operando, pero no afectan la barra ni habilitan/inhabilitan Aprobar.
- [x] 3.4 Regenerar Wayfinder si cambió alguna firma de ruta usada por la pantalla (`php artisan wayfinder:generate --with-form`); no hardcodear URLs.

## 4. Tests

- [x] 4.1 Test Feature: pago con obligatorio presente aprobado en la instancia activa y sin faltantes → `pagoListoParaAprobar`/`todosAprobados` = true (con totales verificados) y el payload clasifica el documento como obligatorio.
- [x] 4.2 Test Feature: pago con un ítem obligatorio del checklist sin documento vinculado → aparece en `faltantes` y la aprobación queda bloqueada mientras falte.
- [x] 4.3 Test Feature: pago con obligatorios aprobados pero un documento opcional pendiente/rechazado → sigue siendo aprobable (opcional no bloquea) y el opcional se clasifica en `opcionales`.
- [x] 4.4 Test Feature: mismo escenario evaluado en instancia Finanzas y en instancia Zonal → el gating por obligatorios es independiente por instancia (un obligatorio aprobado por Finanzas figura pendiente para Zonal).
- [x] 4.5 Test Feature: proceso sin checklist generado → `documentosDelCaso` degrada (todo opcional, sin faltantes) y el pago no se marca listo por documentos.

## 5. Validación y cierre

- [x] 5.1 `vendor/bin/pint --dirty --format agent` sobre los PHP tocados.
- [x] 5.2 `composer test` (incluye lint:check + types:check + Pest) y `npm run types:check` + `npm run lint:check` para el frontend.
- [x] 5.3 Revisar los controllers tocados contra la regla de controladores livianos antes de dar por cerrado; verificar en la pantalla real (Finanzas y Zonal) con el caso importado que obligatorios/opcionales/faltantes se muestran y que el gating responde como se especificó.

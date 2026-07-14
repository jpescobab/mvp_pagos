## 1. Backend: exponer tipo_documento_id en el checklist

- [x] 1.1 En `ProcesoResource::toArray()` (`app/Http/Resources/PagoProveedores/ProcesoResource.php:30-35`), agregar `'tipo_documento_id' => $item->tipo_documento_id` al mapeo de cada ítem del checklist
- [x] 1.2 Test: el detalle de un caso incluye `tipo_documento_id` en cada ítem del checklist devuelto

## 2. Frontend: tipos y acceso directo de subida en el checklist

- [x] 2.1 Agregar `tipo_documento_id: number | null` a `ChecklistItem` en `resources/js/types/pago-proveedores.ts`
- [x] 2.2 Generalizar `subirDocumento()` en `show.tsx` para aceptar `(tipoDocumentoId: string, archivo: File)` explícitos, usado tanto por el formulario principal de la sección "Documentos" como por el nuevo atajo del checklist
- [x] 2.3 En cada ítem del checklist con `estado_cumplimiento === 'pendiente'` y `tipo_documento_id !== null`, renderizar (solo si `puedeGestionarDocumentos`) un `<input type="file">` inline que dispare la subida inmediatamente al elegir el archivo, usando el `tipo_documento_id` de ese ítem
- [x] 2.4 Mostrar el estado de carga (`subiendoDocumento`) y cualquier error (`errorDocumento`) también en el contexto del checklist, reutilizando el estado ya existente

## 3. Frontend: quitar contenido no operativo

- [x] 3.1 Eliminar la sección "Historial de snapshots SGF" completa (`show.tsx`, listado + `<pre>{JSON.stringify(...)}</pre>` del payload crudo/normalizado, y el estado `snapshotsExpandidos`/`alternarSnapshotExpandido` que ya no se usa)
- [x] 3.2 Reubicar el botón "Verificar en SGF" (y el mensaje `verificacionSgf` de resultado) junto al encabezado del caso, cerca del `EstadoBadge`
- [x] 3.3 Eliminar el `<span>{caso.sgf_status ?? '—'}</span>` junto al `EstadoBadge` en la cabecera

## 4. Tests y verificación

- [x] 4.1 Test Inertia: subir un documento desde el atajo de un ítem pendiente del checklist crea el `Documento`/`VersionDocumento`/`VinculoDocumento` con el `tipo_documento_id` correcto (reutilizando el endpoint existente `procesos.documentos.store`)
- [x] 4.2 Verificado que `tests/Feature/PagoProveedores/MostrarHistorialSnapshotsSgfTest.php` sigue pasando sin cambios (solo verifica el prop `caso.snapshots_sgf`, no la UI renderizada)
- [x] 4.3 Correr `composer test` (lint:check, types:check, php artisan test) y `vendor/bin/pint --dirty --format agent` — 543 tests, 539 passed, 4 skipped preexistentes, Pint y PHPStan limpios
- [x] 4.4 `npm run build` + `npm run types:check` — ambos limpios
- [x] 4.5 Verificado en el navegador con un usuario `administrativo_finanzas` real (`mmardoneso@pjud.cl`, caso 58/sgf_id 753): cada ítem pendiente del checklist (Factura, Acta de Recepción, Certificado de Vigencia, Resolución de Pago, Comprobante de Pago, Orden de Compra, Contrato) muestra su propio input de archivo inline; "Verificar en SGF" aparece correctamente reubicado junto al badge de estado en la cabecera; no aparece texto de `sgf_status` ni la sección "Historial de snapshots SGF". La subida real de archivo no se pudo simular con la herramienta de navegador (sin acción de "seleccionar archivo"), pero el flujo POST idéntico ya está cubierto end-to-end por el test de la tarea 4.1

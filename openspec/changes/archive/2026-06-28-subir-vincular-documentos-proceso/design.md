## Context

El modelo documental (tarea 06, `documentos-expediente-variable`) está completo: `Documento`, `VersionDocumento`, `VinculoDocumento` (polimórfico `vinculable`), `ValidacionDocumento`, y `ChecklistDocumentalProceso`/`Items` ya se resuelven automáticamente para Adquisiciones (cambio anterior). Lo único que falta es el punto de entrada HTTP para que un usuario realmente suba un archivo y lo vincule a un proceso.

`Proceso` (el modelo raíz de workflow, no `CasoPagoProveedor` ni `ProcesoAdquisicion`) ya es la entidad polimórfica natural: tiene `vinculosDocumento(): MorphMany` y `checklist(): HasOne`. Tanto `pago-proveedores/casos/show.tsx` como `adquisiciones/procesos/show.tsx` ya renderizan `proceso.checklist` desde el mismo `ProcesoResource` compartido.

## Goals / Non-Goals

**Goals:**
- Un único conjunto de rutas/controlador, vinculado a `Proceso` directamente (no duplicado por módulo), reutilizable por cualquier módulo funcional futuro.
- Subir, listar, descargar y desvincular documentos de un proceso.
- Mantener el archivo fuera del disco público (documentos institucionales, no exponer URLs directas).

**Non-Goals:**
- No se implementa el flujo de validación/rechazo (`validaciones_documento`) — un documento subido queda con `estadoVigente() === 'pendiente'` hasta que exista esa feature.
- No se sincroniza automáticamente `checklist_documental_proceso_item.estado_cumplimiento` con los documentos subidos — el checklist sigue siendo informativo, no se "tilda" solo. Se documenta como limitación conocida.
- No se permite subir nuevas versiones de un documento existente (`numero_version > 1`) en este alcance — cada subida crea un `Documento` nuevo. Versionar un documento ya existente queda para una iteración futura.
- No se modifica el workflow interno de ningún módulo.

## Decisions

**D1 — Rutas y controlador genéricos por `Proceso`, no por módulo.**
`routes/documentos.php`, middleware `auth`, rutas:
- `POST /procesos/{proceso}/documentos` → subir + vincular
- `GET /procesos/{proceso}/documentos/{documento}/descargar` → descarga protegida
- `DELETE /procesos/{proceso}/documentos/{vinculo}` → desvincular (vinculo es el `VinculoDocumento`)
Esto evita duplicar el controlador en `Adquisiciones/` y `PagoProveedores/`, y es coherente con que `documentos-expediente-variable` es infraestructura core, no de un módulo funcional. Alternativa descartada: un controlador por módulo (como `VinculoAdquisicionCasoPagoProveedorController`) — hubiera duplicado lógica idéntica dos veces sin ninguna diferencia real entre módulos.

**D2 — `ProcesoResource` necesita exponer `id`.**
Hoy `ProcesoResource` no incluye el `id` del `Proceso`; el frontend solo conoce `caso.id`/`proceso.id` (de `CasoPagoProveedor`/`ProcesoAdquisicion`), no el `id` del `Proceso` interno. Se agrega `'id' => $this->id` al `ProcesoResource` para que el frontend pueda construir las rutas de documentos.

**D3 — Disco de almacenamiento: `local` (privado), con `Storage::disk('local')->putFile()`.**
El disco `local` por defecto ya existe en `config/filesystems.php` y no es accesible públicamente vía URL — coherente con que estos son documentos institucionales sensibles. No se crea un disco nuevo. La descarga pasa siempre por el controlador (`Storage::disk('local')->download()`), nunca por una URL directa al archivo.

**D4 — Servicio `App\Services\Documentos\GestorDocumentoProceso`.**
Encapsula: `subirYVincular(Proceso $proceso, UploadedFile $archivo, TipoDocumento $tipoDocumento, User $usuario): VinculoDocumento` (crea `Documento` + `VersionDocumento` número 1 + `VinculoDocumento` activo, en una transacción) y `desvincular(VinculoDocumento $vinculo): void` (marca `activo = false`). Sigue el mismo patrón de service-layer ya usado por `ResolutorChecklistDocumentalProceso`.

**D5 — Permiso core `documentos.gestionar`, agregado en `RolesAndPermissionsSeeder`.**
No es un permiso de módulo funcional (como `pago_proveedores.*` o `adquisiciones.*`); es infraestructura core, por lo que se agrega junto a `usuarios.administrar` etc. en el seeder core, asignado a `superadmin` y `admin`.

**D6 — Validación de archivo: tipos MIME permitidos y tamaño máximo.**
Form Request con `file` (`mimes:pdf,jpg,jpeg,png|max:10240` — 10MB) y `tipo_documento_id` (`exists:tipos_documento,id,activo,1`). Sin antivirus ni escaneo de contenido en este alcance (fuera de alcance, no mencionado en ninguna spec existente).

## Risks / Trade-offs

- [Riesgo] El checklist no refleja automáticamente que un documento fue subido (Non-Goal) → Mitigación: la UI muestra ambas secciones (checklist exigido + documentos subidos) por separado; el usuario coteja visualmente. Documentado como limitación conocida a resolver en una iteración futura (sincronización checklist↔documentos).
- [Riesgo] Guardar archivos en disco local no escala a multi-servidor → Mitigación: ya es la convención de `config/filesystems.php` en este proyecto (sin S3 configurado); cambiar de disco es una decisión de infraestructura ortogonal a este cambio, no bloqueante para destrabar la funcionalidad.
- [Riesgo] Sin versionado real (Non-Goal), subir "una corrección" del mismo documento crea un `Documento` nuevo en vez de una nueva versión del existente → Mitigación: aceptable para destrabar el caso de uso básico; versionar es una mejora incremental clara para una iteración futura, no un bloqueante de seguridad o integridad.

## Migration Plan

1. Agregar `documentos.gestionar` a `RolesAndPermissionsSeeder`.
2. Crear `GestorDocumentoProceso`.
3. Crear `DocumentoProcesoController` (store/descargar/destroy) + Form Request de subida.
4. Agregar `routes/documentos.php`, registrarlo en `routes/web.php`.
5. Agregar `'id'` y `'documentos'` a `ProcesoResource`.
6. UI: sección "Documentos" en ambos `show.tsx`.
7. Tests: subir, listar, descargar (autorizado/no autorizado), desvincular sin perder historial.

Sin rollback especial: nuevo código aditivo, sin cambios de esquema.

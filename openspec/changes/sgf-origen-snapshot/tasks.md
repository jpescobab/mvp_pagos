## 1. Migraciones

- [ ] 1.1 Crear migración `importaciones_sgf` (fuente string, iniciado_por FK nullable, iniciado_en, finalizado_en nullable, total_filas unsignedInteger default 0, estado string default 'en_progreso')
- [ ] 1.2 Crear migración `snapshots_sgf` (importacion_sgf_id FK, sgf_id string, payload_crudo json, payload_normalizado json, hash string, capturado_en; unique(importacion_sgf_id, sgf_id); index sgf_id)
- [ ] 1.3 Crear migración `snapshots_sgf_documentos` (snapshot_sgf_id FK, documento_id FK, created_at only)

## 2. Modelos Eloquent

- [ ] 2.1 Crear `ImportacionSgf` (hasMany snapshots; `$table` explícito por pluralización español/inglés)
- [ ] 2.2 Crear `SnapshotSgf` (belongsTo importación, hasMany snapshotDocumentos; `$table` explícito)
- [ ] 2.3 Crear `SnapshotSgfDocumento` (belongsTo snapshot, belongsTo documento; `$table` explícito)

## 3. Servicio de importación

- [ ] 3.1 Crear `App\Services\Sgf\NormalizadorSgf`: normaliza una fila SGF cruda (parseo de monto en formato chileno, trim de strings) a `payload_normalizado`
- [ ] 3.2 Crear `App\Services\Sgf\ImportadorSgf::importarFila(ImportacionSgf $importacion, array $filaSgf): SnapshotSgf` — calcula hash del payload crudo, normaliza, crea el snapshot; si la fila trae documentos, los crea como `Documento`/`VersionDocumento` y los vincula vía `SnapshotSgfDocumento`
- [ ] 3.3 Crear `App\Services\Sgf\ImportadorSgf::iniciarImportacion(string $fuente, ?User $user = null): ImportacionSgf` y `finalizarImportacion(ImportacionSgf $importacion): ImportacionSgf`

## 4. Tests

- [ ] 4.1 Test feature: importar una fila SGF crea snapshot con payload crudo, normalizado y hash correctos
- [ ] 4.2 Test feature: reimportar el mismo `sgf_id` crea un snapshot nuevo sin alterar el anterior
- [ ] 4.3 Test feature: importar una fila con documento crea `Documento`/`VersionDocumento` y los vincula via `SnapshotSgfDocumento`
- [ ] 4.4 Test feature: `iniciarImportacion`/`finalizarImportacion` registran fuente, usuario, momentos y total de filas

## 5. Validación

- [ ] 5.1 `composer lint:check`
- [ ] 5.2 `composer types:check`
- [ ] 5.3 `php artisan test --compact`

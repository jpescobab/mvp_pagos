## 1. Servicio de reparación (backend)

- [ ] 1.1 Crear `app/Services/Sgf/ReparadorNombreArchivoSgf.php` con `reparar(string $nombre): string` según la heurística del design (mojibake solo si el roundtrip Latin-1→UTF-8 es válido y distinto).
- [ ] 1.2 Test `tests/Feature/Sgf/ReparadorNombreArchivoSgfTest.php`: `N°`, `é`, `á`, caso `Ñ` reversible, nombre legítimo con tilde intacto, nombre ASCII intacto, cadena vacía.

## 2. Ingesta (backend)

- [ ] 2.1 En `ConectorSgfPlaywrightService::vincularDocumento()` reparar nombre y ruta antes de registrar; mover el archivo físico al nombre reparado si existe con el corrupto y el destino no existe.
- [ ] 2.2 Ajustar `yaVinculado` para comparar contra la ruta cruda y la reparada (idempotencia).
- [ ] 2.3 Tests en `tests/Feature/Sgf/`: documento con mojibake se registra reparado; payload crudo del snapshot intacto; reimportación no duplica (ruta corrupta previa vs. reparada y viceversa).

## 3. Scraper

- [ ] 3.1 Agregar en `services/sgf-playwright/sgf-scraper.js` el helper equivalente de reparación y usarlo en `descargarDocumentosDeFila()` antes de `writeFile`/`ruta_archivo`.
- [ ] 3.2 Verificar el helper JS con un script de prueba simple contra los mismos casos del test PHP (sin credenciales ni corrida real contra SGF).

## 4. Reparación de datos existentes

- [ ] 4.1 Crear comando Artisan (p. ej. `sgf:reparar-nombres-documentos`) con `--dry-run`, transacción por documento, sin sobrescribir destino y auditoría con `AuditLogger`; la lógica vive en un Service, el comando solo orquesta.
- [ ] 4.2 Tests: dry-run no cambia nada; la reparación actualiza `titulo`/`nombre_archivo`/`ruta_archivo` y renombra el archivo sin tocar vínculos ni hash; conflicto de destino se omite; segunda ejecución no hace nada.
- [ ] 4.3 Ejecutar `--dry-run` y luego el comando real sobre la BD de desarrollo (6 documentos: ids 223, 233, 241, 249, 251, 252) y verificar el resultado y el reporte de irreversibles.

## 5. Validación final

- [ ] 5.1 `vendor/bin/pint --dirty --format agent` sobre los PHP tocados.
- [ ] 5.2 `composer types:check` (PHPStan) y `php artisan test --compact` sobre `tests/Feature/Sgf` y la suite relacionada.
- [ ] 5.3 Documentar en `services/sgf-playwright/CALIBRACION.md` que la confirmación del origen del mojibake (SGF vs lectura del scraper) requiere una corrida real ejecutada por el usuario.

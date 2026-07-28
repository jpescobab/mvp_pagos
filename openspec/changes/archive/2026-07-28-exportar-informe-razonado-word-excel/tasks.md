# Tasks

## 1. Dependencias
- [x] 1.1 `composer require phpoffice/phpword phpoffice/phpspreadsheet` (ambas con auto-discovery de sus proveedores no requerido; son librerías, no paquetes Laravel).

## 2. ExportadorInformeRazonadoService
- [x] 2.1 Ampliar `FORMATOS_SOPORTADOS` a `['html', 'pdf', 'docx', 'xlsx']`.
- [x] 2.2 Ampliar el `match($formato)` de `exportar()` para delegar a `exportarDocx()` y `exportarXlsx()`, conservando el `InvalidArgumentException` en el `default`.
- [x] 2.3 Implementar el helper privado `guardarDesdeArchivoTemporal(string $tmp, string $ruta): void` (tempnam → writer->save → Storage put → unlink, con `try/finally` para limpiar el temp).
- [x] 2.4 Implementar `exportarDocx()`: `PhpWord` + sección + `Html::addHtml($section, $this->render($ejecucion), false, false)`, writer `Word2007`, guardar en `informes-razonados/{id}/informe-{id}-{timestamp}.docx`.
- [x] 2.5 Implementar `exportarXlsx()`: `Spreadsheet` con metadata + hoja de métricas (Código/Etiqueta/Valor/Unidad) y hoja de excepciones (Código/Severidad/Descripción) desde las relaciones del modelo, writer `Xlsx`, guardar en `informes-razonados/{id}/informe-{id}-{timestamp}.xlsx`.

## 3. Descarga con content-type correcto
- [x] 3.1 Ampliar el `match($exportacion->formato)` de `ExportacionInformeRazonadoController::descargar()` con `docx` → `application/vnd.openxmlformats-officedocument.wordprocessingml.document` y `xlsx` → `application/vnd.openxmlformats-officedocument.spreadsheetml.sheet`.

## 4. Tests (`tests/Feature/InformesRazonados/`)
- [x] 4.1 Exportar `docx` con permiso: genera archivo, registra evidencia con formato `docx`, `Proceso` sin cambios, el binario empieza con la firma ZIP `PK` (OOXML válido).
- [x] 4.2 Exportar `xlsx` con permiso: genera archivo, registra evidencia con formato `xlsx`, `Proceso` sin cambios, el binario empieza con la firma ZIP `PK`.
- [x] 4.3 Descargar `docx` responde con `Content-Type` de Word; descargar `xlsx` responde con `Content-Type` de Excel.
- [x] 4.4 Actualizar el/los tests de "formato no soportado" para que sigan usando un formato realmente inválido (p. ej. `txt`) — `docx`/`xlsx` ya no deben rechazarse.
- [x] 4.5 (Unit del service) Verificar que `exportar()` genera docx y xlsx con la extensión correcta y firma OOXML.

## 5. Validación
- [x] 5.1 `vendor/bin/pint --dirty --format agent`.
- [x] 5.2 `php artisan test --compact tests/Feature/InformesRazonados/` en verde.
- [x] 5.3 `composer types:check` (PHPStan) en verde.

## Context

Tras el change `exportar-informe-razonado-pdf`, `ExportadorInformeRazonadoService::exportar()` soporta `html` y `pdf` vía un `match($formato)` que delega a métodos privados. `render()` ya devuelve el HTML poblado de la vista `informes-razonados.export.html` (reutilizado por HTML y PDF). El `GenerarExportacionInformeRazonadoRequest` valida contra `FORMATOS_SOPORTADOS`, así que ampliar la constante extiende la validación. La descarga (`ExportacionInformeRazonadoController::descargar()`) ya elige el `Content-Type` por un `match($exportacion->formato)`.

## Goals / Non-Goals

**Goals:**
- Soportar `docx` (Word) y `xlsx` (Excel) reutilizando la arquitectura existente (constante + `match` + método por formato).
- Word: mismo contenido que HTML/PDF (desde la vista renderizada).
- Excel: representación tabular útil (métricas + excepciones + metadata).
- Content-type correcto al descargar cada formato.

**Non-Goals:**
- Rediseñar la plantilla del informe o el formato visual de Word/Excel más allá de lo funcional.
- Volcar narrativa/secciones libres a celdas de Excel (no es representable como tabla).
- Tocar workflow, permisos o esquema de `exportaciones_informe_razonado`.

## Decisions

- **Dependencias**: `phpoffice/phpword` y `phpoffice/phpspreadsheet` (aprobadas). Ambas son librerías PHP puras estándar, sin binarios ni servicios externos. `phpspreadsheet` ya viene como dependencia transitiva de otros paquetes comunes, pero se requiere explícitamente para no depender de eso.
- **Constante**: `FORMATOS_SOPORTADOS = ['html', 'pdf', 'docx', 'xlsx']`. Extiende la validación del Form Request automáticamente.
- **Word (`exportarDocx`)**: crear un `PhpWord`, agregar una sección y poblarla con el HTML de `render($ejecucion)` vía `\PhpOffice\PhpWord\Shared\Html::addHtml($section, $html, false, false)`. Guardar con el writer `Word2007` en un archivo temporal y volcarlo al disco `local` (PhpWord solo escribe a rutas de filesystem, no a un stream de Storage). Reutiliza `render()` → contenido idéntico a HTML/PDF.
- **Excel (`exportarXlsx`)**: construir un `Spreadsheet` desde las relaciones del modelo:
  - Hoja "Métricas": encabezados Código / Etiqueta / Valor / Unidad + una fila por métrica.
  - Hoja "Excepciones": encabezados Código / Severidad / Descripción + una fila por excepción.
  - Metadata (definición, corte, id de ejecución) en las primeras filas de la primera hoja.
  Guardar con el writer `Xlsx` a archivo temporal y volcarlo al disco `local`.
- **Escritura vía temp file**: tanto PhpWord como PhpSpreadsheet escriben a rutas de filesystem. Patrón: `tempnam()` → `$writer->save($tmp)` → `Storage::disk('local')->put($ruta, file_get_contents($tmp))` → `@unlink($tmp)`. Se encapsula en un helper privado `guardarDesdeArchivoTemporal(string $tmp, string $ruta): void` para no repetirlo.
- **Content-type en descarga**: ampliar el `match` existente en el controller con las entradas docx/xlsx.
- **Dispatch**: ampliar el `match($formato)` de `exportar()` con `docx`/`xlsx`; el `default` sigue lanzando `InvalidArgumentException` como defensa en profundidad.

## Risks / Trade-offs

- **`Html::addHtml` de PhpWord** no soporta todo el CSS/HTML; la vista actual es simple (headings, tablas, `<pre>`, párrafos) y cae dentro de lo soportado. Riesgo bajo; el test verifica que el archivo generado sea un OOXML válido (firma ZIP `PK\x03\x04`).
- **Excel omite narrativa**: es una decisión consciente (planilla = datos tabulares). La spec lo refleja explícitamente para no generar expectativa de contenido idéntico al HTML.
- **Temp files**: en tests con `Storage::fake('local')` el volcado sigue funcionando porque el temp file usa el filesystem real del SO y solo el `put` final va al disco fake. Hay que asegurar la limpieza del temp file incluso ante excepción (`try/finally`).
- **Peso de dependencias**: PhpWord/PhpSpreadsheet agregan peso a `vendor/`, aceptable para cerrar el requisito de la spec; sin impacto en runtime salvo al exportar bajo demanda.

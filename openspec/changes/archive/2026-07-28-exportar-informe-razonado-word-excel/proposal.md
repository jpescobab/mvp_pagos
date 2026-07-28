## Why

La spec core `reportabilidad-informes-razonados` exige exportar un informe en **Word, PDF, Excel o HTML**. Ya están soportados `html` y `pdf`; faltan **Word (.docx)** y **Excel (.xlsx)** para cerrar por completo la divergencia entre spec e implementación. Word es el formato editable estándar para entrega institucional; Excel permite trabajar los datos tabulares del informe (métricas, excepciones) en planilla.

## What Changes

- Sumar `docx` y `xlsx` como formatos soportados en `ExportadorInformeRazonadoService`.
- Agregar las dependencias `phpoffice/phpword` (.docx) y `phpoffice/phpspreadsheet` (.xlsx).
- **Word (.docx)**: se genera a partir de la **misma vista HTML renderizada** que HTML/PDF (vía `PhpWord\Shared\Html::addHtml()`), de modo que refleje el mismo contenido ensamblado (secciones, métricas, gráficos, narrativas, excepciones).
- **Excel (.xlsx)**: se genera **tabular/estructurado desde el modelo** (no desde el HTML): una hoja de métricas (código, etiqueta, valor, unidad) y una hoja de excepciones (código, severidad, descripción), más metadata de la ejecución. La narrativa/secciones libres no se vuelcan a celdas por no ser representables como tabla.
- Mantener el registro de evidencia inmutable en `exportaciones_informe_razonado` (formato, ruta, responsable) sin cambios de esquema.
- Servir cada archivo con su `Content-Type` correcto al descargar (`application/vnd.openxmlformats-officedocument.wordprocessingml.document` para docx, `...spreadsheetml.sheet` para xlsx).
- Actualizar la validación de formato para aceptar `html`, `pdf`, `docx` y `xlsx`, rechazando cualquier otro.

## Capabilities

### New Capabilities
<!-- Ninguna -->

### Modified Capabilities
- `gestionar-informes-razonados`: la requirement "Generar y registrar una exportación de una ejecución de informe razonado" pasa de soportar `html`/`pdf` a soportar además `docx` y `xlsx`; se precisa el criterio de contenido de cada formato (Word desde el HTML renderizado; Excel tabular desde el modelo).

## Impact

- **Código**: `app/Services/InformesRazonados/ExportadorInformeRazonadoService.php` (formatos docx/xlsx + generadores), `app/Http/Controllers/InformesRazonados/ExportacionInformeRazonadoController.php` (content-types).
- **Dependencias**: nuevas `phpoffice/phpword` y `phpoffice/phpspreadsheet` (aprobadas por el usuario).
- **Tests**: `tests/Feature/InformesRazonados/` — exportación docx y xlsx (genera archivo, registra evidencia, `Proceso` sin cambios, firma/estructura del binario), descargas con content-type, y actualización de los casos de "formato no soportado".
- **Sin cambios**: workflow, tabla `exportaciones_informe_razonado`, permisos, formatos html/pdf ya existentes.

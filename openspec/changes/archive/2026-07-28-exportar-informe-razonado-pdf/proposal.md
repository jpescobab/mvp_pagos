## Why

La spec core `reportabilidad-informes-razonados` exige registrar exportaciones de un informe en **Word, PDF, Excel o HTML**, pero hoy `ExportadorInformeRazonadoService` solo soporta `html` (`FORMATOS_SOPORTADOS = ['html']`) y la spec `gestionar-informes-razonados` acota explícitamente el alcance a HTML, rechazando cualquier otro formato por validación. PDF es el formato de entrega institucional más solicitado para un informe razonado publicado; agregarlo cierra la divergencia entre spec e implementación con el menor alcance posible.

## What Changes

- Sumar `pdf` como formato soportado en `ExportadorInformeRazonadoService`, reutilizando la vista Blade ya existente (`informes-razonados.export.html`) renderizada a PDF.
- Agregar la dependencia `barryvdh/laravel-dompdf` (wrapper de dompdf sobre HTML/CSS) para la generación del PDF.
- Mantener intacto el registro de evidencia: la exportación PDF se guarda en almacenamiento privado y registra `formato`, `ruta_archivo` y responsable en `exportaciones_informe_razonado`, igual que HTML, vía `InformeRazonadoService::exportar()`.
- Actualizar la validación del formato solicitado para aceptar `html` y `pdf`, y seguir rechazando cualquier otro.
- Servir el PDF con el `Content-Type` correcto (`application/pdf`) en `ExportacionInformeRazonadoController::descargar()`.
- Word y Excel quedan explícitamente **fuera de alcance** de este cambio.

## Capabilities

### New Capabilities
<!-- Ninguna: no se introduce una capacidad nueva -->

### Modified Capabilities
- `gestionar-informes-razonados`: la requirement "Generar y registrar una exportación de una ejecución de informe razonado" pasa de soportar únicamente `html` a soportar `html` y `pdf`; el rechazo por validación aplica ahora solo a formatos distintos de esos dos.

## Impact

- **Código**: `app/Services/InformesRazonados/ExportadorInformeRazonadoService.php` (nuevo método de render PDF + constante de formatos), `app/Http/Requests/InformesRazonados/GenerarExportacionInformeRazonadoRequest.php` (regla de formato), `app/Http/Controllers/InformesRazonados/ExportacionInformeRazonadoController.php` (content-type al descargar).
- **Dependencias**: nueva dependencia Composer `barryvdh/laravel-dompdf` (aprobada por el usuario).
- **Vistas**: se reutiliza `resources/views/informes-razonados/export/html.blade.php`; puede requerir ajustes menores de CSS compatibles con dompdf.
- **Tests**: `tests/Feature/InformesRazonados/` — cobertura de exportación PDF (genera archivo, registra evidencia con formato `pdf`, descarga con content-type correcto) y de que otros formatos siguen rechazándose.
- **Sin cambios**: workflow de la ejecución, tabla `exportaciones_informe_razonado`, permisos.

# Tasks

## 1. Dependencia
- [x] 1.1 Instalar `barryvdh/laravel-dompdf` vía `composer require barryvdh/laravel-dompdf` (auto-discovery del ServiceProvider; sin publicar config).

## 2. ExportadorInformeRazonadoService
- [x] 2.1 Ampliar `FORMATOS_SOPORTADOS` a `['html', 'pdf']` en `app/Services/InformesRazonados/ExportadorInformeRazonadoService.php`.
- [x] 2.2 Extraer el render de la vista a un método privado `render(EjecucionInformeRazonado $ejecucion): string` (carga de relaciones + `View::make(...)->render()`), reutilizado por HTML y PDF para garantizar contenido idéntico.
- [x] 2.3 Cambiar `exportar()` para delegar por `match($formato)` a `exportarHtml()` / `exportarPdf()`, conservando el `InvalidArgumentException` para formatos no soportados como defensa en profundidad.
- [x] 2.4 Implementar `exportarPdf()`: `Pdf::loadHTML($this->render($ejecucion))`, guardar el binario en `informes-razonados/{id}/informe-{id}-{timestamp}.pdf` en disco `local`, devolver la ruta relativa.

## 3. Descarga con content-type correcto
- [x] 3.1 En `ExportacionInformeRazonadoController::descargar()`, servir el archivo con el `Content-Type` explícito según el `formato` de la exportación (`application/pdf` para pdf, `text/html` para html), manteniendo la verificación de existencia y la autorización actuales.

## 4. Vista de exportación
- [x] 4.1 Revisar `resources/views/informes-razonados/export/html.blade.php` para asegurar que su CSS renderiza correctamente en dompdf; ajustar solo lo mínimo necesario (compatibilidad), sin cambiar el contenido.

## 5. Tests (`tests/Feature/InformesRazonados/`)
- [x] 5.1 Exportar en formato `pdf` con permiso `informes.exportar`: genera archivo en almacenamiento privado, registra `exportacion_informe_razonado` con formato `pdf`, ruta y responsable; el estado del `Proceso` no cambia; el archivo empieza con la cabecera `%PDF-`.
- [x] 5.2 Descargar una exportación `pdf` responde con `Content-Type: application/pdf`.
- [x] 5.3 El formato `html` sigue funcionando igual que antes (no regresión).
- [x] 5.4 Un formato distinto de `html`/`pdf` (p. ej. `docx`) se rechaza por validación sin generar archivo ni registro.

## 6. Validación
- [x] 6.1 `vendor/bin/pint --dirty --format agent` sobre los archivos PHP tocados.
- [x] 6.2 `php artisan test --compact tests/Feature/InformesRazonados/` en verde.
- [x] 6.3 `composer types:check` (PHPStan) en verde.

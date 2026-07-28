## Context

`ExportadorInformeRazonadoService::exportar()` hoy soporta solo `html`: renderiza la vista Blade `informes-razonados.export.html`, guarda el HTML en `storage/app/private/informes-razonados/{id}/...html` y devuelve la ruta. `InformeRazonadoService::exportar()` registra la evidencia en `exportaciones_informe_razonado` (formato, ruta, responsable). El `GenerarExportacionInformeRazonadoRequest` valida el formato contra `ExportadorInformeRazonadoService::FORMATOS_SOPORTADOS`, así que la validación se extiende sola al ampliar la constante. La descarga usa `Storage::disk('local')->download()`.

## Goals / Non-Goals

**Goals:**
- Soportar `pdf` como formato de exportación reutilizando exactamente el mismo contenido que el HTML.
- Mantener el registro de evidencia inmutable sin cambios de esquema.
- Servir el PDF con `Content-Type: application/pdf` explícito al descargar.

**Non-Goals:**
- Formatos Word y Excel (fuera de alcance).
- Cambiar el diseño/plantilla del informe más allá de ajustes de CSS necesarios para dompdf.
- Tocar el workflow de la ejecución o los permisos.

## Decisions

- **Dependencia**: `barryvdh/laravel-dompdf` (wrapper de dompdf). Aprobada por el usuario. Auto-descubre su ServiceProvider; no requiere publicar config en este alcance.
- **Reutilización de contenido**: extraer el render de la vista a un método privado `render(EjecucionInformeRazonado): string` que devuelve el HTML ya poblado. `exportarHtml()` guarda ese HTML tal cual; `exportarPdf()` pasa ese mismo HTML a `Pdf::loadHTML($html)` y guarda el binario. Así ambos formatos garantizan idéntico contenido (requerido por la spec).
- **Constante**: `FORMATOS_SOPORTADOS = ['html', 'pdf']`. Extiende la validación del Form Request automáticamente.
- **Ruta/almacenamiento**: misma convención (`informes-razonados/{id}/informe-{id}-{timestamp}.pdf`), mismo disco `local` (privado).
- **Content-Type en descarga**: pasar explícitamente el header según la extensión/formato registrado. `Storage::download()` ya infiere `application/pdf` por la extensión `.pdf`, pero se fija el header de forma explícita para cumplir el escenario de la spec sin depender del guesser.
- **Dispatch por formato**: dentro de `exportar()`, un `match($formato)` delega a `exportarHtml`/`exportarPdf`. El `InvalidArgumentException` para formato no soportado se mantiene como defensa en profundidad (la validación primaria es el Form Request).

## Risks / Trade-offs

- **CSS de dompdf**: dompdf no soporta todo CSS moderno. La vista actual usa CSS simple (fuentes del sistema, tablas con bordes, `rem`/`px`), compatible con dompdf; `overflow-x` es irrelevante en PDF. Riesgo bajo; se verifica con un test que el archivo generado empiece con la cabecera `%PDF-`.
- **Peso de la dependencia**: dompdf agrega peso a `vendor/`, pero es una librería PHP pura estándar y ampliamente usada; sin binarios externos ni servicios.
- **Rendimiento**: renderizar PDF es más costoso que guardar HTML, pero la exportación es una acción puntual bajo demanda, no un proceso masivo; no requiere Job en este alcance.

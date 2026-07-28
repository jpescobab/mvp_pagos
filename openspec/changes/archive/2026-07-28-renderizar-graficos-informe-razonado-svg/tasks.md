## 1. Forma canónica de datos y validación

- [x] 1.1 Endurecer `app/Http/Requests/InformesRazonados/GuardarGraficoInformeRazonadoRequest.php`: validar que `datos` tenga `categorias` (array de string, no vacío) y `series` (array de objetos `{ nombre: string, valores: number[] }`), con cada `valores` del mismo largo que `categorias`. Agregar un `after()` que rechace si el largo no coincide o si el `tipo` es `torta` y hay más de una serie. Mensajes de error en español.
- [x] 1.2 Test de feature sobre el endpoint de guardado de gráficos (`ejecuciones.graficos.store`): rechaza `torta` con 2 series, rechaza serie con valores de largo distinto a categorías, acepta `barra` con datos canónicos válidos.

## 2. Renderer SVG server-side

- [x] 2.1 Crear `app/Services/InformesRazonados/GraficoSvgRenderer.php` con `render(string $tipo, array $datos, array $opciones = []): string` que devuelva `<svg role="img">` con `<title>`. Implementar `barra`, `linea`, `area`, `torta` con paleta fija de contraste alto. Método/estrategia de fallback interno que detecte datos vacíos o forma no reconocida y devuelva un SVG/HTML con texto "Sin datos para graficar" / "Formato de datos no reconocido" sin lanzar excepción.
- [x] 2.2 Test unitario de `GraficoSvgRenderer`: cada tipo válido produce un string que empieza con `<svg` y contiene el título; datos vacíos y forma no reconocida producen el texto de fallback y no lanzan excepción.

## 3. Integración en la exportación (Blade → HTML/PDF/Word/Excel)

- [x] 3.1 En `resources/views/informes-razonados/export/html.blade.php`, reemplazar el bloque `<pre>{{ json_encode(...) }}</pre>` de la sección Gráficos por la invocación a `GraficoSvgRenderer::render($grafico->tipo, $grafico->datos)` (inyectado o resuelto vía `app()` en la vista de forma acotada). Mantener el `<h3>` con título + tipo.
- [x] 3.2 Test de feature de exportación: para una ejecución con un gráfico válido, el HTML generado por `ExportadorInformeRazonadoService` contiene `<svg`; para un gráfico con datos vacíos, la exportación se completa e incluye el texto de fallback. Verificar (y documentar en el propio test/PR el resultado real) que los cuatro formatos generan sin excepción; si algún formato no preserva el SVG inline, dejar registrado el comportamiento observado.

## 4. Integración en la vista React

- [x] 4.1 Crear `resources/js/components/informes-razonados/grafico-svg.tsx`: componente que recibe `{ tipo, datos }` y dibuja el mismo gráfico (barra/linea/area/torta) contra la forma canónica, usando tokens de tema para claro/oscuro y con fallback textual para datos vacíos/no reconocidos. Tipar `datos` en `resources/js/types/informes-razonados.ts` acorde a la forma canónica (tolerando la forma legada como opcional).
- [x] 4.2 En `resources/js/pages/informes-razonados/ejecuciones/show.tsx`, reemplazar el listado de gráficos como texto por el render con `<GraficoSvg>` (manteniendo los controles de editar/eliminar cuando `editable && puedeElaborar`).

## 5. Validación y cierre

- [x] 5.1 `vendor/bin/pint --dirty --format agent`, `php artisan test` (suite de informes razonados y reportabilidad), `npm run types:check`, `npm run lint:check`.
- [x] 5.2 Regenerar Wayfinder si hiciera falta (no se agregan rutas, así que en principio no); confirmar build front (`npm run build`) sin errores.

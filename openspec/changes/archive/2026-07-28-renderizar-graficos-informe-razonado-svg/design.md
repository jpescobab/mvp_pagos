## Context

Un `GraficoInformeRazonado` (`tipo`: barra/linea/torta/area, `datos`: JSON libre, hoy validado solo como `array`) se persiste pero nunca se dibuja. La vista `resources/js/pages/informes-razonados/ejecuciones/show.tsx` lista título + tipo como texto; la plantilla `resources/views/informes-razonados/export/html.blade.php` vuelca `json_encode($grafico->datos)` en un `<pre>`. Las cuatro exportaciones (HTML/PDF/Word/Excel) derivan de esa misma plantilla Blade a través de `ExportadorInformeRazonadoService`, por lo que ninguna muestra el gráfico.

Los gráficos se crean solo a mano (no hay auto-generación desde el corte), así que no hay un gran volumen de datos legados con forma fija que respetar; el placeholder del formulario ya sugiere `{"series": [...]}`.

## Goals / Non-Goals

**Goals:**
- Dibujar cada gráfico como SVG real en la vista y en las cuatro exportaciones.
- SVG server-side, sin JS en runtime, para que PDF/Word (que no ejecutan JS) incluyan el gráfico.
- Una única fuente de verdad de la geometría del gráfico, reutilizada por Blade y por el export; la vista React replica el mismo resultado visual.
- Forma canónica de `datos` validada por tipo, con fallback seguro para datos vacíos/legados.

**Non-Goals:**
- No se agregan librerías de charting (ni PHP ni JS): SVG a mano, para no sumar dependencias ni romper el render en dompdf/word.
- No se auto-generan gráficos desde el corte (sigue siendo alta manual).
- No se tocan workflow, permisos, migraciones ni el modelo.
- No se hace interactividad (tooltips, zoom): el gráfico es estático, coherente con un deliverable imprimible.

## Decisions

**1. `GraficoSvgRenderer` (Service PHP) como única fuente de verdad del SVG.**
`app/Services/InformesRazonados/GraficoSvgRenderer.php` expone `render(string $tipo, array $datos, array $opciones = []): string` y devuelve un `<svg>` inline. La plantilla Blade lo invoca (vía un método estático o inyección) reemplazando el `<pre>`. Es un renderer puro (sin estado, sin DB), fácil de testear unitariamente. Colores: paleta fija de contraste alto (segura tanto en pantalla clara como impresa); no se depende de variables de tema para el export.

**2. La vista React replica el render con un componente propio, no consume el SVG del backend.**
Generar el SVG en React (`resources/js/components/informes-razonados/grafico-svg.tsx`) evita un round-trip/endpoint nuevo y permite usar tokens de tema (`currentColor`, variables CSS) para claro/oscuro. La geometría (escalas, ejes, paths) se implementa una vez en PHP y una vez en TS, ambas contra la MISMA forma canónica de `datos`, de modo que producen el mismo dibujo. Es duplicación acotada y deliberada (dos entornos de render distintos: Blade sin JS vs React); la spec fija el contrato de datos, no la implementación.

**3. Forma canónica de `datos`: `{ categorias: string[], series: [{ nombre: string, valores: number[] }] }`.**
- `barra`/`linea`/`area`: 1..N series, cada `valores` con largo == `categorias`.
- `torta`: exactamente 1 serie; cada categoría es un sector proporcional a su valor.
La validación vive en `GuardarGraficoInformeRazonadoRequest` mediante reglas + un `after()` que chequea coherencia de largos y la cardinalidad de series por tipo. Solo aplica a escrituras nuevas/ediciones; no se revalida retroactivamente lo ya guardado.

**4. Fallback en el renderer, no en la validación.**
El renderer detecta datos vacíos o de forma no reconocida y devuelve un SVG/HTML de fallback con texto ("Sin datos para graficar" / "Formato de datos no reconocido"). Así, un gráfico legado con forma antigua se muestra degradado pero el informe completo se exporta sin excepción. La validación estricta protege los datos nuevos; el fallback protege el render de los viejos.

**5. Reutilización en el export.** No se cambia `ExportadorInformeRazonadoService`: al derivar los cuatro formatos del mismo HTML, basta arreglar la plantilla Blade para que los cuatro incluyan el `<svg>`. Se verifica en test que el HTML de export contiene `<svg` para un gráfico válido.

## Risks / Trade-offs

- **Duplicación PHP/TS del render.** Mitigación: contrato de datos único en la spec; tests en ambos lados; geometría simple (barras/líneas/área/torta) de bajo cambio. Se acepta a cambio de no introducir un endpoint de imagen ni una dependencia de charting.
- **SVG en Word/Excel.** El pipeline actual de export ya produce esos formatos desde HTML; hay que verificar que el `<svg>` inline sobreviva a esa conversión. Si algún formato no soporta SVG inline, el fallback será degradar a la tabla de datos en ese formato puntual — se confirma durante apply con un test por formato y se documenta el resultado real, sin prometer soporte no verificado.
- **Datos legados.** Cubierto por el fallback; no se migra ni se rechaza retroactivamente.
- **Accesibilidad.** SVG con `role="img"` + `<title>`; el texto de las categorías/valores queda igualmente disponible en la sección de métricas y en el propio gráfico.

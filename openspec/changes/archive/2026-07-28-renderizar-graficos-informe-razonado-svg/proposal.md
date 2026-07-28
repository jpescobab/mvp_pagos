## Why

Un gráfico de informe razonado se guarda con `tipo` (barra/línea/torta/área) y `datos`, pero nunca se visualiza: en la vista de la ejecución solo se lista título + tipo como texto, y en la exportación (`resources/views/informes-razonados/export/html.blade.php`) se vuelca `json_encode($grafico->datos)` dentro de un `<pre>`. El deliverable institucional del informe — el que se publica y exporta a PDF/Word/Excel — nunca muestra el gráfico, así que la capacidad de "gráficos" existe a nivel de datos pero no aporta valor al lector.

## What Changes

- Se define una **forma canónica de `datos`** por tipo de gráfico: `{ categorias: string[], series: [{ nombre: string, valores: number[] }] }`. El endpoint de guardado/edición valida esa forma según el `tipo` (torta admite una sola serie), rechazando datos que no puedan renderizarse. Retrocompatible: los gráficos ya guardados con otras formas se toleran mostrando un fallback en vez de romper el informe.
- Se agrega un **renderer SVG server-side** (`GraficoSvgRenderer` en `app/Services/InformesRazonados/`) que recibe `tipo` + `datos` y devuelve SVG inline accesible (con `role="img"` + `<title>`/`<desc>`), sin depender de JavaScript en runtime — requisito para que los exports PDF/Word, que no ejecutan JS, incluyan el gráfico real.
- La **plantilla de exportación HTML** reemplaza el `<pre>` con JSON por el SVG del renderer; como los cuatro formatos derivan del mismo HTML, todos (HTML/PDF/Word/Excel) pasan a incluir el gráfico dibujado.
- La **vista React de la ejecución** (`ejecuciones/show.tsx`) muestra el mismo gráfico dibujado (SVG) en vez del texto plano actual, respetando el tema visual claro/oscuro.
- Datos vacíos o con forma no reconocida producen un **fallback explícito** ("Sin datos para graficar" / "Formato de datos no reconocido"), nunca una excepción ni un informe roto.

## Capabilities

### New Capabilities
<!-- Ninguna capability nueva: es un refinamiento de comportamiento de una existente. -->

### Modified Capabilities
- `reportabilidad-informes-razonados`: el comportamiento de los gráficos de un informe cambia a nivel de spec — pasan de guardarse como datos opacos a renderizarse como SVG tanto en la vista como en todas las exportaciones, y su `datos` adquiere una forma canónica validada por tipo.

## Impact

- **Backend nuevo**: `app/Services/InformesRazonados/GraficoSvgRenderer.php`. Endurecimiento de `app/Http/Requests/InformesRazonados/GuardarGraficoInformeRazonadoRequest.php` (validación de la forma de `datos` por tipo).
- **Plantilla**: `resources/views/informes-razonados/export/html.blade.php` (sección Gráficos).
- **Frontend**: `resources/js/pages/informes-razonados/ejecuciones/show.tsx` (bloque de listado de gráficos) + un componente de gráfico SVG en React.
- **Sin migraciones ni tablas nuevas.** No se toca el workflow, los permisos existentes (`informes.elaborar` / `informes.exportar`) ni el modelo `GraficoInformeRazonado`.
- **Riesgo de compatibilidad**: gráficos preexistentes con `datos` de forma libre; mitigado por el fallback y por no rechazar retroactivamente lo ya almacenado (la validación estricta solo aplica a nuevas escrituras/ediciones).

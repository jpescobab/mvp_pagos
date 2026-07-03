## Context

`resources/js/pages/maestros/proveedores/index.tsx` (rediseñado en `mejorar-catalogo-proveedores`) resolvió, sobre datos reales, un problema de densidad y de desbordamiento horizontal: con el sidebar expandido, una tabla sin anchos de columna fijos deja que el navegador reparta el ancho según el contenido más largo (direcciones, correos), empujando columnas como "Estado" fuera del viewport. La solución aplicada ahí es genérica y reutilizable para cualquier índice futuro (tablas maestras institucionales, y cualquier otro catálogo).

## Goals / Non-Goals

**Goals:**
- Dejar un requirement testable en `tema-visual-layout` que describa el patrón de tabla densa de forma independiente de "Proveedores", para que cualquier índice nuevo lo cumpla desde su primer borrador.
- Apuntar, desde `HARNESS_IA.md`, a esa spec y al componente de referencia, sin duplicar el detalle (mismo criterio ya aplicado en el harness: no mantener listas que se desactualizan, apuntar a la fuente de verdad).

**Non-Goals:**
- No se construyen las páginas de Instituciones/Jurisdicciones/Cfinancieros/Ccostos/Items/Asignaciones/Catálogos en este cambio.
- No se modifica el catálogo de proveedores ni el de clientes medidores ya existentes.
- No se crea un componente de tabla genérico/abstracto (`<DataTable>`) todavía — eso sería una decisión de refactor de código, no de convención documental; se deja como posible trabajo futuro si la duplicación entre índices lo justifica cuando existan 2-3 implementaciones reales.

## Decisions

- **Dónde vive el requirement**: en `tema-visual-layout`, no en `tablas-maestras-institucionales`. Razón: es una convención de presentación transversal a toda la aplicación (cualquier listado, no solo tablas maestras), coherente con que esa capability ya concentra sidebar, topbar, tema y panel general — el resto de convenciones visuales del sistema.
- **Nivel de detalle en `HARNESS_IA.md`**: un párrafo corto con puntero a la spec y al archivo de referencia, no una copia del detalle. El harness ya tiene la lección aprendida de no enumerar listas que se desactualizan (ver nota en `CLAUDE.md` sobre specs ad-hoc); el mismo criterio aplica aquí.
- **No crear un componente `<DataTable>` genérico todavía**: con una sola implementación real (Proveedores) abstraerlo sería prematuro. Se documenta el patrón como convención a replicar manualmente; si al construir 2-3 índices de tablas maestras el código se vuelve claramente duplicado, esa extracción es un refactor natural para ese momento, no de este change.

## Risks / Trade-offs

- [Riesgo: la convención documentada quede obsoleta si el componente de referencia cambia de forma no compatible] → Mitigación: el requirement describe el patrón en términos de comportamiento observable (columnas fijas, truncado con tooltip, badge semántico, menú de acciones), no en términos de clases Tailwind exactas, para que sobreviva a ajustes menores de estilo.
- [Trade-off: repetir manualmente el patrón en cada índice nuevo en vez de un componente compartido] → Aceptado explícitamente; ver Non-Goals.

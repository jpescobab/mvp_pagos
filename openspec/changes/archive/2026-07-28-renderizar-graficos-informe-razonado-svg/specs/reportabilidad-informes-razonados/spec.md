## ADDED Requirements

### Requirement: Los gráficos de un informe se renderizan como SVG en la vista y en las exportaciones

Un gráfico de una ejecución de informe razonado SHALL renderizarse como un gráfico SVG real, tanto en la vista de la ejecución como en todas las exportaciones (HTML, PDF, Word, Excel). El SVG SHALL generarse server-side sin depender de JavaScript en runtime, de modo que los formatos que no ejecutan JavaScript (PDF, Word) incluyan el gráfico dibujado. El SVG SHALL ser accesible: incluir `role="img"` y un `<title>` con el título del gráfico. El sistema SHALL soportar los tipos `barra`, `linea`, `torta` y `area`.

#### Scenario: La exportación incluye el gráfico dibujado, no el JSON crudo

- **WHEN** se exporta una ejecución que tiene un gráfico con datos válidos, en cualquiera de los formatos soportados
- **THEN** el documento generado contiene un elemento `<svg>` que dibuja el gráfico según su `tipo`
- **AND** no contiene el volcado de `datos` como JSON crudo

#### Scenario: La vista de la ejecución muestra el gráfico dibujado

- **WHEN** un usuario abre la vista de una ejecución que tiene un gráfico con datos válidos
- **THEN** ve el gráfico renderizado como SVG, respetando el tema visual claro/oscuro
- **AND** no ve únicamente el título y el tipo como texto plano

### Requirement: Los datos de un gráfico tienen una forma canónica validada por tipo

El `datos` de un gráfico SHALL seguir la forma canónica `{ categorias: string[], series: [{ nombre: string, valores: number[] }] }`, donde cada serie tiene tantos `valores` como `categorias`. Al crear o editar un gráfico, el sistema SHALL validar esa forma según el `tipo`: un gráfico de tipo `torta` SHALL admitir exactamente una serie; `barra`, `linea` y `area` SHALL admitir una o más series. El sistema SHALL rechazar con error de validación un gráfico cuyo `datos` no pueda renderizarse (categorías vacías, series sin valores, o largo de valores distinto del de categorías).

#### Scenario: Se rechaza un gráfico con datos no renderizables

- **WHEN** un usuario con permiso `informes.elaborar` intenta guardar un gráfico cuyo `datos` no respeta la forma canónica (por ejemplo, una serie con menos valores que categorías, o un `torta` con dos series)
- **THEN** el sistema rechaza la operación con un error de validación
- **AND** no se persiste el gráfico

#### Scenario: Se acepta un gráfico con datos canónicos

- **WHEN** un usuario con permiso `informes.elaborar` guarda un gráfico de tipo `barra` con `categorias` y una o más `series` cuyos `valores` tienen el mismo largo que las `categorias`
- **THEN** el gráfico se persiste correctamente

### Requirement: Un gráfico sin datos renderizables muestra un fallback, nunca rompe el informe

Cuando el `datos` de un gráfico está vacío o tiene una forma no reconocida (por ejemplo, gráficos preexistentes guardados antes de la forma canónica), el renderer SHALL producir un fallback textual explícito en lugar de un gráfico, y SHALL NOT lanzar una excepción ni impedir que el resto del informe se muestre o se exporte.

#### Scenario: Un gráfico con datos vacíos no interrumpe la exportación

- **WHEN** se exporta una ejecución que contiene un gráfico con `datos` vacío o de forma no reconocida
- **THEN** la exportación se completa correctamente
- **AND** en el lugar del gráfico aparece un mensaje de fallback explícito ("Sin datos para graficar" o "Formato de datos no reconocido")

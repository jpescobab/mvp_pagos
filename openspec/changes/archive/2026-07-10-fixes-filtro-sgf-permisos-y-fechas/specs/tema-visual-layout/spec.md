## ADDED Requirements

### Requirement: Formato de fechas determinista y compatible con SSR
Toda fecha mostrada en el frontend SHALL formatearse con los helpers compartidos de `@/lib/format` (`formatFechaHora` para fecha y hora, `formatFecha` para solo fecha), que usan `Intl.DateTimeFormat` con locale `es-CL` y componentes explícitos (día/mes/año de 2-4 dígitos, hora de 24 horas), de modo que el render del servidor (SSR de Inertia) y el del cliente produzcan exactamente el mismo texto y no rompan la hidratación de React. `formatFechaHora` SHALL usar la zona horaria `America/Santiago`; `formatFecha` SHALL usar `UTC` a propósito, porque las columnas `date` llegan como medianoche UTC y formatearlas en una zona con offset las correría un día. Ambos helpers devuelven `"—"` para valores nulos o inválidos. Queda prohibido usar `toLocaleString`/`toLocaleDateString`/`toLocaleTimeString` sin locale y zona fijos en componentes que se rendericen por SSR.

#### Scenario: El SSR y el cliente producen el mismo texto de fecha
- **WHEN** una página con fechas (por ejemplo, el detalle de una importación SGF) se sirve con SSR y se hidrata en el navegador
- **THEN** el texto de cada fecha es idéntico en ambos renders
- **AND** la consola del navegador no registra errores de hidratación de React por desajuste de texto

#### Scenario: Una columna solo-fecha no se corre de día
- **WHEN** se formatea un valor de columna `date` (por ejemplo `2026-07-05T00:00:00Z`) con `formatFecha`
- **THEN** se muestra `05-07-2026`, la fecha civil original, sin desplazarse por zona horaria

#### Scenario: Valor nulo o inválido
- **WHEN** se formatea `null`, cadena vacía o una fecha no parseable
- **THEN** el helper devuelve `"—"` sin lanzar error

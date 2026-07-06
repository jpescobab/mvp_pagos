## ADDED Requirements

### Requirement: Formato numérico global
El sistema SHALL presentar todo número de negocio visible al usuario (montos en pesos, indicadores económicos, cantidades, KPIs y demás magnitudes) con un formato legible y consistente en toda la aplicación: separador de miles con punto (`.`) y separador decimal con coma (`,`) — convención `es-CL`. El sistema SHALL resolver este formateo mediante un helper/componente central reutilizable en `resources/js` en vez de lógica de formateo repetida por página. Este requirement SHALL NOT aplicar a identificadores, códigos institucionales, años ni otros valores que no representen una magnitud de negocio.

#### Scenario: Monto grande con miles y decimales
- **WHEN** una vista renderiza un monto o cantidad igual o mayor a 1.000
- **THEN** se muestra con punto como separador de miles y, si tiene decimales, coma como separador decimal (ej. `1.234.567,89`)

#### Scenario: Reutilización del helper central
- **WHEN** una página nueva o existente necesita mostrar un monto, indicador o cantidad
- **THEN** usa el helper/componente central de formato numérico en vez de invocar `Intl.NumberFormat` o `toLocaleString` de forma ad-hoc

### Requirement: Valores negativos en rojo
El sistema SHALL mostrar todo valor numérico de negocio negativo (monto, indicador, cantidad, KPI) en el color semántico "danger" del tema (rojo), reutilizando el token existente `text-destructive` sin introducir un color hardcodeado nuevo. El sistema SHALL NOT aplicar este color a valores en cero o positivos.

#### Scenario: Monto negativo resaltado
- **WHEN** una vista renderiza un monto o cantidad con valor negativo
- **THEN** el número se muestra en el color rojo semántico del tema, distinguible del texto normal

#### Scenario: Monto positivo o cero sin color especial
- **WHEN** una vista renderiza un monto o cantidad con valor cero o positivo
- **THEN** el número se muestra con el color de texto normal, sin el rojo reservado para negativos

### Requirement: Legibilidad tipográfica de cifras
Por ser una aplicación financiera, el sistema SHALL renderizar todo número de negocio (montos, indicadores, cantidades, KPIs) con la tipografía monoespaciada del tema (`font-mono` / `JetBrains Mono`, definida en `resources/css/app.css` y ya usada como convención para códigos e identificadores), de modo que los dígitos no se presten a confusión entre sí (ej. `0` con `8`, `1` con `l`) y las cifras en una misma columna queden alineadas. El sistema SHALL NOT usar para cifras una tipografía o estilo donde dígitos distintos resulten visualmente ambiguos.

#### Scenario: Cifra con tipografía monoespaciada
- **WHEN** una vista renderiza un monto, indicador, cantidad o KPI
- **THEN** el número se muestra con la fuente monoespaciada del tema, con ancho de dígito uniforme

#### Scenario: Columna de montos alineada
- **WHEN** una tabla o listado muestra varias filas con montos en la misma columna
- **THEN** las cifras quedan alineadas verticalmente entre filas gracias al ancho uniforme de la tipografía monoespaciada

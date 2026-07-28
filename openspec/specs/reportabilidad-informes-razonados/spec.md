# Spec: reportabilidad-informes-razonados

## Purpose

Capa de cortes/snapshots de reportabilidad (core, no desactivable) e informes razonados de gestión (módulo funcional activable). Los informes no son cierres contables ni presupuestarios oficiales; son evidencia de gestión, seguimiento y toma de decisiones, siempre con revisión y aprobación humana antes de publicarse. Los informes nacen de cortes y snapshots, nunca de datos vivos cambiantes.

## Requirements

### Requirement: Registrar períodos y cortes de reportabilidad
El sistema SHALL registrar períodos de reportabilidad (`periodos_reportabilidad`) y, dentro de cada período, cortes (`cortes_reportabilidad`) que comienzan en estado `borrador` y solo pueden publicarse mediante una acción explícita con permiso `reportabilidad.publicar_corte`.

#### Scenario: Crear un corte en borrador
- **WHEN** se crea un `corte_reportabilidad` para un `periodo_reportabilidad`
- **THEN** el corte queda en estado `borrador`

#### Scenario: Publicar un corte
- **WHEN** un usuario con permiso `reportabilidad.publicar_corte` publica un corte en borrador
- **THEN** el corte pasa a estado `publicado` con su fecha y responsable

#### Scenario: Rechazar publicar un corte sin permiso
- **WHEN** un usuario sin permiso `reportabilidad.publicar_corte` intenta publicar un corte
- **THEN** el sistema rechaza la operación
- **AND** el corte permanece en estado `borrador`

### Requirement: Un corte publicado es inmutable
El sistema SHALL NOT permitir agregar items ni snapshots a un `corte_reportabilidad` cuyo estado sea `publicado`.

#### Scenario: Intentar modificar un corte ya publicado
- **WHEN** se intenta agregar un `corte_reportabilidad_item` o un `snapshot_corte_reportabilidad` a un corte en estado `publicado`
- **THEN** el sistema rechaza la operación
- **AND** no se crea ningún registro nuevo

### Requirement: Conservar evidencia inmutable de cada corte
El sistema SHALL conservar, para cada corte, un `snapshot_corte_reportabilidad` con su payload crudo y hash de contenido, vinculado opcionalmente a los `cortes_reportabilidad_items` que representan las entidades internas incluidas en el corte.

#### Scenario: Capturar snapshot de un corte
- **WHEN** se captura un snapshot dentro de un corte en borrador
- **THEN** se crea un `snapshot_corte_reportabilidad` con `payload_crudo` y `hash`
- **AND** puede asociarse a un `corte_reportabilidad_item` específico

### Requirement: Un informe solo puede iniciarse sobre un corte publicado
El sistema SHALL impedir iniciar una `ejecucion_informe_razonado` sobre un `corte_reportabilidad` que no esté en estado `publicado`.

#### Scenario: Iniciar un informe sobre un corte no publicado
- **WHEN** se intenta iniciar una `ejecucion_informe_razonado` sobre un corte en estado `borrador`
- **THEN** el sistema rechaza la operación
- **AND** no se crea ninguna `ejecucion_informe_razonado`

### Requirement: El ciclo de vida de una ejecución de informe se gobierna por workflow
El sistema SHALL gobernar el estado de cada `ejecucion_informe_razonado` (`borrador` → `en_revision` → `aprobado`/`rechazado` → `publicado`) exclusivamente mediante `TransicionWorkflowService::execute()`, igual que cualquier otro proceso de workflow del sistema.

#### Scenario: Iniciar una ejecución de informe en su estado inicial
- **WHEN** se inicia una `ejecucion_informe_razonado` sobre un corte publicado
- **THEN** se crea un `Proceso` asociado en el estado inicial del workflow "informes_razonados"

#### Scenario: Aprobar una ejecución de informe
- **WHEN** un usuario con permiso `informes.aprobar` aprueba una ejecución en revisión
- **THEN** el `Proceso` asociado transiciona a su estado `aprobado`
- **AND** se crea una `aprobacion_informe_razonado` con la decisión, el usuario y el momento

#### Scenario: Publicar una ejecución de informe aprobada
- **WHEN** un usuario con permiso `informes.publicar` publica una ejecución aprobada
- **THEN** el `Proceso` asociado transiciona a su estado final `publicado`
- **AND** se crea un `snapshot_informe_razonado` inmutable con el contenido final ensamblado del informe

### Requirement: La narrativa generada por IA requiere revisión humana separada de la aprobación
El sistema SHALL registrar en cada `narrativa_informe_razonado` si su contenido fue generado por IA (`generado_por_ia`) y SHALL NOT considerar una narrativa generada por IA como aprobada por el solo hecho de existir; requiere su propio registro de revisión humana (`revisado_por`, `revisado_en`), independiente de la aprobación de workflow de la ejecución completa.

#### Scenario: Registrar una narrativa generada por IA
- **WHEN** se registra una `narrativa_informe_razonado` con `generado_por_ia = true`
- **THEN** sus campos `revisado_por` y `revisado_en` quedan nulos hasta que un humano la revise explícitamente

### Requirement: Registrar evidencia de cada exportación de informe
El sistema SHALL registrar cada exportación de una `ejecucion_informe_razonado` (Word, PDF, Excel o HTML) en `exportaciones_informe_razonado`, incluyendo formato, ruta del archivo y responsable.

#### Scenario: Exportar un informe publicado
- **WHEN** se exporta una `ejecucion_informe_razonado` en un formato soportado
- **THEN** se crea una `exportacion_informe_razonado` con el formato, la ruta del archivo y el usuario responsable

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

### Requirement: Roles dedicados con separación de deberes para el flujo de informes
El sistema SHALL sembrar roles dedicados que materialicen la separación de deberes del flujo de informes razonados y reportabilidad, agrupando permisos existentes sin crear permisos nuevos:

- `gestor_reportabilidad` SHALL tener `reportabilidad.ver`, `reportabilidad.generar_corte`, `reportabilidad.publicar_corte` e `informes.ver`.
- `elaborador_informes` SHALL tener `informes.ver`, `informes.elaborar` e `informes.exportar`.
- `revisor_informes` SHALL tener `informes.ver`, `informes.aprobar`, `informes.publicar` e `informes.exportar`.

El rol `elaborador_informes` SHALL NOT tener `informes.aprobar` ni `informes.publicar`, y el rol `revisor_informes` SHALL NOT tener `informes.elaborar`, de modo que quien elabora un informe no pueda aprobarlo ni publicarlo, y quien aprueba/publica no lo elabore. El permiso `informes.administrar` (gestión de definiciones/plantillas) SHALL permanecer fuera de estos tres roles operativos. La siembra SHALL ser idempotente y aditiva, y SHALL NOT alterar el conjunto de permisos de los roles `admin` y `superadmin`, que conservan el acceso completo.

#### Scenario: Un elaborador no puede aprobar ni publicar
- **WHEN** se inspeccionan los permisos del rol `elaborador_informes`
- **THEN** incluye `informes.elaborar`
- **AND** no incluye `informes.aprobar` ni `informes.publicar`

#### Scenario: Un revisor no puede elaborar
- **WHEN** se inspeccionan los permisos del rol `revisor_informes`
- **THEN** incluye `informes.aprobar` e `informes.publicar`
- **AND** no incluye `informes.elaborar`

#### Scenario: El gestor de reportabilidad gobierna los cortes
- **WHEN** se inspeccionan los permisos del rol `gestor_reportabilidad`
- **THEN** incluye `reportabilidad.generar_corte` y `reportabilidad.publicar_corte`
- **AND** no incluye `informes.elaborar`, `informes.aprobar` ni `informes.publicar`

#### Scenario: La siembra de roles es idempotente y no altera admin
- **WHEN** se ejecuta el seeder de roles dedicados más de una vez
- **THEN** cada rol dedicado queda con exactamente su conjunto de permisos esperado
- **AND** el rol `admin` conserva el superset completo del flujo

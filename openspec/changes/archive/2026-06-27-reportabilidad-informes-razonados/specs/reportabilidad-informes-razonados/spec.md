## ADDED Requirements

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

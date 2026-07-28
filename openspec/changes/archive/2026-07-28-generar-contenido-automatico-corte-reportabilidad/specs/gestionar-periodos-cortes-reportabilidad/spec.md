## ADDED Requirements

### Requirement: Generar automáticamente el contenido de un corte de reportabilidad
El sistema SHALL permitir a los usuarios con el permiso `reportabilidad.generar_corte` generar el contenido de un `corte_reportabilidad` en estado `borrador`: para el período del corte, el sistema SHALL recolectar las entidades internas reportables del período (en este alcance, los `casos_pago_proveedor` cuyo `periodo` coincide con el `codigo` del `periodo_reportabilidad` del corte) y, por cada una, SHALL crear un `corte_reportabilidad_item` (vinculable polimórfico a la entidad, con una etiqueta) y capturar un `snapshot_corte_reportabilidad` con el `payload_crudo` serializado de la entidad, su `hash` SHA-256 y `capturado_en`, vinculado a ese item. Toda la escritura SHALL ocurrir dentro de una transacción. Generar el contenido de un corte **no** es una transición de workflow y no altera ningún `Proceso`. El generador SHALL diseñarse extensible a otras entidades reportables (p. ej. `egresos_cgu`) sin cambiar la interfaz.

#### Scenario: Generar el contenido de un corte en borrador con permiso
- **WHEN** un usuario con el permiso `reportabilidad.generar_corte` genera el contenido de un corte en estado `borrador` cuyo período tiene casos de pago a proveedor asociados
- **THEN** el sistema crea un `corte_reportabilidad_item` por cada entidad reportable del período, vinculado a esa entidad
- **AND** captura un `snapshot_corte_reportabilidad` con el payload crudo y el hash SHA-256 de cada entidad, vinculado a su item

#### Scenario: Regenerar reemplaza el contenido previo
- **WHEN** un usuario con el permiso `reportabilidad.generar_corte` genera el contenido de un corte en `borrador` que ya tenía items y snapshots
- **THEN** el sistema elimina los items y snapshots previos del corte y vuelve a capturarlos a partir del estado actual de las entidades del período
- **AND** el corte no queda con contenido duplicado

#### Scenario: No se puede generar contenido sobre un corte publicado
- **WHEN** un usuario intenta generar el contenido de un corte en estado `publicado`
- **THEN** el sistema bloquea la operación
- **AND** el corte conserva su contenido sin cambios

#### Scenario: Generar contenido sin el permiso requerido
- **WHEN** un usuario autenticado sin el permiso `reportabilidad.generar_corte` intenta generar el contenido de un corte
- **THEN** el sistema rechaza la solicitud con un error de autorización
- **AND** el corte no se modifica

#### Scenario: Generar contenido para un período sin entidades reportables
- **WHEN** un usuario con el permiso `reportabilidad.generar_corte` genera el contenido de un corte en `borrador` cuyo período no tiene entidades reportables
- **THEN** el sistema completa la operación sin crear items ni snapshots
- **AND** el corte permanece en `borrador` sin contenido

## MODIFIED Requirements

### Requirement: Mostrar el detalle de un corte
El sistema SHALL exponer el detalle de un `corte_reportabilidad` con su estado, período, cantidad de items y snapshots asociados, e incluir la lista de sus `corte_reportabilidad_items` con un resumen de la entidad vinculada (tipo, identificador y etiqueta), de modo que el frontend pueda mostrar el contenido generado del corte.

#### Scenario: Ver el detalle de un corte
- **WHEN** un usuario autenticado abre el detalle de un corte
- **THEN** la respuesta incluye su estado, período, cantidad de items y cantidad de snapshots

#### Scenario: El detalle lista los items con su entidad vinculada
- **WHEN** un usuario autenticado abre el detalle de un corte que tiene items generados
- **THEN** la respuesta incluye cada `corte_reportabilidad_item` con el tipo de su entidad vinculada, su identificador y su etiqueta

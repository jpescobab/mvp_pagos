# Spec: gestionar-periodos-cortes-reportabilidad

## Purpose

Permitir abrir períodos de reportabilidad, crear cortes dentro de ellos y publicarlos, como base institucional para los informes razonados (que solo pueden generarse sobre un corte ya publicado).

## Requirements

### Requirement: Abrir un período de reportabilidad
El sistema SHALL permitir a cualquier usuario autenticado abrir un `periodo_reportabilidad` con su código y rango de fechas.

#### Scenario: Abrir un período
- **WHEN** un usuario autenticado abre un período con código y fechas de inicio/fin
- **THEN** se crea un `periodo_reportabilidad` con estado `abierto`

### Requirement: Crear y publicar un corte de reportabilidad
El sistema SHALL permitir crear un `corte_reportabilidad` en estado `borrador` dentro de un período, y publicarlo solo a usuarios con el permiso `reportabilidad.publicar_corte`. Una vez publicado, el corte no SHALL admitir nuevos items ni snapshots.

#### Scenario: Crear un corte dentro de un período
- **WHEN** un usuario autenticado crea un corte para un `periodo_reportabilidad`
- **THEN** se crea un `corte_reportabilidad` en estado `borrador` asociado al período

#### Scenario: Publicar un corte con permiso
- **WHEN** un usuario con el permiso `reportabilidad.publicar_corte` publica un corte en borrador
- **THEN** el corte queda en estado `publicado`, con `publicado_por` y `publicado_en` registrados

#### Scenario: Publicar un corte sin permiso
- **WHEN** un usuario sin el permiso `reportabilidad.publicar_corte` intenta publicar un corte
- **THEN** el sistema bloquea la operación
- **AND** el corte permanece en estado `borrador`

### Requirement: Mostrar el detalle de un corte
El sistema SHALL exponer el detalle de un `corte_reportabilidad` con su estado, período, cantidad de items y snapshots asociados, e incluir la lista de sus `corte_reportabilidad_items` con un resumen de la entidad vinculada (tipo, identificador y etiqueta), de modo que el frontend pueda mostrar el contenido generado del corte.

#### Scenario: Ver el detalle de un corte
- **WHEN** un usuario autenticado abre el detalle de un corte
- **THEN** la respuesta incluye su estado, período, cantidad de items y cantidad de snapshots

#### Scenario: El detalle lista los items con su entidad vinculada
- **WHEN** un usuario autenticado abre el detalle de un corte que tiene items generados
- **THEN** la respuesta incluye cada `corte_reportabilidad_item` con el tipo de su entidad vinculada, su identificador y su etiqueta

### Requirement: Restringir el listado de períodos y cortes de reportabilidad
El sistema SHALL exigir el permiso `reportabilidad.ver` para listar los `periodo_reportabilidad` existentes y sus `corte_reportabilidad` asociados. Este permiso es distinto del ya existente `reportabilidad.publicar_corte`, que sigue gobernando exclusivamente la publicación de un corte.

#### Scenario: Listar con permiso
- **WHEN** un usuario con el permiso `reportabilidad.ver` visita el listado de períodos de reportabilidad
- **THEN** el sistema muestra los períodos existentes con sus cortes asociados

#### Scenario: Usuario sin permiso no puede listar
- **WHEN** un usuario autenticado sin el permiso `reportabilidad.ver` intenta visitar el listado de períodos de reportabilidad
- **THEN** el sistema rechaza la solicitud

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

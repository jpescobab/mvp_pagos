## ADDED Requirements

### Requirement: Restringir el listado de períodos y cortes de reportabilidad
El sistema SHALL exigir el permiso `reportabilidad.ver` para listar los `periodo_reportabilidad` existentes y sus `corte_reportabilidad` asociados. Este permiso es distinto del ya existente `reportabilidad.publicar_corte`, que sigue gobernando exclusivamente la publicación de un corte.

#### Scenario: Listar con permiso
- **WHEN** un usuario con el permiso `reportabilidad.ver` visita el listado de períodos de reportabilidad
- **THEN** el sistema muestra los períodos existentes con sus cortes asociados

#### Scenario: Usuario sin permiso no puede listar
- **WHEN** un usuario autenticado sin el permiso `reportabilidad.ver` intenta visitar el listado de períodos de reportabilidad
- **THEN** el sistema rechaza la solicitud

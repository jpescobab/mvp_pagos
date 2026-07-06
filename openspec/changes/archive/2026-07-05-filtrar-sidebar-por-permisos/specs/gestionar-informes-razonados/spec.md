## ADDED Requirements

### Requirement: Restringir el listado de definiciones y ejecuciones de informes razonados
El sistema SHALL exigir el permiso `informes.ver` para listar las `definicion_informe_razonado` y `ejecucion_informe_razonado` existentes. Este permiso es distinto de los ya existentes `informes.aprobar` e `informes.publicar`, que siguen gobernando exclusivamente esas transiciones de workflow.

#### Scenario: Listar con permiso
- **WHEN** un usuario con el permiso `informes.ver` visita el listado de definiciones o de ejecuciones de informes razonados
- **THEN** el sistema muestra el listado correspondiente

#### Scenario: Usuario sin permiso no puede listar
- **WHEN** un usuario autenticado sin el permiso `informes.ver` intenta visitar el listado de definiciones o de ejecuciones de informes razonados
- **THEN** el sistema rechaza la solicitud

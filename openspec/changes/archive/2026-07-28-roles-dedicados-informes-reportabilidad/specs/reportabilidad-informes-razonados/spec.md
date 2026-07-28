## ADDED Requirements

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

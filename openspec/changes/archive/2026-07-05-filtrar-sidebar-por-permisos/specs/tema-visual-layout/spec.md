## MODIFIED Requirements

### Requirement: Navegación principal como riel de íconos
El sistema SHALL presentar la navegación principal del sidebar como grupos colapsables por módulo funcional implementado, con la marca institucional (logo + "CAPJ +" + subtítulo "Finanzas y Ppto") en el encabezado, labels de grupo en mayúsculas, ítem activo destacado con fondo acentuado y barra lateral, y colapso del sidebar a modo ícono con tooltips. El sidebar SHALL seguir sin listar módulos funcionales que no tengan páginas implementadas. El sidebar SHALL además filtrar cada ítem individualmente según los permisos del usuario autenticado (`auth.permissions`), mostrando solo aquellos a los que el usuario tiene acceso, y SHALL ocultar un grupo completo si, tras filtrar, no le queda ningún ítem visible.

#### Scenario: Grupos por módulo implementado
- **WHEN** un usuario autenticado visualiza el sidebar principal
- **THEN** los ítems de navegación aparecen agrupados por módulo (General, Administración, Pago de Proveedores, Adquisiciones, Reportabilidad, Integraciones) y no como lista plana
- **AND** el grupo Administración incluye, además de las funciones de administración de usuarios y seguridad, los catálogos de consulta (Proveedores, Clientes Medidores, Centros Financieros, Centros de Costos)

#### Scenario: Ítem activo destacado
- **WHEN** el usuario navega a una página de un módulo
- **THEN** el ítem correspondiente del sidebar se muestra con estado activo destacado y su grupo aparece expandido

#### Scenario: Sin módulos no implementados
- **WHEN** un usuario autenticado visualiza el sidebar principal
- **THEN** no se muestran entradas para módulos sin páginas implementadas (p. ej. Presupuesto, Contabilidad, Mercado Público)

#### Scenario: Sin enlaces al scaffolding original
- **WHEN** un usuario autenticado visualiza el sidebar
- **THEN** no se muestran enlaces al repositorio o documentación de `laravel/react-starter-kit`

#### Scenario: Ítem oculto sin el permiso requerido
- **WHEN** un usuario autenticado sin el permiso que gobierna un ítem del sidebar (p. ej. `usuarios.ver`, `auditoria.ver`, `roles.administrar`, `core_institucional.administrar`, `reportabilidad.ver`, `informes.ver`) visualiza el sidebar principal
- **THEN** ese ítem no aparece en la navegación

#### Scenario: Grupo oculto si queda vacío tras filtrar
- **WHEN** un usuario autenticado no tiene permiso para ningún ítem de un grupo del sidebar
- **THEN** el grupo completo no se muestra

#### Scenario: Ítems de acceso abierto siguen visibles
- **WHEN** un usuario autenticado sin permisos administrativos visualiza el sidebar principal
- **THEN** sigue viendo los ítems cuya visibilidad es intencionalmente abierta a cualquier autenticado (Casos, Egresos CGU, Procesos de Adquisición, Conectores Playwright, Definiciones de Workflow, Importaciones SGF, Sistemas Externos, Indicadores Económicos)

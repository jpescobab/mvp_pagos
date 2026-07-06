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
El sistema SHALL exponer el detalle de un `corte_reportabilidad` con su estado, período, cantidad de items y snapshots asociados.

#### Scenario: Ver el detalle de un corte
- **WHEN** un usuario autenticado abre el detalle de un corte
- **THEN** la respuesta incluye su estado, período, cantidad de items y cantidad de snapshots

### Requirement: Restringir el listado de períodos y cortes de reportabilidad
El sistema SHALL exigir el permiso `reportabilidad.ver` para listar los `periodo_reportabilidad` existentes y sus `corte_reportabilidad` asociados. Este permiso es distinto del ya existente `reportabilidad.publicar_corte`, que sigue gobernando exclusivamente la publicación de un corte.

#### Scenario: Listar con permiso
- **WHEN** un usuario con el permiso `reportabilidad.ver` visita el listado de períodos de reportabilidad
- **THEN** el sistema muestra los períodos existentes con sus cortes asociados

#### Scenario: Usuario sin permiso no puede listar
- **WHEN** un usuario autenticado sin el permiso `reportabilidad.ver` intenta visitar el listado de períodos de reportabilidad
- **THEN** el sistema rechaza la solicitud

## Why

La jerarquía institucional CAPJ es `instituciones -> jurisdicciones -> cfinancieros -> ccostos` y gobierna permisos, filtros, reportes y trazabilidad de todo el sistema. Hoy solo los **dos niveles inferiores** son administrables: `cfinancieros` y `ccostos` tienen controlador, rutas, páginas React, policies, tests y auditoría; `instituciones` y `jurisdicciones` tienen modelo, migración y seeder, y nada más. Los dos niveles que están más arriba —de los que cuelga todo lo demás— solo se pueden modificar corriendo un seeder o entrando a la base de datos a mano.

Eso deja tres huecos concretos: no hay forma de dar de alta una jurisdicción nueva sin tocar código, no hay pantalla donde ver la jerarquía completa (el selector de jurisdicción al crear un centro financiero es la única superficie donde aparecen, y solo como opciones de un `<select>`), y una mutación en el nivel raíz —el único que no se puede hacer desde la app— es también la única que no queda auditada.

## What Changes

- **CRUD completo de instituciones**: listado con búsqueda y paginación, detalle, crear, editar, eliminar. El detalle muestra las jurisdicciones que dependen de la institución.
- **CRUD completo de jurisdicciones**: listado con búsqueda y paginación mostrando la institución padre, detalle, crear, editar, eliminar. El detalle muestra los centros financieros que dependen de la jurisdicción.
- **Eliminación protegida por dependencias**: eliminar una institución con jurisdicciones, o una jurisdicción con centros financieros, se rechaza con un mensaje explícito en vez de fallar contra la restricción de clave foránea (`restrictOnDelete`).
- **Mismo permiso que el resto de la estructura institucional**: `core_institucional.administrar` para listar, ver, crear, editar y eliminar. No se introducen permisos nuevos.
- **Auditoría de las mutaciones**: `Institucion` y `Jurisdiccion` pasan a registrar creación, edición y eliminación en `audit_logs` mediante el trait `RegistraAuditoria`, igual que las otras nueve tablas maestras.
- **Navegación**: dos ítems nuevos en el grupo "Estructura Institucional" del sidebar, por encima de Centros Financieros, de modo que la barra lateral refleje la jerarquía completa de arriba hacia abajo.

Sin migraciones, sin permisos nuevos, sin cambios de esquema. Todo el trabajo es capa de aplicación sobre tablas que ya existen.

## Capabilities

### New Capabilities

- `administracion-instituciones-jurisdicciones`: administración (listar, ver, crear, editar, eliminar) de los dos niveles superiores de la jerarquía institucional CAPJ, con búsqueda, unicidad por código institucional, protección ante eliminación con dependencias y restricción por el permiso `core_institucional.administrar`.

### Modified Capabilities

- `seguridad-auditoria`: el requirement "Auditar las mutaciones del catálogo de tablas maestras institucionales" enumera explícitamente las tablas cubiertas (centros financieros, centros de costo, proveedores, clientes medidores, ítems, tipos de documento, tipos de proceso de pago, asignaciones y catálogos). Se extiende para incluir instituciones y jurisdicciones, que hasta ahora quedaban fuera porque no eran mutables desde la aplicación.

## Impact

**Código nuevo**

- `app/Http/Controllers/Maestros/InstitucionController.php`, `JurisdiccionController.php`
- `app/Http/Requests/Maestros/{Store,Update}{Institucion,Jurisdiccion}Request.php`
- `app/Http/Resources/Maestros/{Institucion,Jurisdiccion}Resource.php`
- `app/Policies/{Institucion,Jurisdiccion}Policy.php`
- `resources/js/pages/maestros/{instituciones,jurisdicciones}/{index,show,create,edit}.tsx`
- `tests/Feature/Maestros/*{Institucion,Jurisdiccion}*Test.php`

**Código modificado**

- `routes/maestros.php`: dos bloques de rutas nuevos.
- `app/Models/Institucion.php`, `app/Models/Jurisdiccion.php`: trait `RegistraAuditoria` y relación `Jurisdiccion::cfinancieros()` ya existente reutilizada para la validación de dependencias.
- `app/Providers/AppServiceProvider.php`: registro manual de las dos policies nuevas (no hay auto-discovery).
- `resources/js/components/app-sidebar.tsx`: dos ítems de navegación.
- Wayfinder: regenerar `resources/js/routes/maestros/**` y `resources/js/actions/**`.

**Dependencias entre changes**

El trait `RegistraAuditoria` proviene del change `auditar-crud-tablas-maestras` (PR #27, aún sin fusionar a `master`). Este change SHALL implementarse sobre una rama que lo contenga; si aquel se revierte, la parte de auditoría de este queda sin base.

**Sin impacto en**: esquema de base de datos, permisos, seeders existentes, workflow, SGF ni integraciones externas. La jerarquía institucional sigue siendo fija en su forma (cuatro niveles, en ese orden); lo que cambia es quién puede editar sus dos primeros niveles y desde dónde.

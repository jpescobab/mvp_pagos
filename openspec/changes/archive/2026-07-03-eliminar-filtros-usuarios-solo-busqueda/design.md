## Context

`UserController::index()` hoy acepta seis parámetros de consulta (`search` + cinco filtros institucionales) y comparte `catalogs` (roles, jurisdicciones, centros financieros, centros de costo) para poblar los `<Select>` de `UserFilters`. El pedido es reducir esto a solo búsqueda, siguiendo el mismo espíritu minimalista que ya aplican Proveedores, Centros Financieros y Centros de Costos (búsqueda simple, sin filtros adicionales).

## Goals / Non-Goals

**Goals:**
- Que el índice de Usuarios solo tenga un input de búsqueda, sin selects de filtro ni botón "Limpiar filtros".
- No dejar código muerto: eliminar el componente `UserFilters`, los campos de tipo ya no usados, y la parte de `catalogos()` que solo servía al filtro eliminado (`jurisdicciones`).

**Non-Goals:**
- No se toca el orden (`sort`/`direction`) ni la paginación (`per_page`) — no fueron mencionados y no son filtros.
- No se tocan `create`/`edit` de usuario ni sus catálogos (`roles`, `centros_financieros`, `centros_costos`), que siguen usando `catalogos()` para los selects del formulario.

## Decisions

1. **Eliminar `UserFilters` por completo, no dejarlo con un solo campo de búsqueda.**
   El componente existe únicamente para los cinco filtros; con la búsqueda como único control, se integra directamente en `usuarios/index.tsx` con un `<Input>` simple, igual al patrón de `proveedores/index.tsx`. Mantener `UserFilters` como wrapper de un solo input sería una capa sin propósito.

2. **`catalogos()` pierde `jurisdicciones` pero no se elimina el método.**
   `create()` y `edit()` siguen necesitando `roles`, `centros_financieros` y `centros_costos` para asignar la institucionalidad de un usuario. Solo se quita la clave que ya no consume nadie.

3. **El mensaje de "sin resultados" se simplifica a un solo caso (antes distinguía "sin usuarios registrados" de "sin resultados por filtros").**
   Con un único control (la búsqueda), basta distinguir "no hay usuarios" de "la búsqueda no encontró nada", igual que ya hacen Proveedores/Clientes Medidores con su mensaje "Sin … que coincidan.".

## Risks / Trade-offs

- [Riesgo] Se pierde la capacidad de acotar el listado por institucionalidad (útil para admins con muchos usuarios) → Mitigación: es la instrucción explícita del usuario; si se necesita a futuro, se puede reintroducir como un change nuevo sin arrastrar este código.
- [Riesgo] El test `tests/Feature/Seguridad/UserControllerTest.php` tenía un caso dedicado a los filtros institucionales → Mitigación: se elimina ese test (la capacidad ya no existe) y se ajustan las aserciones que esperaban `catalogs` en el índice.

## Migration Plan

Sin migraciones de base de datos. Cambio de controlador + frontend + tests; rollback trivial revirtiendo el commit.

## Context

`Proveedor` normaliza el RUT al guardarlo (mutator `rutproveedor` → `Proveedor::normalizarRut()`: sin puntos, con guión, DV en mayúscula), para que un mismo RUT no genere duplicados sin importar el origen. Pero `ProveedorController::index()` busca con `where('rutproveedor', 'like', "%{$q}%")` usando el término crudo, por lo que un RUT con puntos nunca coincide con el valor almacenado sin puntos.

## Goals / Non-Goals

**Goals:**
- Que buscar por RUT con o sin puntos/guión encuentre al proveedor.

**Non-Goals:**
- Cambiar cómo se almacena el RUT (sigue normalizado).
- Cambiar la búsqueda por nombre.
- Tocar los otros lugares que buscan por RUT (ya normalizan el término).

## Decisions

### D1 — Normalizar el término de RUT con la misma función que el almacenamiento
En `index()`, se calcula `Proveedor::normalizarRut($q)` y se agrega una cláusula `orWhere('rutproveedor', 'like', "%{$rutNormalizado}%")` **solo cuando** esa normalización no queda vacía. Se conservan las comparaciones existentes (`rutproveedor LIKE %término%` y `nombre LIKE %término%`), de modo que la búsqueda por nombre y por RUT ya sin puntos siguen funcionando igual, y se suma la coincidencia por RUT normalizado. **Alternativa descartada:** normalizar el campo en SQL con `REPLACE()` — menos portable y más costoso que normalizar el término una vez en PHP.

### D2 — Guardar contra el caso "término no es un RUT"
`normalizarRut()` de un texto sin dígitos ni K devuelve `''`. La cláusula extra se aplica solo si `rutNormalizado !== ''`, evitando un `LIKE "%%"` que traería todo el catálogo cuando el usuario busca un nombre.

## Risks / Trade-offs

- [Un término parcial de RUT normalizado podría traer coincidencias amplias] → Es el comportamiento esperado de una búsqueda por coincidencia (`LIKE %...%`), igual que hoy para el nombre.

## Migration Plan

Cambio de una sola consulta + tests; sin migraciones ni pasos de despliegue. Rollback = revertir el código.

## Open Questions

_(ninguna)_

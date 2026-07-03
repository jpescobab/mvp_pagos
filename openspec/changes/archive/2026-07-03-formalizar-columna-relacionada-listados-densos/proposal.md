## Why

Las vistas `maestros/cfinancieros/index.tsx` y `maestros/ccostos/index.tsx` (change `consulta-catalogo-centros-financieros-costos`, ya archivado) introdujeron dos patrones que el requirement "Listados tabulares densos" de `tema-visual-layout` todavía no describe explícitamente: una columna secundaria que muestra el nombre de una entidad relacionada (jurisdicción/centro financiero padre) y el indicador `"—"` para un campo opcional en `null`. `HARNESS_IA.md` ya se actualizó para apuntar a estas dos páginas como implementación de referencia; falta formalizar el mismo detalle en la spec para que quede como convención obligatoria y no solo como ejemplo de código.

## What Changes

- Agregar dos escenarios al requirement "Listados tabulares densos" de `tema-visual-layout`: columna con entidad relacionada, y valor `null` en columna opcional mostrado como `"—"`.
- Sin cambios de código: el comportamiento ya está implementado y testeado en `consulta-catalogo-centros-financieros-costos`; este change solo formaliza el patrón como convención general para listados futuros.

## Capabilities

### Modified Capabilities
- `tema-visual-layout`: el requirement "Listados tabulares densos" incorpora los patrones de columna con entidad relacionada y de valor opcional nulo.

## Impact

- Código: ninguno.
- Documentación: `openspec/specs/tema-visual-layout/spec.md` (vía este change) y `HARNESS_IA.md` (ya actualizado directamente, referencia el mismo requirement).

## Context

Este change es puramente documental: formaliza en la spec un patrón de UI que ya existe, ya está implementado (`cfinancieros/index.tsx`, `ccostos/index.tsx`) y ya está testeado (change `consulta-catalogo-centros-financieros-costos`). No hay decisión técnica nueva que tomar; el propósito es que la spec deje de estar un paso por detrás del código de referencia que `HARNESS_IA.md` ya señala.

## Goals / Non-Goals

**Goals:**
- Que el requirement "Listados tabulares densos" describa, con el mismo nivel de detalle que el resto de sus escenarios, el patrón de columna con entidad relacionada y el fallback `"—"` para valores opcionales nulos.

**Non-Goals:**
- No se cambia ningún archivo de código ni de test.
- No se introduce ningún patrón nuevo no probado; solo se documenta lo ya construido.

## Decisions

- Se agregan los dos escenarios directamente al requirement existente "Listados tabulares densos" (no se crea un requirement nuevo), porque ambos son extensiones del mismo comportamiento de listado denso, no una capacidad distinta.

## Risks / Trade-offs

Ninguno relevante: cambio de documentación sin efecto en tiempo de ejecución.

## Context

`lib/format.ts`/`<Monto>` ya implementan la convención de formato numérico institucional y ya están adoptados en la enorme mayoría de la app. Este cambio es el remate puntual de esa migración (contadores de paginación) más su documentación retroactiva en spec.

## Goals / Non-Goals

**Goals:**
- Los contadores de paginación usan el mismo formato `es-CL` que el resto de los números de la app.
- La convención de formato numérico queda documentada como requirement verificable.

**Non-Goals:**
- No se crea ninguna utilidad ni componente nuevo — se reutiliza `formatNumero()` ya existente.
- No se toca ningún lugar que ya use `<Monto>`/`formatMonto`/`formatPorcentaje`/`formatNumero`.
- No se consolida la paginación inline duplicada en el componente compartido `shared/pagination.tsx` — eso es un refactor de estructura aparte, fuera de alcance de un cambio de formato numérico.

## Decisions

**1. `formatNumero()`, no `<Monto variante="numero">`, para los contadores de paginación.**
Los contadores (`from`, `to`, `total`) son siempre enteros no negativos — no necesitan el coloreado condicional de negativos que aporta `<Monto>`. Usar la función directamente es más liviano y evita importar un componente con lógica que nunca se ejercita en este contexto.

**2. Corregir tanto las 11 páginas con paginación inline como el componente compartido `shared/pagination.tsx`, sin migrar las 11 páginas a usar el componente compartido.**
Son dos problemas distintos: el formato numérico (alcance de este cambio) y la duplicación de estructura entre 11 páginas y el componente compartido (un refactor de arquitectura que no se pidió y que ameritaría su propio cambio, con sus propias decisiones sobre props/API). Mezclar ambos infla el alcance sin necesidad.

## Risks / Trade-offs

- [Ninguno relevante] → Cambio puramente visual, sin lógica de negocio ni datos involucrados; el peor caso posible es un `npm run types:check`/`lint:check` fallido, detectable de inmediato.

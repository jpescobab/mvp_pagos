## Context

`CoreInstitucionalSeeder` ya sembró la institución CAPJ y una sola jurisdicción (`14`, "Zonal Coyhaique"). El usuario aportó la lista real completa de jurisdicciones desde otro proyecto Laravel (`trifu`), con una columna `descripcion` que no existe en nuestra migración actual.

## Goals / Non-Goals

**Goals:**
- Sembrar las 20 jurisdicciones reales sin duplicar ni sobrescribir la jurisdicción `14` ya existente.
- Agregar la columna `descripcion` a `jurisdicciones` para alinear el esquema con el dato de origen, aunque hoy venga siempre `null`.
- Sembrar `00` como jurisdicción con el nombre completo de CAPJ, por decisión explícita del usuario (no se fusiona con la tabla `instituciones`).

**Non-Goals:**
- No se modela una relación formal entre la jurisdicción `00` y la institución CAPJ más allá de la FK estándar `institucion_id` que ya tienen todas las jurisdicciones.
- No se rellena contenido real en `descripcion` — queda `null` para las 20 filas, igual que en el origen.

## Decisions

- **`firstOrCreate` por `codigo`** para las 20 filas. Evita sobrescribir la jurisdicción `14` ya sembrada con nombre "Zonal Coyhaique" (la lista de origen dice solo "Coyhaique"; el usuario confirmó mantener el nombre ya aprobado). Alternativa descartada: `upsert`/`updateOrCreate`, que habría sobrescrito ese nombre en cada corrida.
- **Migración separada para `descripcion`** (`add_descripcion_to_jurisdicciones_table`) en vez de modificar la migración original de la tarea 1 — esa migración ya está aplicada y archivada como parte de un change cerrado; alterarla retroactivamente rompería el principio de no reescribir historial ya aplicado.
- **`00` se siembra igual que las demás**, vía el mismo seeder y el mismo `institucion_id` (CAPJ), sin tratamiento especial en código — la única particularidad es de datos (nombre completo en vez de abreviado), no de estructura.

## Risks / Trade-offs

- **[Riesgo] La jurisdicción `00` duplica conceptualmente a la institución CAPJ** (ambas representan la misma entidad real) → Mitigación: aceptado explícitamente por el usuario; se documenta aquí para que quede claro que es una decisión consciente y no un error de modelado.
- **[Riesgo] `descripcion` queda sin uso real por ahora** → Mitigación: es nullable y no rompe nada; se puede poblar más adelante sin otra migración.

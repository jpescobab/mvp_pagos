## Context

El repositorio es hoy el scaffolding del starter kit (`laravel/react-starter-kit`): solo existen `users`, `cache`, `jobs` y la capa de auth/Fortify. No hay ningún dominio institucional implementado todavía. Esta es la primera tabla/modelo de negocio del proyecto y la base de la que dependen por FK casi todos los dominios posteriores (permisos, indicadores, workflow, pagos, reportabilidad).

## Goals / Non-Goals

**Goals:**
- Modelar la jerarquía `instituciones -> jurisdicciones -> cfinancieros -> ccostos` exactamente como la fija el harness, sin variaciones.
- Dejar sembrada la institución CAPJ y la jurisdicción inicial (código `14`) para que el resto de los dominios tengan un registro válido sobre el cual anclarse.
- Dejar la jerarquía cubierta por tests que sirvan de contrato para los dominios que se construirán encima.

**Non-Goals:**
- No se modelan permisos, roles ni ningún dato de seguridad (tarea 03).
- No se modelan tablas maestras adicionales (`proveedores`, `funcionarios`, etc. — tarea 02).
- No hay UI/React todavía; esta tarea es solo backend (migraciones, modelos, seeder, tests).

## Decisions

- **`$table` explícito en los 4 modelos.** El inflector de Laravel pluraliza en inglés (`institucion` -> `institucions`), lo que no coincide con los nombres de tabla en español fijados por el harness (`instituciones`, `jurisdicciones`). Declarar `$table` evita depender de una coincidencia accidental.
- **FK con `onDelete('restrict')`** en vez de `cascade`. Alternativa considerada: `cascade` (más simple, pero permite borrar en cadena toda una jurisdicción con sus centros financieros y de costo sin aviso). Se elige `restrict` porque el harness exige no romper trazabilidad — borrar un nivel de la jerarquía con hijos asociados debe ser una operación explícita y consciente, no un efecto secundario.
- **`codigo` de la institución CAPJ = `'CAPJ'`.** No hay un código numérico oficial definido en el harness; se usa un valor legible y estable en vez de inventar una codificación numérica no documentada.
- **Jurisdicción inicial: `codigo='14'`, `nombre='Zonal Coyhaique'`.** El código `14` es una regla explícita del harness (`jurisdicciones.codigo` default `14`); el nombre fue confirmado directamente por el usuario.
- **`activo` boolean en las 4 tablas**, aunque el spec original solo lo menciona explícitamente para la institución ("institución CAPJ activa"). Se aplica a las 4 por consistencia, ya que son tablas maestras paralelas del mismo nivel de jerarquía y van a necesitar soft-disable más adelante (reorganizaciones institucionales) sin perder histórico.

## Risks / Trade-offs

- **[Riesgo] `restrict` bloquea reorganizaciones legítimas** (ej. fusionar dos centros de costo) → Mitigación: cualquier reorganización futura se modela como un nuevo registro + marcar el anterior `activo=false`, nunca como un DELETE directo, consistente con "no romper trazabilidad".
- **[Riesgo] El código `'CAPJ'` y el nombre `'Zonal Coyhaique'` son valores de seed, no parametrizables todavía** → Mitigación: aceptable para esta tarea (un solo registro semilla); si se necesitan más jurisdicciones reales, se agregan vía seeder/admin en una tarea posterior, no se hardcodea más lógica.

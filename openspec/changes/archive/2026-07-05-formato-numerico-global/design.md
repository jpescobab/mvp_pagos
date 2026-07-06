## Context

El tema visual ya define tokens semánticos de color en `resources/css/app.css` (`--color-destructive` / clase Tailwind `text-destructive`, usada hoy para badges de estado "danger" como `proveedor-status-badge.tsx`, `cfinanciero-status-badge.tsx`, etc.) y ya define `--font-mono: 'JetBrains Mono', ...`, usada como convención en 36 archivos para códigos/identificadores/fechas (ej. `font-mono text-xs` en `indicadores-economicos/index.tsx:124`). No existe hoy ningún helper de formateo numérico compartido: `dashboard.tsx` y `lib/indicadores.ts` usan `Intl.NumberFormat('es-CL', ...)` in-line con distintas opciones cada uno, y el resto de las vistas de detalle interpola números crudos (`{monto}`) sin formatear miles ni decimales ni usar una tipografía que evite ambigüedad entre dígitos.

Al ser una app financiera, el usuario pidió explícitamente que el formato de cifras sea "clarísimo, nítido y sin lugar a confusiones" para el usuario, dando como ejemplo la confusión entre `0` y `8`. Esto se suma al requirement de formato (miles/decimales) y color de negativos ya definidos.

`Intl.NumberFormat('es-CL')` ya produce por defecto separador de miles `.` y decimal `,` (es el comportamiento nativo del locale chileno), así que no hace falta reimplementar el parsing — el trabajo real es centralizar las opciones (moneda, porcentaje, cantidad de decimales) y resolver el color rojo para negativos, que hoy no existe en ningún punto.

## Goals / Non-Goals

**Goals:**
- Un único módulo (`resources/js/lib/format.ts`) con funciones puras de formateo: `formatNumero(valor, opciones?)`, `formatMonto(valor, opciones?)` (con símbolo `$` y sin decimales por defecto, como ya hace `dashboard.tsx`), `formatPorcentaje(valor, decimales?)`.
- Un componente presentacional (`resources/js/components/ui/monto.tsx`, export `<Monto>`) que consume esas funciones, renderiza siempre con `font-mono` (tipografía monoespaciada del tema, dígitos de ancho uniforme y sin ambigüedad 0/8/1/l) y aplica `text-destructive` cuando `valor < 0`, sin color adicional cuando `valor >= 0` (hereda el color de texto del contexto, no fuerza verde/negro).
- Migrar los puntos de formateo numérico detectados en la exploración inicial (dashboard, indicadores, casos de pago proveedores, ejecuciones de informes razonados, procesos de adquisiciones, importaciones SGF, auditoría, tabla de usuarios) a estas funciones/componente.
- Documentar la convención en `openspec/specs/tema-visual-layout/spec.md` como Requirement nuevo.

**Non-Goals:**
- No se toca el formateo de fechas (`toLocaleString()` sin argumentos para timestamps) — es un problema aparte, no forma parte de "formato numérico".
- No se agrega un token de color nuevo al tema — se reutiliza `text-destructive`, que ya es el rojo semántico "danger" existente.
- No se migra automáticamente cualquier número que aparezca en el código (ids, años, códigos institucionales, teléfonos, etc.) — el requirement aplica a magnitudes/montos/cantidades mostradas como dato numérico de negocio, no a identificadores.
- No se cambia ninguna lógica de negocio, cálculo ni fuente de datos — es puramente presentación.

## Decisions

- **Locale fijo `es-CL` vía `Intl.NumberFormat`, no una librería externa.** Ya es el patrón usado en `dashboard.tsx` y `lib/indicadores.ts`; agregar una dependencia (ej. `numeral`, `numbro`) sería una capa innecesaria para algo que el `Intl` nativo ya resuelve, y CLAUDE.md pide no agregar dependencias sin aprobación.
- **Componente `<Monto>` separado de las funciones puras de `lib/format.ts`.** Las funciones puras se pueden usar donde no se necesita color (ej. tooltips, exports, texto plano dentro de una oración), mientras que `<Monto>` es para el caso común de "renderizar un valor numérico de negocio en una celda/celda de tabla/tarjeta KPI" con el color rojo automático. Evita forzar JSX donde solo se necesita un string.
- **Rojo = `text-destructive` existente, no un nuevo `--color-negativo`.** Ya es el rojo "danger" usado en badges de estado; introducir un segundo token rojo sería inconsistente con la convención de tema existente y con la instrucción del harness de no reinventar tokens ya establecidos.
- **Sin decimales por defecto en `formatMonto`, configurable vía opción.** Replica el comportamiento actual de `dashboard.tsx` (`maximumFractionDigits: 0`) que es el caso más común (montos en pesos chilenos no usan centavos en la práctica de la app); `lib/indicadores.ts` seguirá pudiendo pedir explícitamente más decimales para UF/dólar.
- **Las funciones aceptan `number | string | null | undefined`.** Los campos `decimal` de Laravel se serializan como string (ej. `"69542.0000"`, ver `indicadores-economicos/index.tsx`), y varios montos pueden venir `null` cuando el dato es opcional; el helper hace `Number(valor)` internamente y devuelve `'—'` (fallback ya usado en el resto de la app, ver convención de listados densos) cuando el valor es `null`/`undefined`/`NaN`, en vez de que cada página repita ese chequeo.
- **`<Monto>` fuerza `font-mono` (JetBrains Mono) en vez de dejarlo opcional o heredado del contexto.** Alternativas consideradas: (a) dejar que cada página decida si aplica `font-mono` — se descarta porque el pedido explícito es "sin lugar a confusiones" en *todo* número de negocio, y dejarlo opcional garantiza que algún sitio lo omita; (b) usar `font-feature-settings: 'tnum'` sobre la fuente sans (`Manrope`) para solo alinear ancho de dígito sin cambiar de tipografía — se descarta porque Manrope no está diseñada para distinguir `0`/`8`/`1`/`l` tan claramente como una monoespaciada de código, y JetBrains Mono ya es la fuente monoespaciada oficial del tema (no se agrega ninguna fuente nueva). `<Monto>` es el único punto de entrada para cifras de negocio, así que forzar la fuente ahí no duplica la decisión en cada página.

## Risks / Trade-offs

- [Riesgo: al centralizar, algún caso de uso actual pierde una opción muy específica de formateo (ej. algún indicador con reglas particulares de redondeo)] → Mitigación: `formatMonto`/`formatNumero` aceptan un segundo argumento de opciones que se pasa a `Intl.NumberFormat`, para no perder flexibilidad caso por caso; se revisa cada sitio migrado contra su comportamiento actual antes de reemplazar.
- [Riesgo: migrar ~9 archivos en un solo change es superficie amplia para introducir una regresión visual] → Mitigación: migración archivo por archivo en `tasks.md`, verificación visual con el preview de cada vista tras el cambio (no solo `types:check`).
- [Riesgo: "número mostrado a un usuario" es ambiguo y alguien podría aplicar `<Monto>` a un id o código] → Mitigación: el Requirement en el spec aclara que aplica a magnitudes de negocio (montos, cantidades, indicadores, KPIs), no a identificadores.
- [Riesgo: forzar `font-mono` en cifras dentro de texto corrido (ej. una oración con un monto inline) puede verse inconsistente con el resto de la tipografía sans de esa oración] → Mitigación: es el mismo trade-off ya aceptado hoy para códigos/fechas en 36 archivos existentes (`font-mono text-xs` dentro de filas de tabla junto a texto sans); se verifica visualmente en el preview que no se vea descolocado antes de dar por cerrada la migración de cada vista.

## Migration Plan

1. Crear `resources/js/lib/format.ts` y `resources/js/components/ui/monto.tsx`.
2. Migrar `dashboard.tsx` y `lib/indicadores.ts` primero (ya tienen formateo es-CL, es el reemplazo más directo).
3. Migrar el resto de vistas de detalle (pago-proveedores, informes-razonados, adquisiciones, sgf, auditoría, users-table) agregando formateo donde hoy no existe.
4. Actualizar `openspec/specs/tema-visual-layout/spec.md` (delta spec de este change) con el nuevo Requirement.
5. Verificar visualmente cada vista migrada en el preview y correr `npm run lint:check` / `npm run types:check`.

No hay rollback especial: son cambios de presentación en componentes React, revertibles con `git revert` si hiciera falta.

## Open Questions

Ninguna — la imagen de referencia del usuario confirmó el caso concreto a corregir (`indicadores-economicos/index.tsx`, valor `69542.0000` sin formatear) y el requisito adicional de legibilidad tipográfica (evitar confusión entre dígitos como `0`/`8`) quedó resuelto reutilizando `font-mono`/JetBrains Mono, ya existente en el tema.

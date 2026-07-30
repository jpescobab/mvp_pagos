## Context

El módulo Presupuesto parte de cero: no hay modelos, tablas, rutas ni permisos. La formulación y
aprobación del presupuesto ocurren en CGU, un sistema externo que este proyecto no gobierna —
mismo principio ya aplicado a SGF ("origen, no gobierno"). CGU entrega el presupuesto asignado
como un reporte Excel exportado manualmente; no hay API.

Se dispone de una muestra real de ese Excel (columnas: `Nro.Versión, Catalogo, Descripción,
P.Pptario., U.Ejecutora, PROG, SUBPR, ACTIV, TAREA, Enero...Diciembre, Total Proyectado,
Ppto.Vigente`) y de evidencia operativa real adicional (dos PDF de Certificados de Disponibilidad
Presupuestaria — CDP — emitidos por CAPJ, y la planilla de control de 142 CDP que la zonal
Coyhaique lleva hoy) que valida varias decisiones de este change, aunque el CDP en sí es el
siguiente change de la secuencia.

El clasificador presupuestario institucional (`items` → `asignaciones`/`catalogos`) ya existe
(`tablas-maestras-institucionales`) pero solo tiene sembrado el Subtítulo 22 (bienes y servicios de
consumo). La evidencia real muestra uso activo de Subtítulo 29 (activos no financieros) y 31
(iniciativas de inversión) — sin ampliarlo, el Excel real y los futuros CDP de esos subtítulos no
tienen `catalogo_id` contra el cual imputar.

## Goals / Non-Goals

**Goals:**
- Importar el presupuesto asignado (`Ppto.Vigente`) y el catálogo de planes de tarea desde el
  Excel de CGU, con snapshot inmutable del archivo original.
- Modelar las líneas de presupuesto al grano correcto: `cfinanciero × catalogo × plan_tarea × año`.
- Soportar reimportación cuando CGU emite una versión nueva del mismo año, sin perder historial.
- Ampliar el clasificador presupuestario (Subtítulo 29 y 31) para que el Excel real importe sin
  filas huérfanas.
- Dar visibilidad: pantalla de importación + listado de líneas de presupuesto con su saldo.

**Non-Goals:**
- El CDP (Certificado de Disponibilidad Presupuestaria), su ciclo borrador→firmado, y el
  compromiso/ejecución contra estas líneas — es el siguiente change de la secuencia
  (`presupuesto-certificado-disponibilidad`). Este change solo deja las líneas de presupuesto
  disponibles con su `monto_asignado`; el saldo (`asignado − comprometido − ejecutado`) se calcula
  ahí, no acá (acá el "saldo" mostrado es simplemente `monto_asignado`, sin movimientos todavía).
- Ampliar el clasificador a Subtítulo 24 o 33 — no hay evidencia real de uso por CAPJ; se agregan
  solo 29 y 31, confirmados por el usuario.
- Multi-moneda (CLP/UF/USD) y su `paridad` — el presupuesto asignado de CGU siempre viene en CLP;
  eso es un campo del CDP, no de esta importación.
- Un lector de Excel genérico o reutilizable para otros dominios — se escribe específico para el
  formato de columnas de CGU, sin abstraer prematuramente.

## Decisions

### El nivel es `cfinanciero`, no `ccosto`
`U.Ejecutora` en el Excel corresponde al código de `cfinanciero` (verificado: `U.Ejecutora=1400`
calza con `CfinancierosSeeder`; los `ccosto` son de 10 dígitos). Confirmado además en los CDP
reales (`Unidad Ejecutora: COYHAIQUE` + `N° UE: 14`). Modelar `presupuestos.cfinanciero_id` en vez
de `ccosto_id` evita una distribución interna que CGU no entrega y que nadie pidió.

### `planes_tarea` es un catálogo independiente, no hijo de `catalogo`
La misma tupla `PROG/SUBPR/ACTIV/TAREA` (ej. `PEGV`) se repite bajo cuentas distintas en el Excel
real — un `catalogo` puede combinarse con varios `plan_tarea` y viceversa. La relación
cuenta↔plan nace en la línea de presupuesto (`presupuestos`), no en el catálogo de planes.
Alternativa descartada: `planes_tarea.catalogo_id` — no soporta el caso real observado.

### El monto asignado es `Ppto.Vigente`, no `Total Proyectado`
Verificado con datos reales: la cuenta `2203002000` tiene los 12 meses en 0 y `Total
Proyectado=0`, pero `Ppto.Vigente=124.085`. Usar `Total Proyectado` dejaría líneas con presupuesto
real mostrando saldo cero. La programación mensual (`Enero`...`Diciembre`) no se persigue en este
change — no hay consumidor definido para ella todavía (evitar estructura sin necesidad).

### Reutilizar la capa transversal de integraciones para el snapshot, sistema `CGU`
`sistemas_externos` ya tiene sembrado `CGU` (`tipo_integracion: manual`, `activo: false`) en
`IntegracionesSeeder`. Este change lo activa y usa `IntegracionExternaService` +
`snapshots_datos_externos` (método de captura `excel`, ya soportado por el enum existente) — mismo
mecanismo que SGF y Mercado Público, sin inventar una tabla de importación paralela a
`trabajos_integracion`. `importaciones_presupuesto` es una tabla propia del dominio (guarda
`nro_version`, totales de filas procesadas/omitidas/erróneas — dato específico de esta
importación) que se relaciona 1:1 con el `trabajo_integracion` que la originó, siguiendo el mismo
patrón que `IndicadorEconomicoImportacion`.

### Reimportación por versión, sin tocar histórico
El Excel trae `Nro.Versión`. Una nueva importación para el mismo año actualiza `monto_asignado` de
las líneas existentes (`upsert` por `cfinanciero_id + catalogo_id + plan_tarea_id + anio`) y
registra una `importacion_presupuesto` nueva con su propio snapshot — no se sobrescribe ni se
borra el snapshot anterior (coherente con "snapshot obligatorio": nunca se sobrescribe evidencia).
El siguiente change (CDP) es responsable de decidir qué pasa con compromisos ya firmados si el
monto baja; fuera de alcance acá.

### Ampliar el clasificador es prerequisito de datos, no un requirement nuevo
`tablas-maestras-institucionales` ya modela `items`/`asignaciones`/`catalogos` de forma genérica,
sin restringir a un subtítulo. Sembrar Subtítulo 29 y 31 es una extensión de datos (mismo shape,
mismos seeders `ItemsSeeder`/`AsignacionesSeeder`/`CatalogosSeeder`), no un cambio de
comportamiento — por eso no hay `Modified Capabilities` sobre esa spec en este change.

### Lector de Excel específico, no genérico
`phpoffice/phpspreadsheet` está instalado pero hoy solo se usa para **escribir**
(`ExportadorInformeRazonadoService`). Este es el primer lector del proyecto. Se escribe acoplado al
formato de columnas de CGU (`LectorExcelPresupuestoCgu`) en vez de una abstracción reutilizable —
no hay un segundo caso de uso hoy que la justifique.

## Risks / Trade-offs

- **[Riesgo] El formato del Excel de CGU puede variar entre exportaciones** (orden de columnas,
  nombres, hojas adicionales) → **Mitigación**: el lector valida encabezados esperados antes de
  procesar y falla explícito (fila/columna) en vez de importar datos mal mapeados; los totales de
  la `importacion_presupuesto` (recibidos/creados/omitidos/fallidos) quedan visibles para
  detectarlo rápido, mismo patrón que `IndicadorEconomicoImportacion`.
- **[Riesgo] Confundir `Programa Presupuestario` (fuente de financiamiento, 100/200) con `plan de
  tarea`** → **Mitigación**: son conceptos distintos confirmados con evidencia real; este change
  NO modela `Programa Presupuestario` (es un campo del CDP, no del presupuesto importado) —
  documentado explícitamente en Non-Goals para que el siguiente change no lo dé por resuelto acá.
- **[Trade-off] No hay ccosto en la línea de presupuesto** → Los procesos de adquisición sí tienen
  `ccosto_id`. El change del CDP deberá resolver la cuenta/línea por el `cfinanciero` del ccosto
  de la adquisición (`ccosto.cfinanciero_id`), no por el ccosto directo — a tener en cuenta ahí,
  no bloquea este change.
- **[Trade-off] Sin multi-moneda en esta importación** → El presupuesto de CGU es en CLP; si en el
  futuro CGU exportara otra moneda, este change no lo contempla. Bajo riesgo: no hay evidencia de
  eso hoy.

## Migration Plan

- Migraciones nuevas (`planes_tarea`, `presupuestos`, `importaciones_presupuesto`) — sin datos que
  migrar, tablas nuevas.
- Seeders: `PresupuestoSeeder` (permisos nuevos, idempotente); ampliar
  `ItemsSeeder`/`AsignacionesSeeder`/`CatalogosSeeder` con `firstOrCreate` por `codigo` (mismo
  patrón ya usado, aditivo — no rompe lo sembrado hoy); activar `CGU` en `IntegracionesSeeder`
  (cambia `activo: false` → `true`, aditivo).
- Sin rollback especial: son tablas y datos nuevos: `php artisan migrate:rollback` estándar basta
  si hace falta revertir antes de llegar a producción.

## Open Questions

- Formato exacto de encabezados a validar en el lector (nombres de columna, posible variación
  entre exportaciones de CGU) — se resuelve calibrando contra la muestra real ya en mano al
  implementar (`/opsx:apply`), no bloquea el proposal/design.
- Si CGU llega a exportar Subtítulo 24 o 33 en el futuro, se ampliará el clasificador en ese
  momento con la misma mecánica — no se preseeda sin evidencia.

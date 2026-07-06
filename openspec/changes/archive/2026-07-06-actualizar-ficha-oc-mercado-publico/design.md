## Context

La ficha de detalle de una Orden de Compra de Mercado Público ya sigue el orden de secciones definido en `paginas-ordenes-compra-mercado-publico` (cronograma como segunda sección). Lo que falta para calzar con el formato de referencia entregado por el usuario es puramente de presentación y de fidelidad de dato: el cronograma se ve como texto plano sin iconos de estado, y la fecha de cada hito llega truncada a solo el día porque `OrdenCompraMercadoPublicoService::cronogramaDesdeFechas()` y el mapeo de `fecha_emision` aplican `substr($valor, 0, 10)` sobre el datetime que entrega Mercado Público en `Fechas.*` (`FechaCreacion`, `FechaEnvio`, `FechaAceptacion`, `FechaCancelacion`).

El payload normalizado actual (confirmado en `OrdenCompraMercadoPublicoServiceTest.php` y `ApiOrdenesCompraMercadoPublicoTest.php`) solo captura: código, estado, moneda, forma de pago, monto neto/total, organismo comprador (nombre/unidad/rut), proveedor (rut/nombre), ítems (código/descripción/cantidad/precio/total) y cronograma. Además, `fecha_emision` es una columna `date` (no `dateTime`) en `ordenes_compra_mercado_publico` — PostgreSQL trunca cualquier componente de hora a nivel de base de datos sin importar qué le pase el backend en PHP, así que el requisito de fecha/hora reales aplica solo al cronograma (columna `json`, sin esa restricción). La imagen de referencia muestra campos adicionales (comuna, región, dirección, giro, IVA%, tipo de despacho, forma de emisión, categoría de ítem, título de licitación) que la API de Mercado Público podría exponer, pero que hoy no están mapeados en `normalizarPayload()` ni existen columnas para ellos — agregarlos requeriría confirmar los nombres de campo reales contra la API en producción, lo cual está fuera de alcance de este change.

## Goals / Non-Goals

**Goals:**
- Conservar fecha y hora reales de cada hito del cronograma, en vez de truncar a solo el día. (`fecha_emision` no cambia: su columna es `date` por diseño y Postgres trunca la hora igual, la conserve o no el PHP.)
- Rediseñar `CronogramaTimeline` como línea de tiempo horizontal con icono de estado por etapa (check verde relleno si está completada, círculo vacío si no), conectados por una línea, con etiqueta de etapa + fecha/hora + "Completado" debajo de cada ícono.
- Agregar una sección "Desglose financiero" (neto, impuesto calculado, total) usando exclusivamente campos ya existentes.
- Exponer "Ver JSON" (payload crudo del snapshot) como acción funcional en el encabezado de la ficha; dejar "Ver PDF" y "Ver en Mercado Público" visibles pero deshabilitados con "Disponible próximamente", siguiendo el mismo patrón ya usado en menús de acciones de otros listados (`item-actions-menu.tsx`, etc.).
- Mantener intacta la configuración de iconos/badges ya existente en el encabezado (badge de estado) y no romper `buscar.tsx` (vista previa) ni `show.tsx` (ficha guardada), que comparten el mismo componente de ficha.

**Non-Goals:**
- No se agregan campos que la API de Mercado Público no entrega hoy en el payload normalizado (comuna, región, dirección, giro, IVA%, tipo de despacho, forma de emisión, categoría de ítem, título de licitación). Si en el futuro se confirma que la API sí los expone, es un change aparte que extiende `normalizarPayload()`.
- No se implementa generación de PDF ni se construye una URL profunda hacia el detalle público de Mercado Público (no hay forma confiable de armar ese enlace sin verificarlo contra el portal real); esas acciones quedan como "Disponible próximamente".
- No se modifica el flujo de negocio (búsqueda, comparación, guardado, vínculo) de la capability `ordenes-compra-mercado-publico`, solo la fidelidad de dato del cronograma/fecha y su presentación.

## Decisions

1. **Conservar el datetime completo del cronograma tal cual lo entrega la API, sin normalizar formato en el backend.** `cronogramaDesdeFechas` deja de aplicar `substr(..., 0, 10)` y devuelve el string de fecha/hora tal como llega de Mercado Público (ej. `2026-05-11 12:34:00` o `2026-05-11T12:34:00`, según el formato real de la API). El formateo a texto legible ("11 may 2026 · 12:34") se hace en el frontend con la utilidad de fecha ya usada en el resto de la app, no en PHP, para no acoplar el dominio a un formato de presentación. `fecha_emision` se deja sin cambios (sigue mapeada con `substr(..., 0, 10)`), porque su columna `date` truncaría la hora igual.
   - Alternativa descartada: formatear a un string fijo en el backend (ej. `Y-m-d H:i`) — se descarta porque perdería precisión si la API entrega otro formato, y mezclaría presentación con dominio.
2. **El ícono de "completado" se deriva de la sola presencia del hito, no de un estado adicional.** Como hoy `cronogramaDesdeFechas` ya solo agrega un hito al arreglo cuando la fecha viene informada (`! empty($fechas[$campo])`), cualquier evento presente en el arreglo se considera completado; no se necesita un campo booleano nuevo. La única etapa que puede faltar sin ser "pendiente futura" es "Cancelada", que solo aparece si la OC fue cancelada.
3. **"Ver JSON" reutiliza el snapshot ya vinculado a la OC (`snapshot_datos_externo_id`), no vuelve a consultar la API.** Se expone `payload_crudo` (ya almacenado por `IntegracionExternaService::registrarSnapshot`) a través del recurso de la OC cuando existe snapshot, evitando una llamada nueva a Mercado Público solo para mostrar el JSON.
4. **"Ver PDF" y "Ver en Mercado Público" quedan deshabilitados con tooltip "Disponible próximamente"**, reutilizando el patrón ya establecido en dropdowns de acciones de otros módulos (`resources/js/components/maestros/*-actions-menu.tsx`) en vez de inventar un componente nuevo o un enlace externo no verificado.
5. **El desglose financiero se calcula en el frontend a partir de `monto_neto` y `monto_total`** (`impuesto = monto_total - monto_neto`), sin agregar una columna nueva ni tocar el backend, porque ambos valores ya viajan en el payload y en el modelo.

## Risks / Trade-offs

- [Riesgo] El formato real de fecha/hora que entrega la API de Mercado Público en `Fechas.*` no está confirmado en este repo (los fixtures de test usan solo `YYYY-MM-DD`) → Mitigación: el parseo de fecha en el frontend usa una utilidad tolerante a "solo fecha" o "fecha y hora" (si no hay hora, no se muestra hora en vez de mostrar `00:00` engañoso); se debe verificar contra una respuesta real de la API en ambiente de pruebas antes de dar por cerrado el cronograma con horas.
- [Riesgo] Quitar campos de la imagen de referencia (comuna, región, IVA%, etc.) puede no cumplir 100% la expectativa visual del usuario → Mitigación: se documenta explícitamente como Non-Goal y se deja como candidato a un change posterior una vez confirmados los campos reales de la API.
- [Trade-off] Dejar "Ver PDF"/"Ver en Mercado Público" deshabilitados en vez de intentar un enlace best-effort evita construir una URL rota, a costa de no cumplir literalmente esos dos botones de la imagen en esta iteración.

## Migration Plan

- Sin migración de base de datos (los campos `cronograma` y `fecha_emision` ya son `array`/`date` en el modelo; el cambio es en cómo se llenan, no en el esquema).
- Cambio de puro frontend + normalización de servicio, desplegable junto con el resto de la app sin pasos manuales adicionales.
- Rollback: revertir el commit; no hay dato persistido que dependa del nuevo formato (las OC ya guardadas conservan el `cronograma`/`fecha_emision` con el formato que tenían al momento de guardarse).

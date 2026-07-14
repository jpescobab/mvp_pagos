## Context

`ImportacionSgfResource::mapSnapshots()` (`app/Http/Resources/Sgf/ImportacionSgfResource.php:35-45`) hoy solo expone `id`, `referencia_externa`, `hash` y `capturado_en` de cada `snapshot_datos_externo`. El dato de negocio (proveedor, monto, estado SGF, folio, período) ya existe en `payload_normalizado` (poblado por `NormalizadorSgf`) pero no llega al frontend. `payload_normalizado` es un array asociativo sin garantía de todas las claves presentes (los tests existentes crean snapshots con payloads mínimos, ej. solo `['estado' => 'EN_TRAMITE']`), así que cualquier lectura debe ser defensiva.

El `caso_pago_proveedor` resultante de un snapshot se localiza por `sgf_id = snapshot.referencia_externa` (ver `CasoPagoProveedorImporter::importarDesdeSnapshot`), no por una FK directa en el snapshot — no existe hoy una relación Eloquent lista para esto, hay que resolverla por lote para evitar N+1.

## Goals / Non-Goals

**Goals:**
- Mostrar, por snapshot, la información normalizada real: proveedor (nombre si el RUT coincide con un `Proveedor`, si no el RUT), monto, estado SGF, folio de egreso, número, período, fecha SII, observaciones.
- Enlazar cada snapshot al `caso_pago_proveedor` que produjo (si ya se importó) y a su estado de workflow interno actual, para navegar al detalle del caso.
- Mostrar un resumen agregado de la corrida completa: monto total y cantidad de proveedores identificados vs. no identificados.
- Resolver todo esto sin N+1: una sola consulta batch de `casos_pago_proveedor` por los `sgf_id` de los snapshots de la página.

**Non-Goals:**
- No se modifica `NormalizadorSgf` ni el payload que ya se captura — solo se expone lo que ya existe.
- No se agrega paginación a la lista de snapshots dentro del detalle (se mantiene la carga completa actual, acotada al total de una corrida).
- No se toca el listado (`sgf/importaciones/index.tsx`), solo el detalle (`show.tsx`).

## Decisions

**1. Resolver los `casos_pago_proveedor` relacionados en el controlador, no dentro del Resource.**
`ImportacionSgfController::show()` carga, además de `snapshotsDatosExternos`, un mapa `sgf_id => CasoPagoProveedor` (con `proveedor` eager-loaded) para todos los `referencia_externa` de esos snapshots, y lo pasa al `ImportacionSgfResource` vía `additional()`. Alternativa descartada: resolver el caso dentro de `mapSnapshots()` con una query por snapshot — N+1 evidente con corridas de decenas de elementos.

**2. El nombre del proveedor sale del `caso_pago_proveedor->proveedor` ya resuelto, no de un lookup nuevo por RUT en el Resource.**
El `CasoPagoProveedorImporter` ya intenta resolver `proveedor_id` por RUT al crear el caso; si no hay coincidencia, `proveedor_id` queda `null` y el caso solo tiene `rut_proveedor`. El Resource simplemente refleja ese resultado (ya persistido) en vez de repetir la búsqueda por RUT — evita duplicar la lógica de matching y dos fuentes de verdad pudiendo divergir.

**3. El resumen agregado (monto total, proveedores identificados/no identificados) se calcula en PHP sobre la colección ya cargada, no con una query de agregación aparte.**
El volumen esperado por corrida (decenas, no miles, de elementos por importación) hace que sumar/contar en memoria sobre la colección de snapshots ya traída sea más simple y suficientemente rápido que una segunda query agregada; se evita mantener dos caminos de acceso a los mismos datos.

**4. Snapshots sin `caso_pago_proveedor` asociado (importación en curso, o snapshot de un tipo que no crea `casos_pago_proveedor`, ej. `verificar_caso`) muestran los datos normalizados igual, sin el enlace al caso.**
El campo `caso_id`/`caso_estado` en la respuesta es nullable; el frontend condiciona el link a que exista.

## Risks / Trade-offs

- [Riesgo] Si una corrida futura maneja miles de elementos, cargar todos los `casos_pago_proveedor` relacionados de una vez podría ser pesado. → Mitigación: no aplica hoy (los `trabajos_integracion` de SGF importan decenas de casos por corrida, ver `total_elementos` real en producción); si crece, es un ajuste de paginación a evaluar aparte, no bloqueante para este change.
- [Riesgo] `payload_normalizado` con claves faltantes en snapshots antiguos (de antes de campos como `folio_egreso`/`numero`) mostraría "—" en vez de dato real. → Mitigación: es el comportamiento correcto (dato realmente no capturado en ese momento), no un bug a ocultar.

## Migration Plan

1. Sin migración de esquema.
2. Cambio de solo lectura (Resource + Controller + página React) — deploy normal, sin downtime.
3. Rollback: revertir el commit vuelve a la vista actual sin pérdida de datos (los datos ya estaban en `payload_normalizado`, solo se dejan de mostrar).

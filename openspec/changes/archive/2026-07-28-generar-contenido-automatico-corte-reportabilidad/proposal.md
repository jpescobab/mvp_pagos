## Why

Hoy un `corte_reportabilidad` solo se puede **crear vacío** y **publicar vacío**: `CorteReportabilidadService` implementa y prueba `agregarItem()` y `capturarSnapshot()`, pero ningún controlador, job ni comando los invoca. En consecuencia, el requirement "Conservar evidencia inmutable de cada corte" (`reportabilidad-informes-razonados`) no es operable, y como los informes razonados nacen de cortes publicados + sus snapshots, hoy solo pueden producir informes huecos. Este es el eslabón que hace que la cadena reportabilidad → informes tenga datos reales.

## What Changes

- **Generar el contenido de un corte**: nueva acción "Generar corte" que, para el período del corte, recolecta las entidades internas reportables del período y por cada una crea un `corte_reportabilidad_item` (vinculable polimórfico + etiqueta) y captura un `snapshot_corte_reportabilidad` con el payload crudo serializado de la entidad y su hash SHA-256. Toda la escritura ocurre en una `DB::transaction` y **solo** mientras el corte está en `borrador` (las guardas ya existen en el service).
- **Entidad reportable inicial**: `casos_pago_proveedor` cuyo `periodo` coincide con el `codigo` del `periodo_reportabilidad` del corte. El generador se diseña **extensible** a `egresos_cgu` y otras entidades reportables sin reabrir el resto (fuera de alcance implementar más de una en este change).
- **Regeneración idempotente**: volver a generar un corte en `borrador` **reemplaza** su contenido previo (borra ítems y snapshots del corte y los vuelve a capturar), de modo que el resultado refleje el estado actual sin duplicar. Un corte `publicado` no admite regeneración (inmutable).
- **Autorización**: nuevo permiso `reportabilidad.generar_corte` (convención `modulo_accion.verbo`), sembrado en el seeder del dominio y verificado por policy.
- **UI**: en `reportabilidad/cortes/show.tsx`, botón "Generar/Poblar corte" (visible solo en `borrador` y con permiso), listado de ítems con su entidad vinculada y etiqueta, y el conteo de snapshots ya existente.
- Regenerar los helpers de Wayfinder tras agregar la ruta.
- **No** se tocan el workflow ni el ciclo de publicación (poblar un corte no es una transición); **no** hay migraciones nuevas (las tablas ya existen).

## Capabilities

### New Capabilities
<!-- Ninguna: se extiende una capability existente -->

### Modified Capabilities
- `gestionar-periodos-cortes-reportabilidad`: se agrega el requirement de **generar automáticamente el contenido de un corte** (recolectar las entidades reportables del período, crear un ítem y capturar un snapshot con payload+hash por cada una, solo en `borrador`, regeneración reemplazante, gated por `reportabilidad.generar_corte`).

## Impact

- **Service**: nuevo `GeneradorCorteReportabilidadService` en `app/Services/Reportabilidad/` que orquesta la recolección + `agregarItem`/`capturarSnapshot` de `CorteReportabilidadService` en una transacción; incluye el borrado del contenido previo en la regeneración. Define una estrategia por tipo de entidad reportable (hoy solo `casos_pago_proveedor`), extensible.
- **Controlador** (liviano): `CorteReportabilidadController@generar` (o un controlador dedicado) que autoriza y delega en el generador.
- **Ruta**: `POST reportabilidad/cortes/{corte}/generar` en `routes/reportabilidad.php`.
- **Policy**: `CorteReportabilidadPolicy` (nueva o existente) con la ability `generar` autorizada por `reportabilidad.generar_corte`; registro en `AppServiceProvider::configureAuthorization()` si es nueva.
- **Permisos**: nuevo `reportabilidad.generar_corte` en el seeder del dominio de reportabilidad; actualizar el test de lista de permisos si afirma la lista exacta.
- **Frontend**: `resources/js/pages/reportabilidad/cortes/show.tsx` y su `CorteReportabilidadResource` (exponer los ítems con su entidad vinculada resumida: tipo, etiqueta, identificador).
- **Snapshot/evidencia**: cada snapshot guarda `payload_crudo` (estado serializado de la entidad al momento del corte), `hash` SHA-256, `capturado_en`, vinculado al `corte_reportabilidad_item`. Consistente con la regla de snapshot obligatorio.
- **Tests**: feature test HTTP `GenerarCorteReportabilidadTest` (genera ítems+snapshots para los casos del período; idempotencia/reemplazo; bloqueo en `publicado`; 403 sin permiso) y unit del generador.
- **Sin migraciones**: `cortes_reportabilidad_items` y `snapshots_corte_reportabilidad` ya existen.

## Open decision (a validar en apply)

El emparejamiento período↔entidad se hará por `casos_pago_proveedor.periodo == periodo_reportabilidad.codigo`. El campo `periodo` del caso proviene de la importación SGF (texto); en `apply` se verifica el formato real contra el `codigo` del período y, si difiere, se normaliza el match (no se asume ciegamente igualdad de strings).

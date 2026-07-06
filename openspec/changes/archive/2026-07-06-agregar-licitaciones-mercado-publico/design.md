## Context

Adquisiciones ya integra la API de Órdenes de Compra de Mercado Público (`OrdenCompraMercadoPublicoService`, capabilities `ordenes-compra-mercado-publico` y `paginas-ordenes-compra-mercado-publico`). Este cambio agrega el mismo tipo de integración para Licitaciones, contra el endpoint `licitaciones.json` del mismo `SistemaExterno` `MERCADO_PUBLICO` (`config('services.mercadopublico')`).

Se investigó la respuesta real de la API pública (`GET .../licitaciones.json?codigo=<codigo>&ticket=<api_key>`) contra dos licitaciones reales (una publicada sin adjudicar, otra adjudicada) para no asumir que su estructura es igual a la de una OC. Diferencias relevantes encontradas:

- La OC tiene un único proveedor emisor (`Proveedor.RutSucursal`/`Nombre`) a nivel de la orden completa. Una licitación **no** tiene eso: el `Listado[0]` no trae proveedor propio, y la adjudicación (si existe) vive en `Listado[0].Adjudicacion` (nivel licitación: tipo, fecha, número de acta, número de oferentes, URL del acta) y, por separado, en `Items.Listado[].Adjudicacion` (nivel ítem: `RutProveedor`, `NombreProveedor`, `Cantidad` adjudicada, `MontoUnitario`) — puede haber un proveedor distinto adjudicado por cada ítem, o ítems sin adjudicar todavía.
- La OC no expone plazo/cronograma rico; una licitación sí: `Fechas` trae hasts 16 hitos (creación, publicación, inicio/fin de preguntas, publicación de respuestas, cierre de recepción de ofertas, apertura técnica/económica, adjudicación real y estimada, entre otros).
- Los ítems de una licitación no tienen precio unitario propio (son requerimientos, no líneas de compra con precio ya pactado) — el único monto asociado a un ítem es el que aparece en su `Adjudicacion.MontoUnitario` una vez adjudicado.
- `CodigoEstado`/`Estado` (texto, ej. `"Publicada"`, `"Adjudicada"`) reemplaza al estado de la OC.

## Goals / Non-Goals

**Goals:**
- Replicar para Licitaciones el mismo flujo ya validado para OC: búsqueda local-primero, consulta a la API solo si no existe localmente, snapshot obligatorio de todo payload recibido, comparación campo a campo bajo confirmación, guardado bajo confirmación, vínculo opcional (sin workflow) a un `proceso_adquisicion`, listado paginado y ficha de detalle reutilizando `FichaConsultaMercadoPublico`.
- Mostrar el cronograma completo de una licitación (más rico que el de una OC) con el mismo componente `CronogramaTimeline` ya existente, sin modificarlo.
- Conservar el proveedor(es) adjudicado(s) por ítem como dato informativo del payload normalizado, sin crear ni tocar registros de `Proveedor`.

**Non-Goals:**
- No se replica la resolución/creación automática de un `Proveedor` en el catálogo al guardar una licitación (no aplica: no hay un único proveedor emisor, y crear/actualizar `Proveedor` a partir de adjudicaciones por ítem es una decisión de negocio distinta que no fue pedida).
- No se implementa la descarga directa de PDF para licitaciones en este cambio (no hay un patrón de extracción verificado como el de `imgPDF` en la página de detalle de OC). La acción queda deshabilitada ("Disponible próximamente"), consistente con la convención ya usada en el proyecto para acciones no implementadas.
- No se importa el listado completo de licitaciones por fecha (`licitaciones.json?fecha=...`); igual que con las OC, el flujo es buscar por código puntual, no sincronizar en bloque.

## Decisions

### Modelo de datos
- `licitaciones_mercado_publico`: `codigo` (unique), `nombre`, `estado_mercado_publico`, `codigo_estado_mercado_publico` (int, para futuras reglas por código en vez de parsear el texto), `moneda`, `monto_estimado`, `organismo_comprador` (jsonb: nombre/unidad/rut, igual forma que en OC), `cronograma` (jsonb: array `{estado, fecha}`, igual forma que en OC para reutilizar `CronogramaTimeline` sin cambios), `adjudicacion` (jsonb nullable: `{tipo, fecha, numero, numero_oferentes, url_acta}`), `proceso_adquisicion_id` (nullable, FK), `snapshot_datos_externo_id` (FK).
- `licitaciones_mercado_publico_items`: `licitacion_mercado_publico_id` (FK), `correlativo`, `codigo_producto`, `categoria`, `nombre_producto`, `descripcion`, `unidad_medida`, `cantidad`, `adjudicacion` (jsonb nullable: `{rut_proveedor, nombre_proveedor, cantidad, monto_unitario}`).
- No se agrega `proveedor_id` a `licitaciones_mercado_publico` (ver Non-Goals).

### Normalización del payload
`LicitacionMercadoPublicoService::normalizarPayload()` traduce `Listado[0]` así:
- `codigo` ← `CodigoExterno`, `nombre` ← `Nombre`, `estado` ← `Estado`, `codigo_estado` ← `CodigoEstado`, `moneda` ← `Moneda`, `monto_estimado` ← `MontoEstimado` (nullable, `VisibilidadMonto` puede ocultarlo).
- `organismo_comprador` ← `Comprador.{NombreOrganismo, NombreUnidad, RutUnidad}` (misma forma que en OC).
- `cronograma` ← hitos de `Fechas` en este orden fijo (solo se agregan los informados, igual que en OC): `FechaCreacion`→"Creada", `FechaPublicacion`→"Publicada", `FechaInicio`→"Inicio de preguntas", `FechaFinal`→"Cierre de preguntas", `FechaPubRespuestas`→"Publicación de respuestas", `FechaCierre`→"Cierre de recepción de ofertas", `FechaActoAperturaTecnica`→"Apertura técnica", `FechaActoAperturaEconomica`→"Apertura económica", `FechaAdjudicacion` (o `FechaEstimadaAdjudicacion` si la real no está informada aún)→"Adjudicación".
- `adjudicacion` ← `Adjudicacion.{Tipo, Fecha, Numero, NumeroOferentes, UrlActa}` cuando `Listado[0].Adjudicacion` no es null; si no, `null`.
- `items` ← `Items.Listado[]`: `correlativo` ← `Correlativo`, `codigo_producto` ← `CodigoProducto`, `categoria` ← `Categoria`, `nombre_producto` ← `NombreProducto`, `descripcion` ← `Descripcion`, `unidad_medida` ← `UnidadMedida`, `cantidad` ← `Cantidad`, `adjudicacion` ← `Item.Adjudicacion.{RutProveedor, NombreProveedor, Cantidad, MontoUnitario}` o `null`.
- Igual que en OC, `apiDevuelveLicitacion()` exige que `Listado` tenga al menos un elemento y que su `CodigoExterno` coincida (case-insensitive) con el código solicitado, para distinguir una respuesta válida-pero-vacía de un ticket/parámetro inválido.

### Generalización de `AccionesEncabezadoFichaMercadoPublico`
El componente pasa a recibir `urlDetalle: string` y `urlPdf: string | null` como props (en vez de calcularlos internamente a partir de constantes y rutas de OC). `urlPdf === null` deshabilita el botón "Ver PDF" con el mismo estilo que ya usa "Ver JSON" cuando no hay snapshot. `ordenes-compra-mercado-publico/show.tsx` pasa `pdfOrdenCompraMp.url(...)` y su URL pública ya existente; `licitaciones-mercado-publico/show.tsx` pasa `null` para `urlPdf` y la URL pública `https://www.mercadopublico.cl/Procurement/Modules/RFB/DetailsAcquisition.aspx?idlicitacion=<codigo>` (verificada: redirige con 302 al detalle público real, igual patrón que la OC).

### Permisos y rutas
Nuevo permiso `adquisiciones.consultar_licitacion_mp` (mismo patrón `modulo_accion.verbo`), agregado al seeder de permisos junto al de OC. Rutas bajo `adquisiciones/licitaciones-mercado-publico` con los mismos nombres de acción que `ordenes_compra_mp` (`index`, `buscar`, `guardar`, `show`, `verificar`, `actualizar`, `vinculo.store`, `vinculo.destroy`) — sin acción `pdf` (no aplica, ver Non-Goals).

## Risks / Trade-offs

- [Riesgo] La API de licitaciones expone muchos más campos de los modelados (financiamiento, contrato, renovación, etc.) → Mitigación: se modela solo lo necesario para el flujo de consulta/trazabilidad ya definido (igual criterio que con OC, que tampoco modela el 100% del payload); el snapshot crudo completo queda disponible vía "Ver JSON" para cualquier campo no modelado.
- [Riesgo] Generalizar `AccionesEncabezadoFichaMercadoPublico` toca un componente ya usado por OC → Mitigación: cambio de firma acotado (dos props explícitas en vez de cálculo interno), sin tocar su comportamiento visual; se valida con el test Feature/Browser existente de la ficha de OC antes de dar por cerrado el cambio.
- [Trade-off] No hookear "Ver PDF" para licitaciones deja una acción deshabilitada visible en la UI → aceptado explícitamente como parte del alcance (ver Non-Goals); puede proponerse como change ad-hoc separado si más adelante se verifica un patrón de extracción confiable.

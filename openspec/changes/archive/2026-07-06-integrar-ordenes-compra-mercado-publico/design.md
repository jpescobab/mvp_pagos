## Context

Hoy el dominio `adquisiciones` modela únicamente `proceso_adquisicion`, la representación interna del proceso de adquisición gobernada por `TransicionWorkflowService`. No existe ningún modelo que represente la Orden de Compra (OC) tal como la emite Mercado Público. La capa transversal de integraciones (`sistemas_externos`, `solicitudes_api_externas`, `snapshots_datos_externos`, `trabajos_integracion`, `App\Services\Integraciones\IntegracionExternaService`) ya existe y se usa hoy para CMF/SII; el registro `MERCADO_PUBLICO` en `sistemas_externos` ya está sembrado pero inactivo. El catálogo de proveedores y su alta completa (datos comerciales/bancarios) ya existen en `Maestros\ProveedorController`. El patrón de vínculo manual opcional entre una entidad y `proceso_adquisicion` ya existe (`VinculoAdquisicionCasoPagoProveedorController`: FK nullable + `AuditLogger`, sin pasar por workflow).

El spec archivado `adquisiciones` tiene hoy un requirement que prohíbe expresamente cualquier integración con Mercado Público como origen de datos. Este change reemplaza ese requirement.

## Goals / Non-Goals

**Goals:**
- Permitir buscar una OC por código, primero localmente y luego (opcionalmente) contra la API de Mercado Público.
- Dejar evidencia trazable de toda consulta externa: snapshot con hash del payload crudo, log de la solicitud HTTP, éxito o fallo.
- Comparar el registro local contra la API y dejar que el usuario decida si actualiza, sin sobrescribir nada automáticamente.
- Vincular la OC verificada con el proveedor real (creándolo o actualizándolo si hace falta, reusando el flujo existente) y, opcionalmente, con un `proceso_adquisicion`.
- Reemplazar el requirement de `adquisiciones` que hoy prohíbe esta integración.

**Non-Goals:**
- No se modela la OC como sujeto de workflow propio: no tiene `Proceso`, no tiene estados internos, no pasa por `TransicionWorkflowService`. Es evidencia, igual que un snapshot de SGF.
- No se automatiza ninguna actualización ni creación: toda escritura (guardar OC nueva, aplicar diferencias, crear/actualizar proveedor, vincular a un proceso de adquisición) requiere una acción explícita del usuario en cada paso.
- No se implementa sincronización periódica/batch con Mercado Público (ni Jobs programados) — es una consulta puntual iniciada por el usuario.
- No se cubre aquí la búsqueda de licitaciones ni otros recursos de la API de Mercado Público, solo Órdenes de Compra por código. El componente de ficha se diseña genérico para que ese trabajo futuro lo reutilice, pero su entidad, servicio, API y permiso quedan fuera de alcance de este change.

## Decisions

### La OC de Mercado Público es una entidad propia, no una extensión de `proceso_adquisicion`
Una OC es un documento que emite Mercado Público con su propio código, ítems y proveedor emisor; un `proceso_adquisicion` es el expediente interno con workflow propio. Igual que `sgf_id`/`caso_pago_proveedor`, se mantienen separados: `orden_compra_mercado_publico` (con `orden_compra_mercado_publico_items`) es la representación fiel de lo que devuelve la API, vinculable opcionalmente a un `proceso_adquisicion` vía FK nullable — mismo patrón que ya existe entre `caso_pago_proveedor` y `proceso_adquisicion`.

**Alternativa descartada**: agregar columnas de OC directamente a `procesos_adquisicion`. Se descarta porque mezclaría evidencia externa con el sujeto de workflow, y porque una misma OC podría necesitar existir localmente antes de que exista o se decida un `proceso_adquisicion` asociado (el flujo permite consultar una OC sin vincularla a nada).

### Reutilizar `IntegracionExternaService` sin service HTTP propio de Mercado Público
El nuevo servicio de dominio (`OrdenCompraMercadoPublicoService` o similar, bajo `App\Services\Adquisiciones` o un namespace propio `App\Services\OrdenesCompraMercadoPublico`) es responsable de: construir la petición HTTP al endpoint de OC de Mercado Público, y delegar en `IntegracionExternaService::iniciarTrabajo()` / `registrarSolicitud()` / `registrarSnapshot()` para todo el registro de evidencia. No se crea un servicio de integración paralelo ni tablas nuevas de log — se sigue exactamente el mismo patrón que ya usan CMF/SII.

Cliente HTTP: `Illuminate\Support\Facades\Http` con base URL y ticket desde `config('services.mercadopublico')`, igual que el patrón `config('services.cmf')`.

### Comparación de diferencias es una operación de solo lectura, sin persistencia intermedia
Cuando el usuario pide verificar una OC local contra la API, el servicio: consulta la API, registra la solicitud/snapshot (la consulta en sí siempre se evidencia, se use o no el resultado), calcula un diff campo a campo entre el registro local y el payload normalizado, y devuelve ese diff a la UI. Nada se escribe sobre el registro local hasta que el usuario confirma explícitamente aplicar la actualización (segunda llamada, explícita, distinta de la de comparar).

### Verificación/alta de proveedor reutiliza el flujo existente
No se crea un formulario de proveedor nuevo. El servicio de OC solo determina si el proveedor emisor (identificado por RUT/código en el payload de Mercado Público) ya existe en `proveedores`; si no, la UI redirige/reusa el mismo formulario de alta ya implementado en `Maestros\ProveedorController`, precargado con los datos que trae la OC. La OC solo se guarda una vez que el proveedor ya existe (creado en este flujo o preexistente).

**Corrección post-implementación**: se detectó que comparar el RUT tal cual viene de Mercado Público (con puntos, p. ej. `89.862.200-2`) contra el RUT almacenado (sin puntos en el catálogo real, p. ej. `89862200-2`) hacía que `verificarProveedor()` no encontrara proveedores existentes y terminara creando duplicados. Se agregó `Proveedor::normalizarRut()` (sin puntos, con guión, dígito verificador en mayúscula) como mutator del atributo `rutproveedor` — se aplica automáticamente en cualquier punto de guardado (alta/edición manual, este flujo de OC) — y se normaliza también en `StoreProveedorRequest`/`UpdateProveedorRequest::prepareForValidation()` para que la regla `unique` compare valores ya normalizados.

### Vínculo a `proceso_adquisicion`: mismo patrón que `caso_pago_proveedor`
`orden_compra_mercado_publico` tiene una columna nullable `proceso_adquisicion_id`. Un controlador análogo a `VinculoAdquisicionCasoPagoProveedorController` (store/destroy) gestiona vincular/desvincular, con `AuditLogger`, sin transición de workflow. Vincular u desvincular una OC nunca soluciona ni activa una transición del `Proceso` del `proceso_adquisicion`.

### Layout de la ficha: componente genérico y reutilizable, filtros en una sola fila
La página de búsqueda/resultado sigue el patrón visual de referencia de tipo "ficha" (mockup entregado por el usuario, originalmente para una consulta de Licitaciones en Mercado Público): el resultado se presenta como un conjunto de tarjetas/bloques separados, en este orden fijo: (1) encabezado (código, tipo/estado, organismo comprador), (2) **cronograma de estados** (línea de tiempo, solo informativo), (3) datos del organismo comprador, (4) condiciones (moneda, forma de pago, plazo de entrega), (5) adjudicación/proveedor, (6) tabla de ítems. A diferencia del mockup de referencia, que ubica los controles de búsqueda en una barra lateral izquierda, en esta implementación el código de OC y el botón de consultar van en **una sola fila horizontal por sobre la ficha**, sin sidebar de filtros.

El usuario confirmó que quiere este mismo diseño de ficha para Licitaciones además de Órdenes de Compra. Por eso el componente de ficha SHALL construirse genérico y parametrizable por secciones (no acoplado a los campos específicos de una OC), de modo que un change futuro que agregue la consulta de Licitaciones (fuera de alcance aquí, ver Non-Goals) lo reutilice sin rediseñar el layout. En este change solo se implementa y se prueba con datos de Órdenes de Compra; el componente no debe importar tipos ni lógica específica de OC en su capa de presentación de secciones — recibe los datos de cada sección ya resueltos por quien lo consuma.

**Alternativa descartada**: replicar el mockup literalmente con una barra lateral de filtros. Se descarta porque el caso de uso solo tiene un filtro real (el código de OC) — una barra lateral completa es sobredimensionada para un único campo de búsqueda, y una fila horizontal es más consistente con el resto de listados densos del sistema (ver `tema-visual-layout`).

**Alternativa descartada**: construir el componente de ficha acoplado a los campos de OC ahora y generalizarlo recién cuando se proponga Licitaciones. Se descarta porque el usuario ya pidió expresamente el mismo diseño para ambos recursos — diseñarlo genérico desde el inicio evita un refactor previsible y evita duplicar el layout más adelante.

El cronograma de estados de Mercado Público (si el payload de la API lo trae) se muestra únicamente como línea de tiempo de lectura — es evidencia externa, igual que el resto del payload, y no debe confundirse ni interactuar con el workflow interno del `proceso_adquisicion` (ver requirement modificado de `adquisiciones`).

### Reemplazo del requirement "No modelar integración externa todavía"
Se reemplaza completo por un nuevo requirement que autoriza expresamente esta integración como origen de evidencia (no de gobierno), preservando la restricción de fondo: Mercado Público sigue sin gobernar workflow ni estados de `proceso_adquisicion`.

## Risks / Trade-offs

- [Riesgo] El ticket/API key real de Mercado Público no está disponible todavía → Mitigación: `config/services.php` + `.env.example` con placeholder vacío (mismo patrón que `CMF_API_KEY`), tests con HTTP fake/mock; el change queda completo y testeable sin bloquear por la credencial real.
- [Riesgo] El payload de la API de Mercado Público puede variar entre ambientes (certificación vs. producción) o cambiar de forma → Mitigación: guardar siempre `payload_crudo` sin normalizar en el snapshot; el `payload_normalizado` es best-effort y no es la fuente de verdad legal.
- [Riesgo] Confundir la OC con el `proceso_adquisicion` en la UI (dar la impresión de que gobierna workflow) → Mitigación: la página de OC nunca muestra acciones de transición; solo el detalle de `proceso_adquisicion` las muestra, y ahí la OC aparece solo como referencia vinculada.
- [Trade-off] No hay sincronización automática/periódica: si la OC cambia en Mercado Público después de guardada, el sistema no se entera hasta que alguien la vuelve a consultar manualmente. Aceptado porque está fuera de alcance (Non-Goals) y es consistente con "todo dato externo relevante se snapshotea en el momento de la consulta", no en tiempo real.

## Migration Plan

1. Migraciones: `sistemas_externos` ya existe (solo se activa `MERCADO_PUBLICO` vía seeder, no migración); nuevas tablas `ordenes_compra_mercado_publico`, `orden_compra_mercado_publico_items`.
2. `config/services.php` + `.env.example`: nuevas claves `MERCADOPUBLICO_API_KEY` / `MERCADOPUBLICO_API_BASE_URL` (vacías por defecto).
3. Seeder: activar `MERCADO_PUBLICO` en `IntegracionesSeeder` y agregar el permiso `adquisiciones.consultar_orden_compra_mp`.
4. Sin rollback especial: son tablas nuevas y un flag de activación: un `down()` estándar de migración basta; no hay dato productivo previo que migrar.

## Open Questions

- Ninguna pendiente para iniciar la implementación; el ticket real de Mercado Público se configurará en `.env` cuando el usuario lo tenga (no bloquea el desarrollo ni los tests).

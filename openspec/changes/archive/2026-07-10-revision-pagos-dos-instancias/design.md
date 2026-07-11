## Context

El workflow de Pago de Proveedores (`WorkflowPagoProveedoresSeeder`, `TransicionWorkflowService`) hoy tiene una sola etapa de revisión documental (`en_revision_documental` → `observada`/`subsanada` → `lista_para_registro_cgu`). El dominio ya cuenta con el andamiaje necesario: `CasoPagoProveedor` (1 `sgf_id` = 1 caso = 1 `Proceso` polimórfico), `EgresoCgu`/`EgresoCguItem` (agrupación de casos), `Documento`/`ValidacionDocumento` (validación con historial inmutable), `ChecklistDocumentalProceso`/`RequisitoDocumental` (checklist variable resuelto por backend) y la jerarquía institucional `instituciones → jurisdicciones → cfinancieros → ccostos`.

El prototipo entregado ("Revisión de Pagos") define el flujo de trabajo del revisor a nivel de UI: Egreso → pagos (cada uno con su tipo de pago, folio, montos y documentos) → revisión documento por documento + verificación de totales + aprobación/rechazo del pago. Sobre eso, el requerimiento agrega **dos instancias secuenciales** (Jefe de Finanzas → Administrador Zonal) con devolución a la instancia anterior, no presentes en el prototipo.

Roles actuales: solo `superadmin` y `admin`. No existen `jefe_finanzas` ni `administrador_zonal`.

## Goals / Non-Goals

**Goals:**
- Modelar las dos instancias como estados/transiciones del workflow del caso, gobernados exclusivamente por `TransicionWorkflowService::execute()`.
- Revisión pago por pago; el Egreso es el contenedor de trabajo y su estado de revisión es **derivado**, no persistido.
- Revisión documental independiente por instancia, sin perder el rastro de validaciones previas.
- Verificación de totales como precondición de aprobación de un pago.
- Devolución siempre disponible a la instancia anterior, con comentario obligatorio.
- Scope zonal: el Administrador Zonal solo opera Egresos de su jurisdicción.
- Agrupación automática de casos en Egresos al importar de SGF, ajustable manualmente.
- Pantalla React que reproduce el prototipo, con acciones condicionadas por permiso e instancia.

**Non-Goals:**
- No se introduce un motor de estados propio del Egreso: el Egreso no tiene `Proceso` ni `estado` persistido; su estado se calcula.
- No se altera el contrato de `TransicionWorkflowService`.
- No se usa SGF como gobierno de estados: SGF sigue siendo solo origen/evidencia.
- El modo "Checklist" de validación documental del prototipo queda como mejora posterior; esta entrega usa aprobar/rechazar + motivo.
- No se toca el visor con PDFs mock del prototipo: en la app se conecta a `Documento`/`VersionDocumento` reales.

## Decisions

### 1. El estado de las dos instancias vive en el workflow del caso, no en el Egreso
Se agregan los estados `en_revision_finanzas` y `en_revision_zonal` al workflow `pago_proveedores`, reemplazando el uso de `en_revision_documental` como única etapa de revisión. Transiciones nuevas en `WorkflowPagoProveedoresSeeder`:
- `aprobar_finanzas`: `en_revision_finanzas → en_revision_zonal` (permiso `pago_proveedores.revisar_finanzas`; documentos requeridos validados por `ResolutorValidacionDocumental`).
- `aprobar_zonal`: `en_revision_zonal → lista_para_registro_cgu` (permiso `pago_proveedores.revisar_zonal`).
- `devolver_a_finanzas`: `en_revision_zonal → en_revision_finanzas` (comentario obligatorio; permiso `pago_proveedores.revisar_zonal`).
- `observar_finanzas` / `rechazar_finanzas`: `en_revision_finanzas → observada` / `rechazada` (comentario obligatorio).
- `rechazar_zonal`: `en_revision_zonal → rechazada` (comentario obligatorio).

**Por qué:** el harness exige que todo cambio de estado pase por `TransicionWorkflowService` y que el caso sea la unidad de workflow (1 `sgf_id` = 1 caso = 1 proceso). Modelar las instancias como estados del caso reutiliza permisos, historial, auditoría y notificaciones ya existentes.
**Alternativa descartada:** máquina de estados propia sobre `EgresoCgu`. Duplicaría el motor de workflow y violaría la regla de punto único de transición.

### 2. El Egreso es el contenedor de revisión; su estado es derivado
`EgresoCgu` no gana columna de estado. Un `EgresoResource`/accesor calcula el estado de revisión y la **instancia activa** a partir de los estados de sus casos (mismo criterio de agregación que el prototipo: todos aprobados → aprobado; alguno rechazado → rechazado; alguno en revisión → en revisión; si todos comparten instancia, esa es la instancia activa). El avance/devolución "del Egreso" es azúcar de UI: `RevisionEgresoService` itera sobre los casos y dispara la transición individual de cada uno vía `TransicionWorkflowService`, dentro de una transacción, validando primero que todos estén listos.

**Por qué:** evita un estado duplicado que podría desincronizarse del workflow real de cada caso, que es la fuente de verdad.

### 3. Validación documental por instancia
Se agrega `validaciones_documento.instancia` (string/enum, nullable para compatibilidad con validaciones no-instanciadas existentes). El estado vigente de un documento se resuelve **por instancia**: el evento más reciente cuya `instancia` coincide. Así el mismo documento puede estar `aprobado` para `finanzas` y `pendiente` para `zonal`. La precondición de "todos los documentos aprobados" para aprobar un pago se evalúa contra la instancia activa.

**Por qué:** el Administrador Zonal repite la revisión documental completa; sin la dimensión de instancia, su validación pisaría la de Finanzas o viceversa, perdiendo la separación de funciones.
**Alternativa descartada:** resetear el estado del documento al cambiar de instancia. Perdería trazabilidad, prohibido por el harness.

### 4. Verificación de totales
La verificación de totales es una precondición por instancia de la aprobación del pago. Se persiste como parte de la revisión del caso en la instancia activa (bandera derivable de un registro de revisión del caso por instancia, o campo en el pivote de revisión). El servicio de aprobación del pago rechaza la transición si los totales no fueron verificados en la instancia activa. La comparación usa `Factura`, `RegistroContableCgu`/recepción y `monto` del caso.

### 5. Scope zonal por jurisdicción derivada del Egreso
La jurisdicción de un Egreso se deriva del centro financiero de sus casos (`caso → proceso_adquisicion/cfinanciero → jurisdiccion`). La agrupación automática (decisión 6) garantiza un Egreso por jurisdicción, por lo que la derivación es unívoca. `EgresoCguPolicy` autoriza al `administrador_zonal` solo si la jurisdicción del Egreso está entre las de su usuario; los intentos denegados se registran en `security_audit_logs`. El `jefe_finanzas` no tiene restricción zonal (o la que defina el modelo institucional del usuario).

**Por qué:** respeta la jerarquía institucional fija como gobernante de filtros y permisos.
**Nota:** la asociación usuario↔jurisdicción se resuelve contra el modelo institucional del `User` existente; si no existe hoy, se acota vía rol + centro/jurisdicción del usuario (a confirmar en implementación, ver Open Questions).

### 6. Agrupación automática al importar, ajustable manualmente
**Refinado durante la implementación:** el caso ya trae `folio_egreso` desde SGF (SGF agrupa sus pagos en egresos y entrega ese folio), y su centro financiero no se conoce al importar (llega con la vinculación manual al proceso de adquisición). Por eso `CasoPagoProveedorImporter` agrupa por `folio_egreso` (clave natural de SGF), no por `periodo`+`cfinanciero`. A `egresos_cgu` se agregan `periodo`, `cfinanciero_id` (nullable, poblado cuando el caso está vinculado) y `generado_automaticamente`. El Egreso automático usa `numero_egreso = folio_egreso`, acumula `monto_total` y deriva su jurisdicción de `cfinanciero_id`. La creación/edición manual de Egresos (ya existente en `EgresoCguController`) permite reasignar casos antes de enviar a revisión, sin tocar `sgf_id`, snapshots ni historial.

### 7. Frontend
Nueva pantalla `resources/js/pages/pago-proveedores/revision/index.tsx` (+ componentes) que reproduce el layout del prototipo con los tokens de tema del proyecto (tipografía reducida, botones outline) y el patrón de listado denso donde aplique. Datos vía Inertia + Resources; acciones vía helpers Wayfinder (`wayfinder:generate --with-form`). Las acciones se muestran/ocultan según `auth.permissions` y la instancia activa del Egreso. Ítem de sidebar condicionado por permiso.

## Risks / Trade-offs

- **Un Egreso que mezcla jurisdicciones rompería el scope zonal** → La agrupación automática particiona por centro financiero (que rola a una jurisdicción); la creación manual valida que todos los casos del Egreso compartan jurisdicción antes de permitir enviarlo a revisión.
- **Reemplazar `en_revision_documental` puede afectar casos ya en ese estado** → Migración de datos: mapear casos en `en_revision_documental` a `en_revision_finanzas` (ver Migration Plan); el seeder usa `firstOrCreate` y no elimina el estado antiguo.
- **La instancia activa derivada puede ser ambigua si los pagos de un Egreso quedan en instancias distintas** → El avance del Egreso es todo-o-nada (decisión 2), así que en operación normal todos los pagos comparten instancia; la UI muestra "en tránsito" y bloquea acciones de Egreso hasta homogeneizar, permitiendo aún acción pago por pago.
- **`validaciones_documento` gana una dimensión** → `instancia` nullable preserva compatibilidad; la resolución de estado vigente cae al comportamiento actual cuando `instancia` es null.
- **Rendimiento de la pantalla (N+1 al derivar estados de Egresos y jurisdicciones)** → Eager loading de casos/validaciones/proceso; verificar `toSql()`/EXPLAIN antes de agregar índices (lección de auditoría previa). Si el estado derivado se recalcula en cada request compartido, cachear con TTL corto + invalidación en la escritura.

## Migration Plan

1. Migraciones: agregar `validaciones_documento.instancia` (nullable) + índice; agregar `egresos_cgu.periodo`, `egresos_cgu.cfinanciero_id` (nullable) + índices.
2. Seeder de workflow: agregar estados/transiciones nuevos (idempotente vía `firstOrCreate`).
3. Seeder de roles/permisos: crear roles `jefe_finanzas`, `administrador_zonal` y permisos `pago_proveedores.revisar_finanzas`, `pago_proveedores.revisar_zonal`; asignarlos a `admin`.
4. Migración de datos (data migration o comando): transicionar casos actualmente en `en_revision_documental` a `en_revision_finanzas` **vía `TransicionWorkflowService`** (no UPDATE directo), o dejarlos y agregar transición puente si no se quiere mover histórico. A confirmar según haya datos productivos.
5. `wayfinder:generate --with-form` para los nuevos endpoints.
6. **Rollback:** los estados/transiciones/roles nuevos son aditivos; revertir implica quitar el ítem de sidebar y las rutas nuevas. La columna `instancia` nullable puede quedar sin efecto. Ningún dato se destruye.

## Open Questions

- ¿Cómo se asocia hoy un `User` a su jurisdicción/zona? De existir un vínculo institucional en `User`, la policy lo usa directo; si no, se define en implementación (rol + centro/jurisdicción del usuario) — no bloquea el diseño pero sí la policy final.
- ¿Hay casos productivos en `en_revision_documental` que deban migrarse (paso 4)? Determina si la data migration es necesaria.
- ¿La verificación de totales se persiste en un registro de revisión del caso por instancia (nuevo pivote) o como bandera derivable de las validaciones? Se decide en `apply` según encaje con `ValidacionDocumento`.

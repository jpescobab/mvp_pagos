## Context

`RequisitoDocumental` (tabla `requisitos_documentales`) es una tabla compartida entre dos conjuntos de requisitos (`ConjuntoRequisitosDocumentales`): `pago_proveedores` (escalado por `tipo_proceso_pago_id`, seed en `RequisitosDocumentalesPagoProveedoresSeeder.php`) y `adquisiciones` (escalado por `modalidad_id`, seed en `RequisitosDocumentalesAdquisicionesSeeder.php`). Hoy solo existe como datos seedeados; no hay controlador, policy, ni página que permita editarla. `TipoProcesoPago` y `TipoDocumento` tampoco tienen CRUD — ambos solo se pueblan vía seeder.

`ResolutorChecklistDocumentalProceso::requisitosAplicables()` ya lee `requisitos_documentales` en cada carga del detalle de un caso (filtrando por `definicion_workflow_id`, `modalidad_id`, `tipo_proceso_pago_id`, `estado_workflow_id`, rango de `monto`), así que cualquier fila que este change cree/edite/desactive se refleja de inmediato sin trabajo adicional.

## Goals / Non-Goals

**Goals:**
- Permitir administrar `TipoProcesoPago` y `TipoDocumento` sin tocar código.
- Permitir asignar/quitar obligatoriedad documental por tipo de proceso de pago desde una matriz visual, con persistencia inmediata por celda.
- Preservar la semántica de "requisito universal" (`tipo_proceso_pago_id = null`) ya usada por Factura y Comprobante.
- No afectar ni exponer las filas de `requisitos_documentales` que pertenecen al conjunto `adquisiciones`.

**Non-Goals:**
- No se construye UI para las dimensiones `estado_workflow_id` ni `monto_desde`/`monto_hasta` de `RequisitoDocumental` — la matriz siempre crea/edita filas con esos campos en `null` (mismo comportamiento que el seeder actual).
- No se migra la columna `tipo_requisito` a un enum de base de datos — se formaliza solo a nivel de código (una constante/enum PHP compartida), sin migración de esquema.
- No se toca `RequisitosDocumentalesPagoProveedoresSeeder.php` ni `RequisitosDocumentalesAdquisicionesSeeder.php`.
- No se construye administración para el conjunto `adquisiciones` en este change (aunque el CRUD de `TipoDocumento`, por ser un catálogo compartido, sí les sirve indirectamente).

## Decisions

**1. Nueva capability agrupa 3 piezas relacionadas en vez de 3 changes separados.**
CRUD de `TipoProcesoPago`, CRUD de `TipoDocumento` y la matriz de asignación se entregan juntos porque la matriz depende funcionalmente de que existan tipos de proceso y tipos de documento nuevos para tener sentido (el caso de uso real — "agregar Consumos básicos y FURBS" — requiere las tres piezas). Separarlos habría creado una dependencia secuencial entre 3 changes de OpenSpec sin beneficio real.

**2. `TipoProcesoPagoController` y `TipoDocumentoController` siguen el patrón exacto de `CfinancieroController`** (`app/Http/Controllers/Maestros/CfinancieroController.php`): controlador delgado, `index()`/`create()`/`store()`/`show()`/`edit()`/`update()`/`destroy()`, Form Requests (`StoreTipoProcesoPagoRequest`, `UpdateTipoProcesoPagoRequest`, análogas para `TipoDocumento`) con `authorize()` vía Policy, `Inertia::flash('toast', ...)` + `to_route()`, y `destroy()` bloqueado con un toast de error si existen `RequisitoDocumental` relacionados (ambas tablas) o `Documento` relacionados (solo `TipoDocumento`, FK `restrictOnDelete` desde `documentos`) — mismo helper `relacionQueImpideEliminar()` que ya usan los maestros existentes.
Rutas: `routes/maestros.php`, prefijo `maestros.tipos-proceso-pago.*` y `maestros.tipos-documento.*` — consistente con que `TipoDocumento` es un catálogo general (no exclusivo de Pago de Proveedores) y `TipoProcesoPago`, aunque hoy solo lo usa Pago de Proveedores, es conceptualmente una tabla de catálogo del mismo tipo que el resto de `maestros/*`.

**3. Permiso dividido: `pago_proveedores.administrar_requisitos_documentales` para `TipoProcesoPago` + matriz, `core_institucional.administrar` para `TipoDocumento`.**
`TipoDocumento` es un catálogo compartido con Adquisiciones (vía `modalidad_id` en `RequisitoDocumental`) y con el expediente documental general (`Documento.tipo_documento_id`) — reutilizar `core_institucional.administrar`, el permiso que ya gobierna todos los catálogos compartidos (`Cfinanciero`, `Ccosto`, `Item`, `Proveedor`, `ClienteMedidor`), evita crear un segundo permiso "administrar catálogo" con superposición de alcance. `TipoProcesoPago` y la matriz sí son exclusivos de Pago de Proveedores, así que usan el permiso nuevo del módulo, siguiendo la convención `modulo_accion.verbo`. Alternativa descartada: un único permiso nuevo para las 3 piezas — se descarta porque mezclaría un catálogo compartido (`TipoDocumento`) bajo un permiso de un solo módulo, inconsistente con cómo ya se protege ese catálogo en el resto del sistema (indirectamente, hoy nadie lo edita, pero cuando se vincula/valida un documento se usa `documentos.gestionar`/`documentos.validar`, permisos de alcance general, no de un módulo).

**4. La matriz es una página nueva (`resources/js/pages/pago-proveedores/requisitos-documentales/matriz.tsx`), no una extensión de los maestros.**
Aunque reutiliza los catálogos `TipoProcesoPago`/`TipoDocumento`, la matriz en sí es una vista de configuración específica de Pago de Proveedores (filtrada al conjunto `pago_proveedores`), así que vive bajo `resources/js/pages/pago-proveedores/` y su ruta bajo `routes/pago-proveedores.php` (`pago-proveedores.requisitos-documentales.*`), no bajo `maestros`.

**5. Endpoint de la matriz: una sola acción `PUT` idempotente por celda, no un CRUD de `RequisitoDocumental` genérico.**
`PUT /pago-proveedores/requisitos-documentales/{tipoDocumento}` recibe `{ tipo_proceso_pago_id: int|null, tipo_requisito: 'obligatorio'|'opcional'|null }` (`null` en `tipo_requisito` = "no aplica", equivalente a eliminar la fila si existe). El controlador hace `updateOrCreate`/`delete` sobre `RequisitoDocumental` fijando siempre `conjunto_requisitos_documentales_id` (resuelto server-side por código `pago_proveedores`, nunca enviado por el cliente), `definicion_workflow_id` (ídem, código `pago_proveedores`), `modalidad_id = null`, `estado_workflow_id = null`, `monto_desde = null`, `monto_hasta = null`. Esto hace imposible que el cliente escriba accidentalmente una fila del conjunto `adquisiciones`, y coincide con la interacción "clic en una celda = un request" descrita en el proposal. Alternativa descartada: exponer un CRUD genérico de `RequisitoDocumental` con todos sus campos editables desde el cliente — se descarta por el riesgo de que un payload mal formado cruce hacia filas de `adquisiciones` o hacia las dimensiones fuera de alcance (`estado_workflow_id`, montos).

**6. `TipoRequisitoDocumental` como enum de PHP (backed enum de string) en `app/Enums/`**, con valores `Obligatorio = 'obligatorio'` y `Opcional = 'opcional'`, usado en el nuevo controlador/Form Request de la matriz y en el cast del modelo `RequisitoDocumental::tipo_requisito`. El resto del código que ya compara contra los strings literales (`ResolutorChecklistDocumentalProceso`, el seeder) no se modifica en este change — se deja como deuda documentada, no se fuerza una migración de todos los call sites para mantener el diff acotado.

**7. `TipoProcesoPagoController`/`TipoDocumentoController::index()` no necesitan el patrón de "listado denso" de tablas grandes** (búsqueda con debounce, paginación) dado el volumen esperado (decenas de filas, no miles) — se listan todas activas + inactivas en una sola tabla simple, ordenadas por nombre, siguiendo igual el resto de convenciones visuales del proyecto (avatar/badge de estado, acciones en dropdown) pero sin paginación ni buscador, que serían sobre-ingeniería para este volumen.

## Risks / Trade-offs

- [Riesgo] Desactivar un `TipoProcesoPago` o `TipoDocumento` que ya está referenciado por casos existentes (`Proceso.tipo_proceso_pago_id`, `Documento.tipo_documento_id`) podría dejar casos "huérfanos" mostrando un tipo inactivo. → Mitigación: `activo = false` es un soft-toggle, no un `destroy()` — los casos existentes siguen resolviendo su relación normalmente (el modelo no filtra por `activo` al cargar una relación ya asignada); las páginas de selección (`Select` de tipo de proceso en el detalle de un caso, `TipoDocumentoSeleccionable` al subir un documento) ya filtran `where('activo', true)`, así que un tipo desactivado simplemente deja de ofrecerse para casos nuevos.
- [Riesgo] Un usuario podría desactivar o eliminar (bloqueado por FK, pero podría desactivar) un requisito que hace que ningún caso pueda nunca completar su checklist obligatorio (ej. dejar 0 documentos obligatorios para un tipo de proceso). → Mitigación: es una decisión de negocio legítima (ej. un tipo de proceso que no requiere ningún documento), no se bloquea; el criterio `listo_para_egreso` ya maneja correctamente "0 obligatorios pendientes" como completo (`ListoParaEgresoResolver`).
- [Riesgo] La matriz podría crecer mucho si se agregan muchos tipos de documento/proceso (grilla NxM). → Mitigación: fuera de alcance optimizar para volumen alto en este change; con los ~13-20 tipos de documento y ~6-10 tipos de proceso esperados, una tabla HTML simple con scroll horizontal es suficiente (mismo patrón de "tabla ancha con `overflow-x: auto`" ya usado en el resto del proyecto).

## Migration Plan

1. Migraciones nuevas: ninguna — `tipos_proceso_pago`, `tipos_documento` y `requisitos_documentales` ya existen; este change es puramente CRUD/UI sobre tablas existentes.
2. Deploy normal (backend + `npm run build`), sin downtime.
3. Rollback: revertir el commit deja el sistema en el estado actual (matriz y CRUDs dejan de estar disponibles); los datos ya creados/editados a través de la UI permanecen en `requisitos_documentales`/`tipos_proceso_pago`/`tipos_documento` sin pérdida, y siguen siendo leídos igual por `ResolutorChecklistDocumentalProceso` aunque ya no haya UI para seguir editándolos.

## 1. Migración

- [x] 1.1 Crear migración `procesos_adquisicion` (`codigo` string unique, `modalidad_id` FK `modalidades_adquisicion` no nullable, `ccosto_id` FK `ccostos` no nullable, `proveedor_id` FK `proveedores` nullable, `monto` decimal nullable, `objeto` text)

## 2. Modelo Eloquent

- [x] 2.1 Crear `ProcesoAdquisicion` (belongsTo modalidad/ccosto/proveedor; `proceso()` como `MorphOne` vía `sujeto`, igual patrón que `CasoPagoProveedor`)

## 3. Workflow "adquisiciones"

- [x] 3.1 Crear `WorkflowAdquisicionesSeeder`: sembrar permisos `adquisiciones.publicar`, `adquisiciones.adjudicar`, `adquisiciones.anular` (otorgados al rol `admin`); `DefinicionWorkflow` codigo `adquisiciones` + 8 `EstadoWorkflow` (`borrador` es_inicial; `cerrada`/`rechazada`/`anulada` es_final) + 8 `TransicionWorkflow` según design.md decisión 4 (incluye `documentos_requeridos: ['CONTRATO']` en `formalizar_contrato` y `permiso_requerido` en `publicar`/`adjudicar`/`anular`)
- [x] 3.2 Crear `ModalidadesAdquisicionSeeder`: sembrar `LICITACION_PUBLICA`, `LICITACION_PRIVADA`, `TRATO_DIRECTO`, `CONVENIO_MARCO` en `modalidades_adquisicion` (hoy vacía)
- [x] 3.3 Registrar ambos seeders en `DatabaseSeeder`

## 4. Servicio de creación

- [x] 4.1 Crear `App\Services\Adquisiciones\ProcesoAdquisicionService::crear(array $datos): ProcesoAdquisicion` — valida que la modalidad referenciada exista y esté activa, crea el `ProcesoAdquisicion` y su `Proceso` asociado en una transacción, asignando `estado_actual_id` al estado `es_inicial` del workflow "adquisiciones" (sin pasar por `TransicionWorkflowService`, igual que `CasoPagoProveedorImporter`)

## 5. Autorización

- [x] 5.1 Crear `ProcesoAdquisicionPolicy` con reglas básicas `view`/`create` (sin controladores HTTP en este change)

## 6. Tests

- [x] 6.1 Test feature: crear un proceso de adquisición con una modalidad activa crea `ProcesoAdquisicion` + `Proceso` en estado `borrador`
- [x] 6.2 Test feature: crear un proceso de adquisición con una modalidad inexistente o inactiva es rechazado y no crea ningún registro
- [x] 6.3 Test feature: el seeder de workflow "adquisiciones" permite ejecutar una transición real (`enviar_a_revision`) vía `TransicionWorkflowService`
- [x] 6.4 Test feature: la transición `formalizar_contrato` se bloquea sin un documento `CONTRATO` vinculado y validado, y se permite una vez vinculado
- [x] 6.5 Test feature: las transiciones `publicar`/`adjudicar`/`anular` se bloquean sin el permiso requerido
- [x] 6.6 Test feature: el checklist documental de un proceso de adquisición se resuelve según su `modalidad_id`, reutilizando `ResolutorChecklistDocumentalProceso` sin cambios

## 7. Validación

- [x] 7.1 `composer lint:check`
- [x] 7.2 `composer types:check`
- [x] 7.3 `php artisan test --compact`

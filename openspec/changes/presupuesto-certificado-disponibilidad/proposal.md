## Why

El módulo Presupuesto hoy solo importa el monto asignado desde CGU
(`presupuesto-importacion-cgu`, ya archivado) — no existe ninguna forma de comprometer ese
presupuesto contra una compra real. La pieza que falta es el **CDP** (Certificado de
Disponibilidad Presupuestaria): el documento que CAPJ Coyhaique ya emite hoy manualmente para
autorizar el gasto y reservar la línea presupuestaria antes de comprar. Sin el CDP modelado en el
sistema, el módulo Presupuesto no cumple su propósito de capa de control — solo muestra montos
asignados sin ningún mecanismo de compromiso ni evidencia del gasto reservado.

El alcance de este change se definió con evidencia real (6 PDF de CDP emitidos por CAPJ y la
planilla de control que la Corporación usa hoy), no de forma teórica.

## What Changes

- Nuevo modelo `CertificadoDisponibilidadPresupuestaria` (namespace `App\Models\Presupuesto`),
  con ciclo de vida **Borrador → Firmado** gobernado por `TransicionWorkflowService` (nueva
  `DefinicionWorkflow` `presupuesto_cdp`), siguiendo el mismo patrón de `ProcesoAdquisicion` +
  `WorkflowAdquisicionesSeeder` ya existente para Adquisiciones.
- El Borrador reserva folio (`CDP {correlativo}-{año}`) sin comprometer presupuesto; **Firmar** sí
  compromete — valida saldo disponible, alerta si hay sobregiro (no bloquea), y genera el PDF
  oficial replicando la plantilla exacta de CAPJ.
- Una sola cuenta presupuestaria por CDP (sin líneas múltiples) — confirmado con evidencia real:
  la plantilla no tiene fila repetible.
- Soporte multi-moneda (CLP/UF/USD) con `paridad` cuando la moneda de compra no es CLP.
- **Anulación** modelada como un CDP nuevo con monto 100% negativo (nunca parcial), referenciando
  al original vía `cdp_original_id` — un CDP firmado nunca cambia de estado ni se edita.
- Vínculo opcional a una Adquisición (`proceso_adquisicion_id`, nullable) y opcionalmente a una
  Orden de Compra o Licitación de Mercado Público ya importada — ambos son **solo FK de datos**,
  sin gate de workflow: no se modifica `WorkflowAdquisicionesSeeder` en este change.
- Registro del PDF firmado como `Documento` de tipo `CDP`, vinculado por `VinculoDocumento` al
  expediente del propio CDP (y opcionalmente al de la Adquisición vinculada), reutilizando la
  infraestructura de expediente documental existente.
- Nuevos permisos `presupuesto.crear_cdp`, `presupuesto.firmar_cdp`, `presupuesto.anular_cdp`.
- Nuevo tipo de movimiento presupuestario (`movimientos_presupuestarios`, tipo `compromiso`) que
  descuenta saldo disponible de la línea de `presupuesto` afectada.

## Capabilities

### New Capabilities

- `presupuesto-certificado-disponibilidad`: emisión, firma y anulación del Certificado de
  Disponibilidad Presupuestaria — ciclo de vida Borrador→Firmado vía el motor de workflow
  genérico, cálculo de saldo comprometido por línea de presupuesto, plantilla PDF oficial, y
  vínculo opcional (no gobernante) hacia Adquisiciones y Mercado Público.

### Modified Capabilities

(ninguna — `presupuesto-importacion-cgu` no cambia sus requisitos; este change solo agrega
capacidad nueva sobre las líneas de `presupuesto` que ya expone)

## Impact

- **Nuevo**: `app/Models/Presupuesto/{CertificadoDisponibilidadPresupuestaria,MovimientoPresupuestario}.php`,
  `app/Services/Presupuesto/{CrearBorradorCertificadoDisponibilidadService,FirmarCertificadoDisponibilidadService,AnularCertificadoDisponibilidadService}.php`,
  `app/Http/Controllers/Presupuesto/{CertificadoDisponibilidadPresupuestariaController,TransicionCertificadoDisponibilidadController}.php`,
  `app/Policies/Presupuesto/CertificadoDisponibilidadPresupuestariaPolicy.php`,
  `database/migrations/*_create_certificados_disponibilidad_presupuestaria_table.php`,
  `database/migrations/*_create_movimientos_presupuestarios_table.php`,
  `database/seeders/WorkflowPresupuestoCdpSeeder.php`,
  `resources/views/presupuesto/cdp.blade.php` (plantilla PDF exacta),
  `resources/js/pages/presupuesto/cdp/*`, `tests/Feature/Presupuesto/*Cdp*Test.php`.
- **Modificado**: `routes/presupuesto.php` (nuevas rutas), `database/seeders/PresupuestoSeeder.php`
  (permisos nuevos), `app/Providers/AppServiceProvider.php` (registrar policy), `app/Models/Proceso.php`
  (`descriptorNotificacion()` gana un caso para el sujeto CDP), `app/Models/Adquisiciones/ProcesoAdquisicion.php`
  (nueva relación `HasMany cdps()`), `database/seeders/TiposDocumentoSeeder.php` (nuevo tipo `CDP`).
- **No modificado**: `WorkflowAdquisicionesSeeder`, `TransicionWorkflowService`,
  `ResolutorValidacionDocumental` — se reutilizan tal cual, sin cambios de comportamiento.

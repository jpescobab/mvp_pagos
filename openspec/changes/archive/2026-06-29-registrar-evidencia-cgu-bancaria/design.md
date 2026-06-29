## Context

`registros_contables_cgu` y `registros_pago_bancario` existen desde la tarea 8 (`pago-proveedores-sgf`) como tablas y modelos (`RegistroContableCgu`, `RegistroPagoBancario`) con sus relaciones `BelongsTo` hacia `CasoPagoProveedor` y `User` (`registrado_por`), pero nunca tuvieron controlador, ruta, policy ni página: cero código las usa fuera de las dos relaciones `HasMany` ya declaradas en `CasoPagoProveedor`. El propio `HARNESS_IA.md` (sección 10) ubica estos dos registros entre los estados `lista_para_registro_cgu → registrada_en_cgu` y `lista_para_pago → pagada_bancoestado` del workflow de Pago de Proveedores, y el seeder (`WorkflowPagoProveedoresSeeder`) ya gatea esas dos transiciones exactas con los permisos `pago_proveedores.registrar_cgu` y `pago_proveedores.pagar` — permisos que hoy solo se usan para la transición, nunca para el registro de evidencia.

`VinculoAdquisicionCasoPagoProveedorController` ya es el precedente directo en este mismo módulo de una acción que registra datos sobre un `caso_pago_proveedor` sin pasar por `TransicionWorkflowService` (porque no es un cambio de estado) y que se audita explícitamente vía `AuditLogger::log()`.

## Goals / Non-Goals

**Goals:**
- Registrar un `RegistroContableCgu` y un `RegistroPagoBancario` asociados a un `caso_pago_proveedor`, cada uno detrás de su permiso ya existente (`pago_proveedores.registrar_cgu`, `pago_proveedores.pagar`).
- Mostrar el historial completo de ambos registros (no solo el último) en `pago-proveedores/casos/show`, con un formulario para agregar uno nuevo, mismo patrón visual que las demás secciones de esa página.
- Auditar cada registro creado vía `AuditLogger`, mismo patrón que `caso_pago_proveedor.vincular_adquisicion`.

**Non-Goals:**
- No se acoplan estos registros al estado actual del `Proceso` (no se exige que el caso esté en `lista_para_registro_cgu` o `lista_para_pago` para registrar evidencia, ni se ejecuta ninguna transición como efecto del registro). Mismo criterio ya usado por `vincular_adquisicion`: es evidencia de gestión paralela al workflow, no un cambio de estado. Si en el futuro se decide acoplarlos a una transición específica, ese es un cambio de diseño distinto que afecta a `TransicionCasoPagoProveedorController` y a los tests ya archivados de `api-pago-proveedores` (que ejecutan `registrar_en_cgu` sin datos de registro y esperan éxito) — fuera de alcance aquí para no romper ese contrato ya probado.
- No se permite editar ni eliminar un registro ya creado — son evidencia append-only, igual que `validaciones_documento` e `historial_transiciones_workflow`.
- No se valida el `numero_registro`/`numero_operacion` contra ningún sistema externo real (CGU, BancoEstado): son campos de texto libre que el usuario transcribe manualmente, igual que ya documenta el design de `pago-proveedores-sgf` ("evidencia de registro manual, no snapshots de API").

## Decisions

1. **Reutilizar los permisos ya sembrados (`pago_proveedores.registrar_cgu`, `pago_proveedores.pagar`) en vez de crear permisos nuevos.** Ambos ya existen con el propósito conceptual correcto (gatear quién puede certificar que el caso fue registrado en CGU / pagado por BancoEstado); usarlos también para autorizar el registro de la evidencia mantiene un único permiso por concepto institucional en vez de duplicar uno paralelo solo porque la acción HTTP es distinta de la transición.
2. **Dos controladores delgados (`RegistroContableCguController`, `RegistroPagoBancarioController`), cada uno con un único método `store()`.** Mismo criterio que separó `DocumentoProcesoController` de `DocumentoEgresoCguController`: cada uno gatea un permiso distinto y crea un modelo distinto: forzarlos a un controlador combinado no simplifica nada y complica la autorización.
3. **Sin `destroy()` ni `update()`.** A diferencia de `vincular_adquisicion` (un FK nullable que se reemplaza/quita), estos registros son un historial append-only: cada llamada a `store()` agrega una fila nueva, nunca se modifica una existente. Si se registró un número de operación erróneo, la corrección es agregar un registro nuevo, no editar el anterior — mismo principio que ya rige `historial_transiciones_workflow` y `validaciones_documento`.
4. **`registrado_por` se completa con el usuario autenticado, no es un campo del formulario.** Mismo patrón que `validaciones_documento.validado_por`.
5. **El resource (`CasoPagoProveedorResource`) expone ambas colecciones completas (`registros_contables_cgu`, `registros_pago_bancario`), no solo el último registro.** El caso de uso institucional (evidencia ante observaciones/auditoría) requiere ver todo el historial, no solo el estado vigente.

## Risks / Trade-offs

- **[Riesgo] Sin acoplar el registro a la transición correspondiente, un usuario podría ejecutar `registrar_en_cgu` sin haber registrado nunca un `RegistroContableCgu`, o viceversa.** → Mitigación: aceptado explícitamente en esta tarea (ver Non-Goals); ambas transiciones ya están protegidas por su propio permiso, y este change agrega la capacidad de registrar evidencia sin tocar `TransicionWorkflowService` ni los tests ya archivados de `api-pago-proveedores`. Acoplarlos es un cambio de diseño futuro explícito, no un descuido.
- **[Riesgo] `monto` es nullable en ambas tablas (decisión de la tarea 8), pero omitirlo debilita el valor de la evidencia.** → Mitigación: se mantiene nullable en base de datos (no se toca el esquema), pero el Form Request de esta tarea SÍ lo exige (`required`) porque a diferencia de la importación SGF (donde el monto podía no estar disponible aún), aquí el usuario lo transcribe manualmente desde un comprobante real que ya tiene en mano.

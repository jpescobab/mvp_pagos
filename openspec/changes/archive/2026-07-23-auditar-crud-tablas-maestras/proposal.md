## Why

El catálogo de tablas maestras institucionales —centros financieros, centros de costo, proveedores, clientes medidores, ítems presupuestarios, tipos de documento, tipos de proceso de pago, asignaciones y catálogos— se crea, edita y elimina sin dejar ningún rastro en `audit_logs`. Los nueve controladores de `Maestros` mutan datos y ninguno registra la acción.

Es una inconsistencia con el resto del core, no una omisión deliberada:

- El harness pone la jerarquía institucional (`instituciones → jurisdicciones → cfinancieros → ccostos`) como lo que gobierna "permisos, filtros, reportes y **trazabilidad**", y lista la **auditoría** dentro del core no desactivable.
- Los otros dominios core ya auditan exactamente estas operaciones: usuarios (`crear_usuario`, `editar_usuario`, …) y roles (`crear_rol`, `editar_rol`, `eliminar_rol`). Las tablas maestras quedaron afuera.

Hoy, si alguien elimina un centro financiero o cambia el RUT de un proveedor, no hay forma de saber quién lo hizo ni cuándo. Estos son los datos que gobiernan la trazabilidad de todo lo demás: que ellos mismos no sean trazables es el hueco.

## What Changes

- El sistema SHALL registrar en `audit_logs`, mediante `AuditLogger`, la creación, edición y eliminación de cada tabla maestra institucional: `Cfinanciero`, `Ccosto`, `Proveedor`, `ClienteMedidor`, `Item`, `Asignacion`, `Catalogo`, `TipoDocumento` y `TipoProcesoPago`.
- Cada registro SHALL guardar la entidad afectada (`auditable_type`/`auditable_id`), el usuario responsable, y el estado anterior y nuevo de los atributos que cambiaron.
- La auditoría SHALL capturarse mediante un mecanismo común (un trait de modelo que engancha los eventos de Eloquent), en vez de repetir la llamada en cada uno de los ~27 métodos de controlador. Esto mantiene los controladores livianos que ya existen sin tocarlos.
- La auditoría SHALL registrarse únicamente cuando la mutación ocurre en el contexto de un usuario autenticado. Las escrituras sin usuario —seeders, migraciones, jobs de importación— SHALL NOT generar registros de auditoría, para no inundar `audit_logs` con ruido de siembra y sincronización.
- Sin permisos nuevos, sin migraciones de esquema. `audit_logs` ya existe y `AuditLogger` ya provee el registro genérico.

## Capabilities

### New Capabilities

Ninguna. La auditoría de negocio ya es una capability existente; esto extiende su cobertura.

### Modified Capabilities

- `seguridad-auditoria`: se agrega el requirement de que las mutaciones del catálogo de tablas maestras institucionales queden auditadas, junto a las de usuarios, roles y workflow que ya lo están.

## Impact

- **Backend**: un trait nuevo (p. ej. `App\Models\Concerns\RegistraAuditoria`) que en `booted()` engancha los eventos `created`, `updated` y `deleted` de Eloquent y delega en `AuditLogger`, resolviéndolo del contenedor. Se agrega el `use` del trait a los nueve modelos maestros. Los controladores de `Maestros` **no** se tocan.
- **Auditoría**: nuevas acciones con la convención `<verbo>_<entidad>` ya usada por usuarios/roles (`crear_cfinanciero`, `editar_proveedor`, `eliminar_cliente_medidor`, …), derivadas del nombre del modelo. El `before`/`after` se toma de `getOriginal()`/`getChanges()` de Eloquent, de modo que un `update` registra solo los atributos que cambiaron.
- **Soft deletes**: cinco de los nueve modelos usan `SoftDeletes` y cuatro no; el evento `deleted` de Eloquent cubre ambos casos, así que la eliminación se audita igual sea lógica o física.
- **Tests**: `tests/Feature/Maestros/` y/o `tests/Feature/Seguridad/`. No hay tests existentes que cuenten `audit_logs` sobre modelos maestros, así que no se rompe ninguno por el registro nuevo.
- **Rendimiento**: una escritura extra en `audit_logs` por mutación de catálogo. Las tablas maestras se modifican con baja frecuencia (son configuración institucional, no datos transaccionales), así que el costo es despreciable.
- Sin permisos nuevos, sin cambios en seeders de roles, sin migraciones.

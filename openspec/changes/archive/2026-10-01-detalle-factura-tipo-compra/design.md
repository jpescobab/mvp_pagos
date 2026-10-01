## Context

`Factura` (`app/Models/Factura.php`) es hoy un registro plano ligado a `CasoPagoProveedor` (`hasMany`) con solo `folio`/`monto`/`fecha_emision`, registrado a través de un único endpoint `FacturaController::store` cuya policy (`CasoPagoProveedorPolicy::registrarFactura`) solo chequea el permiso — nunca el estado del `Proceso`/workflow. El detalle de qué se compró (tipo de compra, cantidad, centro de costo) no existe en ningún lugar. El usuario necesita capturarlo sin que su ausencia bloquee el pago del caso, exactamente como ya ocurre hoy con la propia `Factura` (un caso puede pagarse sin ninguna factura registrada).

Se calibró el diseño contra el módulo `ConsumoBasico`, construido en el change inmediatamente anterior — mismo problema en apariencia ("detalle independiente del pago"), pero con una diferencia estructural clave: `ConsumoBasico` se vincula 1:1 y **obligatoriamente** a `CasoPagoProveedor` (porque un caso de servicios básicos tiene una sola boleta/factura relevante). Aquí un caso puede tener **varias** `Factura` (`CasoPagoProveedor::facturas()` es `hasMany`), y cada una puede necesitar su propio detalle — por eso el vínculo debe ser con `Factura`, no con `CasoPagoProveedor`, y debe ser opcional (no toda factura necesita detalle de inmediato).

El usuario confirmó explícitamente que `ConsumoBasico` queda intacto — este change no lo toca, no lo generaliza ni reutiliza su infraestructura de tablas/permisos.

## Goals / Non-Goals

**Goals:**
- Registrar el detalle de una `Factura` (tipo de compra, centro de costo, cantidad, unidad de medida, monto) de forma completamente independiente del estado de pago del `CasoPagoProveedor`.
- Permitir que una `Factura` sin detalle registrado no bloquee ni condicione el pago del caso — el detalle es puramente informativo.
- Clasificar el tipo de compra mediante un catálogo abierto y editable desde la UI (sin tocar código para agregar/renombrar tipos).

**Non-Goals:**
- No se modela más de un `DetalleFactura` por `Factura` (sin líneas/ítems múltiples) — si aparece esa necesidad, se aborda en un change futuro con evidencia real de que hace falta desglosar por ítem.
- No se modifica `ConsumoBasico` ni se intenta unificar ambos módulos bajo una abstracción común — son dos necesidades estructuralmente distintas (1:1 con `CasoPagoProveedor` vs. 1:1 opcional con `Factura`).
- No se deriva el centro de costo automáticamente desde ninguna relación (a diferencia de `ConsumoBasico`, que lo saca de `ClienteMedidor`) — aquí se selecciona manualmente, porque no existe un vínculo estructural equivalente entre una compra de bienes/servicios genéricos y un centro de costo.
- No se agrega ningún endpoint de listado/consulta agregada de detalles (ej. reportes por tipo de compra) — queda para un change futuro si se confirma la necesidad.

## Decisions

### 1. `DetalleFactura` vinculado 1:1 a `Factura`, con FK única pero NO obligatoria
`factura_id` es `unique` (impide más de un detalle por factura) pero la ausencia de `DetalleFactura` es un estado válido y esperado — no hay ninguna regla que exija su existencia para que la `Factura`/el caso avancen. Esto es lo opuesto a la Decision 1 de `ConsumoBasico` (`caso_pago_proveedor_id` `NOT NULL`) precisamente porque el flujo real es distinto: en Consumo Básico el registro es una acción puntual sobre un caso que el usuario decide completar; aquí, con potencialmente varias facturas por caso, exigir el detalle bloquearía el registro de facturas simples sin aportar valor.

Alternativa descartada: reutilizar el vínculo 1:1-obligatorio de `ConsumoBasico` aplicado a `Factura` — se descarta porque contradice el requisito explícito del usuario ("se puede pagar aun cuando no se le ha ingresado su detalle").

### 2. Catálogo `TipoCompra` con CRUD completo en Maestros, mismo patrón que `TipoProcesoPago`
`TipoProcesoPago` (`app/Models/TipoProcesoPago.php`, `app/Http/Controllers/Maestros/TipoProcesoPagoController.php`) ya resuelve exactamente este problema: catálogo simple `codigo`/`nombre`/`activo`, administrable sin tocar código. Se replica tal cual (no se reutiliza `Item`/`Catalogo`, que es un catálogo jerárquico genérico sin relación con `Factura`/`ProcesoAdquisicion` y agregaría complejidad — anidamiento item→catálogo — que no aporta nada aquí). Sembrado inicial: `BIENES`, `SERVICIOS_GENERALES`, `OBRAS` — el usuario los ajustará después desde la UI.

Alternativa descartada: campo de texto libre en `DetalleFactura` en vez de catálogo — se descarta porque el usuario pidió explícitamente un catálogo editable, y porque texto libre impediría filtrar/reportar por tipo de compra de forma consistente más adelante.

### 3. Centro de costo: selección manual desde `Ccosto`, sin herencia automática
A diferencia de `ConsumoBasico` (que resuelve el `ccosto_id` a través de `ClienteMedidor`), aquí no existe ningún vínculo estructural entre una `Factura` genérica y un centro de costo — el `ProcesoAdquisicion` opcionalmente vinculado al caso tiene su propio `ccosto_id`, pero exigir esa cadena completa (caso → adquisición → ccosto) fallaría para el caso común de una factura sin adquisición vinculada. Se opta por selección manual simple, reutilizando el mismo patrón ya usado en `ConsumoBasicoController::create()` (`Ccosto::all(['id','codigo','nombre'])` como opciones de un `Select`).

Alternativa descartada: heredar del `ProcesoAdquisicion` vinculado cuando exista, con fallback a manual — se descarta por complejidad innecesaria para el alcance de este change; si se confirma que la mayoría de los casos sí tienen adquisición vinculada y el usuario quiere ese atajo, se agrega después con evidencia real de uso.

### 4. `unidad_medida` como texto libre, sin catálogo de unidades
Mismo criterio ya usado en `ContratoItemConvenioPrecio.unidad_medida` (string nullable, sin catálogo) — no hay evidencia de que valga la pena un catálogo cerrado de unidades de medida para este alcance.

### 5. Permisos bajo el namespace `pago_proveedores.*`
A diferencia de `ConsumoBasico` (que introdujo un dominio nuevo y bridgea Adquisiciones/Pago de Proveedores vía `ClienteMedidor`, justificando su propio namespace `consumo_basico.*`), `DetalleFactura` es una extensión directa y puramente interna de `Factura`, que ya vive bajo `pago_proveedores.registrar_factura`. Se agregan `pago_proveedores.registrar_detalle_factura` y `pago_proveedores.administrar_tipos_compra` al mismo seeder (`WorkflowPagoProveedoresSeeder`), otorgados a `admin` y `administrativo_finanzas` (mismo rol operativo que ya registra facturas).

### 6. `FacturaController` gana un método `show`
Hoy no existe ninguna ruta para ver una `Factura` individual (solo `store`). Se agrega el mínimo necesario (`show`, cargando `detalle.tipoCompra`/`detalle.ccosto`) en vez de construir un controlador paralelo — `Factura` sigue siendo el recurso principal, `DetalleFactura` es su detalle asociado.

## Risks / Trade-offs

- [Riesgo] Sin herencia automática de centro de costo, el usuario debe re-seleccionarlo manualmente para cada factura aunque todas pertenezcan al mismo caso/adquisición → Mitigación: aceptado como no-goal explícito; si resulta tedioso en la práctica, se agrega la herencia desde `ProcesoAdquisicion` en un change de seguimiento con evidencia real de fricción.
- [Riesgo] Sin líneas/ítems múltiples por factura, una factura que en realidad contiene compras de varios tipos distintos no se puede desglosar → Mitigación: aceptado como no-goal explícito, mismo criterio que "sin tabla de líneas de cargo" en `ConsumoBasico` — se aborda si aparece evidencia real de que hace falta.
- [Riesgo] Dos módulos con forma similar (`ConsumoBasico` y `DetalleFactura`) pero sin abstracción compartida puede generar la tentación de unificarlos más adelante sin evidencia suficiente → Mitigación: se documenta explícitamente aquí por qué son distintos (1:1 obligatorio vs. opcional, origen del centro de costo) para que una futura unificación parta de esta decisión informada, no la ignore.

## Migration Plan

1. Migración `create_tipos_compra_table` (aditiva) + seeder idempotente con los 3 tipos base.
2. Migración `create_detalles_factura_table` (aditiva, FK única a `facturas`) — sin tocar datos existentes.
3. `Factura` gana `detalle(): HasOne` — no afecta las relaciones/queries existentes de `Factura`.
4. Permisos nuevos, aditivos e idempotentes (`firstOrCreate`), otorgados a `admin`/`administrativo_finanzas`.
5. Sin cambios al workflow de `CasoPagoProveedor` ni a `ConsumoBasico`.

## Open Questions

- ¿El permiso `pago_proveedores.administrar_tipos_compra` debería en cambio reutilizar `pago_proveedores.administrar_requisitos_documentales` (ya existe, mismo "nivel" administrativo) en vez de crear uno nuevo? Se propone uno nuevo por claridad semántica (administrar un catálogo de tipos de compra es conceptualmente distinto de administrar requisitos documentales), a confirmar en `/opsx:apply`.
- ¿`DetalleFacturaController`/`TipoCompraController` viven en `app/Http/Controllers/PagoProveedores/` y `app/Http/Controllers/Maestros/` respectivamente (como en el resto del proyecto), o conviene agruparlos en un namespace propio? Se asume la ubicación por dominio ya usada en todo el proyecto (mismo criterio que `FacturaController` y `TipoProcesoPagoController`).

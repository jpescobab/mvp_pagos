## Context

`OrdenCompraMercadoPublicoService` resuelve el proveedor emisor al guardar una OC nueva (`guardarDesdeApi()` → `resolverProveedor()`, dentro de una `DB::transaction`). Hoy `normalizarPayload()` solo extrae `rut` y `nombre` del bloque `Proveedor` del payload de Mercado Público, y `resolverProveedor()` crea/completa el proveedor solo con esos dos campos — pese a que el payload trae `Direccion`, `Comuna`, `Region`, `Actividad`, `MailContacto`, `NombreContacto`, `CargoContacto`, `FonoContacto`, y el modelo `Proveedor` tiene columnas para todos (`direccion`, `comuna`, `region`, `giro`, `correo`, `contacto`, `contacto_cargo`, `contacto_telefono`).

El modelo `Proveedor` tiene un unique constraint de BD sobre `rutproveedor` (`proveedores_rutproveedor_unique`) y usa `SoftDeletes`. Un proveedor creado con `rutproveedor = ''` (cuando falta el RUT) ocupa ese índice y hace que una segunda OC sin RUT falle con unique violation, abortando toda la transacción.

## Goals / Non-Goals

**Goals:**
- Guardar el proveedor emisor de una OC con todos los datos que Mercado Público entrega.
- Completar campos vacíos de un proveedor ya existente con esos datos, sin pisar valores ya cargados.
- Manejar de forma segura una OC sin RUT de proveedor identificable.

**Non-Goals:**
- Backfill retroactivo de proveedores ya creados con solo RUT+nombre (se completan la próxima vez que se guarde/actualice una OC de ese proveedor).
- Cambiar el modelo de datos (las columnas ya existen) ni el frontend.
- Hacer `orden_compra_mercado_publico.proveedor_id` nullable.

## Decisions

### D1 — Mapeo payload → modelo, normalizando vacíos a null
`normalizarPayload()` amplía el bloque `proveedor` con: `direccion` (`Direccion`), `comuna` (`Comuna`), `region` (`Region`), `giro` (`Actividad`), `correo` (`MailContacto`), `contacto` (`NombreContacto`), `contacto_cargo` (`CargoContacto`), `contacto_telefono` (`FonoContacto`). Mercado Público entrega strings vacíos o con solo espacios en estos campos (`MailContacto: ""`, `NombreContacto: " "`), por lo que cada valor se normaliza a `null` cuando queda vacío tras `trim`, para no guardar basura ni marcar como "completo" un campo que en realidad viene vacío.

### D2 — Completar campos vacíos de forma genérica
`resolverProveedor()` reemplaza el completado hoy hardcodeado a `nombre` por un recorrido sobre todos los campos mapeados: para un proveedor existente, se rellenan solo los que están vacíos localmente (`null`/`''`) y que el payload aporta con valor, sin sobrescribir ninguno ya cargado. Para un proveedor nuevo, se crean con todos los campos disponibles.

### D3 — Salvaguarda de RUT ausente
Cuando no hay override manual y el RUT normalizado del payload es vacío, `resolverProveedor()` lanza una excepción de dominio (`OrdenCompraSinProveedorException`) **antes** de intentar crear el proveedor, evitando tanto el proveedor basura como el rollback por unique violation. `OrdenCompraMercadoPublicoController::guardar()` la captura y responde con un error claro (`back()->withErrors(...)`), sin persistir la OC. **Alternativa descartada:** hacer `proveedor_id` nullable y guardar la OC sin proveedor — más invasivo (migración) y deja OCs sin emisor, contrario al modelo actual.

## Risks / Trade-offs

- [Sobre-normalización: un campo con contenido legítimo pero raro podría perderse] → Solo se aplica `trim` + vacío→null; no se filtra contenido con caracteres válidos.
- [Rechazar OCs sin RUT podría bloquear un caso legítimo] → En Mercado Público el emisor siempre trae RUT; el caso es defensivo. El mensaje de rechazo es explícito para que el usuario lo reporte si ocurre con una OC real.
- [Completar campos de un proveedor existente podría sorprender] → Solo se rellenan campos vacíos, nunca se sobrescribe un valor ya cargado (regla ya vigente para `nombre`).

## Migration Plan

Cambio solo de lógica de servicio + tests; sin migraciones ni pasos de despliegue especiales. Rollback = revertir el código.

## Open Questions

_(ninguna)_

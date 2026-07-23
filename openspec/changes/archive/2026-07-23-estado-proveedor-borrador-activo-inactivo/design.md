## Context

El botón "Borrador" del formulario de alta de proveedor está deshabilitado con "Disponible próximamente" desde que se construyó el formulario. Al investigar qué tenía que hacer, el diagnóstico resultó distinto del esperado:

```
proveedores.activo ──┬── se escribe desde el formulario      ✓
                     ├── se muestra en listado y detalle     ✓
                     └── filtra algo, en alguna parte        ✗   ← nadie
```

No hay ninguna consulta `Proveedor::where('activo', …)` en la aplicación. Los dos puntos donde se elige un proveedor **para operar** —crear un proceso de adquisición (`ProcesoAdquisicionController`) y asociar un cliente-medidor (`ClienteMedidorController`)— usan `Proveedor::all()`. Un proveedor dado de baja aparece en ambos como si nada.

Y por el otro lado, "guardar incompleto" ya está resuelto: el requirement vigente exige solo RUT y razón social, con un escenario explícito de alta con datos mínimos.

De ahí que este change no trate de "agregar el botón" sino de **hacer que el estado de un proveedor signifique algo**, y que borrador sea uno de esos significados.

## Goals / Non-Goals

**Goals:**

- Que el estado de un proveedor gobierne su disponibilidad para operar, en vez de ser decorativo.
- Distinguir "todavía no está listo" (borrador) de "estuvo y se dio de baja" (inactivo), que un booleano no puede expresar.
- Que "Guardar como borrador" sea una acción real en el alta y que un borrador se pueda promover a activo desde la edición.
- Cerrar la última acción diferida del repositorio.

**Non-Goals:**

- No se agrega auditoría al CRUD de proveedores. Es un hueco real (ver Impact del proposal) pero de alcance propio.
- No se agrega un flujo de aprobación ni revisión de borradores: promover un borrador es una edición, no una transición de workflow.
- No se toca `TransicionWorkflowService` ni el workflow de ningún módulo. El estado de un dato maestro no es un estado de proceso.
- No se filtran los proveedores por estado en el catálogo de proveedores ni en la importación desde SGF/Mercado Público (ver decisión 4).
- No se agrega un permiso nuevo.

## Decisions

### 1. Un `estado` de tres valores reemplaza el booleano `activo`, en vez de sumarle una columna

La alternativa obvia era dejar `activo` y agregar `es_borrador`. Se descarta: dos booleanos independientes admiten cuatro combinaciones para tres estados reales, y una de ellas (`es_borrador = true` + `activo = true`) no significa nada. Un solo campo con dominio cerrado hace imposible ese estado inválido.

Los valores viven como constantes en el modelo (`Proveedor::ESTADO_BORRADOR`, `ESTADO_ACTIVO`, `ESTADO_INACTIVO`) con una lista para validar, en línea con cómo el repo maneja otros dominios cerrados en tablas maestras. El default de la columna es `activo`, que es lo que ya hacía `activo = true` y evita que un registro creado por fuera del formulario nazca invisible.

*Alternativa considerada*: un enum de PHP respaldado por string. Sería más expresivo, pero el resto de las tablas maestras del repo usa constantes + validación, y mezclar los dos estilos en el mismo dominio cuesta más de lo que aporta.

### 2. Solo los selectores **operativos** filtran por estado; el catálogo muestra todo

`ProcesoAdquisicionController` y `ClienteMedidorController` ofrecen proveedores para **usarlos** en algo: ahí solo corresponde el estado `activo`. El listado de proveedores en Maestros es la pantalla de administración del catálogo — si filtrara, un borrador sería invisible justo en la pantalla desde la que hay que completarlo.

El filtro se expresa como un scope en el modelo (`Proveedor::activos()`) y no como un `where` repetido en cada controlador, para que el próximo selector que aparezca tenga un camino evidente y no vuelva a nacer sin filtrar. Esa es la causa raíz del defecto que este change arregla: el filtro nunca existió como concepto, así que nadie lo omitió a propósito.

### 3. Borrador y activo se validan igual; lo único que cambia es el estado con el que nace el registro

Relajar la validación para el borrador sería tentador —es lo que "borrador" sugiere— pero rompe cosas concretas: `rutproveedor` es `unique` y es la identidad del registro, así que un borrador sin RUT no se puede guardar sin volver la columna nullable, y dos borradores sin RUT colisionarían entre sí. Además el formulario ya trata todo lo demás como opcional, así que no hay nada más que relajar.

Decisión: las dos acciones del alta comparten Form Request y campos obligatorios (RUT + razón social). La acción elegida determina el estado inicial y nada más. Concretamente, esto significa que "Guardar como borrador" no sirve para guardar un formulario a medio llenar **sin RUT** — sirve para registrar un proveedor identificado que todavía no está habilitado para operar.

*Alternativa considerada*: permitir borradores sin RUT, con la unicidad aplicada solo a los no-borradores (índice parcial). Descartada: convierte la identidad del catálogo en condicional y obliga a resolver qué pasa cuando el borrador se promueve y su RUT choca con uno existente. Mucho costo para un caso que nadie pidió.

### 4. La importación externa no crea borradores

`CasoPagoProveedorImporter` y `OrdenCompraMercadoPublicoService` resuelven o crean proveedores a partir del RUT que viene de SGF o Mercado Público. Podría argumentarse que un proveedor creado automáticamente debería nacer como borrador, para que alguien lo revise.

Se descarta en este change: hoy esos proveedores nacen operables, y volverlos borrador cambiaría el comportamiento de dos integraciones —incluida la de pago a proveedores— como efecto colateral de un cambio sobre el formulario de alta. El default `activo` de la columna preserva exactamente lo que hacen hoy. Si se quiere que la importación exija revisión humana, ese es un cambio propio, con su propia discusión sobre qué pasa con un pago cuyo proveedor quedó en borrador.

### 5. La migración se unifica en vez de apilar un tercer parche

`proveedores` ya tiene dos migraciones: la de creación y `add_datos_completos_to_proveedores_table`. Agregar `add_estado_to_proveedores_table` dejaría tres capas sobre la misma tabla.

Decisión: se edita la migración de creación para que quede con el esquema final —los campos del parche de julio incluidos, y `estado` en lugar de `activo`— y se elimina la migración de parche. Es la regla vigente del proyecto mientras no haya datos de producción: no acumular deuda de migraciones evitable.

Consecuencia operativa, explícita porque no es gratis: **los entornos locales necesitan `php artisan migrate:fresh --seed`**, y lo que esté cargado a mano se pierde. Los tests no se ven afectados (corren sobre sqlite en memoria, que se construye desde cero en cada corrida). Esta decisión se revierte el día que haya datos reales: ahí el criterio pasa a ser no romper trazabilidad, y un cambio de esquema se versiona en vez de reescribirse.

## Risks / Trade-offs

- **Filtrar los selectores cambia lo que ve el usuario**: un proveedor hoy elegible puede dejar de estarlo si está inactivo. → Es exactamente el defecto que se corrige, no un efecto colateral. Como el default de la columna es `activo`, ningún proveedor existente cambia de comportamiento al migrar; solo dejan de ofrecerse los que alguien marcó como inactivos a propósito.
- **`migrate:fresh` destruye los datos locales** (decisión 5) → Mitigación: queda dicho en el proposal, en el design y en el resumen de la implementación, con el comando exacto. No lo ejecuta la implementación: lo corre quien opera cada entorno.
- **Un borrador puede quedar olvidado**, sin nada que recuerde completarlo → Aceptado en este alcance. El listado de proveedores los muestra con su distintivo, que es el mecanismo mínimo. Un recordatorio o un filtro "solo borradores" es una mejora posterior si el uso real la pide.
- **`estado` como string admite valores fuera del dominio si alguien escribe directo en la base** → Mitigación: validación en los Form Requests contra la lista de constantes, que es donde entra todo lo que viene de la aplicación. No se agrega un CHECK en la base para no divergir del resto de las tablas maestras del repo, que resuelven sus dominios cerrados igual.

## Context

`IndicadorEconomicoImporter` ya implementa correctamente la parte más difícil (idempotencia real vía `firstOrCreate` con clave `[tipo, fecha_valor, periodo]`, nunca actualiza valores existentes, guarda `source_payload`/`source_url`/`source_hash`, caché de selección con invalidación) — el trabajo de este cambio es de **esquema y organización**, no de reinventar la lógica de idempotencia que ya funciona y ya tiene tests pasando.

El spec detallado que trajo el usuario pide una clave única más rica (`codigo+fecha_valor+periodo+fuente+es_proyectado`), estados de ejecución completos, trazabilidad de captura, comandos Artisan, reproceso controlado y disparo manual autorizado — ninguno de estos existe hoy.

## Goals / Non-Goals

**Goals:**
- Cumplir la clave única, los campos de trazabilidad y los estados de ejecución exigidos por el spec del usuario.
- Descomponer `IndicadorEconomicoImporter` en servicios con responsabilidad única, preservando exactamente su comportamiento actual (mismo cálculo de UTA, mismo tramo de UF, mismo manejo de advertencias USD).
- Agregar comandos Artisan, reproceso controlado y disparo manual autorizado, gateado por permiso.
- Corregir la zona horaria del scheduler a `America/Coyhaique` (Aysén, coincide con la jurisdicción 14 "Zonal Coyhaique" ya sembrada).

**Non-Goals:**
- No se construye un cliente SII (fuera de alcance, confirmado con el usuario).
- No se cambia la regla de fallback de USD en `IndicadorEconomicoSelector` (ya vive ahí y funciona).
- No se agrega edición manual de indicadores ni aprobación de proyectados.
- No se reescribe `CmfClient` (ya funciona, tiene tests propios).

## Decisions

**1. Modificar las 2 migraciones existentes in-place, no agregar migraciones `alter table`.**
El proyecto está en construcción activa (regla ya establecida: "unificar migraciones en vez de parchear"). Alternativa descartada: migraciones incrementales de alteración — se descarta porque fragmentaría el historial de un esquema que todavía no tiene datos de producción, y dificultaría leer la forma final de la tabla.

**2. Separar `codigo` (UF/USD/UTM/UTA/IPC) de un nuevo campo `tipo` (categoría semántica).**
La columna actual `tipo` en realidad cumple el rol de "código de indicador" que pide el spec nuevo bajo el nombre `codigo`. El spec además pide un campo `tipo` distinto (`unidad_reajustable`/`unidad_tributaria`/`moneda`/`indice`) que hoy no existe como concepto. Alternativa descartada: reutilizar la columna `tipo` actual para ambos significados — se descarta porque son dos conceptos genuinamente distintos (identificador vs. categoría) y mezclarlos impediría, por ejemplo, agrupar por categoría en reportes futuros.

**3. Dos índices únicos (no uno de 5 columnas), reemplazando los 2 actuales por una versión ampliada del mismo patrón.**
Se intentó primero un único índice `unique(codigo, fecha_valor, periodo, fuente, es_proyectado)`, pero se descartó tras detectarlo roto en pruebas: en SQL estándar (Postgres y SQLite) un `NULL` nunca colisiona con otro `NULL` dentro de un índice único — y `fecha_valor`/`periodo` son mutuamente excluyentes (uno de los dos siempre es `NULL` según el tipo de indicador), así que **toda fila** tendría al menos un `NULL` entre esas dos columnas y el índice no protegería nada. La solución, verificada con test (`IndicadoresEconomicosEsquemaTest`), es calcar el patrón que ya funcionaba en el esquema anterior — dos índices, cada uno completo para su categoría de indicador: `unique(codigo, fecha_valor, fuente, es_proyectado)` (protege UF/USD, donde `fecha_valor` siempre existe) y `unique(codigo, periodo, fuente, es_proyectado)` (protege UTM/UTA/IPC, donde `periodo` siempre existe).

**3.1. `fecha_valor`/`vigente_desde`/`vigente_hasta` casteados como `date:Y-m-d`, no `date`.**
Detectado también con test: el cast `date` (sin formato) permite leer el atributo como `Carbon`, pero al guardar usa el formato de datetime completo del modelo (`Y-m-d H:i:s`) — mientras que `firstOrCreate()` busca con el string plano `Y-m-d` que arma el servicio. En Postgres esto quedaba enmascarado porque la columna `DATE` nativa trunca la hora en el propio motor; en SQLite no hay ese truncamiento automático, así que la búsqueda nunca encontraba el registro ya creado y el segundo intento de importación fallaba contra el índice único (en vez de omitir correctamente). `date:Y-m-d` fuerza el mismo formato en ambos lados.

**4. `advertencias` se elimina de `indicadores_economicos` y se centraliza en `indicadores_economicos_importaciones.advertencias`.**
El spec del usuario no contempla advertencias a nivel de indicador individual, solo a nivel de ejecución. Hoy la única advertencia real (USD con fecha distinta a la esperada) ya se duplica en ambas tablas (`crearIndicador()` la guarda en el indicador Y `importarDolarDiario()` la guarda en la importación) — centralizarla en la importación elimina esa duplicación sin perder información, porque la importación ya sabe a qué `codigo`/`fecha_valor` se refiere cada advertencia (se guarda como texto descriptivo, no como referencia estructurada, igual que hoy).

**5. Descomposición en 4 servicios, todos en español, bajo `App\Services\Indicadores`:**
- `ServicioImportacionIndicadores::importarMensual()` / `::importarUsd()` — reemplaza la orquestación de alto nivel de `IndicadorEconomicoImporter`, delegando en los otros 3 servicios. Conserva los métodos privados de tramo UF / UTM por año / UTA calculada / IPC tal cual existen hoy (mismo comportamiento, solo reubicados).
- `ServicioNormalizadorIndicadores::normalizarValor(string $crudo): float` — implementa la limpieza completa del spec (quita `$`, puntos de miles, espacios, `%`, cambia coma decimal por punto). `CmfClient::parseNumeroChileno()` NO se modifica ni se elimina (sigue usándose dentro de `CmfClient`, tiene su propio test) — el normalizador nuevo se usa en la capa de ensamblado del DTO antes de persistir, como una segunda defensa explícita pedida por el spec, no como reemplazo del parseo interno de `CmfClient`.
- `ServicioPersistenciaIndicadores::crearSiNoExiste(array $atributos): array{indicador: IndicadorEconomico, creado: bool}` — reemplaza `crearIndicador()`, usa el nuevo índice único compuesto como llave de `firstOrCreate`, invalida el caché del `codigo` cuando `creado === true` (mismo comportamiento actual).
- `RegistradorImportacionIndicadores` — crea la fila de importación en `pending`, la pasa a `running` al empezar, y la cierra en `success`/`partial_success`/`failed` según los conteos acumulados (calcado del `try/catch` por bloque que ya existe en `importarMensual()`, solo formalizado como conteos explícitos en vez de un array de strings de error).

**6. Jobs conservan su nombre actual (`ImportarIndicadoresMensualesJob`/`ImportarDolarDiarioJob`), solo cambia su `handle()` para llamar al servicio nuevo.**
Ya están en español y ya reflejan exactamente lo que hacen; renombrarlos no aporta nada y rompe referencias en `routes/console.php` sin necesidad.

**7. Comandos Artisan como capa delgada sobre los jobs, no un camino de ejecución paralelo.**
`indicadores:importar-mensual` y `indicadores:importar-usd` despachan el mismo job (`dispatchSync()` para que el usuario vea el resultado inmediatamente en CLI) con las opciones `--periodo=`/`--fecha=` pasadas como contexto de reproceso controlado (`tipo_importacion = reproceso_controlado` cuando se use la opción, `manual` si se ejecuta el comando sin opción fuera del scheduler).

**8. Disparo manual HTTP reutiliza el mismo servicio, no un tercer camino de ejecución.**
La acción HTTP (`POST /indicadores-economicos/importar-mensual`) llama directamente a `ServicioImportacionIndicadores::importarMensual()` de forma síncrona (no encola), gateada por `Gate::authorize('importar', IndicadorEconomicoImportacion::class)` con el permiso nuevo `indicadores.importar`. Solo se ofrece el disparo manual mensual (UF/UTM/UTA/IPC), no el diario de USD — así lo pide el brief del usuario explícitamente ("bajo petición... para UF, UTM, UTA e IPC").

**9. Permiso `indicadores.importar` gatea solo la acción de importar, no la visibilidad de la página.**
La página de indicadores económicos sigue abierta a cualquier autenticado (`consulta-indicadores-economicos`, ya ratificada, no se toca su control de acceso) — el botón "Importar ahora" se oculta en el frontend si el usuario no tiene el permiso, y la ruta HTTP igual lo exige en el backend (mismo patrón de refuerzo doble ya usado en `filtrar-sidebar-por-permisos`).

## Risks / Trade-offs

- [Se pierden los indicadores ya importados en desarrollo al correr `migrate:fresh`] → Aceptable, sin datos de producción en juego; se puede re-importar corriendo los comandos nuevos tras la migración.
- [Renombrar `tipo`→`codigo` rompe el frontend/HTTP existente si se olvida algún consumidor] → Mitigado enumerando explícitamente en proposal.md/tasks.md los 5 archivos frontend + el resource + el middleware que lo consumen; se verifica con `npm run types:check` (TypeScript fallará en cualquier referencia olvidada a `.tipo` tipada) y pruebas manuales en navegador.
- [Centralizar advertencias en la importación pierde granularidad si una importación mensual trajera advertencias de más de un indicador a la vez] → Aceptable: cada advertencia sigue siendo una cadena de texto descriptiva (ya lo es hoy), simplemente se acumulan todas en el array de la importación en vez de repetirse también en cada indicador.

## Migration Plan

1. Modificar las 2 migraciones in-place.
2. Reescribir modelos (`$fillable`, casts, sin agregar `updated_at` a `IndicadorEconomico`).
3. Crear los 4 servicios nuevos, refactorizar jobs para usarlos.
4. Agregar comandos Artisan, permiso nuevo, acción HTTP + botón condicionado.
5. Ajustar scheduler (timezone + withoutOverlapping).
6. Ajustar frontend/resource/middleware que referencian `tipo`.
7. `php artisan migrate:fresh` en desarrollo + `db:seed --class=RolesAndPermissionsSeeder` para propagar el permiso nuevo.
8. Adaptar los 3 tests existentes y agregar los nuevos del §20 del brief.
9. Sin rollback especial más allá de revertir el commit — es un cambio de esquema sin datos de producción.

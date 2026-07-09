## Context

Hoy existe un único mecanismo de importación desde SGF: `ImportarCasosPendientesSgfController::store()` → `ImportarCasosPendientesSgfJob` → `ConectorSgfPlaywrightService::importarPendientes()` → microservicio Node (`services/sgf-playwright/`) → `sgf-scraper.js::importarPendientes()`, que recorre toda la Bandeja SGF ("Mis pendientes") sin ningún filtro, descargando además los documentos adjuntos de cada fila (`descargarDocumentosDeFila()`, el paso más costoso de la corrida).

El campo `grupo_actual` de cada fila ya se extrae (`MAPEO_COLUMNAS_BANDEJA` en `selectors.js`, ya calibrado) y ya se persiste en `caso_pago_proveedor.sgf_current_group_raw`, pero no se usa para filtrar nada — la importación masiva trae el 100% de la Bandeja.

SGF tiene un filtro nativo de "Grupo" en el formulario "Buscar" de la Bandeja. Calibración adicional aportada por el usuario (captura real del formulario) confirma que sí es utilizable: es un multiselect (`GRUPO`) donde se puede elegir "Pago Operaciones" como chip, junto a un rango de fechas (`FECHA INICIAL`/`FECHA FINAL`, con "hoy" precargado por defecto en la final) y un botón "Buscar" — sin el efecto secundario de vaciar la tabla que se había asumido antes. El flujo real observado: seleccionar "Pago Operaciones" en `GRUPO`, fijar `FECHA INICIAL` en la fecha actual menos un mes (mismo día del mes), dejar `FECHA FINAL` en su valor por defecto (hoy) y hacer clic en "Buscar".

## Goals / Non-Goals

**Goals:**
- Traer solo los casos SGF cuyo `grupo_actual` sea "Pago operaciones", sin afectar el comportamiento de la importación masiva existente.
- Reutilizar el 100% de los selectores y la navegación ya calibrados de la Bandeja (login, paginación, extracción de filas) — cero superficie nueva de automatización no calibrada.
- Permitir que la importación selectiva y la masiva corran de forma independiente (sin bloquearse mutuamente por el mismo lock).

**Non-Goals:**
- No se construye un sistema genérico de "importar por cualquier grupo arbitrario" — queda acotado a "Pago operaciones" tal como se pidió. Generalizar a N grupos configurables es una posible iteración futura, no parte de este change.
- No se cambia el significado de `sgf_current_group_raw` ni cómo se persiste hoy (sigue siendo solo referencia externa, poblado igual para ambos tipos de importación).

## Decisions

**1. Usar el filtro nativo de SGF (Grupo + rango de fechas), no filtrado client-side.**
Se agrega una nueva función `importarGrupoPagoOperaciones()` en `sgf-scraper.js` que reutiliza `asegurarSesionIniciada` y `navegarABandeja` tal cual existen hoy, pero antes de leer la tabla completa la nueva función interactúa con el formulario "Buscar" de la Bandeja: selecciona "Pago Operaciones" en el multiselect `GRUPO`, fija `FECHA INICIAL` en la fecha actual menos un mes (mismo día del mes; p. ej. si hoy es 09-07-2026, `FECHA INICIAL` queda en 09-06-2026), deja `FECHA FINAL` en su valor precargado por defecto (hoy) y hace clic en "Buscar". Recién entonces se reutilizan `leerEncabezadosTabla` y `avanzarSiguientePagina` tal cual existen hoy, y se llama `descargarDocumentosDeFila()` para cada fila resultante — ya no hace falta filtrar por `grupo_actual` en el cliente ni descartar filas, porque SGF ya devuelve solo las del grupo pedido dentro del rango de fechas. Como red de seguridad ante un eventual desajuste del filtro nativo, se mantiene una verificación defensiva de `grupo_actual` (ya calibrada) antes de descargar documentos, pero deja de ser el mecanismo principal de filtrado.
Alternativa descartada: mantener el filtrado 100% client-side (recorrer toda la Bandeja sin fecha ni grupo y descartar filas después de leerlas). Se descarta porque, con el filtro nativo ya calibrado, recorrer toda la Bandeja sin acotar fecha ni grupo es estrictamente más caro (más páginas, más filas leídas) sin ningún beneficio adicional — el riesgo que motivaba evitar el filtro nativo (selector no calibrado, efecto secundario de vaciar la tabla) ya no aplica con esta calibración.

**2. Nuevo endpoint dedicado en el microservicio (`POST /casos/importar-grupo-pago-operaciones`), no un parámetro en el endpoint existente.**
Aunque `ConectorSgfPlaywrightService::llamarMicroservicio()` ya soporta pasar un `$body` arbitrario (lo que técnicamente permitiría parametrizar el endpoint existente con un filtro), se prefiere un endpoint separado porque: (a) mantiene el contrato del endpoint masivo sin cambios (menor riesgo de regresión), (b) permite que el modo stub tenga una respuesta fija propia y predecible para pruebas de desarrollo, (c) es consistente con el patrón de "un tipo de operación = una ruta" ya usado para `casos/verificar` vs `casos/importar-pendientes`.

**3. Job, lock y `tipo` de `trabajo_integracion` independientes de la importación masiva.**
Nuevo Job `ImportarCasosGrupoPagoOperacionesSgfJob` (mismo patrón que `ImportarCasosPendientesSgfJob`: `$timeout = 3600`, `WithoutOverlapping('sgf-importar-grupo-pago-operaciones')->expireAfter(3700)`), y nuevo `tipo` de trabajo `'importar_grupo_pago_operaciones'`. Esto permite correr una importación selectiva mientras hay una masiva en curso (y viceversa) sin que se bloqueen entre sí — son operaciones independientes desde la perspectiva del usuario, y no hay ninguna razón técnica (ni de SGF ni de nuestra base) para serializarlas entre sí.
Alternativa descartada: reutilizar el mismo `tipo`/lock que la importación masiva, distinguiendo por algún campo adicional. Se descarta porque `trabajos_integracion` no tiene columna de "subtipo"/"filtro" hoy, y agregar una rompería el patrón simple ya establecido (`tipo` como único discriminador) sin necesidad real.

**4. Reutilizar el permiso existente `pago_proveedores.importar_casos_sgf` (no crear uno nuevo).**
Se evaluaron ambas opciones. Se opta por reutilizar el permiso existente porque: sigue siendo semánticamente "importar casos desde SGF" (solo cambia el alcance/filtro de la corrida, no la naturaleza de la acción), evita fragmentar permisos por cada variante futura de importación, y hoy ambos botones conviven en la misma página (`/sgf/importaciones`) visible solo para quien ya tiene ese permiso. Si en el futuro se necesita que un rol pueda importar el grupo "Pago operaciones" pero no la masiva completa (o viceversa), se puede introducir un permiso dedicado en un change posterior sin romper nada de este.

**5. Umbral de huérfano propio para el nuevo `tipo`.**
Se agrega `INTEGRACIONES_UMBRAL_HUERFANO_IMPORTAR_GRUPO_PAGO_OPERACIONES_MINUTOS` a `config/integraciones.php`/`.env.example`, con el mismo valor que el de importación masiva (90 min) como punto de partida razonable, en vez de dejar que caiga en el umbral `default` (120 min) sin criterio explícito.

## Risks / Trade-offs

- [Riesgo] El texto exacto de la opción "Pago Operaciones" en el multiselect `GRUPO` (mayúsculas/espacios/acentos) podría diferir del literal usado para seleccionarla → Mitigación: localizar la opción por texto normalizado (trim + case-insensitive), igual criterio que ya se usaba para `grupo_actual`, ahora aplicado también a la selección en el formulario.
- [Riesgo] El selector CSS/estructura exacta del multiselect `GRUPO` y de los inputs de fecha no está calibrado contra el DOM real (la captura aportada es visual, no HTML) → Mitigación: se documentan como TODO-VERIFICAR en `selectors.js` con candidatos razonables, siguiendo el mismo patrón de resiliencia ya usado para el resto de la Bandeja; la verificación final contra SGF real queda como paso explícito de calibración supervisada (ver tarea 1.5).
- [Riesgo] Si un caso "Pago Operaciones" lleva más de un mes pendiente en ese grupo, el rango `FECHA INICIAL = hoy - 1 mes` lo dejaría fuera → Mitigación: se acepta como comportamiento esperado (replica el uso manual observado); si en el futuro se detectan casos más antiguos que deban incluirse, ampliar el rango es un cambio de un solo valor, no de arquitectura.
- [Riesgo] Si SGF renombra o reestructura el grupo "Pago Operaciones" en el futuro, la importación selectiva dejaría de traer resultados sin ningún error explícito (0 filas no es un fallo técnico) → Mitigación: el `trabajo_integracion` sigue registrando `total_elementos = 0` de forma visible en el listado de importaciones; no se agrega alerta proactiva en este change (fuera de alcance), pero queda trazable.

## Migration Plan

1. Backend: nuevo controlador/método, ruta, Job, método de servicio, permiso reutilizado, entrada de config de umbral huérfano.
2. Microservicio: nuevo endpoint + nueva función de scraper que interactúa con el formulario "Buscar" (Grupo + rango de fechas) antes de leer la tabla, más nuevos casos de prueba en el stub (incluyendo al menos un caso con `grupo_actual = 'Pago Operaciones'` y otro de un grupo distinto, para probar que la respuesta filtrada del stub solo trae lo esperado).
3. Frontend: nuevo botón en `/sgf/importaciones`.
4. Specs: actualizar `conector-sgf-playwright` (nuevo requirement) y `consulta-importaciones-sgf` (descripción de `tipo` actualizada).
5. Tests: análogos a los de la importación masiva, cubriendo la interacción con el formulario de filtro.

No requiere rollback especial: es aditivo (nuevo endpoint, nuevo Job, nueva ruta, nuevo botón); revertir es revertir los archivos tocados.

## Open Questions

- Ninguna bloqueante. Queda pendiente, igual que con la importación masiva original, verificar contra SGF real (sesión supervisada) el selector exacto del multiselect `GRUPO`, de los inputs `FECHA INICIAL`/`FECHA FINAL` y que el texto de la opción coincide con "Pago Operaciones" — se deja como paso explícito en tasks.md, no automatizable en este entorno.

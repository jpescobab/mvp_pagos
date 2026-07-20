# Calibrar el scraper real de SGF

**Estado (2026-07-09): calibración básica exitosa.** El flujo completo
(login → Bandeja paginada → "Ver Documentos" → descarga por popup → registro
en Laravel) corrió de punta a punta contra `https://sgf.pjud.cl` real, dos
veces, importando 65 procesos con sus documentos cada vez
(`casos_pago_proveedor`, `snapshots_datos_externos` y `documentos` creados
correctamente) en ~3 minutos por corrida. Sigue habiendo puntos abiertos
(detallados más abajo) — sobre todo el mapeo de `tipo_documento_codigo` — y
cualquier cambio futuro en el HTML de SGF puede volver a romper los
selectores, pero el andamiaje ya no es teórico.

`sgf-scraper.js` y `selectors.js` se escribieron originalmente con selectores
razonables pero no verificados. Antes de confiar en el modo real
(`SGF_MODO=real`), alguien tenía que calibrarlo — y esa persona tenía que ser
un humano que hace el primer login, no un agente automatizado corriéndolo sin
supervisión.

## Por qué esto no lo corre el asistente

Ingresar una credencial real de un sistema financiero institucional en un
formulario de login es una acción que el asistente tiene explícitamente
prohibido ejecutar, incluso con autorización explícita del usuario. Por eso
este scraper se entrega escrito pero sin ejecutar contra el sitio real: la
primera corrida la debe hacer una persona.

## Flujo real confirmado (2026-07-07)

Login → pantalla "Seleccionar unidad de ingreso" (Fuente Financiamiento +
Centro Financiero, ya precargados, botón "Continuar") → sidebar **"RedFlow"**
→ **"Bandeja"** → botón **"Buscar"** → lista de procesos pendientes, cada uno
con un menú de acciones (kebab) → **"Ver Documentos"** → pestaña **"Lista
documentos"** → tabla Doc./Orden/Nombre con un ícono de descarga por
documento → repetir para cada proceso.

Lo que quedaba por calibrar contra el DOM real (todo lo demás ya estaba
descrito arriba y codificado en `selectors.js`), **confirmado funcional en
las corridas reales del 2026-07-08/09** (65 procesos recuperados de forma
consistente en tres corridas distintas):
- ~~Selectores exactos de la fila/tarjeta de proceso~~ (`BANDEJA_PROCESOS` en
  `selectors.js`) — la extracción de RUT/monto/columnas por
  `MAPEO_COLUMNAS_BANDEJA` funcionó correctamente contra los encabezados
  reales. Desde 2026-07-17 el mapa incluye también la columna `"n° traspaso"`
  (penúltima, junto a `"monto"`; ya listada en los encabezados calibrados del
  comentario de `selectors.js`) → se captura como `numero_traspaso` y se
  conserva en el caso como `sgf_numero_traspaso`. Pendiente de confirmar el
  valor real en la próxima corrida supervisada contra SGF.
- ~~El selector del botón de menú (kebab) por fila~~ (`MENU_ACCIONES_PROCESO.botonMenu`)
  — funcionó.
- ~~Si "Ver Documentos" abre un modal/panel o navega a otra vista~~ — abre un
  panel (no navega), y `botonCerrar` funcionó para volver a la Bandeja entre
  procesos.
- ~~Si la descarga del PDF dispara un evento de descarga real de Playwright o
  abre una vista previa en otra pestaña~~ — **resuelto (2026-07-08)**: no hay
  descarga directa ni botón "Descargar" dentro de "Ver documentos". Clickear
  el ícono del documento abre el PDF en una pestaña/ventana nueva del
  navegador (popup) con la URL directa del archivo. `descargarDocumentosDeFila()`
  en `sgf-scraper.js` captura ese popup y pide la URL por HTTP reusando las
  cookies de la sesión (`page.context().request`), en vez de
  `page.waitForEvent('download')`. **Confirmado (2026-07-09)**: la petición sí
  trae el PDF completo con solo las cookies de sesión — 264+ documentos
  descargados y registrados correctamente en dos corridas reales distintas,
  sin ningún caso del error "no empieza con la firma %PDF-".
- El mapeo de `tipo_documento_codigo` por prefijo de nombre de archivo
  (`inferirTipoDocumento()` en `sgf-scraper.js`) — hoy solo distingue
  `FAE-*` → `FACTURA`, todo el resto → `OTRO`.

**Resuelto (2026-07-08)**: el clic en la pestaña "Lista documentos" usaba
`primerSelectorExistente` (chequeo único, sin reintento) justo después de un
clic que abre el panel — funcionó para el primer proceso pero falló ("no
existe") en el segundo, quedando pegado sin avanzar al resto. Se cambió a
`esperarYObtenerPrimero` (con reintento). Verificado en calibración real:
ahora el scraper avanza por todos los procesos de la Bandeja, no solo el
primero.

**Resuelto (detectado 2026-07-08, confirmado 2026-07-09)**: la Bandeja pagina
sus resultados (paginador clásico: números de página + flecha "Siguiente",
descrito por el usuario) y el scraper originalmente solo procesaba la primera
página. Se agregó `PAGINACION_BANDEJA` en `selectors.js` y
`avanzarSiguientePagina()` en `sgf-scraper.js` (usado por
`importarPendientes()` y `verificarCaso()` para recorrer todas las páginas).
Verificado en dos corridas reales: avanza por todas las páginas y se detiene
correctamente en la última (65 procesos recuperados de forma consistente en
ambas corridas, sin bucles ni cortes prematuros).

**Resuelto (2026-07-08) — cierre de sesión tras cada llamada**: antes,
`obtenerPagina()` reutilizaba un navegador/contexto singleton entre llamadas
y nunca se invocaba `cerrarNavegador()` desde `server.js` — la sesión
autenticada de SGF quedaba viva indefinidamente mientras el proceso Node
corriera. Ahora `server.js` cierra el navegador (y por tanto la sesión)
al terminar cada llamada en modo real, éxito o error (`ejecutarEnModoReal()`).
Decisión explícita del usuario: preferir cerrar la sesión sobre mantenerla
viva, dado que el login completo demostró tomar solo ~3 min.

**Resuelto (2026-07-08) — infraestructura de la cola, no del scraper**: durante
la calibración real, `importar_pendientes` (con 64 procesos) moría en silencio
sin ningún error registrado, repetidamente. La causa real no era el scraper:
`php artisan queue:listen` ejecuta cada job en un proceso hijo envuelto en un
`Symfony\Component\Process\Process` con su **propio timeout de 60s**,
independiente del `$timeout` del Job (que solo aplica al límite interno vía
pcntl_alarm, no disponible en Windows). Al vencer, el Symfony Process mata el
proceso hijo y además **crashea `queue:listen` por completo**
(`ProcessTimedOutException` sin capturar) — sin dejar rastro en
`trabajos_integracion`. Fix: `composer.json` (script `dev`) ahora pasa
`--timeout=3700` a `queue:listen`. Si calibras corriendo `queue:listen` a
mano (sin `composer dev`), agrega ese flag manualmente o esto va a volver a
morir en silencio a los 60s con importaciones grandes.

**Resuelto (2026-07-20) — fila placeholder de "Bandeja vacía" rompía
`importarPendientes()`**: cuando "Mis pendientes" no tiene procesos, SGF
renderiza una única fila dentro de `<tbody>` con una sola celda ("No hay
datos disponibles en la tabla") en vez de cero filas. `BANDEJA_PROCESOS.filaProceso`
(`table:visible tbody tr`) la cuenta igual que a una fila de proceso real, y
el código intentaba leerle la columna "Id" (inexistente en esa fila),
lanzando `"No se pudo determinar el N° de proceso..."` — un error de
calibración confuso pese a que los selectores estaban bien y la Bandeja
simplemente no tenía nada pendiente. `verificarCaso()` no se veía afectado
(filtra las filas por `texto.includes(sgfId)` antes de procesarlas, y el
placeholder nunca matchea un ID numérico), pero `importarPendientes()`
procesa todas las filas sin ese filtro previo. Fix: `filaEsPlaceholderTablaVacia()`
en `sgf-scraper.js` detecta la fila por estructura (una sola celda) + texto,
y `procesarFilaProceso()` la descarta (`return null`) antes de intentar leer
ninguna columna; `importarPendientes()` ahora filtra esos `null` en vez de
empujarlos al resultado (mismo patrón que ya usaba `importarGrupoPagoOperaciones()`
para las filas que su propio filtro descarta). Adicionalmente,
`primerSelectorExistente()` (usada por ejemplo para `MENU_ACCIONES_PROCESO.botonMenu`)
no guardaba diagnóstico (screenshot + HTML) al fallar, a diferencia de
`esperarYObtenerPrimero()` — quedaba sin evidencia para calibrar selectores
calibrados antes que dejaron de calzar (posible cambio de HTML en SGF). Ahora
también captura diagnóstico en `services/sgf-playwright/debug/` al fallar,
tanto si `contexto` es la `Page` como si es un `Locator` acotado (usa
`.page()` para llegar a la `Page` real en ese caso).

Ese diagnóstico se usó de inmediato: en la siguiente corrida, `botonMenu`
volvió a fallar contra una fila real (Id 779, no el placeholder), esta vez
con screenshot + HTML guardados. Cargar ese HTML en un Playwright headless
aparte (`page.setContent()`, sin tocar SGF real) y probar cada selector
candidato contra él reveló la causa: la primera `<td>` de la fila (tabla
Angular DataTables) contiene tanto el botón toggle como, ya montadas en el
DOM aunque el menú nunca se abrió, las ~11 opciones del menú ("Generar
ebook", "Editar", "Ver documentos", etc. — confirmado que "Ver documentos"
sí está entre ellas, así que `MENU_ACCIONES_PROCESO.opcionVerDocumentos` no
necesita cambios) — todas `<button class="dropdown-item">`. `td:first-child
button` matchea las ~12 (ambiguo) y depende de que Playwright ".first()"
agarre por suerte el toggle en vez de una opción del menú; funcionó en la
calibración de 2026-07-08/09 pero es frágil. El botón real es
`<button ngbdropdowntoggle class="dropdown-toggle btn btn-purpura m-0">`
(ícono por fuente `<em class="ft-more-vertical">`, no SVG). Fix: se agregó
`button[ngbdropdowntoggle]` (el atributo que la propia directiva
ng-bootstrap agrega solo al botón que abre el dropdown, inequívoco) como
primer candidato de `MENU_ACCIONES_PROCESO.botonMenu` en `selectors.js`,
antes que los candidatos viejos — verificado 1-a-1 contra el HTML real
guardado que gana como primer match y apunta al elemento correcto
(`id="dropdownBasic1"`).

## Pasos para calibrar

1. Instalar dependencias (una sola vez):
   ```bash
   cd services/sgf-playwright
   npm install
   npx playwright install chromium
   ```

2. Confirmar que `.env` tenga `SGF_MODO=real` y `SGF_HEADLESS=false` (navegador
   visible — nunca calibrar en headless).

3. Correr el servidor:
   ```bash
   npm start
   ```

4. Disparar "Importar pendientes de SGF" desde la UI de Laravel y observar la
   ventana del navegador en cada paso: login, selección de unidad, RedFlow →
   Bandeja, Buscar, y — para cada proceso — apertura de "Ver Documentos" y
   descarga de cada PDF. El mensaje de error del `trabajo_integracion` (visible
   en el detalle de la importación) indica cuál selector no calzó.

5. Verificar que los PDF efectivamente se guardaron en
   `storage/app/sgf-documentos/{sgf_id}/` con el nombre correcto, y que el
   caso importado en Laravel (`casos_pago_proveedor`) tiene el RUT/monto/estado
   esperados.

6. **Verificar que la descarga directa (`page.context().request.get(pdfUrl)`)
   realmente trajo el PDF y no una página de login o un error silencioso**:
   - Abrir cada PDF guardado en `storage/app/sgf-documentos/{sgf_id}/` con un
     lector de PDF (o `file nombre.pdf` en una terminal Unix — debe reportar
     "PDF document"). Si el archivo abre y muestra el documento esperado,
     la autenticación se propagó bien con solo las cookies de sesión.
   - Si el paso 4 lanzó un error mencionando "no empieza con la firma
     %PDF-", revisar el archivo `.bin` que quedó en
     `services/sgf-playwright/debug/respuesta-no-pdf-*.bin` (renombrarlo a
     `.html` o `.json` según corresponda y abrirlo) — normalmente será el
     HTML de la pantalla de login de SGF, lo que confirma que la sesión NO
     viajó solo por cookies.
   - Si eso ocurre, comparar en las DevTools del navegador (pestaña Network,
     con "Preserve log" activado) la petición real que dispara el click en
     el ícono del documento cuando se abre manualmente: revisar sus Request
     Headers en busca de un `Authorization: Bearer ...` u otro header
     custom, y en Application → Local Storage/Session Storage si SGF guarda
     ahí un token de sesión en vez de (o además de) la cookie. Si existe tal
     token, hay que capturarlo (ej. leyendo `localStorage` con
     `page.evaluate()` tras el login) y pasarlo como header en
     `page.context().request.get(pdfUrl, { headers: { ... } })` en vez de
     depender solo de las cookies.

7. Una vez que el flujo completo calce de forma consistente (repetir 2-3
   veces con distintos procesos), recién ahí es razonable considerar correr
   con `SGF_HEADLESS=true` en un entorno no supervisado — y aun así, revisar
   periódicamente que SGF no haya cambiado su HTML (este scraper no tiene
   detección automática de cambios de layout).

## Seguridad

- `SGF_PASSWORD` nunca debe aparecer en un `console.log`, mensaje de error, ni
  respuesta HTTP — si al calibrar ves la clave en la terminal, es un bug,
  repórtalo antes de seguir.
- No commitear `.env` (ya está en `.gitignore`).
- Si sospechas que la clave quedó expuesta (por ejemplo, pegada en una
  conversación con un asistente de IA), rótala en SGF apenas termines de
  calibrar.

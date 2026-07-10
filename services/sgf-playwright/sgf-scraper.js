// Scraper Playwright real de SGF.
//
// TODO-VERIFICAR: este módulo es un andamiaje construido a partir de
// capturas de pantalla y descripciones del flujo real (no del HTML real).
// Antes de confiar en él en cualquier entorno hay que calibrarlo — ver
// CALIBRACION.md en esta misma carpeta. Quien lo calibre debe ser una
// persona ejecutando el primer login real: este código nunca debe correr
// sin supervisión la primera vez, y SGF_PASSWORD nunca debe aparecer en un
// console.log, error, o respuesta.
//
// Flujo real confirmado por el usuario (2026-07-07):
//   login -> "Seleccionar unidad de ingreso" -> sidebar "RedFlow" ->
//   "Bandeja" -> botón "Buscar" -> lista de procesos pendientes, cada uno
//   con menú de acciones (kebab) -> "Ver Documentos" -> pestaña
//   "Lista documentos" -> tabla Doc./Orden/Nombre con un ícono de descarga
//   por documento -> iterar todos los procesos.

import { mkdir, writeFile } from 'node:fs/promises';
import path from 'node:path';
import { chromium } from 'playwright';
import { BANDEJA_PROCESOS, FILTRO_BANDEJA, LOGIN, MAPEO_COLUMNAS_BANDEJA, MENU_ACCIONES_PROCESO, NAVEGACION_REDFLOW, PAGINACION_BANDEJA, SELECCION_UNIDAD_INGRESO, VER_DOCUMENTOS } from './selectors.js';

// Tope defensivo de páginas a recorrer en la Bandeja: evita un bucle
// infinito si la detección de "última página" (clase "disabled"/atributo
// aria-disabled) no calza con el DOM real y el botón "Siguiente" queda
// como clicable para siempre. Muy por encima de cualquier volumen real de
// procesos pendientes esperado.
const MAX_PAGINAS_BANDEJA = 200;

const RUTA_STORAGE_DOCUMENTOS =
    process.env.LARAVEL_STORAGE_PATH ?? path.resolve(import.meta.dirname, '../../storage/app');

const RUTA_DEBUG = path.resolve(import.meta.dirname, 'debug');

/**
 * Guarda un screenshot + el HTML visible de la página en services/sgf-playwright/debug/
 * cuando algo falla, para calibrar selectors.js con evidencia real en vez de
 * pedirle al usuario una captura de pantalla cada vez. Nunca lanza (si esto
 * mismo falla, no debe tapar el error original).
 */
async function capturarDiagnostico(page, contexto) {
    try {
        await mkdir(RUTA_DEBUG, { recursive: true });
        const marca = new Date().toISOString().replace(/[:.]/g, '-');
        const base = `${marca}-${contexto}`;

        await page.screenshot({ path: path.join(RUTA_DEBUG, `${base}.png`), fullPage: true });

        const html = await page.content();
        await writeFile(path.join(RUTA_DEBUG, `${base}.html`), html, 'utf-8');

        return `Diagnóstico guardado en services/sgf-playwright/debug/${base}.png y ${base}.html`;
    } catch (e) {
        return `(no se pudo guardar diagnóstico: ${e.message})`;
    }
}

/**
 * Comprueba que los bytes recibidos realmente empiecen con la firma "%PDF-".
 * Sirve para detectar el caso en que page.context().request.get() no logró
 * propagar la autenticación completa (ej. SGF usa un token en localStorage
 * en vez de (o además de) cookies de sesión): en ese escenario la petición
 * directa suele volver 200 OK igual, pero con el HTML de una página de login
 * o un JSON de error en vez del PDF — por eso no basta con revisar
 * respuesta.ok(), hay que verificar el contenido mismo.
 */
function bytesParecenPdf(bytes) {
    return bytes.subarray(0, 5).toString('latin1') === '%PDF-';
}

const REGEX_RUT = /\d{1,2}(?:\.\d{3}){2}-[\dkK]/;
const REGEX_MONTO = /\$?\s?[\d.]{4,}(?:,\d+)?/;

// TODO-VERIFICAR: mapeo heurístico de prefijo de nombre de archivo -> código
// de tipos_documento (ver database/seeders/TiposDocumentoSeeder.php). "FAE"
// (Factura Afecta Electrónica) se asume como FACTURA; el resto (BIT_,
// FURBS-, etc.) cae en OTRO hasta confirmar qué representan institucionalmente.
function inferirTipoDocumento(nombreArchivo) {
    if (/^FAE[-_]/i.test(nombreArchivo)) {
        return 'FACTURA';
    }

    return 'OTRO';
}

let contextoNavegador = null;
let paginaActiva = null;

function paso(accion, estado, detalle = null) {
    return { orden: 0, accion, estado, detalle };
}

function numerarPasos(pasos) {
    return pasos.map((p, i) => ({ ...p, orden: i + 1 }));
}

/**
 * Prueba una lista de selectores candidatos en orden y devuelve el primer
 * locator que efectivamente existe en la página/elemento dado. Lanza de
 * inmediato si ninguno calza — para lookups donde no acaba de ocurrir una
 * navegación/transición (ej. dentro de una fila ya renderizada).
 */
async function primerSelectorExistente(contexto, candidatos, descripcion) {
    for (const selector of candidatos) {
        const locator = contexto.locator(selector).first();
        if ((await locator.count()) > 0) {
            return locator;
        }
    }

    throw new Error(
        `Ningún selector candidato para "${descripcion}" existe en la página actual — hay que calibrar selectors.js contra el DOM real.`,
    );
}

/**
 * Igual que primerSelectorExistente(), pero reintenta con polling hasta
 * timeoutMs antes de rendirse. Usar después de cualquier click que dispare
 * una navegación/transición de la SPA (login, cambio de pantalla, expandir
 * un menú): "networkidle" puede resolver antes de que el DOM termine de
 * renderizarse, y un chequeo puntual justo ahí da falsos negativos — patrón
 * que se repitió varias veces calibrando este scraper.
 */
async function esperarYObtenerPrimero(contexto, candidatos, descripcion, timeoutMs = 8000) {
    const limite = Date.now() + timeoutMs;

    while (Date.now() < limite) {
        for (const selector of candidatos) {
            const locator = contexto.locator(selector).first();
            if ((await locator.count()) > 0 && (await locator.isVisible())) {
                return locator;
            }
        }

        await new Promise((resolve) => setTimeout(resolve, 200));
    }

    // NOTA: asume que `contexto` es la Page (así se llama hoy en todos los
    // call sites) — si en el futuro se pasa un Locator acotado, esto
    // lanzaría al intentar hacer .screenshot()/.content() sobre él; ajustar
    // pasando la Page por separado si hace falta.
    const diagnostico = await capturarDiagnostico(contexto, descripcion.replace(/[^a-z0-9]+/gi, '_'));

    throw new Error(
        `Ningún selector candidato para "${descripcion}" apareció visible dentro de ${timeoutMs}ms — hay que calibrar selectors.js contra el DOM real. ${diagnostico}`,
    );
}

/**
 * Espera a que el overlay de carga (ngx-spinner, usado globalmente en esta
 * SPA) desaparezca antes de hacer clic en algo. VERIFICADO (2026-07-08): un
 * click puede fallar con "... subtree intercepts pointer events" si este
 * spinner sigue tapando la página — no es un modal ni parte del contenido,
 * es un indicador de carga que aparece brevemente tras cualquier acción que
 * dispara una petición al backend.
 */
async function esperarSpinnerAusente(page, timeoutMs = 10000) {
    await page
        .locator('.ngx-spinner-overlay')
        .first()
        .waitFor({ state: 'hidden', timeout: timeoutMs })
        .catch(() => {
            // Puede que ni siquiera haya aparecido para esta acción — no es
            // un error, solo no había nada que esperar.
        });
}

async function obtenerPagina() {
    if (paginaActiva && !paginaActiva.isClosed()) {
        return paginaActiva;
    }

    const headless = process.env.SGF_HEADLESS !== 'false';

    contextoNavegador = await chromium.launchPersistentContext('', {
        headless,
        // Evita el diálogo nativo "¿Guardar contraseña?" de Chrome, que es
        // UI del navegador (no de la página) y puede quedar tapando el flujo
        // o interceptando el foco del teclado justo después del submit.
        args: [
            '--disable-features=PasswordManager,PasswordLeakDetection,AutofillServerCommunication',
            '--disable-save-password-bubble',
        ],
    });
    paginaActiva = contextoNavegador.pages()[0] ?? (await contextoNavegador.newPage());

    return paginaActiva;
}

async function paginaMuestraLogin(page) {
    // Señal positiva primero: si ya apareció la pantalla "Seleccionar unidad
    // de ingreso", el login definitivamente completó — sin importar si
    // Playwright todavía reporta "visible" un input[type="password"] del
    // formulario de login que la SPA dejó montado en el DOM (tapado
    // visualmente por la pantalla nueva). Esto es justo lo que se observó en
    // la calibración real: el usuario veía "Seleccionar unidad de ingreso"
    // en pantalla, pero esta función seguía devolviendo true.
    const yaAvanzoASeleccionUnidad = await page
        .locator(SELECCION_UNIDAD_INGRESO.indicador[0])
        .first()
        .isVisible()
        .catch(() => false);

    if (yaAvanzoASeleccionUnidad) {
        return false;
    }

    // Se usa el campo de contraseña como indicador (no el de usuario): un
    // input[type="password"] es prácticamente inequívoco, mientras que el
    // respaldo genérico de LOGIN.usuario (input[type="text"]) calza con
    // cualquier otro campo de texto de la app ya autenticada.
    for (const selector of LOGIN.password) {
        const locator = page.locator(selector).first();
        if ((await locator.count()) > 0 && (await locator.isVisible())) {
            return true;
        }
    }

    return false;
}

/**
 * Ubica el dropdown asociado a una etiqueta: primero encuentra el texto de
 * la etiqueta con el motor de texto de Playwright (robusto a que el texto
 * esté partido en varios nodos internos — un XPath text() propio falló acá
 * en una calibración anterior), luego toma el siguiente elemento que parece
 * un campo/dropdown en el documento.
 */
function localizarDropdownPorEtiqueta(page, etiqueta) {
    return page.locator(`text=${etiqueta}`).first().locator(SELECCION_UNIDAD_INGRESO.siguienteCampoXpath);
}

/**
 * Selecciona un valor en un dropdown personalizado (no <select> nativo)
 * ubicado por su etiqueta real (no por posición: un selector genérico
 * posicional puede calzar con otro elemento de la página y desalinear los
 * índices). Funciona tanto si el dropdown abre una lista de opciones
 * clicables como si acepta tipear para filtrar.
 */
async function seleccionarDropdownPorTexto(page, etiqueta, textoObjetivo, descripcion) {
    const dropdown = localizarDropdownPorEtiqueta(page, etiqueta);

    if (!(await dropdown.count())) {
        throw new Error(
            `No se encontró el dropdown de "${descripcion}" (etiqueta "${etiqueta}") — hay que calibrar SELECCION_UNIDAD_INGRESO en selectors.js.`,
        );
    }

    await dropdown.click();

    // Si el dropdown acepta tipear para filtrar, esto reduce las opciones
    // visibles; si no acepta texto, no tiene efecto negativo.
    await page.keyboard.type(textoObjetivo, { delay: 20 });

    const opcionPorRol = page.getByRole('option', { name: new RegExp(textoObjetivo, 'i') }).first();
    if ((await opcionPorRol.count()) > 0) {
        await opcionPorRol.click();

        return;
    }

    const opcionPorTexto = page.locator(`text=/${textoObjetivo}/i`).first();
    if ((await opcionPorTexto.count()) > 0) {
        await opcionPorTexto.click();

        return;
    }

    throw new Error(
        `No se encontró ninguna opción que calce con "${textoObjetivo}" para ${descripcion} — hay que calibrar SELECCION_UNIDAD_INGRESO en selectors.js.`,
    );
}

async function dropdownTieneValor(page, etiqueta) {
    const dropdown = localizarDropdownPorEtiqueta(page, etiqueta);
    const texto = (await dropdown.innerText().catch(() => '')).trim();

    return texto.length > 0;
}

async function manejarSeleccionUnidadIngreso(page, pasos) {
    const indicador = page.locator(SELECCION_UNIDAD_INGRESO.indicador[0]).first();

    if (!(await indicador.count()) || !(await indicador.isVisible())) {
        return;
    }

    // TODO-VERIFICAR: en una calibración estos dropdowns ya venían con un
    // valor por defecto (no había que tocarlos); en otra aparecieron vacíos.
    // Se seleccionan solo si están vacíos, para no pisar un valor correcto
    // ya precargado.
    if (!(await dropdownTieneValor(page, SELECCION_UNIDAD_INGRESO.etiquetaFuenteFinanciamiento))) {
        await seleccionarDropdownPorTexto(
            page,
            SELECCION_UNIDAD_INGRESO.etiquetaFuenteFinanciamiento,
            SELECCION_UNIDAD_INGRESO.valorFuenteFinanciamiento,
            'Fuente Financiamiento',
        );

        // TODO-VERIFICAR: se asume que "Centro Financiero" es un dropdown
        // dependiente que recién carga sus opciones después de elegir
        // Fuente Financiamiento (patrón común de selects en cascada). Si no
        // lo es, este wait simplemente no tiene efecto negativo.
        await page.waitForLoadState('networkidle');
    }

    if (!(await dropdownTieneValor(page, SELECCION_UNIDAD_INGRESO.etiquetaCentroFinanciero))) {
        await seleccionarDropdownPorTexto(
            page,
            SELECCION_UNIDAD_INGRESO.etiquetaCentroFinanciero,
            SELECCION_UNIDAD_INGRESO.valorCentroFinanciero,
            'Centro Financiero',
        );
    }

    const botonContinuar = await primerSelectorExistente(
        page,
        SELECCION_UNIDAD_INGRESO.botonContinuar,
        'botón "Continuar" de selección de unidad de ingreso',
    );

    await botonContinuar.click();
    await page.waitForLoadState('networkidle');

    pasos.push(paso('seleccionar_unidad_ingreso', 'completado'));
}

/**
 * Inicia sesión en SGF si la página no está ya autenticada. Idempotente.
 */
async function asegurarSesionIniciada(page, pasos) {
    await page.goto(process.env.SGF_URL, { waitUntil: 'networkidle' });

    const requiereLogin = await paginaMuestraLogin(page);

    if (!requiereLogin) {
        pasos.push(paso('iniciar_sesion', 'completado', { detalle: 'sesion_ya_activa' }));
        await manejarSeleccionUnidadIngreso(page, pasos);

        return;
    }

    const campoUsuario = await primerSelectorExistente(page, LOGIN.usuario, 'campo de usuario');
    const campoPassword = await primerSelectorExistente(page, LOGIN.password, 'campo de contraseña');
    const botonSubmit = await primerSelectorExistente(page, LOGIN.submit, 'botón de ingresar');

    await campoUsuario.fill(process.env.SGF_USUARIO);
    await campoPassword.fill(process.env.SGF_PASSWORD);
    await botonSubmit.click();
    await page.waitForLoadState('networkidle');

    // Respaldo si el flag de lanzamiento no alcanzó a suprimir el diálogo
    // nativo "¿Guardar contraseña?" de Chrome: Escape lo cierra sin guardar
    // ni rechazar nada sensible (es UI del navegador, no de la página).
    await page.keyboard.press('Escape').catch(() => {});

    // Espera explícita (con reintento), no un chequeo puntual: "networkidle"
    // puede resolver antes de que la SPA termine de renderizar la pantalla
    // siguiente, dejando una fracción de segundo donde el formulario de
    // login viejo todavía cuenta como "visible" y la pantalla nueva todavía
    // no existe en el DOM. Se le da hasta 10s a que aparezca la señal de
    // éxito antes de decidir si realmente falló.
    await page
        .locator(SELECCION_UNIDAD_INGRESO.indicador[0])
        .first()
        .waitFor({ state: 'visible', timeout: 10000 })
        .catch(() => {
            // No apareció en 10s — puede que este login no tenga ese paso
            // intermedio (vaya directo a otra pantalla) o que haya fallado.
            // Se re-chequea el estado real a continuación.
        });

    if (await paginaMuestraLogin(page)) {
        pasos.push(paso('iniciar_sesion', 'error', { detalle: 'credenciales_rechazadas_o_selector_invalido' }));

        throw new Error(
            'El login no completó (la página sigue mostrando el formulario de acceso). ' +
                'Puede ser una credencial rechazada o un selector de selectors.js desactualizado — no reintentar en bucle.',
        );
    }

    pasos.push(paso('iniciar_sesion', 'completado'));

    await manejarSeleccionUnidadIngreso(page, pasos);
}

/**
 * Navega sidebar "RedFlow" -> "Bandeja" -> click "Buscar", dejando la
 * página en el listado de procesos pendientes.
 */
async function navegarABandeja(page, pasos) {
    const itemRedFlow = await esperarYObtenerPrimero(page, NAVEGACION_REDFLOW.itemRedFlow, 'ítem de menú "RedFlow"');
    await itemRedFlow.click();

    const itemBandeja = await esperarYObtenerPrimero(page, NAVEGACION_REDFLOW.itemBandeja, 'ítem de menú "Bandeja"');
    await itemBandeja.click();
    await page.waitForLoadState('networkidle');
    await esperarSpinnerAusente(page);

    pasos.push(paso('navegar_bandeja', 'completado'));

    // VERIFICADO (2026-07-08): NO se hace clic en "Buscar". La pestaña "Mis
    // pendientes" ya trae los procesos pendientes por defecto al llegar acá;
    // "Buscar" aplica el formulario de filtros (Fecha inicial/final = hoy
    // por defecto), que en la práctica VACÍA la tabla ("No hay datos
    // disponibles en la tabla") porque filtra a la fecha de hoy en vez de
    // traer todos los pendientes. Si en el futuro se necesita paginar o
    // filtrar de verdad, hay que limpiar/ajustar el formulario antes de
    // usar ese botón — no usarlo con los valores por defecto tal cual.
}

/**
 * Avanza a la siguiente página de la tabla de la Bandeja, si existe.
 *
 * TODO-VERIFICAR: el selector exacto del botón "Siguiente" y de su estado
 * deshabilitado en la última página (PAGINACION_BANDEJA en selectors.js)
 * están descritos por el usuario pero no calibrados contra el DOM real.
 *
 * @returns {Promise<boolean>} true si avanzó de página, false si ya no hay más.
 */
async function avanzarSiguientePagina(page) {
    const boton = page.locator(PAGINACION_BANDEJA.botonSiguiente).first();

    if (!(await boton.count())) {
        return false;
    }

    const deshabilitadoPorAtributo = (await boton.getAttribute('aria-disabled')) === 'true';
    const claseDeshabilitada = (await boton.getAttribute('class')) ?? '';
    // El <li> contenedor suele llevar la clase "disabled" en vez del propio
    // link/botón (patrón Bootstrap) — se revisa también el padre inmediato.
    const claseContenedorDeshabilitada = (await boton.locator('xpath=..').getAttribute('class').catch(() => null)) ?? '';

    if (deshabilitadoPorAtributo || claseDeshabilitada.includes('disabled') || claseContenedorDeshabilitada.includes('disabled')) {
        return false;
    }

    if (!(await boton.isVisible())) {
        return false;
    }

    await boton.click();
    await page.waitForLoadState('networkidle');
    await esperarSpinnerAusente(page);

    return true;
}

function normalizarTexto(texto) {
    return (texto ?? '')
        .toLowerCase()
        .normalize('NFD')
        .replace(/[̀-ͯ]/g, '')
        .trim();
}

/**
 * Lee los encabezados de columna de la tabla de la Bandeja una sola vez
 * (no por fila, para no repetir la misma consulta N veces).
 *
 * Reintenta con polling hasta encontrar la columna "Id" real (no solo que
 * existan <th>, sino que tengan contenido) — la tabla se re-renderiza tras
 * buscar, y leer los encabezados justo en ese momento puede devolver una
 * tabla vacía o de un estado anterior.
 */
async function leerEncabezadosTabla(page, timeoutMs = 8000) {
    const limite = Date.now() + timeoutMs;
    let ultimoIntento = [];

    while (Date.now() < limite) {
        // .first() explícito: aunque el selector ya está acotado a
        // "table:visible", esto evita mezclar encabezados de más de una
        // tabla visible si esa suposición fallara.
        const encabezados = await page.locator(BANDEJA_PROCESOS.tabla).first().locator('th').allTextContents();
        ultimoIntento = encabezados.map(normalizarTexto);

        if (indiceColumna(ultimoIntento, MAPEO_COLUMNAS_BANDEJA.sgf_id) >= 0) {
            return ultimoIntento;
        }

        await new Promise((resolve) => setTimeout(resolve, 200));
    }

    throw new Error(
        `No se encontró la columna "Id" en los encabezados de la tabla de la Bandeja dentro de ${timeoutMs}ms (encabezados leídos: ${JSON.stringify(ultimoIntento)}) — hay que calibrar BANDEJA_PROCESOS/MAPEO_COLUMNAS_BANDEJA en selectors.js.`,
    );
}

/**
 * Encuentra el índice de columna para un campo: primero por igualdad EXACTA
 * de encabezado (para no confundir "rut" con "rut/id" o "rut pago", que
 * también existen en esta tabla), y solo si no hay match exacto, por
 * substring.
 */
function indiceColumna(encabezados, variantes) {
    const indiceExacto = encabezados.findIndex((encabezado) => variantes.includes(encabezado));
    if (indiceExacto >= 0) {
        return indiceExacto;
    }

    return encabezados.findIndex((encabezado) => variantes.some((variante) => encabezado.includes(variante)));
}

/**
 * Extrae los datos de una fila de proceso mapeando celdas por nombre de
 * columna (VERIFICADO: "Id", "Grupo actual", "Rut" y "Monto" son
 * encabezados reales de la Bandeja; no existe columna "Estado" — se usa el
 * valor de "Grupo actual" también para ese campo).
 */
async function extraerDatosFila(filaLocator, encabezados) {
    const celdas = await filaLocator.locator('td').allTextContents();
    const celdasLimpias = celdas.map((c) => c.trim());

    const porColumna = {};
    for (const [campo, variantes] of Object.entries(MAPEO_COLUMNAS_BANDEJA)) {
        const indice = indiceColumna(encabezados, variantes);
        porColumna[campo] = indice >= 0 ? (celdasLimpias[indice] ?? null) : null;
    }

    // Respaldo por regex, solo por si el DOM real difiere de lo calibrado.
    if (!porColumna.rut) {
        const textoCompleto = (await filaLocator.innerText()).trim();
        const rutMatch = textoCompleto.match(REGEX_RUT);
        porColumna.rut = rutMatch ? rutMatch[0] : null;
    }

    if (!porColumna.monto) {
        const textoCompleto = (await filaLocator.innerText()).trim();
        const montoMatch = textoCompleto.match(REGEX_MONTO);
        porColumna.monto = montoMatch ? montoMatch[0].trim() : null;
    }

    return {
        sgf_id: porColumna.sgf_id,
        rut: porColumna.rut,
        monto: porColumna.monto,
        estado: porColumna.estado,
        grupo_actual: porColumna.grupo_actual,
        observaciones: porColumna.observaciones,
        grupo_remitente: porColumna.grupo_remitente,
        periodo: porColumna.periodo,
        folio_egreso: porColumna.folio_egreso,
        numero: porColumna.numero,
        fecha_sii: porColumna.fecha_sii,
        observacion_egreso: porColumna.observacion_egreso,
    };
}

/**
 * Encuentra, entre TODAS las <table> de la página (puede haber más de una
 * montada en el DOM simultáneamente, ej. la de la Bandeja detrás de este
 * panel), la que corresponde a "Lista documentos": la que tiene columnas
 * "Orden" y "Nombre" en su encabezado. No asume ninguna clase de
 * contenedor/modal — identifica la tabla por su contenido real.
 */
async function localizarFilasTablaDocumentos(page, timeoutMs = 8000) {
    const limite = Date.now() + timeoutMs;

    while (Date.now() < limite) {
        const tablas = page.locator(VER_DOCUMENTOS.tabla);
        const totalTablas = await tablas.count();

        for (let i = 0; i < totalTablas; i++) {
            const tabla = tablas.nth(i);
            const encabezados = (await tabla.locator('th').allTextContents()).map(normalizarTexto);

            if (encabezados.some((e) => e.includes('orden')) && encabezados.some((e) => e.includes('nombre'))) {
                return tabla.locator('tbody tr');
            }
        }

        await new Promise((resolve) => setTimeout(resolve, 200));
    }

    throw new Error(
        `No se encontró ninguna tabla con columnas "Orden"/"Nombre" (Lista documentos) dentro de ${timeoutMs}ms — hay que calibrar VER_DOCUMENTOS en selectors.js.`,
    );
}

/**
 * Abre "Ver Documentos" para una fila de proceso, cambia a la pestaña
 * "Lista documentos", descarga cada PDF a
 * `${LARAVEL_STORAGE_PATH}/sgf-documentos/{sgfId}/`, y cierra el panel.
 *
 * @returns {Promise<Array<{tipo_documento_codigo: string, nombre_archivo: string, ruta_archivo: string}>>}
 */
async function descargarDocumentosDeFila(page, filaLocator, sgfId, pasos) {
    await esperarSpinnerAusente(page);

    const botonMenu = await primerSelectorExistente(filaLocator, MENU_ACCIONES_PROCESO.botonMenu, 'botón de menú de acciones del proceso');
    await botonMenu.click();

    // VERIFICADO (2026-07-08): el menú NO vive dentro de la fila (buscarlo
    // acotado a filaLocator da "no existe" — se renderiza aparte, patrón
    // típico de ng-bootstrap). Se busca en toda la página exigiendo que esté
    // VISIBLE, con reintento: como es compartido/reposicionado, un chequeo
    // puntual puede llegar antes de que termine de mostrarse.
    const opcionVerDocumentos = await esperarYObtenerPrimero(
        page,
        MENU_ACCIONES_PROCESO.opcionVerDocumentos,
        'opción "Ver Documentos" del menú de acciones',
    );
    await opcionVerDocumentos.click();
    await page.waitForLoadState('networkidle');
    await esperarSpinnerAusente(page);

    // Con reintento (no primerSelectorExistente): justo como con el menú de
    // acciones, esto va después de un clic que abre el panel — un chequeo
    // puntual puede llegar antes de que termine de renderizarse. VERIFICADO
    // (2026-07-08, calibración real): funcionó al primer intento para el
    // primer proceso pero falló ("no existe") para el segundo, confirmando
    // que es un problema de timing y no de selector incorrecto.
    const pestaña = await esperarYObtenerPrimero(page, VER_DOCUMENTOS.pestañaListaDocumentos, 'pestaña "Lista documentos"');
    await pestaña.click();
    await page.waitForLoadState('networkidle');
    await esperarSpinnerAusente(page);

    const carpetaDestino = path.join(RUTA_STORAGE_DOCUMENTOS, 'sgf-documentos', sgfId);
    await mkdir(carpetaDestino, { recursive: true });

    // NO se busca "table" a secas: la tabla de la Bandeja sigue en el DOM
    // detrás del panel (":visible" no la excluye porque solo mira
    // display/visibility, no si algo la tapa encima) — un lookup sin acotar
    // agarró una fila de la Bandeja (ej. "676", el Id) en vez de la tabla
    // real. Se identifica la tabla correcta por sus columnas reales
    // (Orden/Nombre), sin asumir ninguna clase de contenedor/modal.
    const filas = await localizarFilasTablaDocumentos(page);
    const total = await filas.count();
    const documentos = [];

    for (let i = 0; i < total; i++) {
        const fila = filas.nth(i);
        const celdas = await fila.locator('td').allTextContents();
        const nombreArchivo = (celdas[VER_DOCUMENTOS.columnaNombre] ?? `documento-${i + 1}.pdf`).trim();

        const enlaceDescarga = await primerSelectorExistente(fila, VER_DOCUMENTOS.iconoDescarga, `ícono de descarga del documento "${nombreArchivo}"`);

        await esperarSpinnerAusente(page);

        // VERIFICADO (2026-07-08): no existe un botón "Descargar" dentro del
        // panel de SGF. Clickear el ícono abre el PDF en una pestaña/ventana
        // nueva del navegador (popup) con la URL directa del archivo — no
        // dispara un evento 'download' de Playwright porque Chromium
        // renderiza el PDF inline ahí. Se captura ese popup, se lee su URL,
        // y se descarga el archivo con una petición HTTP que reutiliza las
        // cookies de la sesión (page.context().request comparte el cookie
        // jar del contexto del navegador). Como la pestaña principal nunca
        // navega, no hace falta volver a "Lista documentos" para el
        // siguiente documento.
        const [popup] = await Promise.all([page.waitForEvent('popup'), enlaceDescarga.click()]);
        await popup.waitForLoadState('domcontentloaded');

        const pdfUrl = popup.url();
        const respuesta = await page.context().request.get(pdfUrl);

        if (!respuesta.ok()) {
            await popup.close();

            throw new Error(
                `La descarga de "${nombreArchivo}" devolvió estado ${respuesta.status()} para ${pdfUrl} — puede que la sesión no se haya propagado a la petición directa (revisar cookies/headers).`,
            );
        }

        const bytes = await respuesta.body();

        if (!bytesParecenPdf(bytes)) {
            const contentType = respuesta.headers()['content-type'] ?? '(sin content-type)';
            const nombreDiagnostico = `respuesta-no-pdf-${sgfId}-${i + 1}`;
            await mkdir(RUTA_DEBUG, { recursive: true });
            await writeFile(path.join(RUTA_DEBUG, `${nombreDiagnostico}.bin`), bytes);
            await popup.close();

            throw new Error(
                `La respuesta para "${nombreArchivo}" (${pdfUrl}) no empieza con la firma "%PDF-" ` +
                    `(content-type: "${contentType}", ${bytes.length} bytes) — la petición directa probablemente ` +
                    `no llevó la autenticación completa (ej. SGF usa un token en localStorage que ` +
                    `page.context().request no reenvía junto a las cookies). Se guardó el contenido recibido en ` +
                    `services/sgf-playwright/debug/${nombreDiagnostico}.bin para inspeccionar qué llegó realmente ` +
                    `(probablemente HTML de una página de login o un error de la API).`,
            );
        }

        const rutaDestino = path.join(carpetaDestino, nombreArchivo);
        await writeFile(rutaDestino, bytes);

        await popup.close();

        documentos.push({
            tipo_documento_codigo: inferirTipoDocumento(nombreArchivo),
            nombre_archivo: nombreArchivo,
            ruta_archivo: `sgf-documentos/${sgfId}/${nombreArchivo}`,
        });
    }

    pasos.push(paso(`descargar_documentos_${sgfId}`, 'completado', { total_documentos: documentos.length }));

    const botonCerrar = await primerSelectorExistente(page, VER_DOCUMENTOS.botonCerrar, 'botón para cerrar el panel de documentos');
    await botonCerrar.click();
    await page.waitForLoadState('networkidle');

    return documentos;
}

/**
 * Procesa una única fila de proceso: extrae sus datos y descarga sus
 * documentos adjuntos.
 *
 * @param {{filtro?: (datos: object) => boolean}} [opciones] Si se pasa
 * `filtro`, la fila se descarta (sin descargar sus documentos, el paso más
 * costoso) cuando `filtro(datos)` da `false` — usado por
 * importarGrupoPagoOperaciones() como red de seguridad adicional al filtro
 * nativo de la Bandeja.
 * @returns {Promise<{sgf_id: string, payload_crudo: object}|null>} `null` si
 * `filtro` descartó la fila.
 */
async function procesarFilaProceso(page, filaLocator, encabezados, pasos, opciones = {}) {
    const datos = await extraerDatosFila(filaLocator, encabezados);

    if (opciones.filtro && !opciones.filtro(datos)) {
        return null;
    }

    if (!datos.sgf_id) {
        const celdas = await filaLocator.locator('td').allTextContents();

        throw new Error(
            'No se pudo determinar el N° de proceso de una fila de la Bandeja (columna "Id" esperada) — hay que calibrar MAPEO_COLUMNAS_BANDEJA en selectors.js. ' +
                `Diagnóstico: encabezados=${JSON.stringify(encabezados)} celdas=${JSON.stringify(celdas.map((c) => c.trim()))} datos_extraidos=${JSON.stringify(datos)}`,
        );
    }

    const documentos = await descargarDocumentosDeFila(page, filaLocator, datos.sgf_id, pasos);

    return { sgf_id: datos.sgf_id, payload_crudo: { ...datos, documentos } };
}

/**
 * Busca un único caso por su N° de proceso SGF, dentro del listado de
 * pendientes de la Bandeja.
 *
 * @returns {Promise<{encontrada: boolean, payload_crudo: object|null, pasos: Array}>}
 */
export async function verificarCaso(sgfId) {
    const pasos = [];
    const page = await obtenerPagina();

    await asegurarSesionIniciada(page, pasos);
    await navegarABandeja(page, pasos);

    // Recorre página por página de la Bandeja (VERIFICADO 2026-07-08: la
    // Bandeja pagina sus resultados, "Buscar" no basta) hasta encontrar el
    // sgf_id buscado o agotar el paginador.
    for (let numeroPagina = 1; numeroPagina <= MAX_PAGINAS_BANDEJA; numeroPagina++) {
        const encabezados = await leerEncabezadosTabla(page);
        const filas = page.locator(BANDEJA_PROCESOS.filaProceso);
        const total = await filas.count();

        for (let i = 0; i < total; i++) {
            const fila = filas.nth(i);
            const texto = await fila.innerText();

            if (texto.includes(sgfId)) {
                pasos.push(paso('buscar_caso', 'completado', { sgf_id: sgfId, pagina: numeroPagina }));

                const resultado = await procesarFilaProceso(page, fila, encabezados, pasos);
                pasos.push(paso('extraer_datos', 'completado'));

                return { encontrada: true, payload_crudo: resultado.payload_crudo, pasos: numerarPasos(pasos) };
            }
        }

        if (!(await avanzarSiguientePagina(page))) {
            break;
        }
    }

    pasos.push(paso('buscar_caso', 'completado', { sgf_id: sgfId, encontrada: false }));

    return { encontrada: false, payload_crudo: null, pasos: numerarPasos(pasos) };
}

/**
 * Lista todos los casos pendientes visibles en SGF e importa sus documentos.
 *
 * @returns {Promise<{filas: Array<{sgf_id: string, payload_crudo: object}>, pasos: Array}>}
 */
export async function importarPendientes() {
    const pasos = [];
    const page = await obtenerPagina();

    await asegurarSesionIniciada(page, pasos);
    await navegarABandeja(page, pasos);

    const resultado = [];

    // Recorre página por página (VERIFICADO 2026-07-08: la Bandeja pagina
    // sus resultados; sin esto solo se procesaba la primera página) hasta
    // agotar el paginador o llegar al tope defensivo MAX_PAGINAS_BANDEJA.
    for (let numeroPagina = 1; numeroPagina <= MAX_PAGINAS_BANDEJA; numeroPagina++) {
        const encabezados = await leerEncabezadosTabla(page);
        const total = await page.locator(BANDEJA_PROCESOS.filaProceso).count();

        // Se procesa por índice (no se guardan los locators de antemano)
        // porque cada descarga/cierre de panel puede alterar el DOM de la
        // lista.
        for (let i = 0; i < total; i++) {
            const fila = page.locator(BANDEJA_PROCESOS.filaProceso).nth(i);
            resultado.push(await procesarFilaProceso(page, fila, encabezados, pasos));
        }

        pasos.push(paso(`pagina_bandeja_${numeroPagina}`, 'completado', { filas_procesadas: total }));

        if (!(await avanzarSiguientePagina(page))) {
            break;
        }
    }

    return { filas: resultado, pasos: numerarPasos(pasos) };
}

/**
 * Fecha (mismo día del mes, un mes atrás) en formato "YYYY-MM-DD" — el
 * formato que espera .fill() en un <input type="date"> nativo,
 * independientemente del formato que el navegador muestre visualmente al
 * usuario. TODO-VERIFICAR: se asume que "Fecha inicial" es un input nativo;
 * si el DOM real resulta ser un datepicker de terceros con otro formato de
 * entrada, hay que ajustar este helper.
 */
function fechaHaceUnMes() {
    const hoy = new Date();
    const haceUnMes = new Date(hoy.getFullYear(), hoy.getMonth() - 1, hoy.getDate());

    const año = haceUnMes.getFullYear();
    const mes = String(haceUnMes.getMonth() + 1).padStart(2, '0');
    const dia = String(haceUnMes.getDate()).padStart(2, '0');

    return `${año}-${mes}-${dia}`;
}

/**
 * Aplica el filtro "Grupo" + rango de fechas del formulario "Buscar" de la
 * Bandeja y hace clic en "Buscar". A diferencia de navegarABandeja() (que
 * evita deliberadamente ese botón para la importación masiva), esta función
 * SÍ lo usa: calibración aportada por el usuario (2026-07-09) confirmó que,
 * con un rango de fechas suficientemente amplio (un mes atrás hasta hoy) y
 * un grupo seleccionado, "Buscar" no vacía la tabla — el vaciado
 * VERIFICADO 2026-07-08 (ver navegarABandeja) ocurre porque el rango por
 * defecto es "hoy a hoy", no por un problema del botón en sí.
 *
 * Reutiliza localizarDropdownPorEtiqueta()/seleccionarDropdownPorTexto() tal
 * cual (su xpath "siguiente campo" es genérico, no específico de
 * "unidad de ingreso"). TODO-VERIFICAR: el selector exacto del multiselect
 * "Grupo" y del input "Fecha inicial" no está calibrado contra el DOM real
 * (la captura aportada es visual, no HTML) — ver tarea 1.5 del change
 * importar-casos-grupo-pago-operaciones-sgf.
 */
async function filtrarBandejaPorGrupoPagoOperaciones(page, pasos) {
    await seleccionarDropdownPorTexto(
        page,
        FILTRO_BANDEJA.etiquetaGrupo,
        FILTRO_BANDEJA.valorGrupoPagoOperaciones,
        'filtro "Grupo" de la Bandeja',
    );

    const campoFechaInicial = localizarDropdownPorEtiqueta(page, FILTRO_BANDEJA.etiquetaFechaInicial);
    await campoFechaInicial.fill(fechaHaceUnMes());

    // "Fecha final" se deja en su valor precargado por defecto (hoy) — no
    // se toca.

    const botonBuscar = await primerSelectorExistente(page, BANDEJA_PROCESOS.botonBuscar, 'botón "Buscar" de la Bandeja');
    await botonBuscar.click();
    await page.waitForLoadState('networkidle');
    await esperarSpinnerAusente(page);

    pasos.push(paso('filtrar_grupo_pago_operaciones', 'completado'));
}

/**
 * Lista los casos SGF cuyo grupo actual es "Pago Operaciones", usando el
 * filtro nativo de la Bandeja (Decisión 1, design.md del change
 * importar-casos-grupo-pago-operaciones-sgf) en vez de recorrer y descartar
 * client-side. Como red de seguridad, cada fila igual se valida contra
 * `grupo_actual` antes de descargar sus documentos, por si el filtro nativo
 * devolviera alguna fila inesperada.
 *
 * @returns {Promise<{filas: Array<{sgf_id: string, payload_crudo: object}>, pasos: Array}>}
 */
export async function importarGrupoPagoOperaciones() {
    const pasos = [];
    const page = await obtenerPagina();

    await asegurarSesionIniciada(page, pasos);
    await navegarABandeja(page, pasos);
    await filtrarBandejaPorGrupoPagoOperaciones(page, pasos);

    const resultado = [];

    for (let numeroPagina = 1; numeroPagina <= MAX_PAGINAS_BANDEJA; numeroPagina++) {
        const encabezados = await leerEncabezadosTabla(page);
        const total = await page.locator(BANDEJA_PROCESOS.filaProceso).count();
        const gruposActuales = new Set();

        for (let i = 0; i < total; i++) {
            const fila = page.locator(BANDEJA_PROCESOS.filaProceso).nth(i);
            // El filtro nativo de la Bandeja (dropdown "GRUPO" = "Pago
            // Operaciones") ya acotó el listado en origen: se confía en él. NO
            // se vuelve a filtrar por la columna "Grupo Actual", que es un
            // campo DISTINTO (el paso donde está parado el proceso ahora) y no
            // tiene por qué coincidir con el grupo filtrado — hacerlo descartaba
            // el 100% de las filas legítimas. Se registran los valores distintos
            // de "grupo actual" vistos para trazabilidad/diagnóstico.
            const procesado = await procesarFilaProceso(page, fila, encabezados, pasos);

            if (procesado) {
                gruposActuales.add(procesado.payload_crudo.grupo_actual || '(vacío)');
                resultado.push(procesado);
            }
        }

        pasos.push(paso(`pagina_bandeja_${numeroPagina}`, 'completado', {
            filas_procesadas: total,
            grupos_actuales: [...gruposActuales],
        }));

        if (!(await avanzarSiguientePagina(page))) {
            break;
        }
    }

    return { filas: resultado, pasos: numerarPasos(pasos) };
}

export async function cerrarNavegador() {
    if (contextoNavegador) {
        await contextoNavegador.close();
        contextoNavegador = null;
        paginaActiva = null;
    }
}

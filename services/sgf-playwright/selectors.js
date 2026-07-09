// Selectores del scraper real de SGF.
//
// Cada selector es una lista de candidatos probados en orden (el primero que
// exista/esté visible en la página se usa). Esto da resiliencia mientras se
// termina de calibrar, en vez de fallar directo con un único selector
// adivinado.
//
// Estado de calibración (ver CALIBRACION.md para el detalle del proceso):
//   - LOGIN: verificado como funcional en la calibración real del
//     2026-07-07 (con los selectores genéricos ya definidos).
//   - SELECCION_UNIDAD_INGRESO: verificado (pantalla real confirmada).
//   - NAVEGACION_REDFLOW, BANDEJA_PROCESOS, MENU_ACCIONES_PROCESO,
//     VER_DOCUMENTOS: descritos por el usuario a partir de uso real del
//     sistema, pero los selectores CSS/texto exactos siguen sin calibrar
//     contra el DOM (se construyeron a partir de capturas de pantalla, no
//     del HTML real) — marcados TODO-VERIFICAR.

export const LOGIN = {
    usuario: [
        'input[name="usuario"]',
        'input[name="username"]',
        'input[name="rut"]',
        'input[formcontrolname="usuario"]',
        'input[formcontrolname="rut"]',
        'input[type="text"]',
    ],
    password: [
        'input[name="password"]',
        'input[name="clave"]',
        'input[formcontrolname="password"]',
        'input[formcontrolname="clave"]',
        'input[type="password"]',
    ],
    submit: [
        'button[type="submit"]',
        'button:has-text("Ingresar")',
        'button:has-text("Iniciar sesión")',
        'button:has-text("Entrar")',
    ],
};

// VERIFICADO (2026-07-07, calibración real): tras un login correcto, SGF
// muestra una pantalla intermedia "Seleccionar unidad de ingreso" con dos
// selects (Fuente Financiamiento, Centro Financiero) y un botón "Continuar".
// TODO-VERIFICAR: en una calibración aparecieron ya precargados con un valor
// por defecto; en otra aparecieron vacíos y hubo que seleccionarlos. El
// código maneja ambos casos: si ya tienen valor, no toca nada; si están
// vacíos, selecciona estos valores (institución CAPJ / zonal Coyhaique, ver
// HARNESS_IA.md — coincide con el subtítulo del login "Sección Finanzas y
// Presupuesto - Zonal Coyhaique").
export const SELECCION_UNIDAD_INGRESO = {
    indicador: ['text=Seleccionar unidad de ingreso'],
    // Se ubica cada dropdown por su etiqueta real (no por posición 0/1: un
    // selector genérico posicional calzó mal en una calibración previa). El
    // texto de la etiqueta se busca con el motor de texto de Playwright
    // (robusto a que el texto esté partido en varios nodos internos), y
    // desde ahí se toma el siguiente elemento interactivo en el documento —
    // ver localizarDropdownPorEtiqueta() en sgf-scraper.js.
    // Sufijo XPath reutilizable para "el próximo elemento que parece un
    // campo/dropdown" relativo a la etiqueta ya localizada.
    siguienteCampoXpath:
        'xpath=following::*[self::input or @role="combobox" or contains(@class,"select") or contains(@class,"dropdown")][1]',
    etiquetaFuenteFinanciamiento: 'SELECCIONE FUENTE FINANCIAMIENTO',
    etiquetaCentroFinanciero: 'SELECCIONE CENTRO FINANCIERO',
    valorFuenteFinanciamiento: 'CAPJ',
    valorCentroFinanciero: 'CAPJ ADMINISTRACIÓN ZONAL DE COYHAIQUE',
    botonContinuar: ['button:has-text("Continuar")'],
};

// Descrito por el usuario (2026-07-07): en el sidebar hay un ítem "RedFlow"
// (ícono de maletín) que despliega un submenú con "Bandeja" (ícono de
// flecha). "Bandeja" muestra todos los procesos pendientes.
// VERIFICADO (2026-07-07): el dashboard real (tras el login) muestra el
// sidebar con "Dashboard", "Bandeja de tareas", "Doc. financiera",
// "Boletas garantía", "RedFlow", "Contrato", "Licitación", "Evaluación
// financiera". Dos trampas confirmadas:
//   - El dashboard tiene una tarjeta "TOTAL PENDIENTES RedFlow" que
//     contiene la palabra "RedFlow" como texto plano — un selector de
//     substring genérico puede enganchar esa tarjeta en vez del ítem real
//     del sidebar. Por eso se prioriza un selector de link/ítem clicable.
//   - Existe un ítem de sidebar SEPARADO "Bandeja de tareas" (no
//     relacionado). "Bandeja" (el sub-ítem dentro de RedFlow) debe
//     calzarse por texto EXACTO, no por substring, para no confundirse
//     con "Bandeja de tareas".
export const NAVEGACION_REDFLOW = {
    itemRedFlow: ['aside a:has-text("RedFlow")', 'nav a:has-text("RedFlow")', 'a:has-text("RedFlow")'],
    itemBandeja: ['text="Bandeja"'],
};

// VERIFICADO (2026-07-07): la pestaña "Mis pendientes" de la Bandeja ya
// muestra filas sin necesidad de tocar el formulario de filtros (Grupo,
// Fecha inicial/final, Mis ingresos, Buscar por ID); "Buscar" solo aplica-
// ría un filtro adicional. Es una <table> real (no tarjetas), con muchas
// columnas (Acción, adjuntos, Id, Periodo, Grupo actual, Observación envío,
// Grupo remitente, Tipo Ingreso, Fecha, Rut/Id, Nombre, Rut pago, Tipo pago,
// Observación, Observación traslado, N. ingresos, Fecha Cambio, Grupo
// Destino, N° Ingreso, Monto — leídas de una captura de baja resolución,
// texto orientativo). El sistema NO tiene una columna "Estado" separada:
// "Grupo actual" hace ese rol (la etapa/grupo del flujo RedFlow).
export const BANDEJA_PROCESOS = {
    botonBuscar: ['button:has-text("Buscar")'],
    // La Bandeja tiene pestañas ("Mis pendientes", "Todos", "Pendientes por
    // día", "Otros Centros Financieros"): es probable que cada una tenga su
    // propia <table> en el DOM simultáneamente (oculta si no está activa).
    // "table" a secas mezclaría encabezados/filas de más de una tabla — se
    // acota a la única tabla VISIBLE en cada momento.
    tabla: 'table:visible',
    // Explícitamente dentro de <tbody>: la fila de encabezado también es un
    // <tr>, y un selector "tr" a secas la incluye como si fuera un proceso.
    filaProceso: 'table:visible tbody tr',
};

// TODO-VERIFICAR: descrito por el usuario (2026-07-08) como un paginador
// clásico (números de página + flecha "Siguiente"), pero el selector exacto
// del botón/link "Siguiente" y de su estado deshabilitado en la última
// página no están calibrados contra el DOM real todavía — candidatos
// cubren tanto un patrón Bootstrap típico (<ul class="pagination">) como
// atributos ARIA genéricos.
export const PAGINACION_BANDEJA = {
    botonSiguiente: [
        '.pagination li:not(.disabled) a[aria-label="Next"]',
        'a[aria-label="Next"]',
        'button[aria-label="Next"]',
        '.pagination a:has-text("Siguiente")',
        'a:has-text("Siguiente")',
        'button:has-text("Siguiente")',
        '.pagination li:last-child a',
    ],
};

// VERIFICADO (2026-07-08, encabezados reales completos leídos en
// calibración): "accion" | "" (adjuntos) | "id" | "periodo" |
// "centro financiero" | "grupo actual" | "observacion envio" |
// "grupo remitente" | "tipo de ingreso" | "fecha" | "fecha sii" | "numero" |
// "rut" | "para pago" | "tipo subtitulo" | "observacion" |
// "observacion finalizado" | "folio egreso" | "estado" | "fecha creacion" |
// "grupo creacion" | "n° traspaso" | "monto". Sí existe una columna
// "estado" propia (a diferencia de lo asumido antes) — indiceColumna()
// matchea por igualdad exacta primero, así que "estado" no se confunde con
// "grupo actual", y "observacion envio"/"observacion" tampoco se confunden
// entre sí pese a que una es substring de la otra.
export const MAPEO_COLUMNAS_BANDEJA = {
    sgf_id: ['id'],
    grupo_actual: ['grupo actual'],
    estado: ['estado'],
    observaciones: ['observacion envio'],
    grupo_remitente: ['grupo remitente'],
    periodo: ['periodo'],
    rut: ['rut'],
    monto: ['monto'],
    folio_egreso: ['folio egreso'],
    numero: ['numero'],
    fecha_sii: ['fecha sii'],
};

// El botón de menú de la fila de proceso vive en la primera columna
// ("Acción", según la tabla real confirmada), y las opciones de ese menú.
// VERIFICADO (2026-07-08, diagnóstico real): existen 10 menús "Ver
// documentos" en el DOM simultáneamente (uno por fila, todos con la MISMA
// clase "dropdown-item", el menú se queda montado aunque esté cerrado). El
// que está realmente abierto es el único ".dropdown-menu" con la clase
// "show" agregada (patrón estándar de Bootstrap) — sin acotar a eso,
// ".first()" agarra el de la primera fila del DOM, no el que efectivamente
// se abrió.
export const MENU_ACCIONES_PROCESO = {
    botonMenu: [
        'td:first-child button',
        'button[aria-haspopup="menu"]',
        '[class*="kebab"]',
        'button:has(svg)',
    ],
    opcionVerDocumentos: [
        '.dropdown-menu.show >> text=Ver documentos',
        '.dropdown-menu.show >> text=Ver Documentos',
        'text=Ver Documentos',
        'text=Ver documentos',
    ],
};

// Panel que se abre al elegir "Ver Documentos": título "Previsualización de
// documentos", con dos pestañas ("Vista previa" y "Lista documentos") y, en
// la segunda, una tabla con columnas Doc. (ícono PDF) / Orden / Nombre
// (VERIFICADO 2026-07-08, títulos y columnas reales).
//
// VERIFICADO (2026-07-08): la tabla de la Bandeja (de fondo) sigue en el
// DOM detrás de este panel — Playwright's ":visible" no la excluye porque
// solo mira display/visibility, no si algo la tapa encima. Por eso "tabla"
// se identifica por sus columnas reales (Orden/Nombre), no con
// `page.locator('table')` a secas — ver localizarFilasTablaDocumentos() en
// sgf-scraper.js.
//
// VERIFICADO (2026-07-08): hacer clic en el ícono/fila de un documento en
// "Lista documentos" NO abre un botón "Descargar" dentro del panel — abre el
// PDF en una pestaña/ventana nueva del navegador (popup), con la URL directa
// del archivo visible en la barra de direcciones. La descarga se hace
// capturando ese popup y pidiendo esa URL por HTTP (ver
// descargarDocumentosDeFila() en sgf-scraper.js), no con un botón "Descargar"
// ni con page.waitForEvent('download').
export const VER_DOCUMENTOS = {
    modalIndicador: ['text=Previsualización de documentos'],
    pestañaListaDocumentos: ['text=Lista documentos'],
    tabla: 'table',
    // Dentro de cada fila: el ícono/link que abre la vista previa del
    // documento (celda "Doc.").
    iconoDescarga: ['a[href$=".pdf" i]', 'img[src*="pdf" i]', 'a:has(img)'],
    // Índices de columna dentro de la fila (0-based), según la captura:
    // Doc. | Orden | Nombre
    columnaOrden: 1,
    columnaNombre: 2,
    botonCerrar: ['button:has-text("Cerrar")', '[aria-label="Close"]', '[aria-label="Cerrar"]'],
};

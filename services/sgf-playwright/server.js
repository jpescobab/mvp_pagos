// Conector Playwright de SGF — cumple el contrato HTTP definido en design.md
// del change archivado openspec/changes/archive/2026-07-07-conector-sgf-playwright/.
//
// Dos modos, según SGF_MODO:
//   - "stub" (default): servidor en memoria con casos falsos, sin tocar SGF.
//     Es el modo seguro para ejercitar ConectorSgfPlaywrightService (Laravel)
//     de punta a punta en desarrollo.
//   - "real": usa sgf-scraper.js (Playwright) para iniciar sesión de verdad
//     en SGF y extraer datos reales. TODO-VERIFICAR: los selectores de
//     sgf-scraper.js/selectors.js son una suposición no calibrada contra el
//     sitio real — ver CALIBRACION.md antes de usar este modo. La primera
//     corrida en modo real debe hacerla una persona supervisando
//     (SGF_HEADLESS=false), nunca desatendida.
//
// Uso: SGF_PLAYWRIGHT_API_KEY=<clave> PORT=4100 SGF_MODO=stub node server.js

import 'dotenv/config';
import { createServer } from 'node:http';

const PORT = process.env.PORT || 4100;
const API_KEY = process.env.SGF_PLAYWRIGHT_API_KEY || 'stub-local-key';
const MODO = process.env.SGF_MODO === 'real' ? 'real' : 'stub';

// SGF_URL/SGF_USUARIO/SGF_PASSWORD se cargan desde .env (ver .env.example).
// En modo "stub" no se usan. En modo "real" los consume sgf-scraper.js.
// Nunca deben aparecer en un console.log, error, o respuesta HTTP.

const CASOS = [
    {
        sgf_id: '12345',
        estado: 'EN_TRAMITE',
        grupo_actual: 'FINANZAS',
        observaciones: 'Pendiente de revisión',
        observacion_egreso: 'EGRESO-115',
        rut: '11.111.111-1',
        monto: '1.234.567,89',
        periodo: '2026-07',
        folio_egreso: 'EGR-10045',
        numero: '87231',
        fecha_sii: '05-07-2026',
        pendiente: true,
    },
    {
        sgf_id: '67890',
        estado: 'PAGADA',
        grupo_actual: 'TESORERIA',
        observaciones: null,
        rut: '22.222.222-2',
        monto: '500.000',
        periodo: '2026-06',
        folio_egreso: 'EGR-10012',
        numero: '87102',
        fecha_sii: '18-06-2026',
        pendiente: false,
    },
    {
        sgf_id: '55555',
        estado: 'EN_TRAMITE',
        grupo_actual: 'FINANZAS',
        observaciones: 'Con factura adjunta',
        rut: '33.333.333-3',
        monto: '750.000',
        periodo: '2026-07',
        folio_egreso: 'EGR-10050',
        numero: '87240',
        fecha_sii: '07-07-2026',
        pendiente: true,
        documentos: [
            {
                tipo_documento_codigo: 'FACTURA',
                nombre_archivo: 'factura-55555.pdf',
                ruta_archivo: 'sgf-stub/factura-55555.pdf',
            },
        ],
    },
    {
        sgf_id: '67601',
        estado: 'EN_TRAMITE',
        grupo_actual: 'Pago Operaciones',
        observaciones: 'Reembolso gasto combustible',
        rut: '44.444.444-4',
        monto: '89.900',
        periodo: '2026-07',
        folio_egreso: 'EGR-10061',
        numero: '87301',
        fecha_sii: '08-07-2026',
        pendiente: true,
    },
];

function pasosNavegacion(...acciones) {
    return acciones.map((accion, i) => ({ orden: i + 1, accion, estado: 'completado' }));
}

function json(res, status, body) {
    const payload = JSON.stringify(body);
    res.writeHead(status, {
        'Content-Type': 'application/json',
        'Content-Length': Buffer.byteLength(payload),
    });
    res.end(payload);
}

async function manejarVerificarStub(input) {
    const caso = CASOS.find((c) => c.sgf_id === input.sgf_id);

    if (!caso) {
        return { encontrada: false, pasos: pasosNavegacion('iniciar_sesion', 'buscar_caso') };
    }

    return {
        encontrada: true,
        payload_crudo: caso,
        pasos: pasosNavegacion('iniciar_sesion', 'buscar_caso', 'extraer_datos'),
    };
}

async function manejarImportarPendientesStub() {
    const pendientes = CASOS.filter((c) => c.pendiente);

    return {
        filas: pendientes.map((c) => ({ sgf_id: c.sgf_id, payload_crudo: c })),
        pasos: pasosNavegacion(
            'iniciar_sesion',
            'listar_pendientes',
            ...pendientes.map((c) => `abrir_caso_${c.sgf_id}`),
        ),
    };
}

const GRUPO_PAGO_OPERACIONES = 'pago operaciones';

async function manejarImportarGrupoPagoOperacionesStub() {
    const delGrupo = CASOS.filter((c) => c.grupo_actual.trim().toLowerCase() === GRUPO_PAGO_OPERACIONES);

    return {
        filas: delGrupo.map((c) => ({ sgf_id: c.sgf_id, payload_crudo: c })),
        pasos: pasosNavegacion(
            'iniciar_sesion',
            'navegar_bandeja',
            'filtrar_grupo_pago_operaciones',
            ...delGrupo.map((c) => `abrir_caso_${c.sgf_id}`),
        ),
    };
}

/**
 * En modo "real", ejecuta `operacionReal` contra sgf-scraper.js y SIEMPRE
 * cierra el navegador al terminar (éxito o error) — la sesión autenticada de
 * SGF no debe quedar viva esperando entre llamadas. El costo es que cada
 * llamada vuelve a pagar el login completo, pero calibrado en real ese flujo
 * completo (login + Bandeja paginada + 64 procesos) tomó ~3 minutos, así que
 * no es un costo relevante frente al riesgo de dejar una sesión abierta
 * indefinidamente en un sistema financiero institucional.
 */
async function ejecutarEnModoReal(operacionReal, operacionStub) {
    if (MODO !== 'real') {
        return operacionStub();
    }

    const scraper = await import('./sgf-scraper.js');

    try {
        return await operacionReal(scraper);
    } finally {
        await scraper.cerrarNavegador();
    }
}

const server = createServer(async (req, res) => {
    if (req.headers['x-api-key'] !== API_KEY) {
        return json(res, 401, { error: 'API key inválida o ausente' });
    }

    let body = '';
    for await (const chunk of req) {
        body += chunk;
    }
    const input = body ? JSON.parse(body) : {};

    try {
        if (req.method === 'POST' && req.url === '/casos/verificar') {
            const resultado = await ejecutarEnModoReal(
                (scraper) => scraper.verificarCaso(input.sgf_id),
                () => manejarVerificarStub(input),
            );

            return json(res, 200, resultado);
        }

        if (req.method === 'POST' && req.url === '/casos/importar-pendientes') {
            const resultado = await ejecutarEnModoReal(
                (scraper) => scraper.importarPendientes(),
                () => manejarImportarPendientesStub(),
            );

            return json(res, 200, resultado);
        }

        if (req.method === 'POST' && req.url === '/casos/importar-grupo-pago-operaciones') {
            const resultado = await ejecutarEnModoReal(
                (scraper) => scraper.importarGrupoPagoOperaciones(),
                () => manejarImportarGrupoPagoOperacionesStub(),
            );

            return json(res, 200, resultado);
        }
    } catch (e) {
        // Nunca incluir e.stack ni el body de la request en la respuesta: no
        // deben poder filtrarse credenciales por un mensaje de error mal
        // formado dentro de Playwright.
        return json(res, 500, { error: e.message ?? 'Error desconocido en el conector SGF' });
    }

    return json(res, 404, { error: 'No encontrado' });
});

server.listen(PORT, () => {
    console.log(`[sgf-playwright] modo=${MODO} escuchando en http://localhost:${PORT}`);

    if (MODO === 'stub') {
        console.log(`[sgf-playwright] casos de prueba: ${CASOS.map((c) => c.sgf_id).join(', ')}`);
    } else {
        console.log('[sgf-playwright] MODO REAL — asegúrate de correr esto supervisado (SGF_HEADLESS=false) la primera vez.');
    }
});

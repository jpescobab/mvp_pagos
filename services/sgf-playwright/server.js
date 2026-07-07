// Stub local del conector Playwright de SGF.
//
// Esto NO es un scraper real: es un servidor HTTP en memoria que cumple el
// contrato definido en design.md del change archivado
// openspec/changes/archive/2026-07-07-conector-sgf-playwright/, para poder
// ejercitar ConectorSgfPlaywrightService (Laravel) de punta a punta en
// desarrollo sin depender todavía de acceso real a SGF.
//
// Uso: SGF_PLAYWRIGHT_API_KEY=<clave> PORT=4100 node server.js

import { createServer } from 'node:http';

const PORT = process.env.PORT || 4100;
const API_KEY = process.env.SGF_PLAYWRIGHT_API_KEY || 'stub-local-key';

const CASOS = [
    {
        sgf_id: '12345',
        estado: 'EN_TRAMITE',
        grupo_actual: 'FINANZAS',
        observaciones: 'Pendiente de revisión',
        rut: '11.111.111-1',
        monto: '1.234.567,89',
        pendiente: true,
    },
    {
        sgf_id: '67890',
        estado: 'PAGADA',
        grupo_actual: 'TESORERIA',
        observaciones: null,
        rut: '22.222.222-2',
        monto: '500.000',
        pendiente: false,
    },
    {
        sgf_id: '55555',
        estado: 'EN_TRAMITE',
        grupo_actual: 'FINANZAS',
        observaciones: 'Con factura adjunta',
        rut: '33.333.333-3',
        monto: '750.000',
        pendiente: true,
        documentos: [
            {
                tipo_documento_codigo: 'FACTURA',
                nombre_archivo: 'factura-55555.pdf',
                ruta_archivo: 'sgf-stub/factura-55555.pdf',
            },
        ],
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

const server = createServer(async (req, res) => {
    if (req.headers['x-api-key'] !== API_KEY) {
        return json(res, 401, { error: 'API key inválida o ausente' });
    }

    let body = '';
    for await (const chunk of req) {
        body += chunk;
    }
    const input = body ? JSON.parse(body) : {};

    if (req.method === 'POST' && req.url === '/casos/verificar') {
        const caso = CASOS.find((c) => c.sgf_id === input.sgf_id);

        if (!caso) {
            return json(res, 200, {
                encontrada: false,
                pasos: pasosNavegacion('iniciar_sesion', 'buscar_caso'),
            });
        }

        return json(res, 200, {
            encontrada: true,
            payload_crudo: caso,
            pasos: pasosNavegacion('iniciar_sesion', 'buscar_caso', 'extraer_datos'),
        });
    }

    if (req.method === 'POST' && req.url === '/casos/importar-pendientes') {
        const pendientes = CASOS.filter((c) => c.pendiente);

        return json(res, 200, {
            filas: pendientes.map((c) => ({ sgf_id: c.sgf_id, payload_crudo: c })),
            pasos: pasosNavegacion(
                'iniciar_sesion',
                'listar_pendientes',
                ...pendientes.map((c) => `abrir_caso_${c.sgf_id}`),
            ),
        });
    }

    return json(res, 404, { error: 'No encontrado' });
});

server.listen(PORT, () => {
    console.log(`[sgf-playwright-stub] escuchando en http://localhost:${PORT} (API key: ${API_KEY})`);
    console.log(`[sgf-playwright-stub] casos de prueba: ${CASOS.map((c) => c.sgf_id).join(', ')}`);
});

import { Head, router, usePage } from '@inertiajs/react';
import { useMemo, useState } from 'react';
import { formatMonto } from '@/lib/format';
import egresosCgu from '@/routes/pago-proveedores/egresos-cgu';
import type { CasoSeleccionable } from '@/types/pago-proveedores';

type PageProps = {
    casos: CasoSeleccionable[];
    trabajoIntegracionId: number | null;
};

const IC = {
    check: '<polyline points="20 6 9 17 4 12"/>',
    caso: '<path d="M20 7h-9a2 2 0 0 0-2 2v9a2 2 0 0 0 2 2h9a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2z"/><path d="M13 7V5a2 2 0 0 0-2-2H4a2 2 0 0 0-2 2v9a2 2 0 0 0 2 2h2"/>',
    search: '<circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>',
};

function Icon({ path, className }: { path: string; className?: string }) {
    return (
        <svg
            className={className}
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            strokeWidth={2}
            strokeLinecap="round"
            strokeLinejoin="round"
            dangerouslySetInnerHTML={{ __html: path }}
        />
    );
}

export default function EgresosCguCrear() {
    const { casos, trabajoIntegracionId } = usePage<PageProps>().props;
    const desdeImportacion = trabajoIntegracionId !== null;

    const [numeroEgreso, setNumeroEgreso] = useState('');
    const [fecha, setFecha] = useState('');
    const [observaciones, setObservaciones] = useState('');
    const [seleccion, setSeleccion] = useState<Set<number>>(() =>
        desdeImportacion
            ? new Set(
                  casos
                      .filter((caso) => caso.listo)
                      .map((caso) => caso.id),
              )
            : new Set(),
    );
    const [busqueda, setBusqueda] = useState('');
    const [camposInvalidos, setCamposInvalidos] = useState<{
        numero?: boolean;
        fecha?: boolean;
    }>({});
    const [errors, setErrors] = useState<Record<string, string>>({});
    const [procesando, setProcesando] = useState(false);

    const casosFiltrados = useMemo(() => {
        const termino = busqueda.trim().toLowerCase();

        if (termino === '') {
            return casos;
        }

        return casos.filter(
            (caso) =>
                (caso.proveedor.nombre ?? '')
                    .toLowerCase()
                    .includes(termino) ||
                caso.sgf_id.toLowerCase().includes(termino),
        );
    }, [casos, busqueda]);

    const totalSeleccionado = useMemo(
        () =>
            casos
                .filter((caso) => seleccion.has(caso.id))
                .reduce((suma, caso) => suma + (Number(caso.monto) || 0), 0),
        [casos, seleccion],
    );

    const todosSeleccionados =
        casos.length > 0 && seleccion.size === casos.length;

    const formularioValido =
        numeroEgreso.trim() !== '' && fecha !== '' && seleccion.size > 0;

    function alternarCaso(caso: CasoSeleccionable) {
        setSeleccion((actual) => {
            const siguiente = new Set(actual);

            if (siguiente.has(caso.id)) {
                siguiente.delete(caso.id);
            } else {
                siguiente.add(caso.id);
            }

            return siguiente;
        });
    }

    function alternarTodos() {
        setSeleccion(
            todosSeleccionados ? new Set() : new Set(casos.map((c) => c.id)),
        );
    }

    function enviar() {
        const numeroVacio = numeroEgreso.trim() === '';
        const fechaVacia = fecha === '';

        if (numeroVacio || fechaVacia || seleccion.size === 0) {
            setCamposInvalidos({ numero: numeroVacio, fecha: fechaVacia });

            return;
        }

        setProcesando(true);
        setErrors({});

        router.post(
            egresosCgu.store().url,
            {
                numero_egreso: numeroEgreso,
                fecha,
                observaciones: observaciones || null,
                casos: casos
                    .filter((caso) => seleccion.has(caso.id))
                    .map((caso) => ({
                        caso_pago_proveedor_id: caso.id,
                        monto: caso.monto,
                    })),
            },
            {
                onError: (errores) =>
                    setErrors(errores as Record<string, string>),
                onFinish: () => setProcesando(false),
            },
        );
    }

    return (
        <>
            <Head title="Nuevo egreso CGU" />
            <style>{CSS}</style>

            <div className="eg-crear">
                <div className="page-head">
                    <h1>Nuevo Egreso CGU</h1>
                    <p>
                        Asigna un número de egreso a los procesos de pago
                        pendientes y selecciona los casos que cubrirá.
                    </p>
                    {desdeImportacion && (
                        <p className="ctx-banner">
                            Viniendo de una importación SGF: los casos ya
                            revisados y listos quedaron preseleccionados.
                            Puedes ajustar la selección antes de crear el
                            egreso.
                        </p>
                    )}
                </div>

                <div className="card">
                    <div className="card-head">
                        <h2>Datos del egreso</h2>
                        <span className="badge orange">
                            <span className="d" />
                            Borrador
                        </span>
                    </div>
                    <div className="card-body">
                        <div className="field-grid">
                            <div
                                className={`field${camposInvalidos.numero ? ' invalid' : ''}`}
                            >
                                <label htmlFor="numeroEgreso">
                                    N° de egreso
                                    <span className="req">*</span>
                                </label>
                                <input
                                    type="text"
                                    id="numeroEgreso"
                                    placeholder="Ej: 115"
                                    autoComplete="off"
                                    value={numeroEgreso}
                                    onChange={(e) => {
                                        setNumeroEgreso(e.target.value);
                                        setCamposInvalidos((actual) => ({
                                            ...actual,
                                            numero: false,
                                        }));
                                    }}
                                />
                                <span className="err-msg">
                                    {errors.numero_egreso ||
                                        'Ingresa el número de egreso.'}
                                </span>
                            </div>
                            <div
                                className={`field${camposInvalidos.fecha ? ' invalid' : ''}`}
                            >
                                <label htmlFor="fechaEgreso">
                                    Fecha
                                    <span className="req">*</span>
                                </label>
                                <input
                                    type="date"
                                    id="fechaEgreso"
                                    value={fecha}
                                    onChange={(e) => {
                                        setFecha(e.target.value);
                                        setCamposInvalidos((actual) => ({
                                            ...actual,
                                            fecha: false,
                                        }));
                                    }}
                                />
                                <span className="err-msg">
                                    {errors.fecha || 'Selecciona una fecha.'}
                                </span>
                            </div>
                            <div className="field full">
                                <label htmlFor="observaciones">
                                    Observaciones
                                </label>
                                <textarea
                                    id="observaciones"
                                    placeholder="Notas u observaciones sobre este egreso (opcional)…"
                                    value={observaciones}
                                    onChange={(e) =>
                                        setObservaciones(e.target.value)
                                    }
                                />
                                <span className="hint">
                                    Ej. referencia al lote de pagos, motivo
                                    del egreso, etc.
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <div className="card">
                    <div className="card-head">
                        <h2>
                            Casos a cubrir
                            <span
                                className="req"
                                style={{ color: 'var(--red)' }}
                            >
                                {' '}
                                *
                            </span>
                        </h2>
                        <span className="sub">
                            Selecciona los pagos pendientes que incluirá este
                            egreso
                        </span>
                    </div>

                    {errors.casos && (
                        <p className="err-msg" style={{ display: 'block', padding: '0 22px' }}>
                            {errors.casos}
                        </p>
                    )}

                    {casos.length === 0 ? (
                        <div className="empty-inline">
                            No hay casos pendientes de asignar a un egreso.
                        </div>
                    ) : (
                        <>
                            <div className="casos-toolbar">
                                <div className="search-wrap">
                                    <Icon path={IC.search} />
                                    <input
                                        type="text"
                                        placeholder="Buscar por caso o beneficiario…"
                                        autoComplete="off"
                                        value={busqueda}
                                        onChange={(e) =>
                                            setBusqueda(e.target.value)
                                        }
                                    />
                                </div>
                                <button
                                    type="button"
                                    className="select-all-btn"
                                    onClick={alternarTodos}
                                >
                                    {todosSeleccionados
                                        ? 'Deseleccionar todos'
                                        : 'Seleccionar todos'}
                                </button>
                                <div className="casos-summary">
                                    <span className="mono">
                                        {seleccion.size}
                                    </span>
                                    de{' '}
                                    <span className="mono">
                                        {casos.length}
                                    </span>
                                    casos seleccionados
                                </div>
                            </div>

                            {casosFiltrados.length === 0 ? (
                                <div className="empty-inline">
                                    No hay casos que coincidan con tu
                                    búsqueda.
                                </div>
                            ) : (
                                <div className="caso-list">
                                    {casosFiltrados.map((caso) => {
                                        const marcado = seleccion.has(
                                            caso.id,
                                        );

                                        return (
                                            <div
                                                key={caso.id}
                                                className={`caso-row${marcado ? ' checked' : ''}`}
                                                onClick={() =>
                                                    alternarCaso(caso)
                                                }
                                            >
                                                <div className="chk-box">
                                                    <Icon path={IC.check} />
                                                </div>
                                                <div className="caso-ic">
                                                    <Icon path={IC.caso} />
                                                </div>
                                                <div className="caso-info">
                                                    <div className="cn">
                                                        {caso.proveedor
                                                            .nombre ??
                                                            caso.sgf_id}
                                                    </div>
                                                    <div className="cid">
                                                        Caso N°{' '}
                                                        {caso.sgf_id}
                                                    </div>
                                                </div>
                                                <div className="caso-monto">
                                                    {formatMonto(caso.monto)}
                                                </div>
                                            </div>
                                        );
                                    })}
                                </div>
                            )}
                        </>
                    )}
                </div>

                <div className="form-footer">
                    <div className="ff-total">
                        <span className="k">Monto total seleccionado</span>
                        <span className="v">
                            {formatMonto(totalSeleccionado)}
                        </span>
                    </div>
                    <div className="ff-actions">
                        <button
                            type="button"
                            className="fbtn ghost"
                            disabled={procesando}
                            onClick={() =>
                                router.get(egresosCgu.index().url)
                            }
                        >
                            Cancelar
                        </button>
                        <button
                            type="button"
                            className="fbtn primary"
                            disabled={procesando || !formularioValido}
                            onClick={enviar}
                        >
                            <Icon path={IC.check} />
                            Crear Egreso
                        </button>
                    </div>
                </div>
            </div>
        </>
    );
}

EgresosCguCrear.layout = {
    breadcrumbs: [
        { title: 'Egresos CGU', href: egresosCgu.index() },
        { title: 'Nuevo', href: egresosCgu.create() },
    ],
};

const CSS = `
.eg-crear{
  --panel:#ffffff;--panel-2:#f8fafc;
  --border:rgba(15,23,42,0.07);--border-strong:rgba(15,23,42,0.14);
  --fg:#0b1220;--fg-muted:#5b6478;--fg-soft:#98a0b3;
  --accent:#2563eb;--accent-2:#1e40af;--accent-soft:rgba(37,99,235,0.10);
  --green:#16a34a;--green-soft:rgba(22,163,74,0.12);
  --red:#dc2626;--red-soft:rgba(220,38,38,0.12);
  --orange:#f59e0b;--orange-soft:rgba(245,158,11,0.14);
  --purple:#7c3aed;--purple-soft:rgba(124,58,237,0.12);
  --shadow-sm:0 1px 2px rgba(15,23,42,0.04);
  --shadow:0 8px 28px -20px rgba(15,23,42,0.18),0 2px 6px -2px rgba(15,23,42,0.06);
  --radius:16px;--ring:0 0 0 3px var(--accent-soft);
  display:flex;flex-direction:column;gap:20px;max-width:920px;padding:4px 4px 0;
  color:var(--fg);font-size:14px;letter-spacing:-0.01em;
}
html.dark .eg-crear{
  --panel:#11151f;--panel-2:#0e131c;
  --border:rgba(255,255,255,0.07);--border-strong:rgba(255,255,255,0.14);
  --fg:#e7ecf4;--fg-muted:#9aa3b6;--fg-soft:#6b7385;
  --accent:#3b82f6;--accent-2:#60a5fa;--accent-soft:rgba(59,130,246,0.18);
  --green:#4ade80;--green-soft:rgba(74,222,128,0.16);
  --red:#f87171;--red-soft:rgba(248,113,113,0.16);
  --orange:#fbbf24;--orange-soft:rgba(251,191,36,0.18);
  --purple:#a78bfa;--purple-soft:rgba(167,139,250,0.16);
  --shadow-sm:0 1px 2px rgba(0,0,0,0.4);
  --shadow:0 12px 40px -20px rgba(0,0,0,0.7),0 4px 12px -4px rgba(0,0,0,0.4);
}
.eg-crear *{box-sizing:border-box;}
.eg-crear button{font:inherit;}

.eg-crear .page-head h1{margin:0;font-size:22px;font-weight:700;letter-spacing:-0.02em;}
.eg-crear .page-head p{margin:6px 0 0;font-size:13px;color:var(--fg-muted);}
.eg-crear .page-head .ctx-banner{margin-top:10px;padding:10px 14px;border-radius:10px;background:var(--accent-soft);color:var(--accent-2);font-weight:600;font-size:12.5px;}

.eg-crear .badge{display:inline-flex;align-items:center;gap:5px;height:22px;padding:0 9px;border-radius:999px;font-size:11.5px;font-weight:700;white-space:nowrap;}
.eg-crear .badge .d{width:6px;height:6px;border-radius:50%;background:currentColor;}
.eg-crear .badge.orange{color:var(--orange);background:var(--orange-soft);}

.eg-crear .card{background:var(--panel);border:1px solid var(--border);border-radius:var(--radius);box-shadow:var(--shadow-sm);overflow:hidden;}
.eg-crear .card-head{display:flex;align-items:center;justify-content:space-between;gap:10px;padding:18px 22px;border-bottom:1px solid var(--border);flex-wrap:wrap;}
.eg-crear .card-head h2{margin:0;font-size:15px;font-weight:700;color:var(--fg);}
.eg-crear .card-head .sub{font-size:12px;color:var(--fg-soft);}
.eg-crear .card-body{padding:20px 22px;display:flex;flex-direction:column;gap:18px;}

.eg-crear .field-grid{display:grid;grid-template-columns:1fr 1fr;gap:18px;}
.eg-crear .field{display:flex;flex-direction:column;gap:6px;}
.eg-crear .field.full{grid-column:1/-1;}
.eg-crear .field label{font-size:12.5px;font-weight:700;color:var(--fg-muted);}
.eg-crear .field label .req{color:var(--red);margin-left:2px;}
.eg-crear .field .hint{font-size:11.5px;color:var(--fg-soft);}
.eg-crear input[type=text], .eg-crear input[type=date], .eg-crear textarea{
  border:1px solid var(--border-strong);background:var(--panel-2);color:var(--fg);border-radius:10px;padding:10px 13px;font:inherit;font-size:13.5px;outline:none;transition:border-color .15s,box-shadow .15s;width:100%;
}
.eg-crear input:focus, .eg-crear textarea:focus{border-color:var(--accent);box-shadow:var(--ring);background:var(--panel);}
.eg-crear input::placeholder, .eg-crear textarea::placeholder{color:var(--fg-soft);}
.eg-crear .field.invalid input{border-color:var(--red);}
.eg-crear textarea{resize:vertical;min-height:80px;font-family:inherit;line-height:1.5;}
.eg-crear .err-msg{font-size:11.5px;color:var(--red);display:none;}
.eg-crear .field.invalid .err-msg{display:block;}

.eg-crear .casos-toolbar{display:flex;align-items:center;gap:10px;padding:14px 22px;border-bottom:1px solid var(--border);flex-wrap:wrap;}
.eg-crear .search-wrap{position:relative;flex:1;min-width:200px;max-width:340px;}
.eg-crear .search-wrap svg{position:absolute;left:11px;top:50%;transform:translateY(-50%);width:14px;height:14px;color:var(--fg-soft);pointer-events:none;}
.eg-crear .search-wrap input{padding-left:32px;height:38px;}
.eg-crear .select-all-btn{height:38px;padding:0 14px;border-radius:9px;border:1px solid var(--border-strong);background:var(--panel);color:var(--fg-muted);font:inherit;font-size:12.5px;font-weight:700;cursor:pointer;transition:all .15s;}
.eg-crear .select-all-btn:hover{border-color:var(--accent);color:var(--accent);}
.eg-crear .casos-summary{margin-left:auto;font-size:12.5px;color:var(--fg-muted);display:flex;align-items:center;gap:8px;}
.eg-crear .casos-summary .mono{font-family:ui-monospace,monospace;font-weight:700;color:var(--fg);}

.eg-crear .caso-list{max-height:380px;overflow-y:auto;}
.eg-crear .caso-row{display:flex;align-items:center;gap:14px;padding:13px 22px;border-bottom:1px solid var(--border);cursor:pointer;transition:background .12s;}
.eg-crear .caso-row:last-child{border-bottom:none;}
.eg-crear .caso-row:hover{background:var(--panel-2);}
.eg-crear .caso-row.checked{background:var(--accent-soft);}
.eg-crear .chk-box{width:19px;height:19px;border-radius:6px;border:2px solid var(--border-strong);display:grid;place-items:center;flex-shrink:0;transition:all .15s;background:var(--panel);}
.eg-crear .chk-box svg{width:12px;height:12px;opacity:0;color:#fff;transition:opacity .1s;}
.eg-crear .caso-row.checked .chk-box{background:var(--accent);border-color:var(--accent);}
.eg-crear .caso-row.checked .chk-box svg{opacity:1;}
.eg-crear .caso-ic{width:32px;height:32px;border-radius:8px;background:var(--purple-soft);color:var(--purple);display:grid;place-items:center;flex-shrink:0;}
.eg-crear .caso-ic svg{width:15px;height:15px;}
.eg-crear .caso-info{flex:1;min-width:0;}
.eg-crear .caso-info .cn{font-weight:700;font-size:13px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;color:var(--fg);}
.eg-crear .caso-info .cid{font-size:11.5px;color:var(--fg-soft);font-family:ui-monospace,monospace;}
.eg-crear .caso-monto{font-family:ui-monospace,monospace;font-weight:700;font-size:13px;flex-shrink:0;color:var(--fg);}
.eg-crear .empty-inline{padding:26px 22px;color:var(--fg-soft);font-size:13px;text-align:center;}

.eg-crear .form-footer{position:sticky;bottom:0;background:var(--panel);border:1px solid var(--border);border-radius:var(--radius);padding:16px 22px;display:flex;align-items:center;gap:16px;flex-wrap:wrap;box-shadow:var(--shadow);}
.eg-crear .ff-total{display:flex;flex-direction:column;gap:1px;}
.eg-crear .ff-total .k{font-size:10.5px;color:var(--fg-soft);text-transform:uppercase;letter-spacing:0.05em;}
.eg-crear .ff-total .v{font-size:18px;font-weight:800;font-family:ui-monospace,monospace;color:var(--fg);}
.eg-crear .ff-actions{margin-left:auto;display:flex;gap:10px;}
.eg-crear .fbtn{height:44px;padding:0 22px;border-radius:11px;font:inherit;font-weight:700;font-size:13.5px;cursor:pointer;display:inline-flex;align-items:center;gap:8px;border:1px solid transparent;transition:all .15s;text-decoration:none;}
.eg-crear .fbtn svg{width:16px;height:16px;}
.eg-crear .fbtn.ghost{background:var(--panel);border-color:var(--border-strong);color:var(--fg-muted);}
.eg-crear .fbtn.ghost:hover{color:var(--fg);border-color:var(--fg-soft);}
.eg-crear .fbtn.primary{background:linear-gradient(180deg,var(--accent),var(--accent-2));color:#fff;box-shadow:0 12px 26px -12px var(--accent);}
.eg-crear .fbtn.primary:hover:not(:disabled){transform:translateY(-1px);}
.eg-crear .fbtn.primary:disabled, .eg-crear .fbtn.ghost:disabled{opacity:.45;cursor:not-allowed;transform:none;box-shadow:none;}

@media(max-width:720px){
  .eg-crear .field-grid{grid-template-columns:1fr;}
}
`;

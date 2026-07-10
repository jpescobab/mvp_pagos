import { Head, router, usePage } from '@inertiajs/react';
import { useMemo, useState } from 'react';
import { formatMonto } from '@/lib/format';
import { dashboard } from '@/routes';
import {
    index as revisionIndex,
    transicion as transicionEgreso,
} from '@/routes/pago-proveedores/revision';
import {
    transicion as transicionPago,
    verificarTotales,
} from '@/routes/pago-proveedores/revision/pagos';
import { validar as validarDocumento } from '@/routes/pago-proveedores/revision/pagos/documentos';

/* ============================ Tipos ============================ */

type Documento = {
    id: number;
    titulo: string;
    tipo: string | null;
    tipo_codigo: string | null;
    estado: string; // valido | rechazado | pendiente
    observacion: string | null;
};

type Totales = {
    factura: number;
    recepcion: number;
    monto: number;
    coinciden: boolean;
    verificados: boolean;
};

type Pago = {
    id: number;
    sgf_id: string;
    proveedor: string;
    rut: string;
    folio: string | null;
    monto: number;
    estado: string | null;
    estado_label: string | null;
    instancia: string | null;
    puede_operar: boolean;
    totales: Totales;
    listo_para_aprobar: boolean;
    documentos: Documento[];
};

type Egreso = {
    id: number;
    numero_egreso: string;
    periodo: string | null;
    monto_total: number;
    cantidad_pagos: number;
    proveedores: string[];
    estado: string;
    instancia_activa: string | null;
    instancia_label: string | null;
    puede_operar: boolean;
    listo_para_avanzar: boolean;
    pagos: Pago[];
};

type PageProps = {
    egresos: Egreso[];
    egresoInicial?: number;
    permisos: { revisar_finanzas: boolean; revisar_zonal: boolean };
};

/* ============================ Iconos ============================ */

const IC = {
    recibo: '<path d="M4 2v20l3-2 3 2 3-2 3 2 3-2V2l-3 2-3-2-3 2-3-2Z"/><path d="M8 8h8M8 12h8M8 16h5"/>',
    factura:
        '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="8" y1="13" x2="16" y2="13"/><line x1="8" y1="17" x2="13" y2="17"/>',
    oc: '<path d="M9 11V7a3 3 0 1 1 6 0v4"/><rect x="5" y="11" width="14" height="10" rx="2"/>',
    recepcion:
        '<path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/>',
    contrato:
        '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><path d="M9 15h6M9 11h3"/>',
    otro: '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/>',
    check: '<polyline points="20 6 9 17 4 12"/>',
    x: '<line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>',
    trash: '<polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/>',
    zoomOut: '<line x1="5" y1="12" x2="19" y2="12"/>',
    zoomIn: '<line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>',
    reset: '<path d="M3 3v5h5"/><path d="M3.05 13A9 9 0 1 0 6 5.3L3 8"/>',
    expand: '<path d="M8 3H5a2 2 0 0 0-2 2v3M16 3h3a2 2 0 0 1 2 2v3M21 16v3a2 2 0 0 1-2 2h-3M8 21H5a2 2 0 0 1-2-2v-3"/>',
    eye: '<path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12z"/><circle cx="12" cy="12" r="3"/>',
    devolver: '<polyline points="1 4 1 10 7 10"/><path d="M3.51 15a9 9 0 1 0 2.13-9.36L1 10"/>',
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

/* ============================ Helpers ============================ */

const TIPO_META: Record<
    string,
    { icon: string; color: string; tpl: string }
> = {
    FACTURA: { icon: IC.factura, color: 'var(--accent)', tpl: 'factura' },
    ORDEN_COMPRA: { icon: IC.oc, color: 'var(--purple)', tpl: 'oc' },
    ACTA_RECEP: { icon: IC.recepcion, color: 'var(--teal)', tpl: 'recepcion' },
    CONTRATO: { icon: IC.contrato, color: 'var(--orange)', tpl: 'contrato' },
};

function tipoMeta(codigo: string | null) {
    return (codigo && TIPO_META[codigo]) || { icon: IC.otro, color: 'var(--fg-soft)', tpl: 'otro' };
}

const ESTADO_DOC: Record<string, { label: string; cls: string }> = {
    valido: { label: 'Aprobado', cls: 'green' },
    rechazado: { label: 'Rechazado', cls: 'red' },
    pendiente: { label: 'Pendiente', cls: 'orange' },
};

const ESTADO_EGRESO: Record<string, { label: string; cls: string }> = {
    en_revision_finanzas: { label: 'Finanzas', cls: 'blue' },
    en_revision_zonal: { label: 'Zonal', cls: 'blue' },
    en_transito: { label: 'En tránsito', cls: 'orange' },
    aprobado: { label: 'Aprobado', cls: 'green' },
    rechazado: { label: 'Rechazado', cls: 'red' },
    sin_pagos: { label: 'Sin pagos', cls: 'gray' },
};

const fmt = (n: number) => formatMonto(n);

/* ==================== Vista previa de documento ==================== */

function DocPreview({ pago, doc }: { pago: Pago; doc: Documento }) {
    const kind = tipoMeta(doc.tipo_codigo).tpl;
    const neto = Math.round(pago.totales.factura * 0.84);
    const iva = pago.totales.factura - neto;
    const numero = (doc.titulo.replace(/[^0-9]/g, '').slice(0, 7) || '0041231');

    if (kind === 'factura') {
        return (
            <>
                <div className="dp-head">
                    <div>
                        <div className="dp-brand">{pago.proveedor}</div>
                        <div className="dp-tag">Factura electrónica</div>
                    </div>
                    <div>
                        <div className="dp-num">N° {numero}</div>
                        <div className="dp-date">Folio {pago.folio ?? '—'}</div>
                    </div>
                </div>
                <div className="dp-grid">
                    <div><div className="dp-k">RUT Emisor</div><div className="dp-v">{pago.rut}</div></div>
                    <div><div className="dp-k">Referencia</div><div className="dp-v">{pago.folio ?? '—'}</div></div>
                    <div><div className="dp-k">Señor(es)</div><div className="dp-v">Poder Judicial</div></div>
                    <div><div className="dp-k">Monto a pagar</div><div className="dp-v">{fmt(pago.monto)}</div></div>
                </div>
                <table>
                    <thead>
                        <tr><th>Cant.</th><th>Descripción</th><th className="num">P. Unitario</th><th className="num">Total</th></tr>
                    </thead>
                    <tbody>
                        <tr><td>1</td><td>Bienes/servicios — {pago.proveedor}</td><td className="num">{fmt(neto)}</td><td className="num">{fmt(neto)}</td></tr>
                    </tbody>
                </table>
                <div className="dp-totals">
                    <div className="dp-trow"><span>Neto</span><span>{fmt(neto)}</span></div>
                    <div className="dp-trow"><span>IVA (19%)</span><span>{fmt(iva)}</span></div>
                    <div className="dp-trow total"><span>Total</span><span>{fmt(pago.totales.factura)}</span></div>
                </div>
                <div className="dp-stamp">
                    <div className="dp-sign">Firma autorizada</div>
                    {doc.estado === 'valido' && (
                        <div className="dp-seal">TIMBRE<br />ELECTRÓNICO<br />SII</div>
                    )}
                </div>
            </>
        );
    }

    if (kind === 'oc') {
        return (
            <>
                <div className="dp-head">
                    <div><div className="dp-brand">Poder Judicial</div><div className="dp-tag">Orden de compra</div></div>
                    <div><div className="dp-num">{pago.folio ?? '—'}</div><div className="dp-date">Proveedor</div></div>
                </div>
                <div className="dp-grid">
                    <div><div className="dp-k">Proveedor</div><div className="dp-v">{pago.proveedor}</div></div>
                    <div><div className="dp-k">RUT</div><div className="dp-v">{pago.rut}</div></div>
                    <div><div className="dp-k">Unidad requirente</div><div className="dp-v">Administración y Finanzas</div></div>
                    <div><div className="dp-k">Monto</div><div className="dp-v">{fmt(pago.totales.recepcion)}</div></div>
                </div>
                <table>
                    <thead><tr><th>Cant.</th><th>Descripción</th><th className="num">Total</th></tr></thead>
                    <tbody><tr><td>1</td><td>Bienes/servicios</td><td className="num">{fmt(pago.totales.recepcion)}</td></tr></tbody>
                </table>
                <div className="dp-stamp"><div className="dp-sign">V°B° Jefe de Unidad</div></div>
            </>
        );
    }

    if (kind === 'recepcion') {
        return (
            <>
                <div className="dp-head">
                    <div><div className="dp-brand">Poder Judicial</div><div className="dp-tag">Recepción conforme</div></div>
                    <div><div className="dp-num">RC-{pago.id}</div><div className="dp-date">{pago.folio ?? '—'}</div></div>
                </div>
                <div className="dp-body-text">
                    <p>Se deja constancia que se recepcionan conforme los bienes/servicios asociados a {pago.folio ?? 'el pago'}, a favor de <strong>{pago.proveedor}</strong>, RUT {pago.rut}.</p>
                    <p>La recepción se realiza sin observaciones, verificándose cantidad, calidad y especificaciones técnicas.</p>
                    <p>Monto recepcionado conforme: <strong>{fmt(pago.totales.recepcion)}</strong>.</p>
                </div>
                <div className="dp-stamp"><div className="dp-sign">Funcionario receptor</div><div className="dp-sign">Jefe de Unidad</div></div>
            </>
        );
    }

    if (kind === 'contrato') {
        return (
            <>
                <div className="dp-head">
                    <div><div className="dp-brand">Poder Judicial</div><div className="dp-tag">Contrato / respaldo</div></div>
                    <div><div className="dp-num">CT-{pago.id}</div><div className="dp-date">Vigencia 12 meses</div></div>
                </div>
                <div className="dp-body-text">
                    <p><strong>Primero:</strong> {pago.proveedor}, RUT {pago.rut}, se obliga a la prestación de los bienes/servicios asociados al pago {pago.folio ?? ''}.</p>
                    <p><strong>Segundo:</strong> El monto asciende a {fmt(pago.monto)}, IVA incluido.</p>
                    <p><strong>Tercero:</strong> Ambas partes dan cumplimiento a las condiciones administrativas establecidas.</p>
                </div>
                <div className="dp-stamp"><div className="dp-sign">Representante proveedor</div><div className="dp-sign">Representante Poder Judicial</div></div>
            </>
        );
    }

    return (
        <>
            <div className="dp-head">
                <div><div className="dp-brand">{pago.proveedor}</div><div className="dp-tag">Documento de respaldo</div></div>
                <div><div className="dp-num">DOC-{pago.id}</div><div className="dp-date">{pago.folio ?? '—'}</div></div>
            </div>
            <div className="dp-body-text">
                <p>Documento de respaldo asociado al pago {pago.folio ?? ''} de {pago.proveedor}, correspondiente a “{doc.titulo}”.</p>
                <p>Se adjunta como antecedente complementario para la validación del monto de {fmt(pago.monto)}.</p>
            </div>
        </>
    );
}

/* ============================ Página ============================ */

export default function RevisionPagosIndex() {
    const { egresos, egresoInicial } = usePage<PageProps>().props;

    const [egrId, setEgrId] = useState<number | null>(
        egresoInicial ?? egresos[0]?.id ?? null,
    );
    const egreso = useMemo(
        () => egresos.find((e) => e.id === egrId) ?? egresos[0],
        [egresos, egrId],
    );

    const [pagoId, setPagoId] = useState<number | null>(
        egreso?.pagos[0]?.id ?? null,
    );
    const pago = useMemo(
        () => egreso?.pagos.find((p) => p.id === pagoId) ?? egreso?.pagos[0],
        [egreso, pagoId],
    );

    const [docId, setDocId] = useState<number | null>(
        pago?.documentos[0]?.id ?? null,
    );
    const doc = useMemo(
        () => pago?.documentos.find((d) => d.id === docId) ?? pago?.documentos[0],
        [pago, docId],
    );

    const [zoom, setZoom] = useState(1);
    const [rejectingDoc, setRejectingDoc] = useState<number | null>(null);
    const [motivoDoc, setMotivoDoc] = useState('');

    function post(url: string, data: Record<string, string | number | boolean>) {
        router.post(url, data, { preserveScroll: true, preserveState: true });
    }

    function seleccionarEgreso(e: Egreso) {
        setEgrId(e.id);
        setPagoId(e.pagos[0]?.id ?? null);
        setDocId(e.pagos[0]?.documentos[0]?.id ?? null);
        setZoom(1);
        setRejectingDoc(null);
    }

    function seleccionarPago(p: Pago) {
        setPagoId(p.id);
        setDocId(p.documentos[0]?.id ?? null);
        setZoom(1);
        setRejectingDoc(null);
    }

    const puedeOperar = pago?.puede_operar ?? false;
    const puedeOperarEgreso = egreso?.puede_operar ?? false;

    /* --------- acciones --------- */
    function validar(estado: string) {
        if (!egreso || !pago || !doc) {
return;
}

        if (estado === 'rechazado' && !motivoDoc.trim()) {
return;
}

        post(validarDocumento({ egresoCgu: egreso.id, caso: pago.id, documento: doc.id }).url, {
            estado,
            observacion: motivoDoc,
        });
        setRejectingDoc(null);
        setMotivoDoc('');
    }

    function verificar() {
        if (!egreso || !pago) {
return;
}

        post(verificarTotales({ egresoCgu: egreso.id, caso: pago.id }).url, { verificado: true });
    }

    function aprobarPago() {
        if (!egreso || !pago) {
return;
}

        post(transicionPago({ egresoCgu: egreso.id, caso: pago.id }).url, { accion: 'aprobar', comentario: '' });
    }

    function accionPagoConMotivo(accion: 'rechazar' | 'devolver') {
        if (!egreso || !pago) {
return;
}

        const comentario = window.prompt(
            accion === 'rechazar' ? 'Motivo del rechazo del pago:' : 'Motivo de la devolución:',
            '',
        );

        if (comentario === null || comentario.trim() === '') {
return;
}

        post(transicionPago({ egresoCgu: egreso.id, caso: pago.id }).url, { accion, comentario });
    }

    function accionEgresoConfirm(accion: 'aprobar' | 'devolver') {
        if (!egreso) {
return;
}

        let comentario = '';

        if (accion === 'devolver') {
            const c = window.prompt('Motivo de la devolución del egreso:', '');

            if (c === null || c.trim() === '') {
return;
}

            comentario = c;
        }

        post(transicionEgreso(egreso.id).url, { accion, comentario });
    }

    /* --------- derivados --------- */
    const docsOk = pago ? pago.documentos.filter((d) => d.estado === 'valido').length : 0;
    const docsTotal = pago?.documentos.length ?? 0;
    const pct = docsTotal ? Math.round((docsOk / docsTotal) * 100) : 0;
    const finalizado = pago?.estado === 'lista_para_registro_cgu';
    const readyAprobar = (pago?.listo_para_aprobar ?? false) && puedeOperar && !finalizado;

    return (
        <>
            <Head title="Revisión de Pagos" />
            <style>{CSS}</style>

            <div className="revpay">
                {egresos.length === 0 ? (
                    <div className="empty-viewer" style={{ minHeight: 320 }}>
                        <div className="ic"><Icon path={IC.eye} /></div>
                        <p>No hay egresos pendientes de tu revisión.</p>
                    </div>
                ) : (
                    <>
                        {/* Strip de egresos */}
                        <div className="proc-strip">
                            {egresos.map((e) => {
                                const est = ESTADO_EGRESO[e.estado] ?? ESTADO_EGRESO.sin_pagos;

                                return (
                                    <button
                                        key={e.id}
                                        type="button"
                                        className={`proc-chip${e.id === egreso?.id ? ' active' : ''}`}
                                        onClick={() => seleccionarEgreso(e)}
                                    >
                                        <div className="pc-top">
                                            <div className="pc-icon"><Icon path={IC.recibo} /></div>
                                            <div className="pc-text">
                                                <span className="pc-id">{e.numero_egreso}</span>
                                                <span className="pc-prov">
                                                    {e.proveedores[0] ?? '—'}
                                                    {e.proveedores.length > 1 ? ` +${e.proveedores.length - 1}` : ''}
                                                </span>
                                                <span className="pc-monto">{fmt(e.monto_total)}</span>
                                            </div>
                                        </div>
                                        <div className="pc-bottom">
                                            <span className={`badge ${est.cls}`}><span className="d" />
                                                {e.cantidad_pagos} pago{e.cantidad_pagos > 1 ? 's' : ''}
                                            </span>
                                        </div>
                                    </button>
                                );
                            })}
                        </div>

                        {/* Strip de pagos */}
                        {egreso && (
                            <div className="pago-strip">
                                {egreso.pagos.map((p) => {
                                    const est = ESTADO_EGRESO[p.estado ?? ''] ?? { label: p.estado_label ?? '—', cls: 'gray' };

                                    return (
                                        <button
                                            key={p.id}
                                            type="button"
                                            className={`pago-chip${p.id === pago?.id ? ' active' : ''}`}
                                            onClick={() => seleccionarPago(p)}
                                        >
                                            <span className="pg-num">{p.proveedor}</span>
                                            <span className="pg-monto">{fmt(p.monto)}</span>
                                            <span className={`badge ${est.cls}`}><span className="d" />{est.label}</span>
                                        </button>
                                    );
                                })}
                            </div>
                        )}

                        {/* Cabecera del pago */}
                        {pago && (
                            <div className="pago-head">
                                <div className="ph-field"><span className="k">Proveedor</span><span className="v">{pago.proveedor}</span></div>
                                <div className="ph-field"><span className="k">Folio</span><span className="v mono">{pago.folio ?? '—'}</span></div>
                                <div className="ph-progress">
                                    <div className="bar"><div className="bar-fill" style={{ width: `${pct}%`, background: pct === 100 ? 'var(--green)' : 'var(--orange)' }} /></div>
                                    <span>{docsOk}/{docsTotal} docs OK</span>
                                </div>
                                {puedeOperar && (
                                    <div className="right">
                                        {pago.instancia === 'zonal' && (
                                            <button className="pbtn reject" onClick={() => accionPagoConMotivo('devolver')} disabled={finalizado}>
                                                <Icon path={IC.devolver} />Devolver
                                            </button>
                                        )}
                                        <button className="pbtn reject" onClick={() => accionPagoConMotivo('rechazar')} disabled={finalizado}>
                                            <Icon path={IC.trash} />Rechazar Pago
                                        </button>
                                        <button className="pbtn approve" onClick={aprobarPago} disabled={!readyAprobar}>
                                            <Icon path={IC.check} />Aprobar Pago
                                        </button>
                                    </div>
                                )}
                            </div>
                        )}

                        {/* Cuerpo: documentos + visor + panel */}
                        {pago && (
                            <div className="review-body">
                                <div className="docs-col">
                                    <div className="docs-col-label">Documentos del pago</div>
                                    {pago.documentos.map((d) => {
                                        const meta = tipoMeta(d.tipo_codigo);
                                        const est = ESTADO_DOC[d.estado] ?? ESTADO_DOC.pendiente;
                                        const ok = d.estado === 'valido';
                                        const bad = d.estado === 'rechazado';

                                        return (
                                            <button
                                                key={d.id}
                                                type="button"
                                                className={`doc-card${d.id === doc?.id ? ' active' : ''}`}
                                                onClick={() => {
 setDocId(d.id); setZoom(1); setRejectingDoc(null); 
}}
                                            >
                                                <div className="doc-ic" style={{ background: `${meta.color}22`, color: meta.color }}>
                                                    <Icon path={meta.icon} />
                                                </div>
                                                <div className="doc-meta">
                                                    <div className="dn">{d.titulo}</div>
                                                    <div className="dt">{d.tipo ?? 'Documento'}</div>
                                                    <div className="dbadge"><span className={`badge ${est.cls}`}><span className="d" />{est.label}</span></div>
                                                </div>
                                                <div
                                                    className="doc-check"
                                                    style={{
                                                        background: ok ? 'var(--green)' : bad ? 'var(--red)' : 'var(--panel)',
                                                        border: ok || bad ? '1px solid transparent' : '1px solid var(--border-strong)',
                                                        color: ok || bad ? '#fff' : 'var(--fg-soft)',
                                                    }}
                                                >
                                                    {ok && <Icon path={IC.check} />}
                                                    {bad && <Icon path={IC.x} />}
                                                </div>
                                            </button>
                                        );
                                    })}
                                </div>

                                <div className="viewer-col">
                                    <div className="viewer-toolbar">
                                        <div>
                                            <div className="vt-title">{doc?.titulo ?? 'Documento'}</div>
                                            <div className="vt-sub">{doc?.tipo ?? ''} · {pago.proveedor}</div>
                                        </div>
                                        <div className="zoom-ctl">
                                            <button onClick={() => setZoom((z) => Math.max(0.5, +(z - 0.1).toFixed(2)))}><Icon path={IC.zoomOut} /></button>
                                            <span className="zlabel">{Math.round(zoom * 100)}%</span>
                                            <button onClick={() => setZoom((z) => Math.min(2, +(z + 0.1).toFixed(2)))}><Icon path={IC.zoomIn} /></button>
                                            <button onClick={() => setZoom(1)}><Icon path={IC.reset} /></button>
                                        </div>
                                    </div>
                                    <div className="viewer-body">
                                        <div className="viewer-stage">
                                            {doc ? (
                                                <div className="doc-page" style={{ transform: `scale(${zoom})` }}>
                                                    <DocPreview pago={pago} doc={doc} />
                                                </div>
                                            ) : (
                                                <div className="empty-viewer">
                                                    <div className="ic"><Icon path={IC.eye} /></div>
                                                    <p>Selecciona un documento para revisarlo</p>
                                                </div>
                                            )}
                                        </div>

                                        {/* Panel de revisión */}
                                        <div className="review-panel">
                                            <div className="rp-row"><span className="rp-title">Totales del pago</span></div>
                                            <div className="tot-card">
                                                <div className="tot-item"><span className="k">Monto factura</span><span className="v">{fmt(pago.totales.factura)}</span></div>
                                                <div className="tot-item"><span className="k">Monto recepción/OC</span><span className="v">{fmt(pago.totales.recepcion)}</span></div>
                                                <div className="tot-item"><span className="k">Monto a pagar</span><span className="v">{fmt(pago.totales.monto)}</span></div>
                                                <span className={`badge ${pago.totales.coinciden ? 'green' : 'red'}`} style={{ alignSelf: 'flex-start' }}>
                                                    <span className="d" />{pago.totales.coinciden ? 'Totales coinciden' : 'Diferencia detectada'}
                                                </span>
                                                {puedeOperar && (
                                                    <button
                                                        className={`verify-btn${pago.totales.verificados ? ' on' : ''}`}
                                                        onClick={verificar}
                                                        disabled={pago.totales.verificados || finalizado}
                                                    >
                                                        <Icon path={IC.check} />{pago.totales.verificados ? 'Totales verificados' : 'Verificar totales'}
                                                    </button>
                                                )}
                                            </div>

                                            <hr className="rp-divider" />

                                            {doc && (
                                                <>
                                                    <div className="rp-row">
                                                        <span className="rp-title">Revisión del documento</span>
                                                        <span className="rp-status">
                                                            <span className={`badge ${(ESTADO_DOC[doc.estado] ?? ESTADO_DOC.pendiente).cls}`}>
                                                                <span className="d" />{(ESTADO_DOC[doc.estado] ?? ESTADO_DOC.pendiente).label}
                                                            </span>
                                                        </span>
                                                    </div>
                                                    {puedeOperar ? (
                                                        <>
                                                            <div className="rp-row">
                                                                <div className="rp-actions">
                                                                    <button className={`rbtn approve${doc.estado === 'valido' ? ' on' : ''}`} onClick={() => validar('valido')} disabled={finalizado}>
                                                                        <Icon path={IC.check} />Aprobar documento
                                                                    </button>
                                                                    <button
                                                                        className={`rbtn reject${doc.estado === 'rechazado' ? ' on' : ''}`}
                                                                        onClick={() => (rejectingDoc === doc.id ? validar('rechazado') : (setRejectingDoc(doc.id), setMotivoDoc(doc.observacion ?? '')))}
                                                                        disabled={finalizado}
                                                                    >
                                                                        <Icon path={IC.x} />Rechazar documento
                                                                    </button>
                                                                </div>
                                                            </div>
                                                            <div className={`motivo-wrap${rejectingDoc === doc.id ? ' show' : ''}`}>
                                                                <span className="motivo-note">Motivo del rechazo</span>
                                                                <textarea
                                                                    value={motivoDoc}
                                                                    onChange={(e) => setMotivoDoc(e.target.value)}
                                                                    placeholder="Describe la observación encontrada…"
                                                                />
                                                            </div>
                                                        </>
                                                    ) : (
                                                        <p className="motivo-note">
                                                            Este egreso está en otra instancia; solo puedes consultarlo.
                                                        </p>
                                                    )}
                                                    {doc.observacion && doc.estado === 'rechazado' && (
                                                        <p className="motivo-note">“{doc.observacion}”</p>
                                                    )}
                                                </>
                                            )}

                                            {/* Acciones del egreso */}
                                            {puedeOperarEgreso && (
                                                <>
                                                    <hr className="rp-divider" />
                                                    <div className="rp-row"><span className="rp-title">Egreso completo</span></div>
                                                    <div className="rp-actions">
                                                        <button className="rbtn approve" onClick={() => accionEgresoConfirm('aprobar')} disabled={!egreso?.listo_para_avanzar}>
                                                            <Icon path={IC.check} />Aprobar egreso ({egreso?.instancia_label})
                                                        </button>
                                                        {egreso?.instancia_activa === 'zonal' && (
                                                            <button className="rbtn reject" onClick={() => accionEgresoConfirm('devolver')}>
                                                                <Icon path={IC.devolver} />Devolver egreso a Finanzas
                                                            </button>
                                                        )}
                                                    </div>
                                                </>
                                            )}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        )}
                    </>
                )}
            </div>
        </>
    );
}

RevisionPagosIndex.layout = {
    breadcrumbs: [
        { title: 'Panel', href: dashboard() },
        { title: 'Revisión de Pagos', href: revisionIndex() },
    ],
};

/* ============================ CSS (portado del prototipo, scoped a .revpay) ============================ */

const CSS = `
.revpay{
  --panel:#ffffff;--panel-2:#f8fafc;
  --border:rgba(15,23,42,0.07);--border-strong:rgba(15,23,42,0.14);
  --fg:#0b1220;--fg-muted:#5b6478;--fg-soft:#98a0b3;
  --accent:#2563eb;--accent-soft:rgba(37,99,235,0.10);
  --green:#16a34a;--green-soft:rgba(22,163,74,0.12);
  --red:#dc2626;--red-soft:rgba(220,38,38,0.12);
  --orange:#f59e0b;--orange-soft:rgba(245,158,11,0.14);
  --purple:#7c3aed;--purple-soft:rgba(124,58,237,0.12);
  --teal:#0891b2;--teal-soft:rgba(8,145,178,0.12);
  --shadow:0 8px 28px -20px rgba(15,23,42,0.18),0 2px 6px -2px rgba(15,23,42,0.06);
  display:flex;flex-direction:column;min-height:0;height:100%;
  color:var(--fg);font-size:14px;letter-spacing:-0.01em;
}
html.dark .revpay{
  --panel:#11151f;--panel-2:#0e131c;
  --border:rgba(255,255,255,0.07);--border-strong:rgba(255,255,255,0.14);
  --fg:#e7ecf4;--fg-muted:#9aa3b6;--fg-soft:#6b7385;
  --accent:#3b82f6;--accent-soft:rgba(59,130,246,0.18);
  --green:#4ade80;--green-soft:rgba(74,222,128,0.16);
  --red:#f87171;--red-soft:rgba(248,113,113,0.16);
  --orange:#fbbf24;--orange-soft:rgba(251,191,36,0.18);
  --purple:#a78bfa;--purple-soft:rgba(167,139,250,0.16);
  --teal:#22d3ee;--teal-soft:rgba(34,211,238,0.14);
}
.revpay *{box-sizing:border-box;}
.revpay button{font:inherit;}

.revpay .proc-strip{display:flex;gap:6px;padding:4px 4px 0;overflow-x:auto;flex-shrink:0;}
.revpay .proc-chip{display:flex;flex-direction:column;gap:2px;min-width:190px;padding:7px 10px;border-radius:9px;border:1px solid var(--border);background:var(--panel);cursor:pointer;transition:all .15s;flex-shrink:0;text-align:left;}
.revpay .proc-chip:hover{border-color:var(--border-strong);}
.revpay .proc-chip.active{border-color:var(--accent);box-shadow:0 0 0 3px var(--accent-soft);}
.revpay .proc-chip .pc-top{display:flex;align-items:stretch;gap:9px;}
.revpay .proc-chip .pc-icon{width:52px;border-radius:9px;background:var(--panel-2);display:flex;align-items:center;justify-content:center;flex-shrink:0;color:var(--fg-muted);}
.revpay .proc-chip .pc-icon svg{width:50%;height:50%;stroke-width:1.5;}
.revpay .proc-chip .pc-text{display:flex;flex-direction:column;gap:1px;min-width:0;}
.revpay .proc-chip .pc-id{font-size:11.5px;font-weight:700;color:var(--fg);line-height:1.25;}
.revpay .proc-chip.active .pc-id{color:var(--accent);}
.revpay .proc-chip.active .pc-icon{color:var(--accent);}
.revpay .proc-chip .pc-prov{font-size:12.5px;font-weight:600;color:var(--fg-muted);overflow:hidden;text-overflow:ellipsis;white-space:nowrap;}
.revpay .proc-chip .pc-monto{font-family:ui-monospace,monospace;font-size:15px;font-weight:800;color:var(--fg);line-height:1.3;margin-top:1px;}
.revpay .proc-chip .pc-bottom{display:flex;align-items:center;justify-content:flex-end;margin-top:2px;}

.revpay .badge{display:inline-flex;align-items:center;gap:4px;height:18px;padding:0 7px;border-radius:999px;font-size:10px;font-weight:700;white-space:nowrap;}
.revpay .badge .d{width:5px;height:5px;border-radius:50%;background:currentColor;flex-shrink:0;}
.revpay .badge.green{color:var(--green);background:var(--green-soft);}
.revpay .badge.red{color:var(--red);background:var(--red-soft);}
.revpay .badge.orange{color:var(--orange);background:var(--orange-soft);}
.revpay .badge.blue{color:var(--accent);background:var(--accent-soft);}
.revpay .badge.gray{color:var(--fg-soft);background:var(--panel-2);}

.revpay .pago-strip{display:flex;align-items:center;gap:6px;padding:8px 4px 0;flex-shrink:0;flex-wrap:wrap;}
.revpay .pago-chip{display:flex;align-items:center;gap:8px;padding:6px 11px;border-radius:9px;border:1px solid var(--border);background:var(--panel);cursor:pointer;transition:all .15s;font-size:12px;}
.revpay .pago-chip:hover{border-color:var(--border-strong);}
.revpay .pago-chip.active{border-color:var(--accent);box-shadow:0 0 0 3px var(--accent-soft);}
.revpay .pago-chip .pg-num{font-weight:700;color:var(--fg);}
.revpay .pago-chip .pg-monto{font-family:ui-monospace,monospace;color:var(--fg-muted);}

.revpay .pago-head{display:flex;align-items:center;gap:18px;flex-wrap:wrap;padding:10px 4px;flex-shrink:0;}
.revpay .ph-field{display:flex;flex-direction:column;gap:1px;}
.revpay .ph-field .k{font-size:10px;color:var(--fg-soft);text-transform:uppercase;letter-spacing:0.06em;}
.revpay .ph-field .v{font-size:13px;font-weight:700;color:var(--fg);}
.revpay .ph-field .v.mono{font-family:ui-monospace,monospace;font-size:12.5px;}
.revpay .ph-progress{display:flex;align-items:center;gap:8px;font-size:12px;color:var(--fg-muted);}
.revpay .ph-progress .bar{width:80px;height:6px;border-radius:99px;background:var(--panel-2);border:1px solid var(--border);overflow:hidden;}
.revpay .ph-progress .bar-fill{height:100%;border-radius:99px;transition:width .25s;}
.revpay .pago-head .right{margin-left:auto;display:flex;gap:8px;}
.revpay .pbtn{height:36px;padding:0 15px;border-radius:10px;font-weight:700;font-size:12.5px;cursor:pointer;display:inline-flex;align-items:center;gap:7px;border:1px solid transparent;transition:all .15s;}
.revpay .pbtn svg{width:15px;height:15px;}
.revpay .pbtn.approve{background:linear-gradient(180deg,var(--green),#0f8a3f);color:#fff;}
.revpay .pbtn.approve:hover:not(:disabled){transform:translateY(-1px);}
.revpay .pbtn.reject{background:var(--panel);border-color:var(--border-strong);color:var(--red);}
.revpay .pbtn.reject:hover:not(:disabled){background:var(--red-soft);border-color:transparent;}
.revpay .pbtn:disabled{opacity:.4;cursor:not-allowed;}

.revpay .review-body{flex:1;min-height:440px;display:grid;grid-template-columns:240px 1fr;grid-template-rows:minmax(0,1fr);border-top:1px solid var(--border);overflow:hidden;margin-top:6px;border-radius:12px;border:1px solid var(--border);}
.revpay .docs-col{border-right:1px solid var(--border);background:var(--panel);overflow-y:auto;padding:14px;display:flex;flex-direction:column;gap:8px;min-height:0;height:100%;}
.revpay .docs-col-label{font-size:11px;font-weight:700;color:var(--fg-soft);text-transform:uppercase;letter-spacing:0.06em;padding:2px 4px 6px;}
.revpay .doc-card{display:flex;gap:10px;align-items:flex-start;padding:11px 12px;border-radius:12px;border:1px solid var(--border);background:var(--panel-2);cursor:pointer;transition:all .15s;position:relative;text-align:left;}
.revpay .doc-card:hover{border-color:var(--border-strong);}
.revpay .doc-card.active{border-color:var(--accent);background:var(--accent-soft);box-shadow:0 0 0 1px var(--accent);}
.revpay .doc-ic{width:34px;height:34px;border-radius:9px;display:grid;place-items:center;flex-shrink:0;}
.revpay .doc-ic svg{width:16px;height:16px;}
.revpay .doc-meta{min-width:0;flex:1;padding-right:22px;}
.revpay .doc-meta .dn{font-size:12.5px;font-weight:700;line-height:1.3;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;color:var(--fg);}
.revpay .doc-meta .dt{font-size:11px;color:var(--fg-soft);margin-top:1px;}
.revpay .doc-meta .dbadge{margin-top:6px;}
.revpay .doc-check{position:absolute;top:10px;right:10px;width:18px;height:18px;border-radius:50%;display:grid;place-items:center;}
.revpay .doc-check svg{width:11px;height:11px;}

.revpay .viewer-col{display:flex;flex-direction:column;min-height:0;height:100%;background:var(--panel-2);}
.revpay .viewer-toolbar{display:flex;align-items:center;gap:10px;padding:10px 20px;border-bottom:1px solid var(--border);background:var(--panel);flex-shrink:0;flex-wrap:wrap;}
.revpay .viewer-toolbar .vt-title{font-weight:700;font-size:13.5px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;color:var(--fg);}
.revpay .viewer-toolbar .vt-sub{font-size:11.5px;color:var(--fg-soft);}
.revpay .zoom-ctl{display:flex;align-items:center;gap:2px;margin-left:auto;background:var(--panel-2);border:1px solid var(--border);border-radius:9px;padding:2px;}
.revpay .zoom-ctl button{width:28px;height:28px;border-radius:7px;border:none;background:transparent;color:var(--fg-muted);cursor:pointer;display:grid;place-items:center;}
.revpay .zoom-ctl button:hover{background:var(--panel);color:var(--fg);}
.revpay .zoom-ctl button svg{width:14px;height:14px;}
.revpay .zoom-ctl .zlabel{font-size:11.5px;font-weight:700;color:var(--fg-muted);width:38px;text-align:center;font-family:ui-monospace,monospace;}
.revpay .viewer-body{display:flex;flex:1;min-height:0;}
.revpay .viewer-stage{flex:1;min-height:200px;overflow:auto;display:flex;justify-content:center;padding:20px;}

.revpay .review-panel{border-left:1px solid var(--border);background:var(--panel);padding:14px 16px;flex-shrink:0;width:320px;display:flex;flex-direction:column;gap:14px;overflow-y:auto;}
.revpay .rp-divider{height:1px;background:var(--border);border:none;margin:0;}
.revpay .rp-row{display:flex;align-items:center;justify-content:space-between;gap:10px;flex-wrap:wrap;}
.revpay .rp-title{font-size:11.5px;font-weight:700;color:var(--fg-muted);text-transform:uppercase;letter-spacing:0.04em;}
.revpay .rp-status{display:flex;align-items:center;gap:8px;}
.revpay .rp-actions{display:flex;flex-direction:column;gap:8px;width:100%;}
.revpay .tot-card{display:flex;flex-direction:column;gap:8px;padding:12px;border-radius:12px;border:1px solid var(--border);background:var(--panel-2);width:100%;}
.revpay .tot-item{display:flex;align-items:center;justify-content:space-between;gap:8px;}
.revpay .tot-item .k{font-size:10.5px;color:var(--fg-soft);text-transform:uppercase;letter-spacing:0.04em;}
.revpay .tot-item .v{font-size:12.5px;font-weight:700;font-family:ui-monospace,monospace;color:var(--fg);}
.revpay .verify-btn{height:34px;padding:0 12px;border-radius:9px;font-size:12px;font-weight:700;cursor:pointer;display:inline-flex;align-items:center;justify-content:center;gap:6px;border:1px solid var(--border-strong);background:var(--panel);color:var(--fg-muted);transition:all .15s;width:100%;}
.revpay .verify-btn svg{width:13px;height:13px;}
.revpay .verify-btn.on{background:var(--green-soft);border-color:transparent;color:var(--green);}
.revpay .verify-btn:disabled{opacity:.55;cursor:not-allowed;}
.revpay .rbtn{height:36px;padding:0 14px;border-radius:10px;font-weight:700;font-size:12px;cursor:pointer;display:inline-flex;align-items:center;justify-content:center;gap:7px;border:1px solid var(--border-strong);background:var(--panel);color:var(--fg-muted);transition:all .15s;width:100%;}
.revpay .rbtn svg{width:14px;height:14px;}
.revpay .rbtn.approve{color:var(--green);}
.revpay .rbtn.approve:hover:not(:disabled),.revpay .rbtn.approve.on{background:var(--green-soft);border-color:transparent;color:var(--green);}
.revpay .rbtn.reject{color:var(--red);}
.revpay .rbtn.reject:hover:not(:disabled),.revpay .rbtn.reject.on{background:var(--red-soft);border-color:transparent;color:var(--red);}
.revpay .rbtn:disabled{opacity:.45;cursor:not-allowed;}
.revpay .motivo-wrap{display:none;}
.revpay .motivo-wrap.show{display:flex;flex-direction:column;gap:6px;}
.revpay .motivo-wrap textarea{resize:vertical;min-height:60px;border:1px solid var(--border-strong);border-radius:10px;padding:10px 12px;font:inherit;font-size:12.5px;background:var(--panel-2);color:var(--fg);outline:none;}
.revpay .motivo-wrap textarea:focus{border-color:var(--red);}
.revpay .motivo-note{font-size:11px;color:var(--fg-soft);}

.revpay .doc-page{width:600px;flex-shrink:0;background:#fff;color:#1a2030;border-radius:4px;box-shadow:0 12px 40px -12px rgba(15,23,42,0.35);transform-origin:top center;padding:44px 46px;font-size:12.5px;line-height:1.5;transition:transform .15s;height:fit-content;}
.revpay .doc-page .dp-head{display:flex;justify-content:space-between;align-items:flex-start;border-bottom:2px solid #1a2030;padding-bottom:14px;margin-bottom:18px;}
.revpay .doc-page .dp-brand{font-weight:800;font-size:15px;letter-spacing:-0.01em;}
.revpay .doc-page .dp-tag{font-size:10px;color:#8a93a6;text-transform:uppercase;letter-spacing:0.08em;margin-top:2px;}
.revpay .doc-page .dp-num{text-align:right;font-family:ui-monospace,monospace;font-weight:700;font-size:13px;}
.revpay .doc-page .dp-date{text-align:right;font-size:11px;color:#8a93a6;margin-top:2px;}
.revpay .doc-page .dp-grid{display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-bottom:18px;}
.revpay .doc-page .dp-k{font-size:9.5px;color:#8a93a6;text-transform:uppercase;letter-spacing:0.05em;}
.revpay .doc-page .dp-v{font-size:12px;font-weight:700;margin-top:1px;}
.revpay .doc-page table{width:100%;border-collapse:collapse;margin-bottom:14px;}
.revpay .doc-page th{text-align:left;font-size:9.5px;text-transform:uppercase;letter-spacing:0.04em;color:#8a93a6;padding:6px 8px;border-bottom:1px solid #dde2ec;}
.revpay .doc-page td{padding:8px;font-size:11.5px;border-bottom:1px solid #eef1f6;}
.revpay .doc-page td.num,.revpay .doc-page th.num{text-align:right;font-family:ui-monospace,monospace;}
.revpay .doc-page .dp-totals{margin-left:auto;width:220px;display:flex;flex-direction:column;gap:5px;margin-top:6px;}
.revpay .doc-page .dp-trow{display:flex;justify-content:space-between;font-size:11.5px;}
.revpay .doc-page .dp-trow.total{font-weight:800;font-size:13px;border-top:2px solid #1a2030;padding-top:7px;margin-top:3px;}
.revpay .doc-page .dp-stamp{margin-top:26px;display:flex;justify-content:space-between;align-items:flex-end;}
.revpay .doc-page .dp-sign{border-top:1px solid #1a2030;width:160px;padding-top:5px;font-size:10px;color:#8a93a6;text-align:center;}
.revpay .doc-page .dp-seal{width:74px;height:74px;border-radius:50%;border:3px solid #16a34a;color:#16a34a;display:grid;place-items:center;font-size:8.5px;font-weight:800;text-align:center;line-height:1.2;transform:rotate(-14deg);opacity:.85;}
.revpay .doc-page .dp-body-text{font-size:11.5px;line-height:1.75;color:#333;}
.revpay .doc-page .dp-body-text p{margin:0 0 10px;}

.revpay .empty-viewer{flex:1;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:10px;color:var(--fg-soft);}
.revpay .empty-viewer .ic{width:48px;height:48px;border-radius:12px;background:var(--panel);border:1px solid var(--border);display:grid;place-items:center;}
.revpay .empty-viewer .ic svg{width:22px;height:22px;}

@media(max-width:900px){
  .revpay .review-body{grid-template-columns:1fr;}
  .revpay .viewer-body{flex-direction:column;}
  .revpay .review-panel{width:100%;border-left:none;border-top:1px solid var(--border);}
}
`;

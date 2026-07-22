import { Head, Link, router, usePage } from '@inertiajs/react';
import { useState } from 'react';
import type { ReactNode } from 'react';
import { ChecklistDocumentalCard } from '@/components/pago-proveedores/checklist-documental-card';
import { EstadoBadge } from '@/components/pago-proveedores/estado-badge';
import { PreparacionEgresoCard } from '@/components/pago-proveedores/preparacion-egreso-card';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Monto } from '@/components/ui/monto';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { formatFecha, formatFechaHora } from '@/lib/format';
import casos from '@/routes/pago-proveedores/casos';
import egresosCgu from '@/routes/pago-proveedores/egresos-cgu';
import revision from '@/routes/pago-proveedores/revision';
import documentos from '@/routes/procesos/documentos';
import type {
    CasoPagoProveedor,
    RegistroContableCgu,
    TipoDocumentoSeleccionable,
    TipoProcesoPagoSeleccionable,
} from '@/types/pago-proveedores';

type PageProps = {
    caso: CasoPagoProveedor;
    tiposDocumento: TipoDocumentoSeleccionable[];
    tiposProcesoPago: TipoProcesoPagoSeleccionable[];
};

const ESTADOS_EN_REVISION = new Set([
    'en_revision_finanzas',
    'en_revision_zonal',
]);

const NOMBRE_INSTANCIA: Record<string, string> = {
    en_revision_finanzas: 'Jefe de Finanzas',
    en_revision_zonal: 'Administrador Zonal',
};

export default function CasoShow() {
    const page = usePage<PageProps>();
    const { caso, tiposProcesoPago, auth } = page.props;
    const { verificacionSgf } = page.flash;

    const enRevision = ESTADOS_EN_REVISION.has(
        caso.proceso.estado_actual.codigo,
    );
    const puedeRevisar =
        auth.permissions.includes('pago_proveedores.revisar_finanzas') ||
        auth.permissions.includes('pago_proveedores.revisar_zonal');
    const puedeGestionarDocumentos = auth.permissions.includes(
        'documentos.gestionar',
    );
    const puedeGestionarCaso = auth.permissions.includes(
        'pago_proveedores.gestionar_caso',
    );
    const puedeRegistrarCgu = auth.permissions.includes(
        'pago_proveedores.registrar_cgu',
    );
    const puedeVerificarSgf = auth.permissions.includes(
        'pago_proveedores.verificar_caso_sgf',
    );
    const egresoEnRevision = caso.egresos_cgu?.[0];
    const documentosHuerfanos = (caso.proceso.documentos ?? []).filter(
        (doc) => !doc.coincide_checklist,
    );
    const documentosRevinculables = caso.proceso.documentos_revinculables ?? [];

    const historial = [
        ...(caso.proceso.historial_transiciones ?? []),
    ].reverse();

    const [clasificandoTipoProceso, setClasificandoTipoProceso] =
        useState(false);
    const [errorTipoProceso, setErrorTipoProceso] = useState<string | null>(
        null,
    );

    function clasificarTipoProceso(tipoProcesoPagoId: string) {
        setClasificandoTipoProceso(true);
        setErrorTipoProceso(null);

        router.post(
            casos.tipoProcesoPago.store(caso.id).url,
            { tipo_proceso_pago_id: tipoProcesoPagoId },
            {
                preserveScroll: true,
                onError: (errors) =>
                    setErrorTipoProceso(
                        (errors as Record<string, string>)
                            .tipo_proceso_pago_id ?? null,
                    ),
                onFinish: () => setClasificandoTipoProceso(false),
            },
        );
    }

    const [subiendoDocumento, setSubiendoDocumento] = useState(false);
    const [errorDocumento, setErrorDocumento] = useState<string | null>(null);

    function subirDocumento(tipoDocumentoId: string, archivo: File) {
        setSubiendoDocumento(true);
        setErrorDocumento(null);

        const formData = new FormData();
        formData.append('archivo', archivo);
        formData.append('tipo_documento_id', tipoDocumentoId);

        router.post(
            documentos.store({ proceso: caso.proceso.id }).url,
            formData,
            {
                preserveScroll: true,
                onError: (errors) =>
                    setErrorDocumento(
                        (errors as Record<string, string>).archivo ??
                            (errors as Record<string, string>)
                                .tipo_documento_id ??
                            null,
                    ),
                onFinish: () => setSubiendoDocumento(false),
            },
        );
    }

    const [huerfanoSeleccionado, setHuerfanoSeleccionado] = useState<
        Record<number, string | undefined>
    >({});
    const [vinculandoHuerfano, setVinculandoHuerfano] = useState(false);

    const [documentoPreviewId, setDocumentoPreviewId] = useState<number | null>(
        null,
    );

    // El visor sigue al documento vigente (valor derivado, no estado sincronizado):
    // si el documento seleccionado deja de estar vinculado activamente (p. ej.
    // tras desvincularlo), el visor deja de mostrarlo en vez de quedar con un
    // PDF ya desvinculado. Al re-vincular y elegir "Ver", se muestra el vigente.
    const documentoPreviewIdVigente =
        documentoPreviewId !== null &&
        (caso.proceso.documentos ?? []).some(
            (doc) => doc.documento_id === documentoPreviewId,
        )
            ? documentoPreviewId
            : null;
    const documentoPreviewItem = caso.proceso.checklist?.items.find(
        (item) => item.documento_id === documentoPreviewIdVigente,
    );

    function vincularHuerfano(
        tipoDocumentoId: number,
        documentoId: string | undefined,
    ) {
        if (!documentoId) {
            return;
        }

        setVinculandoHuerfano(true);
        setErrorDocumento(null);

        router.patch(
            documentos.tipoDocumento.store({
                proceso: caso.proceso.id,
                documento: Number(documentoId),
            }).url,
            { tipo_documento_id: tipoDocumentoId },
            {
                preserveScroll: true,
                onSuccess: () =>
                    setHuerfanoSeleccionado((actual) => ({
                        ...actual,
                        [tipoDocumentoId]: undefined,
                    })),
                onError: (errors) =>
                    setErrorDocumento(
                        (errors as Record<string, string>).tipo_documento_id ??
                            null,
                    ),
                onFinish: () => setVinculandoHuerfano(false),
            },
        );
    }

    function reactivarDocumento(
        tipoDocumentoId: number,
        documentoId: string | undefined,
    ) {
        if (!documentoId) {
            return;
        }

        setVinculandoHuerfano(true);
        setErrorDocumento(null);

        router.patch(
            documentos.reactivar({
                proceso: caso.proceso.id,
                documento: Number(documentoId),
            }).url,
            { tipo_documento_id: tipoDocumentoId },
            {
                preserveScroll: true,
                onSuccess: () =>
                    setHuerfanoSeleccionado((actual) => ({
                        ...actual,
                        [tipoDocumentoId]: undefined,
                    })),
                onError: (errors) =>
                    setErrorDocumento(
                        (errors as Record<string, string>).tipo_documento_id ??
                            null,
                    ),
                onFinish: () => setVinculandoHuerfano(false),
            },
        );
    }

    function desvincularDocumento(vinculoId: number) {
        router.delete(
            documentos.destroy({ proceso: caso.proceso.id, vinculo: vinculoId })
                .url,
            { preserveScroll: true },
        );
    }

    const ultimoRegistroCgu = (
        caso.registros_contables_cgu ?? []
    ).reduce<RegistroContableCgu | null>(
        (masReciente, registro) =>
            masReciente === null || registro.id > masReciente.id
                ? registro
                : masReciente,
        null,
    );

    const hayTraspaso =
        caso.sgf_numero_traspaso !== null ||
        (caso.registros_contables_cgu ?? []).length > 0;

    const requiereTraspasoCgu =
        caso.proceso.tipo_proceso_pago?.requiere_traspaso_cgu ?? true;

    const [numeroRegistroCgu, setNumeroRegistroCgu] = useState(
        ultimoRegistroCgu?.numero_registro ?? caso.sgf_numero_traspaso ?? '',
    );
    const [fechaRegistroCgu, setFechaRegistroCgu] = useState(
        ultimoRegistroCgu?.fecha_registro?.slice(0, 10) ?? '',
    );
    const [montoRegistroCgu, setMontoRegistroCgu] = useState(
        ultimoRegistroCgu?.monto ?? '',
    );
    const [observacionesRegistroCgu, setObservacionesRegistroCgu] = useState(
        ultimoRegistroCgu?.observaciones ?? '',
    );
    const registroCguSinCambios =
        ultimoRegistroCgu !== null &&
        numeroRegistroCgu === ultimoRegistroCgu.numero_registro &&
        fechaRegistroCgu ===
            (ultimoRegistroCgu.fecha_registro?.slice(0, 10) ?? '') &&
        montoRegistroCgu === ultimoRegistroCgu.monto &&
        observacionesRegistroCgu === (ultimoRegistroCgu.observaciones ?? '');
    const [registrandoCgu, setRegistrandoCgu] = useState(false);
    const [errorRegistroCgu, setErrorRegistroCgu] = useState<string | null>(
        null,
    );

    function registrarContableCgu() {
        setRegistrandoCgu(true);
        setErrorRegistroCgu(null);

        router.post(
            casos.registrosContablesCgu.store(caso.id).url,
            {
                numero_registro: numeroRegistroCgu,
                fecha_registro: fechaRegistroCgu,
                monto: montoRegistroCgu,
                observaciones: observacionesRegistroCgu,
            },
            {
                preserveScroll: true,
                onError: (errors) =>
                    setErrorRegistroCgu(
                        Object.values(errors as Record<string, string>)[0] ??
                            null,
                    ),
                onFinish: () => setRegistrandoCgu(false),
            },
        );
    }

    return (
        <>
            <Head title={`Caso ${caso.sgf_id}`} />

            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <div className="flex flex-wrap items-start justify-between gap-4">
                    <div className="space-y-1">
                        <div className="flex flex-wrap items-baseline gap-x-3">
                            <h1 className="text-xl font-semibold tracking-tight">
                                {caso.proveedor.nombre ?? caso.sgf_id}
                            </h1>
                            <Monto
                                valor={caso.monto}
                                className="text-xl font-semibold tracking-tight"
                            />
                        </div>
                        <p className="text-sm text-muted-foreground">
                            <span className="font-mono">
                                sgf_id: {caso.sgf_id}
                                {caso.proveedor.rutproveedor &&
                                    ` · RUT ${caso.proveedor.rutproveedor}`}
                            </span>
                            {' · '}
                            Período:{' '}
                            <span className="text-foreground">
                                {caso.periodo ?? '—'}
                            </span>
                            {' · '}
                            N° DTE:{' '}
                            <span className="font-mono text-foreground">
                                {caso.numero ?? '—'}
                            </span>
                            {' · '}
                            Fecha SII:{' '}
                            <span className="text-foreground">
                                {caso.fecha_sii
                                    ? formatFecha(caso.fecha_sii)
                                    : '—'}
                            </span>
                            {' · '}
                            Folio de egreso:{' '}
                            <span className="text-foreground">
                                {caso.folio_egreso ?? '—'}
                            </span>
                        </p>
                    </div>
                    <div className="flex flex-col items-end gap-2">
                        <div className="flex items-center gap-2">
                            <EstadoBadge estado={caso.proceso.estado_actual} />
                            {puedeVerificarSgf && (
                                <Button
                                    variant="outline"
                                    size="sm"
                                    onClick={() =>
                                        router.post(
                                            casos.verificarSgf(caso.id).url,
                                            {},
                                            { preserveScroll: true },
                                        )
                                    }
                                >
                                    Verificar en SGF
                                </Button>
                            )}
                        </div>
                        {verificacionSgf && (
                            <p
                                className={
                                    verificacionSgf.encontrada
                                        ? 'text-sm text-success'
                                        : 'text-sm text-muted-foreground'
                                }
                            >
                                {verificacionSgf.encontrada
                                    ? 'Se encontró este caso en SGF y se registró un nuevo snapshot.'
                                    : 'Este caso no fue encontrado en SGF.'}
                            </p>
                        )}
                    </div>
                </div>

                <PreparacionEgresoCard caso={caso} />

                {enRevision && (
                    <Alert className="border-transparent bg-warning-soft text-warning">
                        <AlertTitle>
                            Pago en revisión en dos instancias
                        </AlertTitle>
                        <AlertDescription className="text-warning/80">
                            <span>
                                Instancia actual:{' '}
                                {
                                    NOMBRE_INSTANCIA[
                                        caso.proceso.estado_actual.codigo
                                    ]
                                }
                                . Aprobar, rechazar, observar/devolver y validar
                                documentos se hacen desde Revisión de Pagos.
                            </span>
                            {puedeRevisar && egresoEnRevision && (
                                <Link
                                    href={
                                        revision.show(egresoEnRevision.id).url
                                    }
                                    className="font-medium underline"
                                >
                                    Ir a Revisión de Pagos →
                                </Link>
                            )}
                        </AlertDescription>
                    </Alert>
                )}

                <div className="space-y-6">
                    <div className="grid grid-cols-1 gap-6 lg:grid-cols-[1fr_3fr] lg:items-start">
                        <SeccionGrupo titulo="Clasificación y expediente">
                            <section className="space-y-3 rounded-xl border p-4">
                                <h2 className="text-base font-medium">
                                    Tipo de proceso
                                </h2>
                                <p className="text-sm text-muted-foreground">
                                    Determina qué documentos del checklist son
                                    obligatorios para este caso.
                                </p>

                                {errorTipoProceso && (
                                    <p className="text-sm text-destructive">
                                        {errorTipoProceso}
                                    </p>
                                )}

                                {puedeGestionarCaso ? (
                                    <Select
                                        value={
                                            caso.proceso
                                                .tipo_proceso_pago_id !== null
                                                ? String(
                                                      caso.proceso
                                                          .tipo_proceso_pago_id,
                                                  )
                                                : undefined
                                        }
                                        onValueChange={clasificarTipoProceso}
                                        disabled={
                                            clasificandoTipoProceso ||
                                            enRevision
                                        }
                                    >
                                        <SelectTrigger className="w-64">
                                            <SelectValue placeholder="Sin clasificar" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {tiposProcesoPago.map((tipo) => (
                                                <SelectItem
                                                    key={tipo.id}
                                                    value={String(tipo.id)}
                                                >
                                                    {tipo.nombre}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                ) : (
                                    <p className="text-sm text-muted-foreground">
                                        {caso.proceso.tipo_proceso_pago
                                            ?.nombre ?? 'Sin clasificar'}
                                    </p>
                                )}
                            </section>
                        </SeccionGrupo>

                        <SeccionGrupo titulo="Financiero">
                            <section className="space-y-3 rounded-xl border p-4">
                                <h2 className="text-base font-medium">
                                    Registro contable CGU (Traspaso)
                                </h2>

                                {errorRegistroCgu && (
                                    <p className="text-sm text-destructive">
                                        {errorRegistroCgu}
                                    </p>
                                )}

                                {!requiereTraspasoCgu && (
                                    <p className="text-sm text-muted-foreground">
                                        Este tipo de proceso no requiere
                                        Traspaso (CGU).
                                    </p>
                                )}

                                {caso.sgf_numero_traspaso === null &&
                                (caso.registros_contables_cgu ?? []).length ===
                                    0 ? (
                                    requiereTraspasoCgu && (
                                        <p className="text-sm text-muted-foreground">
                                            Sin Traspaso registrado todavía.
                                        </p>
                                    )
                                ) : (
                                    <ul className="divide-y text-sm">
                                        {(
                                            caso.registros_contables_cgu ?? []
                                        ).map((registro) => (
                                            <li
                                                key={registro.id}
                                                className="py-2"
                                            >
                                                <div className="flex items-center justify-between">
                                                    <span className="font-mono">
                                                        {
                                                            registro.numero_registro
                                                        }
                                                    </span>
                                                    <span className="text-muted-foreground">
                                                        {formatFecha(
                                                            registro.fecha_registro,
                                                        )}{' '}
                                                        ·{' '}
                                                        <Monto
                                                            valor={
                                                                registro.monto
                                                            }
                                                        />
                                                    </span>
                                                </div>
                                                <p className="text-muted-foreground">
                                                    Corrección ·{' '}
                                                    {registro.registrado_por ??
                                                        'Sistema'}
                                                    {registro.observaciones &&
                                                        ` — ${registro.observaciones}`}
                                                </p>
                                            </li>
                                        ))}
                                        {caso.sgf_numero_traspaso !== null && (
                                            <li className="py-2">
                                                <div className="flex items-center justify-between">
                                                    <span className="font-mono">
                                                        {
                                                            caso.sgf_numero_traspaso
                                                        }
                                                    </span>
                                                    <span className="rounded bg-muted px-1.5 py-0.5 text-xs text-muted-foreground">
                                                        desde SGF
                                                    </span>
                                                </div>
                                                <p className="text-muted-foreground">
                                                    Importado automáticamente
                                                    desde SGF
                                                </p>
                                            </li>
                                        )}
                                    </ul>
                                )}

                                {puedeRegistrarCgu && requiereTraspasoCgu && (
                                    <div className="flex flex-wrap items-end gap-2">
                                        {caso.sgf_numero_traspaso !== null && (
                                            <p className="basis-full text-xs text-muted-foreground">
                                                El traspaso se importa desde
                                                SGF. Usa este formulario solo
                                                para registrar una corrección
                                                manual; el valor de SGF se
                                                conserva como referencia.
                                            </p>
                                        )}
                                        <div className="space-y-1">
                                            <Label htmlFor="numero-registro-cgu">
                                                N.º de Traspaso
                                            </Label>
                                            <Input
                                                id="numero-registro-cgu"
                                                value={numeroRegistroCgu}
                                                onChange={(e) =>
                                                    setNumeroRegistroCgu(
                                                        e.target.value,
                                                    )
                                                }
                                            />
                                        </div>
                                        <div className="space-y-1">
                                            <Label htmlFor="fecha-registro-cgu">
                                                Fecha
                                            </Label>
                                            <Input
                                                id="fecha-registro-cgu"
                                                type="date"
                                                value={fechaRegistroCgu}
                                                onChange={(e) =>
                                                    setFechaRegistroCgu(
                                                        e.target.value,
                                                    )
                                                }
                                            />
                                        </div>
                                        <div className="space-y-1">
                                            <Label htmlFor="monto-registro-cgu">
                                                Monto
                                            </Label>
                                            <Input
                                                id="monto-registro-cgu"
                                                type="number"
                                                value={montoRegistroCgu}
                                                onChange={(e) =>
                                                    setMontoRegistroCgu(
                                                        e.target.value,
                                                    )
                                                }
                                            />
                                        </div>
                                        <div className="space-y-1">
                                            <Label htmlFor="observaciones-registro-cgu">
                                                Observaciones
                                            </Label>
                                            <Input
                                                id="observaciones-registro-cgu"
                                                value={observacionesRegistroCgu}
                                                onChange={(e) =>
                                                    setObservacionesRegistroCgu(
                                                        e.target.value,
                                                    )
                                                }
                                            />
                                        </div>
                                        <div className="space-y-1">
                                            <Button
                                                disabled={
                                                    registrandoCgu ||
                                                    numeroRegistroCgu === '' ||
                                                    fechaRegistroCgu === '' ||
                                                    montoRegistroCgu === '' ||
                                                    registroCguSinCambios
                                                }
                                                onClick={registrarContableCgu}
                                            >
                                                {hayTraspaso
                                                    ? 'Corregir traspaso'
                                                    : 'Registrar Traspaso'}
                                            </Button>
                                            {registroCguSinCambios && (
                                                <p className="text-xs text-muted-foreground">
                                                    Sin cambios respecto al
                                                    último registro.
                                                </p>
                                            )}
                                        </div>
                                    </div>
                                )}
                            </section>
                        </SeccionGrupo>
                    </div>

                    <div className="grid grid-cols-1 gap-6 lg:grid-cols-2 lg:items-start">
                        <ChecklistDocumentalCard
                            caso={caso}
                            errorDocumento={errorDocumento}
                            documentosHuerfanos={documentosHuerfanos}
                            documentosRevinculables={documentosRevinculables}
                            puedeGestionarDocumentos={puedeGestionarDocumentos}
                            subiendoDocumento={subiendoDocumento}
                            subirDocumento={subirDocumento}
                            huerfanoSeleccionado={huerfanoSeleccionado}
                            setHuerfanoSeleccionado={setHuerfanoSeleccionado}
                            vinculandoHuerfano={vinculandoHuerfano}
                            vincularHuerfano={vincularHuerfano}
                            reactivarDocumento={reactivarDocumento}
                            documentoPreviewId={documentoPreviewIdVigente}
                            onVerDocumento={setDocumentoPreviewId}
                            desvincularDocumento={desvincularDocumento}
                        />

                        <section className="space-y-3 rounded-xl border p-4">
                            <div className="flex items-center justify-between">
                                <h2 className="text-base font-medium">
                                    Vista previa
                                </h2>
                                {documentoPreviewIdVigente !== null && (
                                    <a
                                        href={
                                            documentos.ver({
                                                proceso: caso.proceso.id,
                                                documento:
                                                    documentoPreviewIdVigente,
                                            }).url
                                        }
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        className="text-sm underline"
                                    >
                                        Abrir en pestaña nueva
                                    </a>
                                )}
                            </div>

                            {documentoPreviewIdVigente !== null ? (
                                <iframe
                                    key={documentoPreviewIdVigente}
                                    src={
                                        documentos.ver({
                                            proceso: caso.proceso.id,
                                            documento: documentoPreviewIdVigente,
                                        }).url
                                    }
                                    title={
                                        documentoPreviewItem?.tipo_documento ??
                                        'Documento'
                                    }
                                    className="h-[600px] w-full rounded-md border"
                                />
                            ) : (
                                <p className="text-sm text-muted-foreground">
                                    Selecciona “Ver documento” en el checklist
                                    para previsualizarlo aquí.
                                </p>
                            )}
                        </section>
                    </div>

                    <SeccionGrupo titulo="Actividad">
                        <section className="space-y-3 rounded-xl border p-4">
                            <h2 className="text-base font-medium">
                                Historial de transiciones
                            </h2>

                            {historial.length === 0 ? (
                                <p className="text-sm text-muted-foreground">
                                    Sin transiciones registradas todavía.
                                </p>
                            ) : (
                                <ul className="divide-y text-sm">
                                    {historial.map((item, i) => (
                                        <li key={i} className="space-y-1 py-3">
                                            <div className="flex items-center justify-between">
                                                <span className="font-medium">
                                                    {item.transicion.nombre}
                                                </span>
                                                <span className="text-muted-foreground">
                                                    {formatFechaHora(
                                                        item.created_at,
                                                    )}
                                                </span>
                                            </div>
                                            <p className="text-muted-foreground">
                                                {item.estado_origen.codigo} →{' '}
                                                {item.estado_destino.codigo} ·{' '}
                                                {item.user.name ?? 'Sistema'}
                                            </p>
                                            {item.comentario && (
                                                <p className="italic">
                                                    “{item.comentario}”
                                                </p>
                                            )}
                                        </li>
                                    ))}
                                </ul>
                            )}
                        </section>

                        <section className="space-y-3 rounded-xl border p-4">
                            <h2 className="text-base font-medium">
                                Egresos CGU asociados
                            </h2>

                            {(caso.egresos_cgu ?? []).length === 0 ? (
                                <p className="text-sm text-muted-foreground">
                                    Sin egresos CGU asociados todavía.
                                </p>
                            ) : (
                                <ul className="divide-y text-sm">
                                    {(caso.egresos_cgu ?? []).map((egreso) => (
                                        <li
                                            key={egreso.id}
                                            className="flex items-center justify-between py-2"
                                        >
                                            <Link
                                                href={
                                                    enRevision
                                                        ? revision.show(
                                                              egreso.id,
                                                          ).url
                                                        : egresosCgu.show(
                                                              egreso.id,
                                                          ).url
                                                }
                                                className="underline"
                                            >
                                                {egreso.numero_egreso}
                                            </Link>
                                            <span className="text-muted-foreground">
                                                {formatFecha(egreso.fecha)} ·{' '}
                                                <Monto valor={egreso.monto} />
                                            </span>
                                        </li>
                                    ))}
                                </ul>
                            )}
                        </section>
                    </SeccionGrupo>
                </div>
            </div>
        </>
    );
}

CasoShow.layout = {
    breadcrumbs: [
        { title: 'Casos de pago de proveedores', href: casos.index() },
        { title: 'Detalle', href: '#' },
    ],
};

function SeccionGrupo({
    titulo,
    children,
}: {
    titulo: string;
    children: ReactNode;
}) {
    return (
        <div className="space-y-3">
            <h3 className="text-xs font-semibold tracking-wide text-muted-foreground uppercase">
                {titulo}
            </h3>
            <div className="space-y-3">{children}</div>
        </div>
    );
}

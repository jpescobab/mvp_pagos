import { Head, Link, router, usePage } from '@inertiajs/react';
import { Fragment, useEffect, useState } from 'react';
import { EstadoBadge } from '@/components/pago-proveedores/estado-badge';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
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
    ProcesoAdquisicionResumen,
    TipoDocumentoSeleccionable,
    TransicionWorkflow,
} from '@/types/pago-proveedores';

type PageProps = {
    caso: CasoPagoProveedor;
    tiposDocumento: TipoDocumentoSeleccionable[];
    verificacionSgf?: {
        encontrada: boolean;
        payload_crudo: Record<string, unknown> | null;
    };
};

/**
 * Transiciones que solo se ejecutan desde Revisión de Pagos
 * (InstanciaRevision::codigosTransicionGobernados() en el backend, que las
 * rechaza si llegan por este endpoint genérico).
 */
const CODIGOS_TRANSICION_GOBERNADOS = new Set([
    'observar_finanzas',
    'aprobar_finanzas',
    'rechazar_finanzas',
    'devolver_a_finanzas',
    'aprobar_zonal',
    'rechazar_zonal',
]);

const ESTADOS_EN_REVISION = new Set([
    'en_revision_finanzas',
    'en_revision_zonal',
]);

const NOMBRE_INSTANCIA: Record<string, string> = {
    en_revision_finanzas: 'Jefe de Finanzas',
    en_revision_zonal: 'Administrador Zonal',
};

export default function CasoShow() {
    const { caso, tiposDocumento, verificacionSgf, auth } =
        usePage<PageProps>().props;
    const [transicionConComentario, setTransicionConComentario] =
        useState<TransicionWorkflow | null>(null);
    const [comentario, setComentario] = useState('');
    const [procesando, setProcesando] = useState(false);
    const [errorTransicion, setErrorTransicion] = useState<string | null>(null);

    const enRevision = ESTADOS_EN_REVISION.has(
        caso.proceso.estado_actual.codigo,
    );
    const puedeRevisar =
        auth.permissions.includes('pago_proveedores.revisar_finanzas') ||
        auth.permissions.includes('pago_proveedores.revisar_zonal');
    const puedeVincularAdquisicion = auth.permissions.includes(
        'pago_proveedores.vincular_adquisicion',
    );
    const puedeGestionarDocumentos = auth.permissions.includes(
        'documentos.gestionar',
    );
    const puedeValidarDocumentos =
        auth.permissions.includes('documentos.validar');
    const puedeRegistrarCgu = auth.permissions.includes(
        'pago_proveedores.registrar_cgu',
    );
    const puedeRegistrarPagoBancario = auth.permissions.includes(
        'pago_proveedores.pagar',
    );
    const puedeRegistrarFactura = auth.permissions.includes(
        'pago_proveedores.registrar_factura',
    );
    const puedeVerificarSgf = auth.permissions.includes(
        'pago_proveedores.verificar_caso_sgf',
    );
    const egresoEnRevision = caso.egresos_cgu?.[0];
    const transicionesVisibles = caso.proceso.transiciones_disponibles.filter(
        (transicion) =>
            !CODIGOS_TRANSICION_GOBERNADOS.has(transicion.codigo) &&
            (transicion.permiso_requerido === null ||
                auth.permissions.includes(transicion.permiso_requerido)),
    );

    function ejecutar(transicion: TransicionWorkflow, comentarioTexto = '') {
        setProcesando(true);
        setErrorTransicion(null);

        router.post(
            casos.transiciones.store(caso.id).url,
            { codigo: transicion.codigo, comentario: comentarioTexto },
            {
                preserveScroll: true,
                onSuccess: () => {
                    setTransicionConComentario(null);
                    setComentario('');
                },
                onError: (errors) =>
                    setErrorTransicion(
                        (errors as Record<string, string>).transicion ?? null,
                    ),
                onFinish: () => setProcesando(false),
            },
        );
    }

    const historial = [
        ...(caso.proceso.historial_transiciones ?? []),
    ].reverse();

    const [snapshotsExpandidos, setSnapshotsExpandidos] = useState<Set<number>>(
        new Set(),
    );

    function alternarSnapshotExpandido(id: number) {
        setSnapshotsExpandidos((actual) => {
            const siguiente = new Set(actual);

            if (siguiente.has(id)) {
                siguiente.delete(id);
            } else {
                siguiente.add(id);
            }

            return siguiente;
        });
    }

    const [terminoBusqueda, setTerminoBusqueda] = useState('');
    const [resultadosBusqueda, setResultadosBusqueda] = useState<
        ProcesoAdquisicionResumen[]
    >([]);
    const [buscandoAdquisicion, setBuscandoAdquisicion] = useState(false);
    const [errorVinculo, setErrorVinculo] = useState<string | null>(null);

    useEffect(() => {
        if (caso.proceso_adquisicion !== null) {
            return;
        }

        const controller = new AbortController();
        const timeout = setTimeout(() => {
            setBuscandoAdquisicion(true);

            fetch(
                `${casos.buscarAdquisiciones.url(caso.id)}?q=${encodeURIComponent(terminoBusqueda)}`,
                {
                    signal: controller.signal,
                    headers: { Accept: 'application/json' },
                },
            )
                .then((response) => response.json())
                .then((json: ProcesoAdquisicionResumen[]) =>
                    setResultadosBusqueda(json),
                )
                .catch(() => undefined)
                .finally(() => setBuscandoAdquisicion(false));
        }, 300);

        return () => {
            controller.abort();
            clearTimeout(timeout);
        };
    }, [terminoBusqueda, caso.id, caso.proceso_adquisicion]);

    function vincularAdquisicion(procesoAdquisicionId: number) {
        setErrorVinculo(null);

        router.post(
            casos.vincularAdquisicion.store(caso.id).url,
            { proceso_adquisicion_id: procesoAdquisicionId },
            {
                preserveScroll: true,
                onError: (errors) =>
                    setErrorVinculo(
                        (errors as Record<string, string>)
                            .proceso_adquisicion_id ?? null,
                    ),
            },
        );
    }

    function desvincularAdquisicion() {
        setErrorVinculo(null);

        router.delete(casos.vincularAdquisicion.destroy(caso.id).url, {
            preserveScroll: true,
        });
    }

    const [tipoDocumentoId, setTipoDocumentoId] = useState<string>('');
    const [archivo, setArchivo] = useState<File | null>(null);
    const [subiendoDocumento, setSubiendoDocumento] = useState(false);
    const [errorDocumento, setErrorDocumento] = useState<string | null>(null);

    function subirDocumento() {
        if (archivo === null || tipoDocumentoId === '') {
            return;
        }

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
                onSuccess: () => {
                    setArchivo(null);
                    setTipoDocumentoId('');
                },
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

    function desvincularDocumento(vinculoId: number) {
        router.delete(
            documentos.destroy({ proceso: caso.proceso.id, vinculo: vinculoId })
                .url,
            { preserveScroll: true },
        );
    }

    const [errorValidacion, setErrorValidacion] = useState<string | null>(null);
    const [documentoARechazar, setDocumentoARechazar] = useState<number | null>(
        null,
    );
    const [observacionRechazo, setObservacionRechazo] = useState('');

    function validarDocumento(documentoId: number) {
        setErrorValidacion(null);

        router.post(
            documentos.validaciones.store({
                proceso: caso.proceso.id,
                documento: documentoId,
            }).url,
            { estado: 'valido' },
            {
                preserveScroll: true,
                onError: (errors) =>
                    setErrorValidacion(
                        (errors as Record<string, string>).estado ?? null,
                    ),
            },
        );
    }

    function confirmarRechazo() {
        if (documentoARechazar === null) {
            return;
        }

        setErrorValidacion(null);

        router.post(
            documentos.validaciones.store({
                proceso: caso.proceso.id,
                documento: documentoARechazar,
            }).url,
            { estado: 'rechazado', observacion: observacionRechazo },
            {
                preserveScroll: true,
                onSuccess: () => {
                    setDocumentoARechazar(null);
                    setObservacionRechazo('');
                },
                onError: (errors) =>
                    setErrorValidacion(
                        (errors as Record<string, string>).observacion ??
                            (errors as Record<string, string>).estado ??
                            null,
                    ),
            },
        );
    }

    function subirNuevaVersion(documentoId: number, archivoVersion: File) {
        setErrorDocumento(null);

        const formData = new FormData();
        formData.append('archivo', archivoVersion);

        router.post(
            documentos.versiones.store({
                proceso: caso.proceso.id,
                documento: documentoId,
            }).url,
            formData,
            {
                preserveScroll: true,
                onError: (errors) =>
                    setErrorDocumento(
                        (errors as Record<string, string>).archivo ?? null,
                    ),
            },
        );
    }

    const [numeroRegistroCgu, setNumeroRegistroCgu] = useState('');
    const [fechaRegistroCgu, setFechaRegistroCgu] = useState('');
    const [montoRegistroCgu, setMontoRegistroCgu] = useState('');
    const [observacionesRegistroCgu, setObservacionesRegistroCgu] =
        useState('');
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
                onSuccess: () => {
                    setNumeroRegistroCgu('');
                    setFechaRegistroCgu('');
                    setMontoRegistroCgu('');
                    setObservacionesRegistroCgu('');
                },
                onError: (errors) =>
                    setErrorRegistroCgu(
                        Object.values(errors as Record<string, string>)[0] ??
                            null,
                    ),
                onFinish: () => setRegistrandoCgu(false),
            },
        );
    }

    const [numeroOperacionPago, setNumeroOperacionPago] = useState('');
    const [fechaPago, setFechaPago] = useState('');
    const [montoPago, setMontoPago] = useState('');
    const [bancoPago, setBancoPago] = useState('');
    const [registrandoPago, setRegistrandoPago] = useState(false);
    const [errorRegistroPago, setErrorRegistroPago] = useState<string | null>(
        null,
    );

    function registrarPagoBancario() {
        setRegistrandoPago(true);
        setErrorRegistroPago(null);

        router.post(
            casos.registrosPagoBancario.store(caso.id).url,
            {
                numero_operacion: numeroOperacionPago,
                fecha_pago: fechaPago,
                monto: montoPago,
                banco: bancoPago,
            },
            {
                preserveScroll: true,
                onSuccess: () => {
                    setNumeroOperacionPago('');
                    setFechaPago('');
                    setMontoPago('');
                    setBancoPago('');
                },
                onError: (errors) =>
                    setErrorRegistroPago(
                        Object.values(errors as Record<string, string>)[0] ??
                            null,
                    ),
                onFinish: () => setRegistrandoPago(false),
            },
        );
    }

    const [folioFactura, setFolioFactura] = useState('');
    const [montoFactura, setMontoFactura] = useState('');
    const [fechaEmisionFactura, setFechaEmisionFactura] = useState('');
    const [registrandoFactura, setRegistrandoFactura] = useState(false);
    const [errorRegistroFactura, setErrorRegistroFactura] = useState<
        string | null
    >(null);

    function registrarFactura() {
        setRegistrandoFactura(true);
        setErrorRegistroFactura(null);

        router.post(
            casos.facturas.store(caso.id).url,
            {
                folio: folioFactura,
                monto: montoFactura,
                fecha_emision: fechaEmisionFactura,
            },
            {
                preserveScroll: true,
                onSuccess: () => {
                    setFolioFactura('');
                    setMontoFactura('');
                    setFechaEmisionFactura('');
                },
                onError: (errors) =>
                    setErrorRegistroFactura(
                        Object.values(errors as Record<string, string>)[0] ??
                            null,
                    ),
                onFinish: () => setRegistrandoFactura(false),
            },
        );
    }

    return (
        <>
            <Head title={`Caso ${caso.sgf_id}`} />

            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <div className="flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <h1 className="text-xl font-semibold tracking-tight">
                            {caso.proveedor.nombre ?? caso.sgf_id}
                        </h1>
                        <p className="font-mono text-sm text-muted-foreground">
                            sgf_id: {caso.sgf_id}
                            {caso.proveedor.rutproveedor &&
                                ` · RUT ${caso.proveedor.rutproveedor}`}
                        </p>
                    </div>
                    <div className="flex items-center gap-2">
                        <span className="text-sm text-muted-foreground">
                            {caso.sgf_status ?? '—'}
                        </span>
                        <EstadoBadge estado={caso.proceso.estado_actual} />
                    </div>
                </div>

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

                <div className="text-sm">
                    <span className="text-muted-foreground">Monto: </span>
                    <Monto valor={caso.monto} />
                </div>

                <section className="space-y-3 rounded-xl border p-4">
                    <h2 className="text-base font-medium">
                        Transiciones disponibles
                    </h2>

                    {errorTransicion && (
                        <p className="text-sm text-destructive">
                            {errorTransicion}
                        </p>
                    )}

                    {transicionesVisibles.length === 0 ? (
                        <p className="text-sm text-muted-foreground">
                            No hay transiciones disponibles desde el estado
                            actual.
                        </p>
                    ) : (
                        <div className="flex flex-wrap gap-2">
                            {transicionesVisibles.map((transicion) => (
                                <Button
                                    key={transicion.codigo}
                                    variant="outline"
                                    disabled={procesando}
                                    onClick={() =>
                                        transicion.requiere_comentario
                                            ? setTransicionConComentario(
                                                  transicion,
                                              )
                                            : ejecutar(transicion)
                                    }
                                >
                                    {transicion.nombre}
                                </Button>
                            ))}
                        </div>
                    )}
                </section>

                <section className="space-y-3 rounded-xl border p-4">
                    <h2 className="text-base font-medium">
                        Proceso de adquisición vinculado
                    </h2>

                    {errorVinculo && (
                        <p className="text-sm text-destructive">
                            {errorVinculo}
                        </p>
                    )}

                    {caso.proceso_adquisicion !== null ? (
                        <div className="flex items-center justify-between text-sm">
                            <span>
                                <span className="font-mono">
                                    {caso.proceso_adquisicion.codigo}
                                </span>{' '}
                                · {caso.proceso_adquisicion.objeto}
                            </span>
                            {puedeVincularAdquisicion && (
                                <Button
                                    variant="outline"
                                    size="sm"
                                    onClick={desvincularAdquisicion}
                                >
                                    Desvincular
                                </Button>
                            )}
                        </div>
                    ) : !puedeVincularAdquisicion ? (
                        <p className="text-sm text-muted-foreground">
                            Sin proceso de adquisición vinculado.
                        </p>
                    ) : (
                        <div className="space-y-2">
                            <Input
                                placeholder="Buscar por código, objeto, proveedor o monto…"
                                value={terminoBusqueda}
                                onChange={(e) =>
                                    setTerminoBusqueda(e.target.value)
                                }
                            />

                            {buscandoAdquisicion && (
                                <p className="text-sm text-muted-foreground">
                                    Buscando…
                                </p>
                            )}

                            {!buscandoAdquisicion &&
                                terminoBusqueda !== '' &&
                                resultadosBusqueda.length === 0 && (
                                    <p className="text-sm text-muted-foreground">
                                        Sin coincidencias.
                                    </p>
                                )}

                            {resultadosBusqueda.length > 0 && (
                                <ul className="divide-y text-sm">
                                    {resultadosBusqueda.map((resultado) => (
                                        <li
                                            key={resultado.id}
                                            className="flex items-center justify-between py-2"
                                        >
                                            <span>
                                                <span className="font-mono">
                                                    {resultado.codigo}
                                                </span>{' '}
                                                · {resultado.objeto}
                                                {resultado.proveedor &&
                                                    ` · ${resultado.proveedor}`}
                                            </span>
                                            <Button
                                                variant="outline"
                                                size="sm"
                                                onClick={() =>
                                                    vincularAdquisicion(
                                                        resultado.id,
                                                    )
                                                }
                                            >
                                                Vincular
                                            </Button>
                                        </li>
                                    ))}
                                </ul>
                            )}
                        </div>
                    )}
                </section>

                <section className="space-y-3 rounded-xl border p-4">
                    <h2 className="text-base font-medium">
                        Checklist documental
                    </h2>

                    {!caso.proceso.checklist ? (
                        <p className="text-sm text-muted-foreground">
                            Sin checklist generado aún.
                        </p>
                    ) : (
                        <ul className="divide-y text-sm">
                            {caso.proceso.checklist.items.map((item, i) => (
                                <li
                                    key={i}
                                    className="flex items-center justify-between py-2"
                                >
                                    <span>
                                        {item.tipo_documento ??
                                            'Documento sin tipo'}{' '}
                                        <span className="text-muted-foreground">
                                            ({item.tipo_requisito})
                                        </span>
                                    </span>
                                    <span className="flex items-center gap-2 text-muted-foreground">
                                        {item.estado_cumplimiento}
                                        {item.documento_id !== null && (
                                            <a
                                                href={
                                                    documentos.descargar({
                                                        proceso:
                                                            caso.proceso.id,
                                                        documento:
                                                            item.documento_id,
                                                    }).url
                                                }
                                                className="underline"
                                            >
                                                Ver documento
                                            </a>
                                        )}
                                    </span>
                                </li>
                            ))}
                        </ul>
                    )}
                </section>

                <section className="space-y-3 rounded-xl border p-4">
                    <h2 className="text-base font-medium">Documentos</h2>

                    {errorDocumento && (
                        <p className="text-sm text-destructive">
                            {errorDocumento}
                        </p>
                    )}

                    {errorValidacion && (
                        <p className="text-sm text-destructive">
                            {errorValidacion}
                        </p>
                    )}

                    {(caso.proceso.documentos ?? []).length === 0 ? (
                        <p className="text-sm text-muted-foreground">
                            Sin documentos vinculados todavía.
                        </p>
                    ) : (
                        <ul className="divide-y text-sm">
                            {(caso.proceso.documentos ?? []).map((doc) => (
                                <li
                                    key={doc.vinculo_id}
                                    className="space-y-2 py-2"
                                >
                                    <div className="flex items-center justify-between">
                                        <span>
                                            {doc.tipo_documento ??
                                                'Documento sin tipo'}{' '}
                                            <span className="text-muted-foreground">
                                                ({doc.nombre_archivo}) ·{' '}
                                                {doc.estado_vigente}
                                            </span>
                                        </span>
                                        <div className="flex gap-2">
                                            <a
                                                href={
                                                    documentos.descargar({
                                                        proceso:
                                                            caso.proceso.id,
                                                        documento:
                                                            doc.documento_id,
                                                    }).url
                                                }
                                                className="text-sm underline"
                                            >
                                                Descargar
                                            </a>
                                            {!enRevision &&
                                                puedeValidarDocumentos && (
                                                    <>
                                                        <Button
                                                            variant="outline"
                                                            size="sm"
                                                            onClick={() =>
                                                                validarDocumento(
                                                                    doc.documento_id,
                                                                )
                                                            }
                                                        >
                                                            Validar
                                                        </Button>
                                                        <Button
                                                            variant="outline"
                                                            size="sm"
                                                            onClick={() =>
                                                                setDocumentoARechazar(
                                                                    doc.documento_id,
                                                                )
                                                            }
                                                        >
                                                            Rechazar
                                                        </Button>
                                                    </>
                                                )}
                                            {puedeGestionarDocumentos && (
                                                <>
                                                    <Button
                                                        variant="outline"
                                                        size="sm"
                                                        onClick={() =>
                                                            desvincularDocumento(
                                                                doc.vinculo_id,
                                                            )
                                                        }
                                                    >
                                                        Desvincular
                                                    </Button>
                                                    <input
                                                        type="file"
                                                        accept=".pdf,.jpg,.jpeg,.png"
                                                        className="w-32 text-xs"
                                                        title="Subir nueva versión"
                                                        onChange={(e) => {
                                                            const archivoVersion =
                                                                e.target
                                                                    .files?.[0];

                                                            if (
                                                                archivoVersion
                                                            ) {
                                                                subirNuevaVersion(
                                                                    doc.documento_id,
                                                                    archivoVersion,
                                                                );
                                                            }

                                                            e.target.value = '';
                                                        }}
                                                    />
                                                </>
                                            )}
                                        </div>
                                    </div>

                                    {doc.validaciones.length > 0 && (
                                        <ul className="space-y-1 pl-4 text-xs text-muted-foreground">
                                            {doc.validaciones.map(
                                                (validacion, i) => (
                                                    <li key={i}>
                                                        {validacion.estado} ·{' '}
                                                        {validacion.validado_por ??
                                                            'Sistema'}
                                                        {validacion.validado_en &&
                                                            ` · ${formatFechaHora(validacion.validado_en)}`}
                                                        {validacion.observacion && (
                                                            <span className="italic">
                                                                {' '}
                                                                — “
                                                                {
                                                                    validacion.observacion
                                                                }
                                                                ”
                                                            </span>
                                                        )}
                                                    </li>
                                                ),
                                            )}
                                        </ul>
                                    )}
                                </li>
                            ))}
                        </ul>
                    )}

                    {puedeGestionarDocumentos && (
                        <div className="flex flex-wrap items-end gap-2">
                            <div className="space-y-1">
                                <Label htmlFor="tipo-documento">
                                    Tipo de documento
                                </Label>
                                <Select
                                    value={tipoDocumentoId}
                                    onValueChange={setTipoDocumentoId}
                                >
                                    <SelectTrigger id="tipo-documento">
                                        <SelectValue placeholder="Selecciona un tipo" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {tiposDocumento.map((tipo) => (
                                            <SelectItem
                                                key={tipo.id}
                                                value={String(tipo.id)}
                                            >
                                                {tipo.nombre}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </div>
                            <div className="space-y-1">
                                <Label htmlFor="archivo">Archivo</Label>
                                <input
                                    id="archivo"
                                    type="file"
                                    accept=".pdf,.jpg,.jpeg,.png"
                                    onChange={(e) =>
                                        setArchivo(e.target.files?.[0] ?? null)
                                    }
                                    className="text-sm"
                                />
                            </div>
                            <Button
                                disabled={
                                    subiendoDocumento ||
                                    archivo === null ||
                                    tipoDocumentoId === ''
                                }
                                onClick={subirDocumento}
                            >
                                Subir
                            </Button>
                        </div>
                    )}
                </section>

                <section className="space-y-3 rounded-xl border p-4">
                    <h2 className="text-base font-medium">
                        Registro contable CGU
                    </h2>

                    {errorRegistroCgu && (
                        <p className="text-sm text-destructive">
                            {errorRegistroCgu}
                        </p>
                    )}

                    {(caso.registros_contables_cgu ?? []).length === 0 ? (
                        <p className="text-sm text-muted-foreground">
                            Sin registro contable CGU todavía.
                        </p>
                    ) : (
                        <ul className="divide-y text-sm">
                            {(caso.registros_contables_cgu ?? []).map(
                                (registro) => (
                                    <li key={registro.id} className="py-2">
                                        <div className="flex items-center justify-between">
                                            <span className="font-mono">
                                                {registro.numero_registro}
                                            </span>
                                            <span className="text-muted-foreground">
                                                {formatFecha(
                                                    registro.fecha_registro,
                                                )}{' '}
                                                ·{' '}
                                                <Monto valor={registro.monto} />
                                            </span>
                                        </div>
                                        <p className="text-muted-foreground">
                                            {registro.registrado_por ??
                                                'Sistema'}
                                            {registro.observaciones &&
                                                ` — ${registro.observaciones}`}
                                        </p>
                                    </li>
                                ),
                            )}
                        </ul>
                    )}

                    {puedeRegistrarCgu && (
                        <div className="flex flex-wrap items-end gap-2">
                            <div className="space-y-1">
                                <Label htmlFor="numero-registro-cgu">
                                    N.º de registro
                                </Label>
                                <Input
                                    id="numero-registro-cgu"
                                    value={numeroRegistroCgu}
                                    onChange={(e) =>
                                        setNumeroRegistroCgu(e.target.value)
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
                                        setFechaRegistroCgu(e.target.value)
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
                                        setMontoRegistroCgu(e.target.value)
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
                            <Button
                                disabled={
                                    registrandoCgu ||
                                    numeroRegistroCgu === '' ||
                                    fechaRegistroCgu === '' ||
                                    montoRegistroCgu === ''
                                }
                                onClick={registrarContableCgu}
                            >
                                Registrar
                            </Button>
                        </div>
                    )}
                </section>

                <section className="space-y-3 rounded-xl border p-4">
                    <h2 className="text-base font-medium">
                        Registro de pago bancario
                    </h2>

                    {errorRegistroPago && (
                        <p className="text-sm text-destructive">
                            {errorRegistroPago}
                        </p>
                    )}

                    {(caso.registros_pago_bancario ?? []).length === 0 ? (
                        <p className="text-sm text-muted-foreground">
                            Sin registro de pago bancario todavía.
                        </p>
                    ) : (
                        <ul className="divide-y text-sm">
                            {(caso.registros_pago_bancario ?? []).map(
                                (registro) => (
                                    <li key={registro.id} className="py-2">
                                        <div className="flex items-center justify-between">
                                            <span className="font-mono">
                                                {registro.numero_operacion}
                                            </span>
                                            <span className="text-muted-foreground">
                                                {formatFecha(
                                                    registro.fecha_pago,
                                                )}{' '}
                                                ·{' '}
                                                <Monto valor={registro.monto} />
                                            </span>
                                        </div>
                                        <p className="text-muted-foreground">
                                            {registro.registrado_por ??
                                                'Sistema'}
                                            {registro.banco &&
                                                ` — ${registro.banco}`}
                                        </p>
                                    </li>
                                ),
                            )}
                        </ul>
                    )}

                    {puedeRegistrarPagoBancario && (
                        <div className="flex flex-wrap items-end gap-2">
                            <div className="space-y-1">
                                <Label htmlFor="numero-operacion-pago">
                                    N.º de operación
                                </Label>
                                <Input
                                    id="numero-operacion-pago"
                                    value={numeroOperacionPago}
                                    onChange={(e) =>
                                        setNumeroOperacionPago(e.target.value)
                                    }
                                />
                            </div>
                            <div className="space-y-1">
                                <Label htmlFor="fecha-pago">Fecha</Label>
                                <Input
                                    id="fecha-pago"
                                    type="date"
                                    value={fechaPago}
                                    onChange={(e) =>
                                        setFechaPago(e.target.value)
                                    }
                                />
                            </div>
                            <div className="space-y-1">
                                <Label htmlFor="monto-pago">Monto</Label>
                                <Input
                                    id="monto-pago"
                                    type="number"
                                    value={montoPago}
                                    onChange={(e) =>
                                        setMontoPago(e.target.value)
                                    }
                                />
                            </div>
                            <div className="space-y-1">
                                <Label htmlFor="banco-pago">Banco</Label>
                                <Input
                                    id="banco-pago"
                                    value={bancoPago}
                                    onChange={(e) =>
                                        setBancoPago(e.target.value)
                                    }
                                />
                            </div>
                            <Button
                                disabled={
                                    registrandoPago ||
                                    numeroOperacionPago === '' ||
                                    fechaPago === '' ||
                                    montoPago === ''
                                }
                                onClick={registrarPagoBancario}
                            >
                                Registrar
                            </Button>
                        </div>
                    )}
                </section>

                <section className="space-y-3 rounded-xl border p-4">
                    <h2 className="text-base font-medium">Facturas</h2>

                    {errorRegistroFactura && (
                        <p className="text-sm text-destructive">
                            {errorRegistroFactura}
                        </p>
                    )}

                    {(caso.facturas ?? []).length === 0 ? (
                        <p className="text-sm text-muted-foreground">
                            Sin facturas registradas todavía.
                        </p>
                    ) : (
                        <ul className="divide-y text-sm">
                            {(caso.facturas ?? []).map((factura) => (
                                <li key={factura.id} className="py-2">
                                    <div className="flex items-center justify-between">
                                        <span className="font-mono">
                                            {factura.folio}
                                        </span>
                                        <span className="text-muted-foreground">
                                            {formatFecha(factura.fecha_emision)}{' '}
                                            · <Monto valor={factura.monto} />
                                        </span>
                                    </div>
                                </li>
                            ))}
                        </ul>
                    )}

                    {puedeRegistrarFactura && (
                        <div className="flex flex-wrap items-end gap-2">
                            <div className="space-y-1">
                                <Label htmlFor="folio-factura">Folio</Label>
                                <Input
                                    id="folio-factura"
                                    value={folioFactura}
                                    onChange={(e) =>
                                        setFolioFactura(e.target.value)
                                    }
                                />
                            </div>
                            <div className="space-y-1">
                                <Label htmlFor="fecha-emision-factura">
                                    Fecha de emisión
                                </Label>
                                <Input
                                    id="fecha-emision-factura"
                                    type="date"
                                    value={fechaEmisionFactura}
                                    onChange={(e) =>
                                        setFechaEmisionFactura(e.target.value)
                                    }
                                />
                            </div>
                            <div className="space-y-1">
                                <Label htmlFor="monto-factura">Monto</Label>
                                <Input
                                    id="monto-factura"
                                    type="number"
                                    value={montoFactura}
                                    onChange={(e) =>
                                        setMontoFactura(e.target.value)
                                    }
                                />
                            </div>
                            <Button
                                disabled={
                                    registrandoFactura ||
                                    folioFactura === '' ||
                                    fechaEmisionFactura === '' ||
                                    montoFactura === ''
                                }
                                onClick={registrarFactura}
                            >
                                Registrar
                            </Button>
                        </div>
                    )}
                </section>

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
                                            {formatFechaHora(item.created_at)}
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
                    <div className="flex items-center justify-between">
                        <h2 className="text-base font-medium">
                            Historial de snapshots SGF
                        </h2>
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

                    {(caso.snapshots_sgf ?? []).length === 0 ? (
                        <p className="text-sm text-muted-foreground">
                            Sin snapshots SGF registrados todavía.
                        </p>
                    ) : (
                        <ul className="divide-y text-sm">
                            {(caso.snapshots_sgf ?? []).map((snapshot) => (
                                <Fragment key={snapshot.id}>
                                    <li className="py-2">
                                        <div className="flex items-center justify-between">
                                            <span>
                                                {formatFechaHora(
                                                    snapshot.capturado_en,
                                                )}{' '}
                                                <span className="text-muted-foreground">
                                                    ·{' '}
                                                    {snapshot.metodo_captura ??
                                                        'Sin fuente'}
                                                </span>
                                            </span>
                                            <div className="flex items-center gap-2">
                                                <span className="font-mono text-xs text-muted-foreground">
                                                    {snapshot.hash.slice(0, 12)}
                                                    …
                                                </span>
                                                <Button
                                                    variant="outline"
                                                    size="sm"
                                                    onClick={() =>
                                                        alternarSnapshotExpandido(
                                                            snapshot.id,
                                                        )
                                                    }
                                                >
                                                    {snapshotsExpandidos.has(
                                                        snapshot.id,
                                                    )
                                                        ? 'Ocultar'
                                                        : 'Ver detalle'}
                                                </Button>
                                            </div>
                                        </div>
                                    </li>
                                    {snapshotsExpandidos.has(snapshot.id) && (
                                        <li className="bg-muted/30 px-2 py-3">
                                            <pre className="overflow-x-auto text-xs">
                                                {JSON.stringify(
                                                    {
                                                        payload_crudo:
                                                            snapshot.payload_crudo,
                                                        payload_normalizado:
                                                            snapshot.payload_normalizado,
                                                    },
                                                    null,
                                                    2,
                                                )}
                                            </pre>
                                        </li>
                                    )}
                                </Fragment>
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
                                                ? revision.show(egreso.id).url
                                                : egresosCgu.show(egreso.id).url
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
            </div>

            <Dialog
                open={transicionConComentario !== null}
                onOpenChange={(open) => {
                    if (!open) {
                        setTransicionConComentario(null);
                        setComentario('');
                    }
                }}
            >
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>
                            {transicionConComentario?.nombre}
                        </DialogTitle>
                        <DialogDescription>
                            Esta transición requiere un comentario.
                        </DialogDescription>
                    </DialogHeader>

                    <div className="grid gap-2">
                        <Label htmlFor="comentario">Comentario</Label>
                        <textarea
                            id="comentario"
                            className="min-h-24 rounded-md border bg-background p-2 text-sm"
                            value={comentario}
                            onChange={(e) => setComentario(e.target.value)}
                        />
                    </div>

                    <DialogFooter>
                        <Button
                            disabled={procesando || comentario === ''}
                            onClick={() =>
                                transicionConComentario &&
                                ejecutar(transicionConComentario, comentario)
                            }
                        >
                            Confirmar
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>

            <Dialog
                open={documentoARechazar !== null}
                onOpenChange={(open) => {
                    if (!open) {
                        setDocumentoARechazar(null);
                        setObservacionRechazo('');
                    }
                }}
            >
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Rechazar documento</DialogTitle>
                        <DialogDescription>
                            Indica el motivo del rechazo.
                        </DialogDescription>
                    </DialogHeader>

                    <div className="grid gap-2">
                        <Label htmlFor="observacion-rechazo">Observación</Label>
                        <textarea
                            id="observacion-rechazo"
                            className="min-h-24 rounded-md border bg-background p-2 text-sm"
                            value={observacionRechazo}
                            onChange={(e) =>
                                setObservacionRechazo(e.target.value)
                            }
                        />
                    </div>

                    <DialogFooter>
                        <Button
                            disabled={observacionRechazo === ''}
                            onClick={confirmarRechazo}
                        >
                            Confirmar rechazo
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </>
    );
}

CasoShow.layout = {
    breadcrumbs: [
        { title: 'Casos de pago de proveedores', href: casos.index() },
        { title: 'Detalle', href: '#' },
    ],
};

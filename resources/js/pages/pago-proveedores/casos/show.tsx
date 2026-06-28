import { Head, router, usePage } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import { EstadoBadge } from '@/components/pago-proveedores/estado-badge';
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
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import casos from '@/routes/pago-proveedores/casos';
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
};

export default function CasoShow() {
    const { caso, tiposDocumento } = usePage<PageProps>().props;
    const [transicionConComentario, setTransicionConComentario] =
        useState<TransicionWorkflow | null>(null);
    const [comentario, setComentario] = useState('');
    const [procesando, setProcesando] = useState(false);
    const [errorTransicion, setErrorTransicion] = useState<string | null>(
        null,
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
                        (errors as Record<string, string>).transicion ??
                            null,
                    ),
                onFinish: () => setProcesando(false),
            },
        );
    }

    const historial = [...(caso.proceso.historial_transiciones ?? [])].reverse();

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
                { signal: controller.signal, headers: { Accept: 'application/json' } },
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

        router.post(documentos.store({ proceso: caso.proceso.id }).url, formData, {
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
        });
    }

    function desvincularDocumento(vinculoId: number) {
        router.delete(
            documentos.destroy({ proceso: caso.proceso.id, vinculo: vinculoId })
                .url,
            { preserveScroll: true },
        );
    }

    const [errorValidacion, setErrorValidacion] = useState<string | null>(
        null,
    );
    const [documentoARechazar, setDocumentoARechazar] = useState<
        number | null
    >(null);
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

                <div className="text-sm">
                    <span className="text-muted-foreground">Monto: </span>
                    {caso.monto}
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

                    {caso.proceso.transiciones_disponibles.length === 0 ? (
                        <p className="text-sm text-muted-foreground">
                            No hay transiciones disponibles desde el estado
                            actual.
                        </p>
                    ) : (
                        <div className="flex flex-wrap gap-2">
                            {caso.proceso.transiciones_disponibles.map(
                                (transicion) => (
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
                                ),
                            )}
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
                            <Button
                                variant="outline"
                                size="sm"
                                onClick={desvincularAdquisicion}
                            >
                                Desvincular
                            </Button>
                        </div>
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
                                    className="flex items-center justify-between py-2"
                                >
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
                                                    proceso: caso.proceso.id,
                                                    documento:
                                                        doc.documento_id,
                                                }).url
                                            }
                                            className="text-sm underline"
                                        >
                                            Descargar
                                        </a>
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
                                                    e.target.files?.[0];

                                                if (archivoVersion) {
                                                    subirNuevaVersion(
                                                        doc.documento_id,
                                                        archivoVersion,
                                                    );
                                                }

                                                e.target.value = '';
                                            }}
                                        />
                                    </div>
                                </li>
                            ))}
                        </ul>
                    )}

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
                                            {new Date(
                                                item.created_at,
                                            ).toLocaleString()}
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
                        <Label htmlFor="observacion-rechazo">
                            Observación
                        </Label>
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

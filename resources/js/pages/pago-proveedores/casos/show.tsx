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
import casos from '@/routes/pago-proveedores/casos';
import type {
    CasoPagoProveedor,
    ProcesoAdquisicionResumen,
    TransicionWorkflow,
} from '@/types/pago-proveedores';

type PageProps = {
    caso: CasoPagoProveedor;
};

export default function CasoShow() {
    const { caso } = usePage<PageProps>().props;
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
                                    <span className="text-muted-foreground">
                                        {item.estado_cumplimiento}
                                    </span>
                                </li>
                            ))}
                        </ul>
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
        </>
    );
}

CasoShow.layout = {
    breadcrumbs: [
        { title: 'Casos de pago de proveedores', href: casos.index() },
        { title: 'Detalle', href: '#' },
    ],
};

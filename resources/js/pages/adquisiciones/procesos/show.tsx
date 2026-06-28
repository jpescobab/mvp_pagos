import { Head, router, usePage } from '@inertiajs/react';
import { useState } from 'react';
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
import { Label } from '@/components/ui/label';
import procesos from '@/routes/adquisiciones/procesos';
import type { ProcesoAdquisicion } from '@/types/adquisiciones';
import type { TransicionWorkflow } from '@/types/pago-proveedores';

type PageProps = {
    proceso: ProcesoAdquisicion;
};

export default function ProcesoShow() {
    const { proceso } = usePage<PageProps>().props;
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
            procesos.transiciones.store(proceso.id).url,
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

    const historial = [
        ...(proceso.proceso.historial_transiciones ?? []),
    ].reverse();

    return (
        <>
            <Head title={`Proceso ${proceso.codigo}`} />

            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <div className="flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <h1 className="text-xl font-semibold tracking-tight">
                            {proceso.modalidad.nombre ?? proceso.codigo}
                        </h1>
                        <p className="font-mono text-sm text-muted-foreground">
                            código: {proceso.codigo}
                            {proceso.ccosto.nombre &&
                                ` · ${proceso.ccosto.nombre}`}
                        </p>
                    </div>
                    <div className="flex items-center gap-2">
                        {proceso.proveedor.nombre && (
                            <span className="text-sm text-muted-foreground">
                                {proceso.proveedor.nombre}
                            </span>
                        )}
                        <EstadoBadge estado={proceso.proceso.estado_actual} />
                    </div>
                </div>

                <div className="text-sm">
                    <span className="text-muted-foreground">Monto: </span>
                    {proceso.monto ?? '—'}
                </div>

                <div className="text-sm">
                    <span className="text-muted-foreground">Objeto: </span>
                    {proceso.objeto}
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

                    {proceso.proceso.transiciones_disponibles.length === 0 ? (
                        <p className="text-sm text-muted-foreground">
                            No hay transiciones disponibles desde el estado
                            actual.
                        </p>
                    ) : (
                        <div className="flex flex-wrap gap-2">
                            {proceso.proceso.transiciones_disponibles.map(
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
                        Casos de pago vinculados
                    </h2>

                    {proceso.casos_pago_proveedor.length === 0 ? (
                        <p className="text-sm text-muted-foreground">
                            Sin casos de pago vinculados todavía.
                        </p>
                    ) : (
                        <ul className="divide-y text-sm">
                            {proceso.casos_pago_proveedor.map((caso) => (
                                <li key={caso.id} className="py-2 font-mono">
                                    {caso.sgf_id}
                                </li>
                            ))}
                        </ul>
                    )}
                </section>

                <section className="space-y-3 rounded-xl border p-4">
                    <h2 className="text-base font-medium">
                        Checklist documental
                    </h2>

                    {!proceso.proceso.checklist ? (
                        <p className="text-sm text-muted-foreground">
                            Sin checklist generado aún.
                        </p>
                    ) : (
                        <ul className="divide-y text-sm">
                            {proceso.proceso.checklist.items.map(
                                (item, i) => (
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
                                ),
                            )}
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

ProcesoShow.layout = {
    breadcrumbs: [
        { title: 'Procesos de adquisición', href: procesos.index() },
        { title: 'Detalle', href: '#' },
    ],
};

import { Head, router, usePage } from '@inertiajs/react';
import { useState } from 'react';
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
import { Monto } from '@/components/ui/monto';
import { formatFecha, formatFechaHora } from '@/lib/format';
import ejecuciones from '@/routes/informes-razonados/ejecuciones';
import narrativas from '@/routes/informes-razonados/narrativas';
import type {
    EjecucionInformeRazonado,
    NarrativaInformeRazonado,
} from '@/types/informes-razonados';
import type { TransicionWorkflow } from '@/types/pago-proveedores';

type PageProps = {
    ejecucion: EjecucionInformeRazonado;
};

export default function EjecucionInformeRazonadoShow() {
    const { ejecucion, auth } = usePage<PageProps>().props;

    const puedeElaborar = auth.permissions.includes('informes.elaborar');
    const puedeRevisar = auth.permissions.includes('informes.aprobar');
    const editable = ejecucion.editable ?? false;

    const [transicionConComentario, setTransicionConComentario] =
        useState<TransicionWorkflow | null>(null);
    const [comentario, setComentario] = useState('');
    const [procesando, setProcesando] = useState(false);
    const [errorTransicion, setErrorTransicion] = useState<string | null>(null);

    const [nuevaNarrativa, setNuevaNarrativa] = useState('');
    const [narrativaEnEdicion, setNarrativaEnEdicion] = useState<number | null>(
        null,
    );
    const [contenidoEdicion, setContenidoEdicion] = useState('');
    const [procesandoNarrativa, setProcesandoNarrativa] = useState(false);

    function agregarNarrativa() {
        if (nuevaNarrativa.trim() === '') {
            return;
        }

        setProcesandoNarrativa(true);
        router.post(
            ejecuciones.narrativas.store(ejecucion.id).url,
            { contenido: nuevaNarrativa },
            {
                preserveScroll: true,
                onSuccess: () => setNuevaNarrativa(''),
                onFinish: () => setProcesandoNarrativa(false),
            },
        );
    }

    function iniciarEdicion(narrativa: NarrativaInformeRazonado) {
        setNarrativaEnEdicion(narrativa.id);
        setContenidoEdicion(narrativa.contenido);
    }

    function guardarEdicion(id: number) {
        if (contenidoEdicion.trim() === '') {
            return;
        }

        setProcesandoNarrativa(true);
        router.patch(
            narrativas.update(id).url,
            { contenido: contenidoEdicion },
            {
                preserveScroll: true,
                onSuccess: () => setNarrativaEnEdicion(null),
                onFinish: () => setProcesandoNarrativa(false),
            },
        );
    }

    function eliminarNarrativa(id: number) {
        if (!window.confirm('¿Eliminar esta narrativa?')) {
            return;
        }

        setProcesandoNarrativa(true);
        router.delete(narrativas.destroy(id).url, {
            preserveScroll: true,
            onFinish: () => setProcesandoNarrativa(false),
        });
    }

    function marcarRevisada(id: number) {
        setProcesandoNarrativa(true);
        router.post(
            narrativas.revisar(id).url,
            {},
            {
                preserveScroll: true,
                onFinish: () => setProcesandoNarrativa(false),
            },
        );
    }

    function ejecutar(transicion: TransicionWorkflow, comentarioTexto = '') {
        setProcesando(true);
        setErrorTransicion(null);

        router.post(
            ejecuciones.transiciones.store(ejecucion.id).url,
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
        ...(ejecucion.proceso?.historial_transiciones ?? []),
    ].reverse();

    return (
        <>
            <Head title={`Ejecución — ${ejecucion.definicion.nombre}`} />

            <div className="flex h-full flex-1 flex-col gap-4 p-4">
                <div>
                    <h1 className="text-xl font-semibold tracking-tight">
                        {ejecucion.definicion.nombre}
                    </h1>
                    <p className="text-sm text-muted-foreground">
                        Corte del período {ejecucion.corte.periodo_codigo} ·
                        generado por {ejecucion.generado_por ?? 'Sistema'} el{' '}
                        {formatFecha(ejecucion.generado_en)}
                    </p>
                </div>

                <section className="space-y-3 rounded-xl border p-4">
                    <h2 className="text-base font-medium">
                        Estado: {ejecucion.proceso?.estado_actual.nombre}
                    </h2>

                    {errorTransicion && (
                        <p className="text-sm text-destructive">
                            {errorTransicion}
                        </p>
                    )}

                    {(ejecucion.proceso?.transiciones_disponibles ?? [])
                        .length === 0 ? (
                        <p className="text-sm text-muted-foreground">
                            No hay transiciones disponibles desde el estado
                            actual.
                        </p>
                    ) : (
                        <div className="flex flex-wrap gap-2">
                            {ejecucion.proceso?.transiciones_disponibles.map(
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

                <div className="grid gap-4 md:grid-cols-2">
                    <section className="space-y-2 rounded-xl border p-4">
                        <h2 className="text-base font-medium">Métricas</h2>
                        {(ejecucion.metricas ?? []).length === 0 ? (
                            <p className="text-sm text-muted-foreground">
                                Sin métricas todavía.
                            </p>
                        ) : (
                            <ul className="divide-y text-sm">
                                {ejecucion.metricas?.map((metrica) => (
                                    <li
                                        key={metrica.id}
                                        className="flex items-center justify-between py-2"
                                    >
                                        <span>{metrica.etiqueta}</span>
                                        <span className="text-muted-foreground">
                                            <Monto
                                                valor={metrica.valor}
                                                variante="numero"
                                            />{' '}
                                            {metrica.unidad}
                                        </span>
                                    </li>
                                ))}
                            </ul>
                        )}
                    </section>

                    <section className="space-y-2 rounded-xl border p-4">
                        <h2 className="text-base font-medium">Gráficos</h2>
                        {(ejecucion.graficos ?? []).length === 0 ? (
                            <p className="text-sm text-muted-foreground">
                                Sin gráficos todavía.
                            </p>
                        ) : (
                            <ul className="divide-y text-sm">
                                {ejecucion.graficos?.map((grafico) => (
                                    <li key={grafico.id} className="py-2">
                                        {grafico.titulo} ({grafico.tipo})
                                    </li>
                                ))}
                            </ul>
                        )}
                    </section>

                    <section className="space-y-3 rounded-xl border p-4">
                        <h2 className="text-base font-medium">Narrativas</h2>
                        {(ejecucion.narrativas ?? []).length === 0 ? (
                            <p className="text-sm text-muted-foreground">
                                Sin narrativas todavía.
                            </p>
                        ) : (
                            <ul className="divide-y text-sm">
                                {ejecucion.narrativas?.map((narrativa) => (
                                    <li
                                        key={narrativa.id}
                                        className="space-y-2 py-3"
                                    >
                                        {narrativaEnEdicion === narrativa.id ? (
                                            <div className="space-y-2">
                                                <textarea
                                                    className="min-h-24 w-full rounded-md border bg-background p-2 text-sm"
                                                    value={contenidoEdicion}
                                                    onChange={(e) =>
                                                        setContenidoEdicion(
                                                            e.target.value,
                                                        )
                                                    }
                                                />
                                                <div className="flex gap-2">
                                                    <Button
                                                        size="sm"
                                                        disabled={
                                                            procesandoNarrativa ||
                                                            contenidoEdicion.trim() ===
                                                                ''
                                                        }
                                                        onClick={() =>
                                                            guardarEdicion(
                                                                narrativa.id,
                                                            )
                                                        }
                                                    >
                                                        Guardar
                                                    </Button>
                                                    <Button
                                                        size="sm"
                                                        variant="outline"
                                                        disabled={
                                                            procesandoNarrativa
                                                        }
                                                        onClick={() =>
                                                            setNarrativaEnEdicion(
                                                                null,
                                                            )
                                                        }
                                                    >
                                                        Cancelar
                                                    </Button>
                                                </div>
                                            </div>
                                        ) : (
                                            <>
                                                <p className="whitespace-pre-wrap">
                                                    {narrativa.contenido}
                                                </p>
                                                <div className="flex flex-wrap items-center gap-2 text-xs text-muted-foreground">
                                                    {narrativa.generado_por_ia && (
                                                        <span className="rounded bg-muted px-1.5 py-0.5">
                                                            IA
                                                        </span>
                                                    )}
                                                    {narrativa.revisado_en ? (
                                                        <span>
                                                            Revisada
                                                            {narrativa.revisado_por
                                                                ? ` por ${narrativa.revisado_por}`
                                                                : ''}{' '}
                                                            ·{' '}
                                                            {formatFechaHora(
                                                                narrativa.revisado_en,
                                                            )}
                                                        </span>
                                                    ) : (
                                                        <span>Sin revisar</span>
                                                    )}
                                                </div>
                                                {(editable && puedeElaborar) ||
                                                puedeRevisar ? (
                                                    <div className="flex flex-wrap gap-2">
                                                        {editable &&
                                                            puedeElaborar && (
                                                                <>
                                                                    <Button
                                                                        size="sm"
                                                                        variant="outline"
                                                                        disabled={
                                                                            procesandoNarrativa
                                                                        }
                                                                        onClick={() =>
                                                                            iniciarEdicion(
                                                                                narrativa,
                                                                            )
                                                                        }
                                                                    >
                                                                        Editar
                                                                    </Button>
                                                                    <Button
                                                                        size="sm"
                                                                        variant="outline"
                                                                        disabled={
                                                                            procesandoNarrativa
                                                                        }
                                                                        onClick={() =>
                                                                            eliminarNarrativa(
                                                                                narrativa.id,
                                                                            )
                                                                        }
                                                                    >
                                                                        Eliminar
                                                                    </Button>
                                                                </>
                                                            )}
                                                        {puedeRevisar &&
                                                            !narrativa.revisado_en && (
                                                                <Button
                                                                    size="sm"
                                                                    variant="outline"
                                                                    disabled={
                                                                        procesandoNarrativa
                                                                    }
                                                                    onClick={() =>
                                                                        marcarRevisada(
                                                                            narrativa.id,
                                                                        )
                                                                    }
                                                                >
                                                                    Marcar
                                                                    revisada
                                                                </Button>
                                                            )}
                                                    </div>
                                                ) : null}
                                            </>
                                        )}
                                    </li>
                                ))}
                            </ul>
                        )}

                        {editable && puedeElaborar && (
                            <div className="space-y-2 border-t pt-3">
                                <Label htmlFor="nueva-narrativa">
                                    Agregar narrativa
                                </Label>
                                <textarea
                                    id="nueva-narrativa"
                                    className="min-h-24 w-full rounded-md border bg-background p-2 text-sm"
                                    value={nuevaNarrativa}
                                    onChange={(e) =>
                                        setNuevaNarrativa(e.target.value)
                                    }
                                />
                                <Button
                                    size="sm"
                                    disabled={
                                        procesandoNarrativa ||
                                        nuevaNarrativa.trim() === ''
                                    }
                                    onClick={agregarNarrativa}
                                >
                                    Agregar
                                </Button>
                            </div>
                        )}
                    </section>

                    <section className="space-y-2 rounded-xl border p-4">
                        <h2 className="text-base font-medium">Excepciones</h2>
                        {(ejecucion.excepciones ?? []).length === 0 ? (
                            <p className="text-sm text-muted-foreground">
                                Sin excepciones todavía.
                            </p>
                        ) : (
                            <ul className="divide-y text-sm">
                                {ejecucion.excepciones?.map((excepcion) => (
                                    <li key={excepcion.id} className="py-2">
                                        <span className="font-mono">
                                            {excepcion.severidad}
                                        </span>{' '}
                                        — {excepcion.descripcion}
                                    </li>
                                ))}
                            </ul>
                        )}
                    </section>

                    <section className="space-y-2 rounded-xl border p-4">
                        <h2 className="text-base font-medium">Snapshots</h2>
                        {(ejecucion.snapshots ?? []).length === 0 ? (
                            <p className="text-sm text-muted-foreground">
                                Sin snapshots todavía.
                            </p>
                        ) : (
                            <ul className="divide-y text-sm">
                                {ejecucion.snapshots?.map((snapshot) => (
                                    <li
                                        key={snapshot.id}
                                        className="py-2 font-mono text-xs"
                                    >
                                        {snapshot.hash} ·{' '}
                                        {formatFechaHora(snapshot.capturado_en)}
                                    </li>
                                ))}
                            </ul>
                        )}
                    </section>

                    <section className="space-y-2 rounded-xl border p-4">
                        <h2 className="text-base font-medium">Aprobaciones</h2>
                        {(ejecucion.aprobaciones ?? []).length === 0 ? (
                            <p className="text-sm text-muted-foreground">
                                Sin aprobaciones todavía.
                            </p>
                        ) : (
                            <ul className="divide-y text-sm">
                                {ejecucion.aprobaciones?.map((aprobacion) => (
                                    <li
                                        key={aprobacion.id}
                                        className="space-y-1 py-2"
                                    >
                                        <div className="flex items-center justify-between">
                                            <span className="font-medium">
                                                {aprobacion.decision}
                                            </span>
                                            <span className="text-muted-foreground">
                                                {formatFechaHora(aprobacion.decidido_en)}
                                            </span>
                                        </div>
                                        <p className="text-muted-foreground">
                                            {aprobacion.aprobado_por ??
                                                'Sistema'}
                                            {aprobacion.comentario &&
                                                ` — ${aprobacion.comentario}`}
                                        </p>
                                    </li>
                                ))}
                            </ul>
                        )}
                    </section>
                </div>

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

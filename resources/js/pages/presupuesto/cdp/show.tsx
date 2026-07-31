import { Head, Link, router, usePage } from '@inertiajs/react';
import { useState } from 'react';
import { EstadoBadge } from '@/components/pago-proveedores/estado-badge';
import { Badge } from '@/components/ui/badge';
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
import { formatFechaHora } from '@/lib/format';
import cdps from '@/routes/presupuesto/cdps';
import documentos from '@/routes/procesos/documentos';
import type { CertificadoDisponibilidadPresupuestaria } from '@/types/presupuesto';

type PageProps = {
    cdp: CertificadoDisponibilidadPresupuestaria;
};

export default function CdpShow() {
    const { cdp, auth } = usePage<PageProps>().props;

    const proceso = cdp.proceso;
    const estadoActual = proceso?.estado_actual.codigo;
    const puedeEditar =
        estadoActual === 'borrador' &&
        auth.permissions.includes('presupuesto.crear_cdp');

    const [procesando, setProcesando] = useState(false);
    const [errorTransicion, setErrorTransicion] = useState<string | null>(null);
    const [dialogAnularAbierto, setDialogAnularAbierto] = useState(false);
    const [comentarioAnulacion, setComentarioAnulacion] = useState('');

    function firmar() {
        setProcesando(true);
        setErrorTransicion(null);

        router.post(
            cdps.transiciones.store(cdp.id).url,
            { codigo: 'firmar' },
            {
                preserveScroll: true,
                onError: (errors) =>
                    setErrorTransicion(
                        (errors as Record<string, string>).transicion ?? null,
                    ),
                onFinish: () => setProcesando(false),
            },
        );
    }

    function anular() {
        setProcesando(true);

        router.post(
            cdps.anular(cdp.id).url,
            { comentario: comentarioAnulacion || null },
            {
                preserveScroll: true,
                onSuccess: () => setDialogAnularAbierto(false),
                onFinish: () => setProcesando(false),
            },
        );
    }

    const documentosVinculados = proceso?.documentos ?? [];

    return (
        <>
            <Head title={cdp.folio} />

            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <div className="flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <h1 className="font-mono text-xl font-semibold tracking-tight">
                            {cdp.folio}
                        </h1>
                        <p className="text-sm text-muted-foreground">
                            {cdp.denominacion} · {cdp.unidad_ejecutora}
                        </p>
                    </div>
                    <div className="flex items-center gap-2">
                        {puedeEditar && (
                            <Button
                                variant="outline"
                                size="sm"
                                onClick={() =>
                                    router.get(cdps.edit(cdp.id).url)
                                }
                            >
                                Editar
                            </Button>
                        )}
                        {proceso && (
                            <EstadoBadge estado={proceso.estado_actual} />
                        )}
                    </div>
                </div>

                {cdp.hubo_sobregiro_al_emitir && (
                    <div className="rounded-md border border-amber-500/40 bg-amber-500/10 px-3 py-2 text-sm text-amber-700 dark:text-amber-400">
                        Este CDP se firmó con sobregiro: el monto comprometido
                        superó el saldo disponible de la línea de presupuesto al
                        momento de emitirlo.
                    </div>
                )}

                {cdp.cdp_original && (
                    <div className="text-sm text-muted-foreground">
                        Anula a{' '}
                        <Link
                            href={cdps.show(cdp.cdp_original.id).url}
                            className="underline"
                        >
                            {cdp.cdp_original.folio}
                        </Link>
                    </div>
                )}

                <section className="grid grid-cols-2 gap-x-6 gap-y-3 rounded-xl border p-4 text-sm">
                    <div>
                        <span className="text-muted-foreground">
                            Cuenta presupuestaria:{' '}
                        </span>
                        {cdp.denominacion}
                    </div>
                    <div>
                        <span className="text-muted-foreground">
                            Monto IVA incluido:{' '}
                        </span>
                        <Monto valor={cdp.monto} />
                    </div>
                    <div>
                        <span className="text-muted-foreground">
                            Tipo de gasto:{' '}
                        </span>
                        {cdp.tipo_gasto === 'GO'
                            ? 'Gasto Operacional'
                            : 'Iniciativa'}
                        {cdp.codigo_iniciativa && ` (${cdp.codigo_iniciativa})`}
                    </div>
                    <div>
                        <span className="text-muted-foreground">
                            Carácter del gasto:{' '}
                        </span>
                        {cdp.caracter_gasto}
                    </div>
                    <div>
                        <span className="text-muted-foreground">
                            Moneda de compra:{' '}
                        </span>
                        {cdp.moneda_compra}
                        {cdp.moneda_compra !== 'CLP' &&
                            ` (${cdp.total_moneda_compra} × ${cdp.paridad ?? '—'})`}
                    </div>
                    <div>
                        <span className="text-muted-foreground">Validez: </span>
                        Año {cdp.anio_validez}
                    </div>
                    <div>
                        <span className="text-muted-foreground">
                            Medio de solicitud:{' '}
                        </span>
                        {cdp.medio_solicitud ?? '—'}
                    </div>
                    <div>
                        <span className="text-muted-foreground">
                            Requerimiento N°:{' '}
                        </span>
                        {cdp.requerimiento_numero ?? '—'}
                    </div>
                    {cdp.nombre_iniciativa && (
                        <div>
                            <span className="text-muted-foreground">
                                Nombre iniciativa:{' '}
                            </span>
                            {cdp.nombre_iniciativa}
                        </div>
                    )}
                    <div>
                        <span className="text-muted-foreground">
                            Adquisición vinculada:{' '}
                        </span>
                        {cdp.proceso_adquisicion?.codigo ?? '—'}
                    </div>
                    {cdp.firmado_en && (
                        <>
                            <div>
                                <span className="text-muted-foreground">
                                    Firmado por:{' '}
                                </span>
                                {cdp.firmado_por ?? '—'}
                            </div>
                            <div>
                                <span className="text-muted-foreground">
                                    Firmado el:{' '}
                                </span>
                                {formatFechaHora(cdp.firmado_en)}
                            </div>
                            {cdp.saldo_disponible_al_emitir && (
                                <div>
                                    <span className="text-muted-foreground">
                                        Saldo disponible al emitir:{' '}
                                    </span>
                                    <Monto
                                        valor={cdp.saldo_disponible_al_emitir}
                                    />
                                </div>
                            )}
                        </>
                    )}
                </section>

                <section className="space-y-3 rounded-xl border p-4">
                    <h2 className="text-base font-medium">Acciones</h2>

                    {errorTransicion && (
                        <p className="text-sm text-destructive">
                            {errorTransicion}
                        </p>
                    )}

                    <div className="flex flex-wrap gap-2">
                        {proceso?.transiciones_disponibles.map((transicion) => (
                            <Button
                                key={transicion.codigo}
                                disabled={procesando}
                                onClick={firmar}
                            >
                                {transicion.nombre}
                            </Button>
                        ))}

                        {estadoActual === 'firmado' && !cdp.cdp_original && (
                            <Button
                                variant="outline"
                                disabled={procesando}
                                onClick={() => setDialogAnularAbierto(true)}
                            >
                                Anular
                            </Button>
                        )}
                    </div>

                    {(!proceso ||
                        proceso.transiciones_disponibles.length === 0) &&
                        estadoActual !== 'firmado' && (
                            <p className="text-sm text-muted-foreground">
                                No hay transiciones disponibles desde el estado
                                actual.
                            </p>
                        )}
                </section>

                <section className="space-y-3 rounded-xl border p-4">
                    <h2 className="text-base font-medium">
                        Documento generado
                    </h2>

                    {documentosVinculados.length === 0 ? (
                        <p className="text-sm text-muted-foreground">
                            El PDF del CDP se genera al firmar.
                        </p>
                    ) : (
                        <ul className="divide-y text-sm">
                            {documentosVinculados.map((doc) => (
                                <li
                                    key={doc.vinculo_id}
                                    className="flex items-center justify-between py-2"
                                >
                                    <span>
                                        {doc.tipo_documento} ·{' '}
                                        {doc.nombre_archivo}
                                    </span>
                                    <a
                                        href={
                                            documentos.descargar({
                                                proceso: proceso!.id,
                                                documento: doc.documento_id,
                                            }).url
                                        }
                                        className="text-sm underline"
                                    >
                                        Descargar
                                    </a>
                                </li>
                            ))}
                        </ul>
                    )}
                </section>
            </div>

            <Dialog
                open={dialogAnularAbierto}
                onOpenChange={(open) => {
                    setDialogAnularAbierto(open);

                    if (!open) {
                        setComentarioAnulacion('');
                    }
                }}
            >
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Anular {cdp.folio}</DialogTitle>
                        <DialogDescription>
                            Se emitirá y firmará un CDP nuevo por el 100% del
                            monto en negativo, referenciando este folio. El CDP
                            original no cambia de estado.
                        </DialogDescription>
                    </DialogHeader>

                    <div className="grid gap-2">
                        <Label htmlFor="comentario-anulacion">
                            Comentario (opcional)
                        </Label>
                        <textarea
                            id="comentario-anulacion"
                            className="min-h-20 rounded-md border bg-background p-2 text-sm"
                            value={comentarioAnulacion}
                            onChange={(e) =>
                                setComentarioAnulacion(e.target.value)
                            }
                        />
                    </div>

                    <DialogFooter>
                        <Badge variant="destructive" className="mr-auto">
                            <Monto valor={`-${cdp.monto}`} />
                        </Badge>
                        <Button disabled={procesando} onClick={anular}>
                            Confirmar anulación
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </>
    );
}

CdpShow.layout = {
    breadcrumbs: [
        { title: 'CDP', href: cdps.index() },
        { title: 'Detalle', href: '#' },
    ],
};

import { Head, Link, usePage } from '@inertiajs/react';
import { TipoProcesoPagoStatusBadge } from '@/components/maestros/tipo-proceso-pago-status-badge';
import { Button } from '@/components/ui/button';
import tiposProcesoPago from '@/routes/maestros/tipos-proceso-pago';
import type { TipoProcesoPagoMaestro } from '@/types/maestros';

type PageProps = {
    tipoProcesoPago: TipoProcesoPagoMaestro;
};

export default function TiposProcesoPagoShow() {
    const { tipoProcesoPago } = usePage<PageProps>().props;

    return (
        <>
            <Head title={tipoProcesoPago.nombre} />

            <div className="flex h-full flex-1 flex-col gap-4 p-4">
                <div className="flex items-center justify-between">
                    <h1 className="text-xl font-semibold tracking-tight">
                        {tipoProcesoPago.nombre}
                    </h1>
                    <div className="flex items-center gap-2">
                        <Button asChild variant="outline">
                            <Link
                                href={
                                    tiposProcesoPago.edit(tipoProcesoPago.id)
                                        .url
                                }
                            >
                                Editar
                            </Link>
                        </Button>
                    </div>
                </div>

                <dl className="grid grid-cols-2 gap-4 rounded-xl border p-4 text-sm">
                    <div>
                        <dt className="text-muted-foreground">Código</dt>
                        <dd className="font-mono">{tipoProcesoPago.codigo}</dd>
                    </div>
                    <div>
                        <dt className="text-muted-foreground">Estado</dt>
                        <dd>
                            <TipoProcesoPagoStatusBadge
                                activo={tipoProcesoPago.activo}
                            />
                        </dd>
                    </div>
                </dl>
            </div>
        </>
    );
}

TiposProcesoPagoShow.layout = {
    breadcrumbs: [
        { title: 'Tipos de Proceso de Pago', href: tiposProcesoPago.index() },
        { title: 'Detalle', href: '#' },
    ],
};

import { Head, Link, usePage } from '@inertiajs/react';
import { CcostoStatusBadge } from '@/components/maestros/ccosto-status-badge';
import { Button } from '@/components/ui/button';
import ccostos from '@/routes/maestros/ccostos';
import type { Ccosto } from '@/types/maestros';

type PageProps = {
    ccosto: Ccosto;
};

export default function CcostosShow() {
    const { ccosto } = usePage<PageProps>().props;

    return (
        <>
            <Head title={ccosto.nombre} />

            <div className="flex h-full flex-1 flex-col gap-4 p-4">
                <div className="flex items-center justify-between">
                    <h1 className="text-xl font-semibold tracking-tight">
                        {ccosto.nombre}
                    </h1>
                    <div className="flex items-center gap-2">
                        <Button asChild variant="outline">
                            <Link href={ccostos.edit(ccosto.id).url}>
                                Editar
                            </Link>
                        </Button>
                    </div>
                </div>

                <dl className="grid grid-cols-2 gap-4 rounded-xl border p-4 text-sm">
                    <div>
                        <dt className="text-muted-foreground">Código</dt>
                        <dd className="font-mono">{ccosto.codigo}</dd>
                    </div>
                    <div>
                        <dt className="text-muted-foreground">Estado</dt>
                        <dd>
                            <CcostoStatusBadge activo={ccosto.activo} />
                        </dd>
                    </div>
                    <div>
                        <dt className="text-muted-foreground">
                            Centro financiero
                        </dt>
                        <dd>{ccosto.cfinanciero.nombre}</dd>
                    </div>
                    <div>
                        <dt className="text-muted-foreground">
                            Código de edificio
                        </dt>
                        <dd>{ccosto.cod_edificio ?? '—'}</dd>
                    </div>
                </dl>
            </div>
        </>
    );
}

CcostosShow.layout = {
    breadcrumbs: [
        { title: 'Centros de Costos', href: ccostos.index() },
        { title: 'Detalle', href: '#' },
    ],
};

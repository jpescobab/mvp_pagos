import { Head, Link, usePage } from '@inertiajs/react';
import { CfinancieroStatusBadge } from '@/components/maestros/cfinanciero-status-badge';
import { Button } from '@/components/ui/button';
import cfinancieros from '@/routes/maestros/cfinancieros';
import type { Cfinanciero } from '@/types/maestros';

type PageProps = {
    cfinanciero: Cfinanciero;
};

export default function CfinancierosShow() {
    const { cfinanciero } = usePage<PageProps>().props;

    return (
        <>
            <Head title={cfinanciero.nombre} />

            <div className="flex h-full flex-1 flex-col gap-4 p-4">
                <div className="flex items-center justify-between">
                    <h1 className="text-xl font-semibold tracking-tight">
                        {cfinanciero.nombre}
                    </h1>
                    <div className="flex items-center gap-2">
                        <Button asChild variant="outline">
                            <Link href={cfinancieros.edit(cfinanciero.id).url}>
                                Editar
                            </Link>
                        </Button>
                    </div>
                </div>

                <dl className="grid grid-cols-2 gap-4 rounded-xl border p-4 text-sm">
                    <div>
                        <dt className="text-muted-foreground">Código</dt>
                        <dd className="font-mono">{cfinanciero.codigo}</dd>
                    </div>
                    <div>
                        <dt className="text-muted-foreground">Estado</dt>
                        <dd>
                            <CfinancieroStatusBadge
                                activo={cfinanciero.activo}
                            />
                        </dd>
                    </div>
                    <div className="col-span-2">
                        <dt className="text-muted-foreground">Jurisdicción</dt>
                        <dd>{cfinanciero.jurisdiccion.nombre}</dd>
                    </div>
                </dl>
            </div>
        </>
    );
}

CfinancierosShow.layout = {
    breadcrumbs: [
        { title: 'Centros Financieros', href: cfinancieros.index() },
        { title: 'Detalle', href: '#' },
    ],
};

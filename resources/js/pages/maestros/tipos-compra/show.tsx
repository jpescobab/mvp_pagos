import { Head, Link, usePage } from '@inertiajs/react';
import { TipoCompraStatusBadge } from '@/components/maestros/tipo-compra-status-badge';
import { Button } from '@/components/ui/button';
import tiposCompra from '@/routes/maestros/tipos-compra';
import type { TipoCompraMaestro } from '@/types/maestros';

type PageProps = {
    tipoCompra: TipoCompraMaestro;
};

export default function TiposCompraShow() {
    const { tipoCompra } = usePage<PageProps>().props;

    return (
        <>
            <Head title={tipoCompra.nombre} />

            <div className="flex h-full flex-1 flex-col gap-4 p-4">
                <div className="flex items-center justify-between">
                    <h1 className="text-xl font-semibold tracking-tight">
                        {tipoCompra.nombre}
                    </h1>
                    <div className="flex items-center gap-2">
                        <Button asChild variant="outline">
                            <Link href={tiposCompra.edit(tipoCompra.id).url}>
                                Editar
                            </Link>
                        </Button>
                    </div>
                </div>

                <dl className="grid grid-cols-2 gap-4 rounded-xl border p-4 text-sm">
                    <div>
                        <dt className="text-muted-foreground">Código</dt>
                        <dd className="font-mono">{tipoCompra.codigo}</dd>
                    </div>
                    <div>
                        <dt className="text-muted-foreground">Estado</dt>
                        <dd>
                            <TipoCompraStatusBadge activo={tipoCompra.activo} />
                        </dd>
                    </div>
                </dl>
            </div>
        </>
    );
}

TiposCompraShow.layout = {
    breadcrumbs: [
        { title: 'Tipos de Compra', href: tiposCompra.index() },
        { title: 'Detalle', href: '#' },
    ],
};

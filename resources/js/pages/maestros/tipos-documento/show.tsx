import { Head, Link, usePage } from '@inertiajs/react';
import { TipoDocumentoStatusBadge } from '@/components/maestros/tipo-documento-status-badge';
import { Button } from '@/components/ui/button';
import tiposDocumento from '@/routes/maestros/tipos-documento';
import type { TipoDocumentoMaestro } from '@/types/maestros';

type PageProps = {
    tipoDocumento: TipoDocumentoMaestro;
};

export default function TiposDocumentoShow() {
    const { tipoDocumento } = usePage<PageProps>().props;

    return (
        <>
            <Head title={tipoDocumento.nombre} />

            <div className="flex h-full flex-1 flex-col gap-4 p-4">
                <div className="flex items-center justify-between">
                    <h1 className="text-xl font-semibold tracking-tight">
                        {tipoDocumento.nombre}
                    </h1>
                    <div className="flex items-center gap-2">
                        <Button asChild variant="outline">
                            <Link
                                href={tiposDocumento.edit(tipoDocumento.id).url}
                            >
                                Editar
                            </Link>
                        </Button>
                    </div>
                </div>

                <dl className="grid grid-cols-2 gap-4 rounded-xl border p-4 text-sm">
                    <div>
                        <dt className="text-muted-foreground">Código</dt>
                        <dd className="font-mono">{tipoDocumento.codigo}</dd>
                    </div>
                    <div>
                        <dt className="text-muted-foreground">Estado</dt>
                        <dd>
                            <TipoDocumentoStatusBadge
                                activo={tipoDocumento.activo}
                            />
                        </dd>
                    </div>
                    <div className="col-span-2">
                        <dt className="text-muted-foreground">Descripción</dt>
                        <dd>{tipoDocumento.descripcion ?? '—'}</dd>
                    </div>
                </dl>
            </div>
        </>
    );
}

TiposDocumentoShow.layout = {
    breadcrumbs: [
        { title: 'Tipos de Documento', href: tiposDocumento.index() },
        { title: 'Detalle', href: '#' },
    ],
};

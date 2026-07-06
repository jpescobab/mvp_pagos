import { Head, Link, usePage } from '@inertiajs/react';
import { ClienteMedidorStatusBadge } from '@/components/maestros/cliente-medidor-status-badge';
import { Button } from '@/components/ui/button';
import clientesMedidores from '@/routes/maestros/clientes-medidores';
import type { ClienteMedidor } from '@/types/maestros';

type PageProps = {
    clienteMedidor: ClienteMedidor;
};

export default function ClientesMedidoresShow() {
    const { clienteMedidor } = usePage<PageProps>().props;

    return (
        <>
            <Head title={clienteMedidor.numero_cliente} />

            <div className="flex h-full flex-1 flex-col gap-4 p-4">
                <div className="flex items-center justify-between">
                    <h1 className="text-xl font-semibold tracking-tight">
                        {clienteMedidor.numero_cliente}
                    </h1>
                    <div className="flex items-center gap-2">
                        <Button asChild variant="outline">
                            <Link
                                href={
                                    clientesMedidores.edit(clienteMedidor.id)
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
                        <dt className="text-muted-foreground">
                            Centro de costo
                        </dt>
                        <dd>
                            {clienteMedidor.ccosto.codigo} ·{' '}
                            {clienteMedidor.ccosto.nombre}
                        </dd>
                    </div>
                    <div>
                        <dt className="text-muted-foreground">
                            Proveedor de servicio
                        </dt>
                        <dd>{clienteMedidor.proveedor?.nombre ?? '—'}</dd>
                    </div>
                    <div>
                        <dt className="text-muted-foreground">
                            Tipo de suministro
                        </dt>
                        <dd>{clienteMedidor.tipo_suministro}</dd>
                    </div>
                    <div>
                        <dt className="text-muted-foreground">Estado</dt>
                        <dd>
                            <ClienteMedidorStatusBadge
                                activo={clienteMedidor.activo}
                            />
                        </dd>
                    </div>
                    <div className="col-span-2">
                        <dt className="text-muted-foreground">
                            Dirección de suministro
                        </dt>
                        <dd>{clienteMedidor.direccion_suministro ?? '—'}</dd>
                    </div>
                </dl>
            </div>
        </>
    );
}

ClientesMedidoresShow.layout = {
    breadcrumbs: [
        { title: 'Clientes Medidores', href: clientesMedidores.index() },
        { title: 'Detalle', href: '#' },
    ],
};

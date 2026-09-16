import { Head, Link, usePage } from '@inertiajs/react';
import { ClienteMedidorStatusBadge } from '@/components/maestros/cliente-medidor-status-badge';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Monto } from '@/components/ui/monto';
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

                <div className="rounded-xl border p-4">
                    <h2 className="mb-3 text-sm font-semibold tracking-tight">
                        Historial de consumo
                    </h2>

                    {!clienteMedidor.consumos ||
                    clienteMedidor.consumos.length === 0 ? (
                        <p className="text-sm text-muted-foreground">
                            Este medidor todavía no tiene consumos registrados.
                        </p>
                    ) : (
                        <table className="w-full text-sm">
                            <thead>
                                <tr className="border-b text-left text-xs text-muted-foreground">
                                    <th className="py-2 font-medium">
                                        Período
                                    </th>
                                    <th className="py-2 font-medium">
                                        Documento
                                    </th>
                                    <th className="py-2 font-medium">
                                        Consumo
                                    </th>
                                    <th className="py-2 font-medium">
                                        Monto total
                                    </th>
                                    <th className="py-2 font-medium">
                                        Lectura
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                {clienteMedidor.consumos.map((consumo) => (
                                    <tr key={consumo.id} className="border-b">
                                        <td className="py-2">
                                            {consumo.fecha_inicio_lectura} —{' '}
                                            {consumo.fecha_fin_lectura}
                                        </td>
                                        <td className="py-2">
                                            {consumo.numero_documento}
                                        </td>
                                        <td className="py-2">
                                            <Monto
                                                valor={consumo.consumo}
                                                variante="numero"
                                            />
                                        </td>
                                        <td className="py-2">
                                            <Monto
                                                valor={consumo.monto_total}
                                            />
                                        </td>
                                        <td className="py-2">
                                            {consumo.lectura_estimada ? (
                                                <Badge variant="secondary">
                                                    Estimada
                                                </Badge>
                                            ) : (
                                                '—'
                                            )}
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    )}
                </div>
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

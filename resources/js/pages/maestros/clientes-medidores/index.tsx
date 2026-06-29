import { Head, usePage } from '@inertiajs/react';
import clientesMedidores from '@/routes/maestros/clientes-medidores';
import type { ClienteMedidor } from '@/types/maestros';

type PageProps = {
    clientes: ClienteMedidor[];
};

export default function ClientesMedidoresIndex() {
    const { clientes } = usePage<PageProps>().props;

    return (
        <>
            <Head title="Clientes Medidores" />

            <div className="flex h-full flex-1 flex-col gap-4 p-4">
                <h1 className="text-xl font-semibold tracking-tight">
                    Clientes Medidores
                </h1>

                <div className="overflow-hidden rounded-xl border">
                    <table className="w-full text-sm">
                        <thead className="bg-muted/50 text-left text-muted-foreground">
                            <tr>
                                <th className="px-4 py-2 font-medium">
                                    N.º de cliente
                                </th>
                                <th className="px-4 py-2 font-medium">
                                    Proveedor
                                </th>
                                <th className="px-4 py-2 font-medium">
                                    Centro de costo
                                </th>
                                <th className="px-4 py-2 font-medium">
                                    Tipo de suministro
                                </th>
                                <th className="px-4 py-2 font-medium">
                                    Dirección
                                </th>
                                <th className="px-4 py-2 font-medium">
                                    Estado
                                </th>
                            </tr>
                        </thead>
                        <tbody className="divide-y">
                            {clientes.length === 0 && (
                                <tr>
                                    <td
                                        colSpan={6}
                                        className="px-4 py-6 text-center text-muted-foreground"
                                    >
                                        Sin clientes medidores registrados
                                        todavía.
                                    </td>
                                </tr>
                            )}
                            {clientes.map((cliente) => (
                                <tr key={cliente.id}>
                                    <td className="px-4 py-2 font-mono">
                                        {cliente.numero_cliente}
                                    </td>
                                    <td className="px-4 py-2">
                                        {cliente.proveedor?.nombre ?? '—'}
                                    </td>
                                    <td className="px-4 py-2 text-muted-foreground">
                                        {cliente.ccosto.codigo} ·{' '}
                                        {cliente.ccosto.nombre}
                                    </td>
                                    <td className="px-4 py-2">
                                        {cliente.tipo_suministro}
                                    </td>
                                    <td className="px-4 py-2 text-muted-foreground">
                                        {cliente.direccion_suministro ?? '—'}
                                    </td>
                                    <td className="px-4 py-2">
                                        {cliente.activo ? (
                                            <span className="text-green-600">
                                                Activo
                                            </span>
                                        ) : (
                                            <span className="text-muted-foreground">
                                                Inactivo
                                            </span>
                                        )}
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            </div>
        </>
    );
}

ClientesMedidoresIndex.layout = {
    breadcrumbs: [
        {
            title: 'Clientes Medidores',
            href: clientesMedidores.index(),
        },
    ],
};

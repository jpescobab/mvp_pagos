import { Head, Link, router, usePage } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import { ClienteMedidorActionsMenu } from '@/components/maestros/cliente-medidor-actions-menu';
import { ClienteMedidorStatusBadge } from '@/components/maestros/cliente-medidor-status-badge';
import { Avatar, AvatarFallback } from '@/components/ui/avatar';
import { Input } from '@/components/ui/input';
import { useInitials } from '@/hooks/use-initials';
import clientesMedidores from '@/routes/maestros/clientes-medidores';
import type { ClienteMedidor } from '@/types/maestros';
import type { Paginated } from '@/types/pago-proveedores';

type PageProps = {
    clientes: Paginated<ClienteMedidor>;
    q: string | null;
};

export default function ClientesMedidoresIndex() {
    const { clientes: pagina, q } = usePage<PageProps>().props;
    const [termino, setTermino] = useState(q ?? '');
    const getInitials = useInitials();

    useEffect(() => {
        const timeout = setTimeout(() => {
            if (termino === (q ?? '')) {
                return;
            }

            router.get(
                clientesMedidores.index().url,
                termino === '' ? {} : { q: termino },
                { preserveState: true, preserveScroll: true },
            );
        }, 300);

        return () => clearTimeout(timeout);
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [termino]);

    return (
        <>
            <Head title="Clientes Medidores" />

            <div className="flex h-full flex-1 flex-col gap-4 p-4">
                <div className="flex items-center justify-between">
                    <h1 className="text-xl font-semibold tracking-tight">
                        Clientes Medidores
                    </h1>
                    <Input
                        placeholder="Buscar por N.º de cliente, proveedor o centro de costo…"
                        value={termino}
                        onChange={(e) => setTermino(e.target.value)}
                        className="w-80"
                    />
                </div>

                <div className="overflow-x-auto rounded-xl border">
                    <table className="w-full table-fixed text-xs">
                        <thead className="bg-muted/50 text-left text-[10px] tracking-wide text-muted-foreground uppercase">
                            <tr>
                                <th className="w-[28%] px-2.5 py-1 font-medium">
                                    Cliente
                                </th>
                                <th className="hidden w-[20%] px-2.5 py-1 font-medium md:table-cell">
                                    Proveedor
                                </th>
                                <th className="hidden w-[20%] px-2.5 py-1 font-medium lg:table-cell">
                                    Centro de costo
                                </th>
                                <th className="hidden w-[17%] px-2.5 py-1 font-medium lg:table-cell">
                                    Dirección
                                </th>
                                <th className="w-[10%] px-2.5 py-1 font-medium">
                                    Estado
                                </th>
                                <th className="w-[5%] px-2.5 py-1 font-medium"></th>
                            </tr>
                        </thead>
                        <tbody className="divide-y">
                            {pagina.data.length === 0 && (
                                <tr>
                                    <td
                                        colSpan={6}
                                        className="px-2.5 py-5 text-center text-muted-foreground"
                                    >
                                        Sin clientes medidores que coincidan.
                                    </td>
                                </tr>
                            )}
                            {pagina.data.map((cliente) => (
                                <tr
                                    key={cliente.id}
                                    className="hover:bg-muted/30"
                                >
                                    <td className="px-2.5 py-1">
                                        <div className="flex items-center gap-2">
                                            <Avatar className="size-6 shrink-0">
                                                <AvatarFallback className="bg-accent text-[10px] font-semibold text-accent-foreground">
                                                    {getInitials(
                                                        cliente.numero_cliente,
                                                    )}
                                                </AvatarFallback>
                                            </Avatar>
                                            <div className="min-w-0">
                                                <div
                                                    className="truncate font-mono font-medium"
                                                    title={
                                                        cliente.numero_cliente
                                                    }
                                                >
                                                    {cliente.numero_cliente}
                                                </div>
                                                <div className="truncate text-[10px] text-muted-foreground">
                                                    {cliente.tipo_suministro}
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td
                                        className="hidden truncate px-2.5 py-1 text-muted-foreground md:table-cell"
                                        title={
                                            cliente.proveedor?.nombre ??
                                            undefined
                                        }
                                    >
                                        {cliente.proveedor?.nombre ?? '—'}
                                    </td>
                                    <td
                                        className="hidden truncate px-2.5 py-1 text-muted-foreground lg:table-cell"
                                        title={`${cliente.ccosto.codigo} · ${cliente.ccosto.nombre}`}
                                    >
                                        {cliente.ccosto.codigo} ·{' '}
                                        {cliente.ccosto.nombre}
                                    </td>
                                    <td
                                        className="hidden truncate px-2.5 py-1 text-muted-foreground lg:table-cell"
                                        title={
                                            cliente.direccion_suministro ??
                                            undefined
                                        }
                                    >
                                        {cliente.direccion_suministro ?? '—'}
                                    </td>
                                    <td className="px-2.5 py-1">
                                        <ClienteMedidorStatusBadge
                                            activo={cliente.activo}
                                        />
                                    </td>
                                    <td className="px-2.5 py-1 text-right">
                                        <ClienteMedidorActionsMenu />
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>

                <div className="flex items-center justify-between text-sm text-muted-foreground">
                    <span>
                        Mostrando {pagina.meta.from ?? 0}–{pagina.meta.to ?? 0}{' '}
                        de {pagina.meta.total}
                    </span>
                    <div className="flex gap-2">
                        <Link
                            href={pagina.links.prev ?? '#'}
                            className={
                                pagina.links.prev
                                    ? 'underline'
                                    : 'pointer-events-none opacity-50'
                            }
                        >
                            Anterior
                        </Link>
                        <Link
                            href={pagina.links.next ?? '#'}
                            className={
                                pagina.links.next
                                    ? 'underline'
                                    : 'pointer-events-none opacity-50'
                            }
                        >
                            Siguiente
                        </Link>
                    </div>
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

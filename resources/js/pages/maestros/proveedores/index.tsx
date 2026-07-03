import { Head, Link, router, usePage } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import { ProveedorActionsMenu } from '@/components/maestros/proveedor-actions-menu';
import { ProveedorStatusBadge } from '@/components/maestros/proveedor-status-badge';
import { Avatar, AvatarFallback } from '@/components/ui/avatar';
import { Input } from '@/components/ui/input';
import { useInitials } from '@/hooks/use-initials';
import proveedores from '@/routes/maestros/proveedores';
import type { Proveedor } from '@/types/maestros';
import type { Paginated } from '@/types/pago-proveedores';

type PageProps = {
    proveedores: Paginated<Proveedor>;
    q: string | null;
};

export default function ProveedoresIndex() {
    const { proveedores: pagina, q } = usePage<PageProps>().props;
    const [termino, setTermino] = useState(q ?? '');
    const getInitials = useInitials();

    useEffect(() => {
        const timeout = setTimeout(() => {
            if (termino === (q ?? '')) {
                return;
            }

            router.get(
                proveedores.index().url,
                termino === '' ? {} : { q: termino },
                { preserveState: true, preserveScroll: true },
            );
        }, 300);

        return () => clearTimeout(timeout);
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [termino]);

    return (
        <>
            <Head title="Proveedores" />

            <div className="flex h-full flex-1 flex-col gap-4 p-4">
                <div className="flex items-center justify-between">
                    <h1 className="text-xl font-semibold tracking-tight">
                        Proveedores
                    </h1>
                    <Input
                        placeholder="Buscar por RUT o nombre…"
                        value={termino}
                        onChange={(e) => setTermino(e.target.value)}
                        className="w-72"
                    />
                </div>

                <div className="overflow-hidden rounded-xl border">
                    <table className="w-full text-sm">
                        <thead className="bg-muted/50 text-left text-xs text-muted-foreground uppercase">
                            <tr>
                                <th className="px-3 py-1.5 font-medium">
                                    Proveedor
                                </th>
                                <th className="hidden px-3 py-1.5 font-medium md:table-cell">
                                    Correo
                                </th>
                                <th className="hidden px-3 py-1.5 font-medium lg:table-cell">
                                    Dirección
                                </th>
                                <th className="hidden px-3 py-1.5 font-medium lg:table-cell">
                                    Contacto
                                </th>
                                <th className="px-3 py-1.5 font-medium">
                                    Estado
                                </th>
                                <th className="px-3 py-1.5 font-medium"></th>
                            </tr>
                        </thead>
                        <tbody className="divide-y">
                            {pagina.data.length === 0 && (
                                <tr>
                                    <td
                                        colSpan={6}
                                        className="px-3 py-6 text-center text-muted-foreground"
                                    >
                                        Sin proveedores que coincidan.
                                    </td>
                                </tr>
                            )}
                            {pagina.data.map((proveedor) => (
                                <tr
                                    key={proveedor.id}
                                    className="hover:bg-muted/30"
                                >
                                    <td className="px-3 py-1.5">
                                        <div className="flex items-center gap-2.5">
                                            <Avatar className="size-7">
                                                <AvatarFallback className="bg-accent text-[11px] font-semibold text-accent-foreground">
                                                    {getInitials(
                                                        proveedor.nombre,
                                                    )}
                                                </AvatarFallback>
                                            </Avatar>
                                            <div className="min-w-0">
                                                <div className="truncate font-medium">
                                                    {proveedor.nombre}
                                                </div>
                                                <div className="font-mono text-xs text-muted-foreground">
                                                    {proveedor.rutproveedor}
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td className="hidden px-3 py-1.5 text-muted-foreground md:table-cell">
                                        {proveedor.correo ?? '—'}
                                    </td>
                                    <td className="hidden px-3 py-1.5 text-muted-foreground lg:table-cell">
                                        {proveedor.direccion ?? '—'}
                                    </td>
                                    <td className="hidden px-3 py-1.5 text-muted-foreground lg:table-cell">
                                        {proveedor.contacto ?? '—'}
                                    </td>
                                    <td className="px-3 py-1.5">
                                        <ProveedorStatusBadge
                                            activo={proveedor.activo}
                                        />
                                    </td>
                                    <td className="px-3 py-1.5 text-right">
                                        <ProveedorActionsMenu />
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

ProveedoresIndex.layout = {
    breadcrumbs: [
        {
            title: 'Proveedores',
            href: proveedores.index(),
        },
    ],
};

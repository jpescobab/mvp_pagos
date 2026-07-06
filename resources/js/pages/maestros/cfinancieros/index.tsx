import { Head, Link, router, usePage } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import { CfinancieroActionsMenu } from '@/components/maestros/cfinanciero-actions-menu';
import { CfinancieroStatusBadge } from '@/components/maestros/cfinanciero-status-badge';
import { Avatar, AvatarFallback } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { useInitials } from '@/hooks/use-initials';
import { formatNumero } from '@/lib/format';
import cfinancieros from '@/routes/maestros/cfinancieros';
import type { Cfinanciero } from '@/types/maestros';
import type { Paginated } from '@/types/pago-proveedores';

type PageProps = {
    cfinancieros: Paginated<Cfinanciero>;
    q: string | null;
};

export default function CfinancierosIndex() {
    const { cfinancieros: pagina, q } = usePage<PageProps>().props;
    const [termino, setTermino] = useState(q ?? '');
    const getInitials = useInitials();

    useEffect(() => {
        const timeout = setTimeout(() => {
            if (termino === (q ?? '')) {
                return;
            }

            router.get(
                cfinancieros.index().url,
                termino === '' ? {} : { q: termino },
                { preserveState: true, preserveScroll: true },
            );
        }, 300);

        return () => clearTimeout(timeout);
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [termino]);

    return (
        <>
            <Head title="Centros Financieros" />

            <div className="flex h-full flex-1 flex-col gap-4 p-4">
                <div className="flex items-center justify-between">
                    <h1 className="text-xl font-semibold tracking-tight">
                        Centros Financieros
                    </h1>
                    <div className="flex items-center gap-2">
                        <Input
                            placeholder="Buscar por código o nombre…"
                            value={termino}
                            onChange={(e) => setTermino(e.target.value)}
                            className="w-72"
                        />
                        <Button asChild>
                            <Link href={cfinancieros.create().url}>
                                Nuevo centro financiero
                            </Link>
                        </Button>
                    </div>
                </div>

                <div className="overflow-x-auto rounded-xl border">
                    <table className="w-full table-fixed text-xs">
                        <thead className="bg-muted/50 text-left text-[10px] tracking-wide text-muted-foreground uppercase">
                            <tr>
                                <th className="w-[35%] px-2.5 py-1 font-medium">
                                    Centro financiero
                                </th>
                                <th className="w-[35%] px-2.5 py-1 font-medium">
                                    Jurisdicción
                                </th>
                                <th className="w-[15%] px-2.5 py-1 font-medium">
                                    Estado
                                </th>
                                <th className="w-[15%] px-2.5 py-1 font-medium"></th>
                            </tr>
                        </thead>
                        <tbody className="divide-y">
                            {pagina.data.length === 0 && (
                                <tr>
                                    <td
                                        colSpan={4}
                                        className="px-2.5 py-5 text-center text-muted-foreground"
                                    >
                                        Sin centros financieros que coincidan.
                                    </td>
                                </tr>
                            )}
                            {pagina.data.map((cfinanciero) => (
                                <tr
                                    key={cfinanciero.id}
                                    className="hover:bg-muted/30"
                                >
                                    <td className="px-2.5 py-1">
                                        <div className="flex items-center gap-2">
                                            <Avatar className="size-6 shrink-0">
                                                <AvatarFallback className="bg-accent text-[10px] font-semibold text-accent-foreground">
                                                    {getInitials(
                                                        cfinanciero.nombre,
                                                    )}
                                                </AvatarFallback>
                                            </Avatar>
                                            <div className="min-w-0">
                                                <div
                                                    className="truncate font-medium"
                                                    title={cfinanciero.nombre}
                                                >
                                                    {cfinanciero.nombre}
                                                </div>
                                                <div className="truncate font-mono text-[10px] text-muted-foreground">
                                                    {cfinanciero.codigo}
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td
                                        className="truncate px-2.5 py-1 text-muted-foreground"
                                        title={cfinanciero.jurisdiccion.nombre}
                                    >
                                        {cfinanciero.jurisdiccion.nombre}
                                    </td>
                                    <td className="px-2.5 py-1">
                                        <CfinancieroStatusBadge
                                            activo={cfinanciero.activo}
                                        />
                                    </td>
                                    <td className="px-2.5 py-1 text-right">
                                        <CfinancieroActionsMenu
                                            cfinanciero={cfinanciero}
                                        />
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>

                <div className="flex items-center justify-between text-sm text-muted-foreground">
                    <span>
                        Mostrando {formatNumero(pagina.meta.from ?? 0)}–
                        {formatNumero(pagina.meta.to ?? 0)}{' '}
                        de {formatNumero(pagina.meta.total)}
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

CfinancierosIndex.layout = {
    breadcrumbs: [
        {
            title: 'Centros Financieros',
            href: cfinancieros.index(),
        },
    ],
};

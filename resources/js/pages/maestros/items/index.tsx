import { Head, Link, router, usePage } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import { ItemActionsMenu } from '@/components/maestros/item-actions-menu';
import { ItemStatusBadge } from '@/components/maestros/item-status-badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { formatNumero } from '@/lib/format';
import items from '@/routes/maestros/items';
import type { ItemPresupuestario } from '@/types/maestros';
import type { Paginated } from '@/types/pago-proveedores';

type PageProps = {
    items: Paginated<ItemPresupuestario>;
    q: string | null;
};

export default function ItemsIndex() {
    const { items: pagina, q } = usePage<PageProps>().props;
    const [termino, setTermino] = useState(q ?? '');

    useEffect(() => {
        const timeout = setTimeout(() => {
            if (termino === (q ?? '')) {
                return;
            }

            router.get(
                items.index().url,
                termino === '' ? {} : { q: termino },
                { preserveState: true, preserveScroll: true },
            );
        }, 300);

        return () => clearTimeout(timeout);
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [termino]);

    return (
        <>
            <Head title="Ítems Presupuestarios" />

            <div className="flex h-full flex-1 flex-col gap-4 p-4">
                <div className="flex items-center justify-between">
                    <h1 className="text-xl font-semibold tracking-tight">
                        Ítems Presupuestarios
                    </h1>
                    <div className="flex items-center gap-2">
                        <Input
                            placeholder="Buscar por código o nombre…"
                            value={termino}
                            onChange={(e) => setTermino(e.target.value)}
                            className="w-72"
                        />
                        <Button asChild>
                            <Link href={items.create().url}>Nuevo ítem</Link>
                        </Button>
                    </div>
                </div>

                <div className="overflow-x-auto rounded-xl border">
                    <table className="w-full table-fixed text-xs">
                        <thead className="bg-muted/50 text-left text-[10px] tracking-wide text-muted-foreground uppercase">
                            <tr>
                                <th className="w-[25%] px-2.5 py-1 font-medium">
                                    Código
                                </th>
                                <th className="w-[30%] px-2.5 py-1 font-medium">
                                    Nombre
                                </th>
                                <th className="hidden w-[30%] px-2.5 py-1 font-medium md:table-cell">
                                    Descripción
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
                                        colSpan={5}
                                        className="px-2.5 py-5 text-center text-muted-foreground"
                                    >
                                        Sin ítems presupuestarios que coincidan.
                                    </td>
                                </tr>
                            )}
                            {pagina.data.map((item) => (
                                <tr key={item.id} className="hover:bg-muted/30">
                                    <td className="truncate px-2.5 py-1 font-mono text-[10px]">
                                        {item.codigo}
                                    </td>
                                    <td
                                        className="truncate px-2.5 py-1 font-medium"
                                        title={item.nombre}
                                    >
                                        {item.nombre}
                                    </td>
                                    <td
                                        className="hidden truncate px-2.5 py-1 text-muted-foreground md:table-cell"
                                        title={item.descripcion ?? undefined}
                                    >
                                        {item.descripcion ?? '—'}
                                    </td>
                                    <td className="px-2.5 py-1">
                                        <ItemStatusBadge activo={item.activo} />
                                    </td>
                                    <td className="px-2.5 py-1 text-right">
                                        <ItemActionsMenu item={item} />
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>

                <div className="flex items-center justify-between text-sm text-muted-foreground">
                    <span>
                        Mostrando {formatNumero(pagina.meta.from ?? 0)}–
                        {formatNumero(pagina.meta.to ?? 0)} de{' '}
                        {formatNumero(pagina.meta.total)}
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

ItemsIndex.layout = {
    breadcrumbs: [
        {
            title: 'Ítems Presupuestarios',
            href: items.index(),
        },
    ],
};

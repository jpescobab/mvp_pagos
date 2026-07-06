import { Head, Link, router, usePage } from '@inertiajs/react';
import { MoreHorizontal } from 'lucide-react';
import { useEffect, useState } from 'react';
import { OrdenCompraEstadoBadge } from '@/components/mercado-publico/orden-compra-estado-badge';
import { Avatar, AvatarFallback } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Input } from '@/components/ui/input';
import { Monto } from '@/components/ui/monto';
import { useInitials } from '@/hooks/use-initials';
import { formatNumero } from '@/lib/format';
import ordenesCompraMp from '@/routes/adquisiciones/ordenes_compra_mp';
import type { OrdenCompraMercadoPublico } from '@/types/adquisiciones';
import type { Paginated } from '@/types/pago-proveedores';

type PageProps = {
    ordenes: Paginated<OrdenCompraMercadoPublico>;
    q: string | null;
};

export default function OrdenesCompraMercadoPublicoIndex() {
    const { ordenes: pagina, q } = usePage<PageProps>().props;
    const [termino, setTermino] = useState(q ?? '');
    const getInitials = useInitials();

    useEffect(() => {
        const timeout = setTimeout(() => {
            if (termino === (q ?? '')) {
                return;
            }

            router.get(
                ordenesCompraMp.index().url,
                termino === '' ? {} : { q: termino },
                { preserveState: true, preserveScroll: true },
            );
        }, 300);

        return () => clearTimeout(timeout);
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [termino]);

    return (
        <>
            <Head title="Órdenes de Compra (Mercado Público)" />

            <div className="flex h-full flex-1 flex-col gap-4 p-4">
                <div className="flex items-center justify-between">
                    <h1 className="text-xl font-semibold tracking-tight">
                        Órdenes de Compra (Mercado Público)
                    </h1>
                    <div className="flex items-center gap-2">
                        <Input
                            placeholder="Buscar en el listado por código…"
                            value={termino}
                            onChange={(e) => setTermino(e.target.value)}
                            className="w-72"
                        />
                        <Button variant="outline" asChild>
                            <Link
                                href={ordenesCompraMp.index.url({
                                    query: { nuevo: 1 },
                                })}
                            >
                                Consultar O.C a Mercado Público
                            </Link>
                        </Button>
                    </div>
                </div>

                <div className="overflow-x-auto rounded-xl border">
                    <table className="w-full table-fixed text-xs">
                        <thead className="bg-muted/50 text-left text-[10px] tracking-wide text-muted-foreground uppercase">
                            <tr>
                                <th className="w-[18%] px-2.5 py-1 font-medium">
                                    Orden de compra
                                </th>
                                <th className="w-[18%] px-2.5 py-1 font-medium">
                                    Proveedor
                                </th>
                                <th className="hidden w-[14%] px-2.5 py-1 font-medium md:table-cell">
                                    RUT proveedor
                                </th>
                                <th className="w-[12%] px-2.5 py-1 text-right font-medium">
                                    Monto total
                                </th>
                                <th className="w-[12%] px-2.5 py-1 font-medium">
                                    Estado
                                </th>
                                <th className="hidden w-[16%] px-2.5 py-1 font-medium lg:table-cell">
                                    Adquisición vinculada
                                </th>
                                <th className="w-[10%] px-2.5 py-1 font-medium"></th>
                            </tr>
                        </thead>
                        <tbody className="divide-y">
                            {pagina.data.length === 0 && (
                                <tr>
                                    <td
                                        colSpan={7}
                                        className="px-2.5 py-5 text-center text-muted-foreground"
                                    >
                                        Sin Órdenes de Compra guardadas que
                                        coincidan.
                                    </td>
                                </tr>
                            )}
                            {pagina.data.map((orden) => (
                                <tr
                                    key={orden.id}
                                    className="cursor-pointer hover:bg-muted/30"
                                    onClick={() =>
                                        router.get(
                                            ordenesCompraMp.show.url(orden.id),
                                        )
                                    }
                                >
                                    <td className="px-2.5 py-1">
                                        <div className="flex items-center gap-2">
                                            <Avatar className="size-6 shrink-0">
                                                <AvatarFallback className="bg-accent text-[10px] font-semibold text-accent-foreground">
                                                    {getInitials(
                                                        orden.proveedor
                                                            ?.nombre ??
                                                            orden.codigo,
                                                    )}
                                                </AvatarFallback>
                                            </Avatar>
                                            <div className="min-w-0">
                                                <div
                                                    className="truncate font-medium"
                                                    title={orden.codigo}
                                                >
                                                    {orden.codigo}
                                                </div>
                                                <div className="truncate font-mono text-[10px] text-muted-foreground">
                                                    {orden.fecha_emision
                                                        ? new Date(
                                                              orden.fecha_emision,
                                                          ).toLocaleDateString()
                                                        : '—'}
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td
                                        className="truncate px-2.5 py-1"
                                        title={orden.proveedor?.nombre ?? '—'}
                                    >
                                        {orden.proveedor?.nombre ?? '—'}
                                    </td>
                                    <td
                                        className="hidden truncate px-2.5 py-1 font-mono text-muted-foreground md:table-cell"
                                        title={
                                            orden.proveedor?.rutproveedor ??
                                            '—'
                                        }
                                    >
                                        {orden.proveedor?.rutproveedor ?? '—'}
                                    </td>
                                    <td className="px-2.5 py-1 text-right">
                                        <Monto valor={orden.monto_total} />
                                    </td>
                                    <td className="px-2.5 py-1">
                                        <OrdenCompraEstadoBadge
                                            estado={
                                                orden.estado_mercado_publico
                                            }
                                        />
                                    </td>
                                    <td
                                        className="hidden truncate px-2.5 py-1 text-muted-foreground lg:table-cell"
                                        title={
                                            orden.proceso_adquisicion?.codigo ??
                                            '—'
                                        }
                                    >
                                        {orden.proceso_adquisicion?.codigo ??
                                            '—'}
                                    </td>
                                    <td
                                        className="px-2.5 py-1 text-right"
                                        onClick={(e) => e.stopPropagation()}
                                    >
                                        <DropdownMenu>
                                            <DropdownMenuTrigger asChild>
                                                <Button
                                                    variant="ghost"
                                                    size="icon"
                                                    className="size-6"
                                                >
                                                    <MoreHorizontal className="size-3.5" />
                                                    <span className="sr-only">
                                                        Acciones
                                                    </span>
                                                </Button>
                                            </DropdownMenuTrigger>
                                            <DropdownMenuContent align="end">
                                                <DropdownMenuItem asChild>
                                                    <Link
                                                        href={ordenesCompraMp.show.url(
                                                            orden.id,
                                                        )}
                                                    >
                                                        Ver detalle
                                                    </Link>
                                                </DropdownMenuItem>
                                            </DropdownMenuContent>
                                        </DropdownMenu>
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

OrdenesCompraMercadoPublicoIndex.layout = {
    breadcrumbs: [
        {
            title: 'Órdenes de compra (Mercado Público)',
            href: ordenesCompraMp.index(),
        },
    ],
};

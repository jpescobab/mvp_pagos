import { Head, Link, router, usePage } from '@inertiajs/react';
import { MoreHorizontal } from 'lucide-react';
import { useEffect, useState } from 'react';
import { LicitacionEstadoBadge } from '@/components/mercado-publico/licitacion-estado-badge';
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
import licitacionesMp from '@/routes/adquisiciones/licitaciones_mp';
import type { LicitacionMercadoPublico } from '@/types/adquisiciones';
import type { Paginated } from '@/types/pago-proveedores';

type PageProps = {
    licitaciones: Paginated<LicitacionMercadoPublico>;
    q: string | null;
};

export default function LicitacionesMercadoPublicoIndex() {
    const { licitaciones: pagina, q } = usePage<PageProps>().props;
    const [termino, setTermino] = useState(q ?? '');
    const getInitials = useInitials();

    useEffect(() => {
        const timeout = setTimeout(() => {
            if (termino === (q ?? '')) {
                return;
            }

            router.get(
                licitacionesMp.index().url,
                termino === '' ? {} : { q: termino },
                { preserveState: true, preserveScroll: true },
            );
        }, 300);

        return () => clearTimeout(timeout);
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [termino]);

    return (
        <>
            <Head title="Licitaciones (Mercado Público)" />

            <div className="flex h-full flex-1 flex-col gap-4 p-4">
                <div className="flex items-center justify-between">
                    <h1 className="text-xl font-semibold tracking-tight">
                        Licitaciones (Mercado Público)
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
                                href={licitacionesMp.index.url({
                                    query: { nuevo: 1 },
                                })}
                            >
                                Consultar Licitación a Mercado Público
                            </Link>
                        </Button>
                    </div>
                </div>

                <div className="overflow-x-auto rounded-xl border">
                    <table className="w-full table-fixed text-xs">
                        <thead className="bg-muted/50 text-left text-[10px] tracking-wide text-muted-foreground uppercase">
                            <tr>
                                <th className="w-[24%] px-2.5 py-1 font-medium">
                                    Licitación
                                </th>
                                <th className="w-[24%] px-2.5 py-1 font-medium">
                                    Organismo comprador
                                </th>
                                <th className="w-[14%] px-2.5 py-1 text-right font-medium">
                                    Monto estimado
                                </th>
                                <th className="w-[14%] px-2.5 py-1 font-medium">
                                    Estado
                                </th>
                                <th className="hidden w-[16%] px-2.5 py-1 font-medium lg:table-cell">
                                    Adquisición vinculada
                                </th>
                                <th className="w-[8%] px-2.5 py-1 font-medium"></th>
                            </tr>
                        </thead>
                        <tbody className="divide-y">
                            {pagina.data.length === 0 && (
                                <tr>
                                    <td
                                        colSpan={6}
                                        className="px-2.5 py-5 text-center text-muted-foreground"
                                    >
                                        Sin Licitaciones guardadas que
                                        coincidan.
                                    </td>
                                </tr>
                            )}
                            {pagina.data.map((licitacion) => (
                                <tr
                                    key={licitacion.id}
                                    className="cursor-pointer hover:bg-muted/30"
                                    onClick={() =>
                                        router.get(
                                            licitacionesMp.show.url(
                                                licitacion.id,
                                            ),
                                        )
                                    }
                                >
                                    <td className="px-2.5 py-1">
                                        <div className="flex items-center gap-2">
                                            <Avatar className="size-6 shrink-0">
                                                <AvatarFallback className="bg-accent text-[10px] font-semibold text-accent-foreground">
                                                    {getInitials(
                                                        licitacion.nombre ??
                                                            licitacion.codigo,
                                                    )}
                                                </AvatarFallback>
                                            </Avatar>
                                            <div className="min-w-0">
                                                <div
                                                    className="truncate font-medium"
                                                    title={licitacion.codigo}
                                                >
                                                    {licitacion.codigo}
                                                </div>
                                                <div
                                                    className="truncate text-[10px] text-muted-foreground"
                                                    title={
                                                        licitacion.nombre ?? ''
                                                    }
                                                >
                                                    {licitacion.nombre ?? '—'}
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td
                                        className="truncate px-2.5 py-1"
                                        title={
                                            licitacion.organismo_comprador
                                                ?.nombre ?? '—'
                                        }
                                    >
                                        {licitacion.organismo_comprador
                                            ?.nombre ?? '—'}
                                    </td>
                                    <td className="px-2.5 py-1 text-right">
                                        <Monto
                                            valor={licitacion.monto_estimado}
                                        />
                                    </td>
                                    <td className="px-2.5 py-1">
                                        <LicitacionEstadoBadge
                                            estado={
                                                licitacion.estado_mercado_publico
                                            }
                                        />
                                    </td>
                                    <td
                                        className="hidden truncate px-2.5 py-1 text-muted-foreground lg:table-cell"
                                        title={
                                            licitacion.proceso_adquisicion
                                                ?.codigo ?? '—'
                                        }
                                    >
                                        {licitacion.proceso_adquisicion
                                            ?.codigo ?? '—'}
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
                                                        href={licitacionesMp.show.url(
                                                            licitacion.id,
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

LicitacionesMercadoPublicoIndex.layout = {
    breadcrumbs: [
        {
            title: 'Licitaciones (Mercado Público)',
            href: licitacionesMp.index(),
        },
    ],
};

import { Head, Link, usePage } from '@inertiajs/react';
import { EstadoBadge } from '@/components/pago-proveedores/estado-badge';
import { Avatar, AvatarFallback } from '@/components/ui/avatar';
import { Monto } from '@/components/ui/monto';
import { useInitials } from '@/hooks/use-initials';
import { formatNumero } from '@/lib/format';
import casos from '@/routes/pago-proveedores/casos';
import type { CasoPagoProveedor, Paginated } from '@/types/pago-proveedores';

type PageProps = {
    casos: Paginated<CasoPagoProveedor>;
};

function formatFecha(valor: string | null): string {
    if (!valor) {
        return '—';
    }

    const [anio, mes, dia] = valor.slice(0, 10).split('-').map(Number);

    return new Date(anio, mes - 1, dia).toLocaleDateString();
}

export default function CasosIndex() {
    const { casos: pagina } = usePage<PageProps>().props;
    const getInitials = useInitials();

    return (
        <>
            <Head title="Casos de pago de proveedores" />

            <div className="flex h-full flex-1 flex-col gap-4 p-4">
                <h1 className="text-xl font-semibold tracking-tight">
                    Casos de pago de proveedores
                </h1>

                <div className="overflow-x-auto rounded-xl border">
                    <table className="w-full table-fixed text-xs">
                        <thead className="bg-muted/50 text-left text-[10px] tracking-wide text-muted-foreground uppercase">
                            <tr>
                                <th className="w-[18%] px-2.5 py-1 font-medium">
                                    Proveedor
                                </th>
                                <th className="w-[6%] px-2.5 py-1 font-medium">
                                    ID
                                </th>
                                <th className="hidden w-[7%] px-2.5 py-1 font-medium lg:table-cell">
                                    Periodo
                                </th>
                                <th className="hidden w-[16%] px-2.5 py-1 font-medium xl:table-cell">
                                    Observación
                                </th>
                                <th className="hidden w-[9%] px-2.5 py-1 font-medium lg:table-cell">
                                    Folio egreso
                                </th>
                                <th className="hidden w-[7%] px-2.5 py-1 font-medium lg:table-cell">
                                    Número
                                </th>
                                <th className="hidden w-[9%] px-2.5 py-1 font-medium md:table-cell">
                                    Fecha SII
                                </th>
                                <th className="w-[14%] px-2.5 py-1 font-medium">
                                    Monto
                                </th>
                                <th className="hidden w-[9%] px-2.5 py-1 font-medium md:table-cell">
                                    Estado SGF
                                </th>
                                <th className="w-[5%] px-2.5 py-1 text-center font-medium">
                                    Estado
                                </th>
                            </tr>
                        </thead>
                        <tbody className="divide-y">
                            {pagina.data.length === 0 && (
                                <tr>
                                    <td
                                        colSpan={10}
                                        className="px-2.5 py-5 text-center text-muted-foreground"
                                    >
                                        No hay casos de pago de proveedores
                                        todavía.
                                    </td>
                                </tr>
                            )}
                            {pagina.data.map((caso) => (
                                <tr key={caso.id} className="hover:bg-muted/30">
                                    <td className="px-2.5 py-1">
                                        <Link
                                            href={casos.show(caso.id)}
                                            className="flex items-center gap-2"
                                        >
                                            <Avatar className="size-6 shrink-0">
                                                <AvatarFallback className="bg-accent text-[10px] font-semibold text-accent-foreground">
                                                    {getInitials(
                                                        caso.proveedor.nombre ??
                                                            caso.sgf_id,
                                                    )}
                                                </AvatarFallback>
                                            </Avatar>
                                            <div className="min-w-0">
                                                <div
                                                    className="truncate font-medium"
                                                    title={
                                                        caso.proveedor.nombre ??
                                                        caso.sgf_id
                                                    }
                                                >
                                                    {caso.proveedor.nombre ??
                                                        caso.sgf_id}
                                                </div>
                                                <div className="truncate font-mono text-[10px] text-muted-foreground">
                                                    {caso.proveedor
                                                        .rutproveedor ?? '—'}
                                                </div>
                                            </div>
                                        </Link>
                                    </td>
                                    <td className="truncate px-2.5 py-1 font-mono text-muted-foreground">
                                        {caso.sgf_id}
                                    </td>
                                    <td className="hidden truncate px-2.5 py-1 text-muted-foreground lg:table-cell">
                                        {caso.periodo ?? '—'}
                                    </td>
                                    <td
                                        className="hidden truncate px-2.5 py-1 text-muted-foreground xl:table-cell"
                                        title={caso.observacion ?? undefined}
                                    >
                                        {caso.observacion ?? '—'}
                                    </td>
                                    <td className="hidden truncate px-2.5 py-1 text-muted-foreground lg:table-cell">
                                        {caso.folio_egreso ?? '—'}
                                    </td>
                                    <td className="hidden truncate px-2.5 py-1 text-muted-foreground lg:table-cell">
                                        {caso.numero ?? '—'}
                                    </td>
                                    <td className="hidden truncate px-2.5 py-1 text-muted-foreground md:table-cell">
                                        {formatFecha(caso.fecha_sii)}
                                    </td>
                                    <td className="px-2.5 py-1 whitespace-nowrap">
                                        <Monto valor={caso.monto} />
                                    </td>
                                    <td className="hidden truncate px-2.5 py-1 text-muted-foreground md:table-cell">
                                        {caso.sgf_status ?? '—'}
                                    </td>
                                    <td className="px-2.5 py-1 text-center">
                                        <EstadoBadge
                                            estado={caso.proceso.estado_actual}
                                            compact
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

CasosIndex.layout = {
    breadcrumbs: [
        {
            title: 'Casos de pago de proveedores',
            href: casos.index(),
        },
    ],
};

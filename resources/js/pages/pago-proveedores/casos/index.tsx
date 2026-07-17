import { Head, Link, router, usePage } from '@inertiajs/react';
import { EstadoBadge } from '@/components/pago-proveedores/estado-badge';
import { ListoParaRevisarBadge } from '@/components/pago-proveedores/listo-para-revisar-badge';
import { Avatar, AvatarFallback } from '@/components/ui/avatar';
import { Monto } from '@/components/ui/monto';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectSeparator,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { useInitials } from '@/hooks/use-initials';
import { formatFecha, formatNumero } from '@/lib/format';
import casos from '@/routes/pago-proveedores/casos';
import type {
    CasoPagoProveedor,
    EstadoWorkflow,
    Paginated,
} from '@/types/pago-proveedores';

type PageProps = {
    casos: Paginated<CasoPagoProveedor>;
    estadosWorkflow: EstadoWorkflow[];
    filtroEstado: string | null;
};

const FILTRO_PENDIENTES = 'pendientes';
const FILTRO_TODOS = 'todos';

export default function CasosIndex() {
    const {
        casos: pagina,
        estadosWorkflow,
        filtroEstado,
    } = usePage<PageProps>().props;
    const getInitials = useInitials();

    function cambiarFiltroEstado(valor: string) {
        router.get(
            casos.index.url(),
            valor === FILTRO_PENDIENTES ? {} : { estado: valor },
            { preserveState: true, preserveScroll: true },
        );
    }

    return (
        <>
            <Head title="Casos de pago de proveedores" />

            <div className="flex h-full flex-1 flex-col gap-4 p-4">
                <div className="flex items-center justify-between">
                    <h1 className="text-xl font-semibold tracking-tight">
                        Casos de pago de proveedores
                    </h1>
                    <Select
                        value={filtroEstado ?? FILTRO_PENDIENTES}
                        onValueChange={cambiarFiltroEstado}
                    >
                        <SelectTrigger className="w-64">
                            <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value={FILTRO_PENDIENTES}>
                                Pendientes de revisión
                            </SelectItem>
                            <SelectItem value={FILTRO_TODOS}>
                                Todos los estados
                            </SelectItem>
                            <SelectSeparator />
                            {estadosWorkflow.map((estado) => (
                                <SelectItem
                                    key={estado.codigo}
                                    value={estado.codigo}
                                >
                                    {estado.nombre}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                </div>

                <div className="overflow-x-auto rounded-xl border">
                    <table className="w-full table-fixed text-xs">
                        <thead className="bg-muted/50 text-left text-[10px] tracking-wide text-muted-foreground uppercase">
                            <tr>
                                <th className="w-[17%] px-2.5 py-1 font-medium">
                                    Proveedor
                                </th>
                                <th className="w-[6%] px-2.5 py-1 font-medium">
                                    ID
                                </th>
                                <th className="hidden w-[6%] px-2.5 py-1 font-medium lg:table-cell">
                                    Periodo
                                </th>
                                <th className="hidden w-[12%] px-2.5 py-1 font-medium lg:table-cell">
                                    Observación
                                </th>
                                <th className="hidden w-[8%] px-2.5 py-1 font-medium lg:table-cell">
                                    Obs. egreso
                                </th>
                                <th className="hidden w-[8%] px-2.5 py-1 font-medium lg:table-cell">
                                    Folio egreso
                                </th>
                                <th className="hidden w-[6%] px-2.5 py-1 font-medium lg:table-cell">
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
                                        colSpan={11}
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
                                        className="hidden truncate px-2.5 py-1 text-muted-foreground lg:table-cell"
                                        title={caso.observacion ?? undefined}
                                    >
                                        {caso.observacion ?? '—'}
                                    </td>
                                    <td
                                        className="hidden truncate px-2.5 py-1 text-muted-foreground lg:table-cell"
                                        title={
                                            caso.observacion_egreso ?? undefined
                                        }
                                    >
                                        {caso.observacion_egreso ?? '—'}
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
                                    <td className="px-2.5 py-1">
                                        <div className="flex items-center justify-center gap-1">
                                            <EstadoBadge
                                                estado={
                                                    caso.proceso.estado_actual
                                                }
                                                compact
                                            />
                                            {caso.listo_para_aprobar && (
                                                <ListoParaRevisarBadge />
                                            )}
                                        </div>
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

CasosIndex.layout = {
    breadcrumbs: [
        {
            title: 'Casos de pago de proveedores',
            href: casos.index(),
        },
    ],
};

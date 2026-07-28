import { Head, Link, router, usePage } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import { JurisdiccionActionsMenu } from '@/components/maestros/jurisdiccion-actions-menu';
import { JurisdiccionStatusBadge } from '@/components/maestros/jurisdiccion-status-badge';
import { Avatar, AvatarFallback } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { useInitials } from '@/hooks/use-initials';
import { formatNumero } from '@/lib/format';
import jurisdicciones from '@/routes/maestros/jurisdicciones';
import type { Jurisdiccion } from '@/types/maestros';
import type { Paginated } from '@/types/pago-proveedores';

type PageProps = {
    jurisdicciones: Paginated<Jurisdiccion>;
    q: string | null;
};

export default function JurisdiccionesIndex() {
    const { jurisdicciones: pagina, q } = usePage<PageProps>().props;
    const [termino, setTermino] = useState(q ?? '');
    const getInitials = useInitials();

    useEffect(() => {
        const timeout = setTimeout(() => {
            if (termino === (q ?? '')) {
                return;
            }

            router.get(
                jurisdicciones.index().url,
                termino === '' ? {} : { q: termino },
                { preserveState: true, preserveScroll: true },
            );
        }, 300);

        return () => clearTimeout(timeout);
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [termino]);

    return (
        <>
            <Head title="Jurisdicciones" />

            <div className="flex h-full flex-1 flex-col gap-4 p-4">
                <div className="flex items-center justify-between">
                    <h1 className="text-xl font-semibold tracking-tight">
                        Jurisdicciones
                    </h1>
                    <div className="flex items-center gap-2">
                        <Input
                            placeholder="Buscar por código o nombre…"
                            value={termino}
                            onChange={(e) => setTermino(e.target.value)}
                            className="w-72"
                        />
                        <Button asChild>
                            <Link href={jurisdicciones.create().url}>
                                Nueva jurisdicción
                            </Link>
                        </Button>
                    </div>
                </div>

                <div className="overflow-x-auto rounded-xl border">
                    <table className="w-full table-fixed text-xs">
                        <thead className="bg-muted/50 text-left text-[10px] tracking-wide text-muted-foreground uppercase">
                            <tr>
                                <th className="w-[30%] px-2.5 py-1 font-medium">
                                    Jurisdicción
                                </th>
                                <th className="w-[25%] px-2.5 py-1 font-medium">
                                    Institución
                                </th>
                                <th className="hidden w-[20%] px-2.5 py-1 font-medium lg:table-cell">
                                    Descripción
                                </th>
                                <th className="w-[12%] px-2.5 py-1 font-medium">
                                    Estado
                                </th>
                                <th className="w-[13%] px-2.5 py-1 font-medium"></th>
                            </tr>
                        </thead>
                        <tbody className="divide-y">
                            {pagina.data.length === 0 && (
                                <tr>
                                    <td
                                        colSpan={5}
                                        className="px-2.5 py-5 text-center text-muted-foreground"
                                    >
                                        Sin jurisdicciones que coincidan.
                                    </td>
                                </tr>
                            )}
                            {pagina.data.map((jurisdiccion) => (
                                <tr
                                    key={jurisdiccion.id}
                                    className="hover:bg-muted/30"
                                >
                                    <td className="px-2.5 py-1">
                                        <div className="flex items-center gap-2">
                                            <Avatar className="size-6 shrink-0">
                                                <AvatarFallback className="bg-accent text-[10px] font-semibold text-accent-foreground">
                                                    {getInitials(
                                                        jurisdiccion.nombre,
                                                    )}
                                                </AvatarFallback>
                                            </Avatar>
                                            <div className="min-w-0">
                                                <div
                                                    className="truncate font-medium"
                                                    title={jurisdiccion.nombre}
                                                >
                                                    {jurisdiccion.nombre}
                                                </div>
                                                <div className="truncate font-mono text-[10px] text-muted-foreground">
                                                    {jurisdiccion.codigo}
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td
                                        className="truncate px-2.5 py-1 text-muted-foreground"
                                        title={jurisdiccion.institucion.nombre}
                                    >
                                        {jurisdiccion.institucion.nombre}
                                    </td>
                                    <td
                                        className="hidden truncate px-2.5 py-1 text-muted-foreground lg:table-cell"
                                        title={jurisdiccion.descripcion ?? ''}
                                    >
                                        {jurisdiccion.descripcion ?? '—'}
                                    </td>
                                    <td className="px-2.5 py-1">
                                        <JurisdiccionStatusBadge
                                            activo={jurisdiccion.activo}
                                        />
                                    </td>
                                    <td className="px-2.5 py-1 text-right">
                                        <JurisdiccionActionsMenu
                                            jurisdiccion={jurisdiccion}
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

JurisdiccionesIndex.layout = {
    breadcrumbs: [
        {
            title: 'Jurisdicciones',
            href: jurisdicciones.index(),
        },
    ],
};

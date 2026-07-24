import { Head, Link, router, usePage } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import { DefinicionActionsMenu } from '@/components/informes-razonados/definicion-actions-menu';
import { DefinicionStatusBadge } from '@/components/informes-razonados/definicion-status-badge';
import { Avatar, AvatarFallback } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { useInitials } from '@/hooks/use-initials';
import { formatNumero } from '@/lib/format';
import definiciones from '@/routes/informes-razonados/definiciones';
import type { DefinicionInformeRazonado } from '@/types/informes-razonados';
import type { Paginated } from '@/types/pago-proveedores';

type PageProps = {
    definiciones: Paginated<DefinicionInformeRazonado>;
    q: string | null;
};

export default function DefinicionesInformeRazonadoIndex() {
    const { definiciones: pagina, q, auth } = usePage<PageProps>().props;
    const [termino, setTermino] = useState(q ?? '');
    const getInitials = useInitials();
    const puedeAdministrar = auth.permissions.includes('informes.administrar');

    useEffect(() => {
        const timeout = setTimeout(() => {
            if (termino === (q ?? '')) {
                return;
            }

            router.get(
                definiciones.index().url,
                termino === '' ? {} : { q: termino },
                { preserveState: true, preserveScroll: true },
            );
        }, 300);

        return () => clearTimeout(timeout);
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [termino]);

    return (
        <>
            <Head title="Definiciones de Informes Razonados" />

            <div className="flex h-full flex-1 flex-col gap-4 p-4">
                <div className="flex items-center justify-between">
                    <h1 className="text-xl font-semibold tracking-tight">
                        Definiciones de Informes Razonados
                    </h1>
                    <div className="flex items-center gap-2">
                        <Input
                            placeholder="Buscar por código o nombre…"
                            value={termino}
                            onChange={(e) => setTermino(e.target.value)}
                            className="w-72"
                        />
                        {puedeAdministrar && (
                            <Button asChild>
                                <Link href={definiciones.create().url}>
                                    Nueva definición
                                </Link>
                            </Button>
                        )}
                    </div>
                </div>

                <div className="overflow-x-auto rounded-xl border">
                    <table className="w-full table-fixed text-xs">
                        <thead className="bg-muted/50 text-left text-[10px] tracking-wide text-muted-foreground uppercase">
                            <tr>
                                <th className="w-[30%] px-2.5 py-1 font-medium">
                                    Definición
                                </th>
                                <th className="hidden w-[32%] px-2.5 py-1 font-medium lg:table-cell">
                                    Descripción
                                </th>
                                <th className="w-[13%] px-2.5 py-1 font-medium">
                                    Estado
                                </th>
                                <th className="w-[12%] px-2.5 py-1 font-medium">
                                    Ejecuciones
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
                                        Sin definiciones que coincidan.
                                    </td>
                                </tr>
                            )}
                            {pagina.data.map((definicion) => (
                                <tr
                                    key={definicion.id}
                                    className="hover:bg-muted/30"
                                >
                                    <td className="px-2.5 py-1">
                                        <div className="flex items-center gap-2">
                                            <Avatar className="size-6 shrink-0">
                                                <AvatarFallback className="bg-accent text-[10px] font-semibold text-accent-foreground">
                                                    {getInitials(
                                                        definicion.nombre,
                                                    )}
                                                </AvatarFallback>
                                            </Avatar>
                                            <div className="min-w-0">
                                                <div
                                                    className="truncate font-medium"
                                                    title={definicion.nombre}
                                                >
                                                    {definicion.nombre}
                                                </div>
                                                <div className="truncate font-mono text-[10px] text-muted-foreground">
                                                    {definicion.codigo}
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td
                                        className="hidden truncate px-2.5 py-1 text-muted-foreground lg:table-cell"
                                        title={definicion.descripcion ?? ''}
                                    >
                                        {definicion.descripcion ?? '—'}
                                    </td>
                                    <td className="px-2.5 py-1">
                                        <DefinicionStatusBadge
                                            activo={definicion.activo}
                                        />
                                    </td>
                                    <td className="px-2.5 py-1 text-muted-foreground">
                                        {formatNumero(
                                            definicion.ejecuciones_count ?? 0,
                                        )}
                                    </td>
                                    <td className="px-2.5 py-1 text-right">
                                        <DefinicionActionsMenu
                                            definicion={definicion}
                                            puedeAdministrar={puedeAdministrar}
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

DefinicionesInformeRazonadoIndex.layout = {
    breadcrumbs: [
        {
            title: 'Definiciones de Informes Razonados',
            href: definiciones.index(),
        },
    ],
};

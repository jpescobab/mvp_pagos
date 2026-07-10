import { Head, Link, router, usePage } from '@inertiajs/react';
import { MoreHorizontal } from 'lucide-react';
import { useEffect, useState } from 'react';
import { ImportacionEstadoBadge } from '@/components/sgf/importacion-estado-badge';
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
import { formatFechaHora } from '@/lib/format';
import { formatNumero } from '@/lib/format';
import casos from '@/routes/sgf/casos';
import importaciones from '@/routes/sgf/importaciones';
import type { Paginated } from '@/types/pago-proveedores';
import type { ImportacionSgf } from '@/types/sgf';

type PageProps = {
    importaciones: Paginated<ImportacionSgf>;
    q: string | null;
};

export default function ImportacionesSgfIndex() {
    const { importaciones: pagina, q } = usePage<PageProps>().props;
    const [termino, setTermino] = useState(q ?? '');
    const getInitials = useInitials();

    useEffect(() => {
        const timeout = setTimeout(() => {
            if (termino === (q ?? '')) {
                return;
            }

            router.get(
                importaciones.index().url,
                termino === '' ? {} : { q: termino },
                { preserveState: true, preserveScroll: true },
            );
        }, 300);

        return () => clearTimeout(timeout);
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [termino]);

    return (
        <>
            <Head title="Importaciones SGF" />

            <div className="flex h-full flex-1 flex-col gap-4 p-4">
                <div className="flex items-center justify-between">
                    <h1 className="text-xl font-semibold tracking-tight">
                        Importaciones SGF
                    </h1>
                    <div className="flex items-center gap-2">
                        <Input
                            placeholder="Buscar por tipo o usuario…"
                            value={termino}
                            onChange={(e) => setTermino(e.target.value)}
                            className="w-64"
                        />
                        <Button
                            variant="outline"
                            onClick={() =>
                                router.post(casos.importarPendientes().url)
                            }
                        >
                            Importar pendientes de SGF
                        </Button>
                        <Button
                            variant="outline"
                            onClick={() =>
                                router.post(
                                    casos.importarGrupoPagoOperaciones().url,
                                )
                            }
                        >
                            Importar grupo Pago Operaciones
                        </Button>
                    </div>
                </div>

                <div className="overflow-x-auto rounded-xl border">
                    <table className="w-full table-fixed text-xs">
                        <thead className="bg-muted/50 text-left text-[10px] tracking-wide text-muted-foreground uppercase">
                            <tr>
                                <th className="w-[20%] px-2.5 py-1 font-medium">
                                    Tipo
                                </th>
                                <th className="w-[18%] px-2.5 py-1 font-medium">
                                    Iniciado por
                                </th>
                                <th className="hidden w-[16%] px-2.5 py-1 font-medium md:table-cell">
                                    Iniciado en
                                </th>
                                <th className="hidden w-[16%] px-2.5 py-1 font-medium lg:table-cell">
                                    Finalizado en
                                </th>
                                <th className="w-[12%] px-2.5 py-1 text-right font-medium">
                                    Total elementos
                                </th>
                                <th className="w-[12%] px-2.5 py-1 font-medium">
                                    Estado
                                </th>
                                <th className="w-[6%] px-2.5 py-1 font-medium"></th>
                            </tr>
                        </thead>
                        <tbody className="divide-y">
                            {pagina.data.length === 0 && (
                                <tr>
                                    <td
                                        colSpan={7}
                                        className="px-2.5 py-5 text-center text-muted-foreground"
                                    >
                                        Sin importaciones SGF registradas que
                                        coincidan.
                                    </td>
                                </tr>
                            )}
                            {pagina.data.map((importacion) => (
                                <tr
                                    key={importacion.id}
                                    className="cursor-pointer hover:bg-muted/30"
                                    onClick={() =>
                                        router.visit(
                                            importaciones.show(importacion.id)
                                                .url,
                                        )
                                    }
                                >
                                    <td
                                        className="truncate px-2.5 py-1 font-medium"
                                        title={importacion.tipo}
                                    >
                                        {importacion.tipo}
                                    </td>
                                    <td className="px-2.5 py-1">
                                        <div className="flex items-center gap-2">
                                            <Avatar className="size-6 shrink-0">
                                                <AvatarFallback className="bg-accent text-[10px] font-semibold text-accent-foreground">
                                                    {getInitials(
                                                        importacion.iniciado_por ??
                                                            'Sistema',
                                                    )}
                                                </AvatarFallback>
                                            </Avatar>
                                            <span
                                                className="truncate text-muted-foreground"
                                                title={
                                                    importacion.iniciado_por ??
                                                    'Sistema'
                                                }
                                            >
                                                {importacion.iniciado_por ??
                                                    'Sistema'}
                                            </span>
                                        </div>
                                    </td>
                                    <td
                                        className="hidden truncate px-2.5 py-1 font-mono text-muted-foreground md:table-cell"
                                        title={formatFechaHora(
                                            importacion.iniciado_en,
                                        )}
                                    >
                                        {formatFechaHora(
                                            importacion.iniciado_en,
                                        )}
                                    </td>
                                    <td
                                        className="hidden truncate px-2.5 py-1 font-mono text-muted-foreground lg:table-cell"
                                        title={formatFechaHora(
                                            importacion.finalizado_en,
                                        )}
                                    >
                                        {formatFechaHora(
                                            importacion.finalizado_en,
                                        )}
                                    </td>
                                    <td className="px-2.5 py-1 text-right">
                                        <Monto
                                            valor={importacion.total_elementos}
                                            variante="numero"
                                        />
                                    </td>
                                    <td className="px-2.5 py-1">
                                        <ImportacionEstadoBadge
                                            estado={importacion.estado}
                                        />
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
                                                        href={importaciones.show.url(
                                                            importacion.id,
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

ImportacionesSgfIndex.layout = {
    breadcrumbs: [
        {
            title: 'Importaciones SGF',
            href: importaciones.index(),
        },
    ],
};

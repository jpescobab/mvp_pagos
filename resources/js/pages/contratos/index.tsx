import { Head, Link, router, usePage } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import { PaginacionFooter } from '@/components/paginacion-footer';
import { EstadoBadge } from '@/components/pago-proveedores/estado-badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import contratos from '@/routes/contratos';
import type { Contrato } from '@/types/contratos';
import type { Paginated } from '@/types/pago-proveedores';

type PageProps = {
    contratos: Paginated<Contrato>;
    q: string | null;
};

export default function ContratoIndex() {
    const { contratos: pagina, q } = usePage<PageProps>().props;
    const [termino, setTermino] = useState(q ?? '');

    useEffect(() => {
        const timeout = setTimeout(() => {
            if (termino === (q ?? '')) {
                return;
            }

            router.get(
                contratos.index().url,
                termino === '' ? {} : { q: termino },
                { preserveState: true, preserveScroll: true },
            );
        }, 300);

        return () => clearTimeout(timeout);
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [termino]);

    return (
        <>
            <Head title="Contratos" />

            <div className="flex h-full flex-1 flex-col gap-4 p-4">
                <div className="flex items-center justify-between">
                    <h1 className="text-xl font-semibold tracking-tight">
                        Contratos
                    </h1>
                    <div className="flex items-center gap-2">
                        <Input
                            placeholder="Buscar por ID, código o referencia…"
                            value={termino}
                            onChange={(e) => setTermino(e.target.value)}
                            className="w-72"
                        />
                        <Button asChild>
                            <Link href={contratos.create().url}>
                                Nuevo contrato
                            </Link>
                        </Button>
                    </div>
                </div>

                <div className="overflow-x-auto rounded-xl border">
                    <table className="w-full table-fixed text-xs">
                        <thead className="bg-muted/50 text-left text-[10px] tracking-wide text-muted-foreground uppercase">
                            <tr>
                                <th className="w-[12%] px-2.5 py-1 font-medium">
                                    ID institucional
                                </th>
                                <th className="w-[13%] px-2.5 py-1 font-medium">
                                    Código
                                </th>
                                <th className="w-[30%] px-2.5 py-1 font-medium">
                                    Referencia
                                </th>
                                <th className="w-[20%] px-2.5 py-1 font-medium">
                                    Proveedor
                                </th>
                                <th className="w-[12%] px-2.5 py-1 font-medium">
                                    Tipo
                                </th>
                                <th className="w-[13%] px-2.5 py-1 font-medium">
                                    Estado
                                </th>
                            </tr>
                        </thead>
                        <tbody className="divide-y">
                            {pagina.data.length === 0 && (
                                <tr>
                                    <td
                                        colSpan={6}
                                        className="px-2.5 py-5 text-center text-muted-foreground"
                                    >
                                        Sin contratos que coincidan.
                                    </td>
                                </tr>
                            )}
                            {pagina.data.map((contrato) => (
                                <tr
                                    key={contrato.id}
                                    className="hover:bg-muted/30"
                                >
                                    <td className="px-2.5 py-1 font-mono">
                                        <Link
                                            href={
                                                contratos.show(contrato.id).url
                                            }
                                            className="font-medium underline"
                                        >
                                            {contrato.id_institucional}
                                        </Link>
                                    </td>
                                    <td className="px-2.5 py-1 font-mono text-muted-foreground">
                                        {contrato.codigo}
                                    </td>
                                    <td
                                        className="truncate px-2.5 py-1"
                                        title={contrato.referencia}
                                    >
                                        {contrato.referencia}
                                    </td>
                                    <td
                                        className="truncate px-2.5 py-1 text-muted-foreground"
                                        title={contrato.proveedor.nombre ?? ''}
                                    >
                                        {contrato.proveedor.nombre ?? '—'}
                                    </td>
                                    <td className="px-2.5 py-1 text-muted-foreground">
                                        {contrato.tipo_contrato}
                                    </td>
                                    <td className="px-2.5 py-1">
                                        {contrato.proceso && (
                                            <EstadoBadge
                                                estado={
                                                    contrato.proceso
                                                        .estado_actual
                                                }
                                            />
                                        )}
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>

                <PaginacionFooter meta={pagina.meta} links={pagina.links} />
            </div>
        </>
    );
}

ContratoIndex.layout = {
    breadcrumbs: [{ title: 'Contratos', href: contratos.index() }],
};

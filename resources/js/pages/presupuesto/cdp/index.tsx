import { Head, Link, router, usePage } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import { PaginacionFooter } from '@/components/paginacion-footer';
import { EstadoBadge } from '@/components/pago-proveedores/estado-badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Monto } from '@/components/ui/monto';
import cdps from '@/routes/presupuesto/cdps';
import type { Paginated } from '@/types/pago-proveedores';
import type { CertificadoDisponibilidadPresupuestaria } from '@/types/presupuesto';

type PageProps = {
    cdps: Paginated<CertificadoDisponibilidadPresupuestaria>;
    q: string | null;
};

export default function CdpIndex() {
    const { cdps: pagina, q } = usePage<PageProps>().props;
    const [termino, setTermino] = useState(q ?? '');

    useEffect(() => {
        const timeout = setTimeout(() => {
            if (termino === (q ?? '')) {
                return;
            }

            router.get(cdps.index().url, termino === '' ? {} : { q: termino }, {
                preserveState: true,
                preserveScroll: true,
            });
        }, 300);

        return () => clearTimeout(timeout);
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [termino]);

    return (
        <>
            <Head title="Certificados de Disponibilidad Presupuestaria" />

            <div className="flex h-full flex-1 flex-col gap-4 p-4">
                <div className="flex items-center justify-between">
                    <h1 className="text-xl font-semibold tracking-tight">
                        Certificados de Disponibilidad Presupuestaria
                    </h1>
                    <div className="flex items-center gap-2">
                        <Input
                            placeholder="Buscar por folio…"
                            value={termino}
                            onChange={(e) => setTermino(e.target.value)}
                            className="w-64"
                        />
                        <Button asChild>
                            <Link href={cdps.create().url}>Nuevo CDP</Link>
                        </Button>
                    </div>
                </div>

                <div className="overflow-x-auto rounded-xl border">
                    <table className="w-full table-fixed text-xs">
                        <thead className="bg-muted/50 text-left text-[10px] tracking-wide text-muted-foreground uppercase">
                            <tr>
                                <th className="w-[15%] px-2.5 py-1 font-medium">
                                    Folio
                                </th>
                                <th className="w-[30%] px-2.5 py-1 font-medium">
                                    Cuenta
                                </th>
                                <th className="w-[20%] px-2.5 py-1 font-medium">
                                    Unidad ejecutora
                                </th>
                                <th className="w-[15%] px-2.5 py-1 font-medium">
                                    Estado
                                </th>
                                <th className="w-[20%] px-2.5 py-1 text-right font-medium">
                                    Monto
                                </th>
                            </tr>
                        </thead>
                        <tbody className="divide-y">
                            {pagina.data.length === 0 && (
                                <tr>
                                    <td
                                        colSpan={5}
                                        className="px-2.5 py-5 text-center text-muted-foreground"
                                    >
                                        Sin CDP que coincidan.
                                    </td>
                                </tr>
                            )}
                            {pagina.data.map((cdp) => (
                                <tr key={cdp.id} className="hover:bg-muted/30">
                                    <td className="px-2.5 py-1">
                                        <Link
                                            href={cdps.show(cdp.id).url}
                                            className="font-mono font-medium underline"
                                        >
                                            {cdp.folio}
                                        </Link>
                                    </td>
                                    <td
                                        className="truncate px-2.5 py-1"
                                        title={cdp.denominacion}
                                    >
                                        {cdp.denominacion}
                                    </td>
                                    <td
                                        className="truncate px-2.5 py-1 text-muted-foreground"
                                        title={cdp.unidad_ejecutora}
                                    >
                                        {cdp.unidad_ejecutora}
                                    </td>
                                    <td className="px-2.5 py-1">
                                        {cdp.proceso && (
                                            <EstadoBadge
                                                estado={
                                                    cdp.proceso.estado_actual
                                                }
                                            />
                                        )}
                                    </td>
                                    <td className="px-2.5 py-1 text-right">
                                        <Monto valor={cdp.monto} />
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

CdpIndex.layout = {
    breadcrumbs: [{ title: 'CDP', href: cdps.index() }],
};

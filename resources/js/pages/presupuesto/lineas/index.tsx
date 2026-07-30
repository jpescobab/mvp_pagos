import { Head, router, usePage } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import { PaginacionFooter } from '@/components/paginacion-footer';
import { Input } from '@/components/ui/input';
import { formatMonto } from '@/lib/format';
import lineas from '@/routes/presupuesto/lineas';
import type { Paginated } from '@/types/pago-proveedores';
import type { Presupuesto } from '@/types/presupuesto';

type PageProps = {
    presupuestos: Paginated<Presupuesto>;
    anio: number | null;
    total_asignado: number;
};

export default function LineasPresupuestoIndex() {
    const {
        presupuestos: pagina,
        anio,
        total_asignado: totalAsignado,
    } = usePage<PageProps>().props;
    const [anioFiltro, setAnioFiltro] = useState(anio ? String(anio) : '');

    useEffect(() => {
        const timeout = setTimeout(() => {
            const actual = anio ? String(anio) : '';

            if (anioFiltro === actual) {
                return;
            }

            router.get(
                lineas.index().url,
                anioFiltro === '' ? {} : { anio: anioFiltro },
                { preserveState: true, preserveScroll: true },
            );
        }, 300);

        return () => clearTimeout(timeout);
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [anioFiltro]);

    return (
        <>
            <Head title="Líneas de Presupuesto" />

            <div className="flex h-full flex-1 flex-col gap-4 p-4">
                <div className="flex items-center justify-between">
                    <h1 className="text-xl font-semibold tracking-tight">
                        Líneas de Presupuesto
                    </h1>
                    <Input
                        type="number"
                        placeholder="Filtrar por año…"
                        value={anioFiltro}
                        onChange={(e) => setAnioFiltro(e.target.value)}
                        className="w-40"
                    />
                </div>

                <div className="overflow-x-auto rounded-xl border">
                    <table className="w-full table-fixed text-xs">
                        <thead className="bg-muted/50 text-left text-[10px] tracking-wide text-muted-foreground uppercase">
                            <tr>
                                <th className="w-[20%] px-2.5 py-1 font-medium">
                                    Centro financiero
                                </th>
                                <th className="w-[30%] px-2.5 py-1 font-medium">
                                    Cuenta
                                </th>
                                <th className="w-[20%] px-2.5 py-1 font-medium">
                                    Plan de tarea
                                </th>
                                <th className="w-[10%] px-2.5 py-1 font-medium">
                                    Año
                                </th>
                                <th className="w-[20%] px-2.5 py-1 text-right font-medium">
                                    Monto asignado
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
                                        Sin líneas de presupuesto que coincidan.
                                    </td>
                                </tr>
                            )}
                            {pagina.data.map((presupuesto) => (
                                <tr
                                    key={presupuesto.id}
                                    className="hover:bg-muted/30"
                                >
                                    <td
                                        className="truncate px-2.5 py-1"
                                        title={presupuesto.cfinanciero.nombre}
                                    >
                                        {presupuesto.cfinanciero.nombre}
                                        <div className="truncate font-mono text-[10px] text-muted-foreground">
                                            {presupuesto.cfinanciero.codigo}
                                        </div>
                                    </td>
                                    <td
                                        className="truncate px-2.5 py-1"
                                        title={presupuesto.catalogo.nombre}
                                    >
                                        {presupuesto.catalogo.nombre}
                                        <div className="truncate font-mono text-[10px] text-muted-foreground">
                                            {presupuesto.catalogo.codigo}
                                        </div>
                                    </td>
                                    <td
                                        className="truncate px-2.5 py-1 text-muted-foreground"
                                        title={presupuesto.plan_tarea.nombre}
                                    >
                                        {presupuesto.plan_tarea.codigo}
                                    </td>
                                    <td className="px-2.5 py-1">
                                        {presupuesto.anio}
                                    </td>
                                    <td className="px-2.5 py-1 text-right tabular-nums">
                                        {formatMonto(
                                            presupuesto.monto_asignado,
                                        )}
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                        <tfoot className="border-t bg-muted/30">
                            <tr>
                                <td
                                    colSpan={4}
                                    className="px-2.5 py-1.5 font-medium"
                                >
                                    Total general
                                </td>
                                <td className="px-2.5 py-1.5 text-right font-mono font-medium tabular-nums">
                                    {formatMonto(totalAsignado)}
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <PaginacionFooter meta={pagina.meta} links={pagina.links} />
            </div>
        </>
    );
}

LineasPresupuestoIndex.layout = {
    breadcrumbs: [
        {
            title: 'Líneas de Presupuesto',
            href: lineas.index(),
        },
    ],
};

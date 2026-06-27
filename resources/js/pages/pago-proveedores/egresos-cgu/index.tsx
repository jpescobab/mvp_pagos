import { Head, Link, usePage } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import egresosCgu from '@/routes/pago-proveedores/egresos-cgu';
import type { EgresoCgu, Paginated } from '@/types/pago-proveedores';

type PageProps = {
    egresos: Paginated<EgresoCgu>;
};

export default function EgresosCguIndex() {
    const { egresos: pagina } = usePage<PageProps>().props;

    return (
        <>
            <Head title="Egresos CGU" />

            <div className="flex h-full flex-1 flex-col gap-4 p-4">
                <div className="flex items-center justify-between">
                    <h1 className="text-xl font-semibold tracking-tight">
                        Egresos CGU
                    </h1>
                    <Button asChild>
                        <Link href={egresosCgu.create()}>Nuevo egreso</Link>
                    </Button>
                </div>

                <div className="overflow-hidden rounded-xl border">
                    <table className="w-full text-sm">
                        <thead className="bg-muted/50 text-left text-muted-foreground">
                            <tr>
                                <th className="px-4 py-2 font-medium">
                                    N° egreso
                                </th>
                                <th className="px-4 py-2 font-medium">
                                    Fecha
                                </th>
                                <th className="px-4 py-2 font-medium">
                                    Monto total
                                </th>
                                <th className="px-4 py-2 font-medium">
                                    Casos cubiertos
                                </th>
                            </tr>
                        </thead>
                        <tbody className="divide-y">
                            {pagina.data.length === 0 && (
                                <tr>
                                    <td
                                        colSpan={4}
                                        className="px-4 py-6 text-center text-muted-foreground"
                                    >
                                        No hay egresos CGU registrados
                                        todavía.
                                    </td>
                                </tr>
                            )}
                            {pagina.data.map((egreso) => (
                                <tr key={egreso.numero_egreso}>
                                    <td className="px-4 py-2 font-mono text-xs">
                                        {egreso.numero_egreso}
                                    </td>
                                    <td className="px-4 py-2">
                                        {new Date(
                                            egreso.fecha,
                                        ).toLocaleDateString()}
                                    </td>
                                    <td className="px-4 py-2">
                                        {egreso.monto_total}
                                    </td>
                                    <td className="px-4 py-2 text-muted-foreground">
                                        {egreso.items
                                            .map((item) => item.caso.sgf_id)
                                            .join(', ')}
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>

                <div className="text-sm text-muted-foreground">
                    Mostrando {pagina.meta.from ?? 0}–{pagina.meta.to ?? 0} de{' '}
                    {pagina.meta.total}
                </div>
            </div>
        </>
    );
}

EgresosCguIndex.layout = {
    breadcrumbs: [{ title: 'Egresos CGU', href: egresosCgu.index() }],
};

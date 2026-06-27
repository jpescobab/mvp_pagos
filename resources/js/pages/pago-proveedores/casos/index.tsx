import { Head, Link, usePage } from '@inertiajs/react';
import { EstadoBadge } from '@/components/pago-proveedores/estado-badge';
import casos from '@/routes/pago-proveedores/casos';
import type { CasoPagoProveedor, Paginated } from '@/types/pago-proveedores';

type PageProps = {
    casos: Paginated<CasoPagoProveedor>;
};

export default function CasosIndex() {
    const { casos: pagina } = usePage<PageProps>().props;

    return (
        <>
            <Head title="Casos de pago de proveedores" />

            <div className="flex h-full flex-1 flex-col gap-4 p-4">
                <h1 className="text-xl font-semibold tracking-tight">
                    Casos de pago de proveedores
                </h1>

                <div className="overflow-hidden rounded-xl border">
                    <table className="w-full text-sm">
                        <thead className="bg-muted/50 text-left text-muted-foreground">
                            <tr>
                                <th className="px-4 py-2 font-medium">
                                    Proveedor
                                </th>
                                <th className="px-4 py-2 font-medium">RUT</th>
                                <th className="px-4 py-2 font-medium">
                                    Monto
                                </th>
                                <th className="px-4 py-2 font-medium">
                                    Estado SGF
                                </th>
                                <th className="px-4 py-2 font-medium">
                                    Estado
                                </th>
                            </tr>
                        </thead>
                        <tbody className="divide-y">
                            {pagina.data.length === 0 && (
                                <tr>
                                    <td
                                        colSpan={5}
                                        className="px-4 py-6 text-center text-muted-foreground"
                                    >
                                        No hay casos de pago de proveedores
                                        todavía.
                                    </td>
                                </tr>
                            )}
                            {pagina.data.map((caso) => (
                                <tr
                                    key={caso.id}
                                    className="hover:bg-muted/30"
                                >
                                    <td className="px-4 py-2">
                                        <Link
                                            href={casos.show(caso.id)}
                                            className="font-medium hover:underline"
                                        >
                                            {caso.proveedor.nombre ??
                                                caso.sgf_id}
                                        </Link>
                                    </td>
                                    <td className="px-4 py-2 font-mono text-xs">
                                        {caso.proveedor.rutproveedor ?? '—'}
                                    </td>
                                    <td className="px-4 py-2">
                                        {caso.monto}
                                    </td>
                                    <td className="px-4 py-2 text-muted-foreground">
                                        {caso.sgf_status ?? '—'}
                                    </td>
                                    <td className="px-4 py-2">
                                        <EstadoBadge
                                            estado={
                                                caso.proceso.estado_actual
                                            }
                                        />
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>

                <div className="flex items-center justify-between text-sm text-muted-foreground">
                    <span>
                        Mostrando {pagina.meta.from ?? 0}–{pagina.meta.to ?? 0}{' '}
                        de {pagina.meta.total}
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

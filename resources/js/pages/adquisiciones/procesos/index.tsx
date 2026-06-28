import { Head, Link, usePage } from '@inertiajs/react';
import { EstadoBadge } from '@/components/pago-proveedores/estado-badge';
import { Button } from '@/components/ui/button';
import procesos from '@/routes/adquisiciones/procesos';
import type { ProcesoAdquisicion } from '@/types/adquisiciones';
import type { Paginated } from '@/types/pago-proveedores';

type PageProps = {
    procesos: Paginated<ProcesoAdquisicion>;
};

export default function ProcesosIndex() {
    const { procesos: pagina } = usePage<PageProps>().props;

    return (
        <>
            <Head title="Procesos de adquisición" />

            <div className="flex h-full flex-1 flex-col gap-4 p-4">
                <div className="flex items-center justify-between">
                    <h1 className="text-xl font-semibold tracking-tight">
                        Procesos de adquisición
                    </h1>
                    <Button asChild>
                        <Link href={procesos.create()}>Nuevo proceso</Link>
                    </Button>
                </div>

                <div className="overflow-hidden rounded-xl border">
                    <table className="w-full text-sm">
                        <thead className="bg-muted/50 text-left text-muted-foreground">
                            <tr>
                                <th className="px-4 py-2 font-medium">
                                    Código
                                </th>
                                <th className="px-4 py-2 font-medium">
                                    Modalidad
                                </th>
                                <th className="px-4 py-2 font-medium">
                                    Centro de costo
                                </th>
                                <th className="px-4 py-2 font-medium">
                                    Proveedor
                                </th>
                                <th className="px-4 py-2 font-medium">
                                    Monto
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
                                        colSpan={6}
                                        className="px-4 py-6 text-center text-muted-foreground"
                                    >
                                        No hay procesos de adquisición
                                        todavía.
                                    </td>
                                </tr>
                            )}
                            {pagina.data.map((proceso) => (
                                <tr
                                    key={proceso.id}
                                    className="hover:bg-muted/30"
                                >
                                    <td className="px-4 py-2 font-mono text-xs">
                                        <Link
                                            href={procesos.show(proceso.id)}
                                            className="font-medium hover:underline"
                                        >
                                            {proceso.codigo}
                                        </Link>
                                    </td>
                                    <td className="px-4 py-2">
                                        {proceso.modalidad.nombre ?? '—'}
                                    </td>
                                    <td className="px-4 py-2">
                                        {proceso.ccosto.nombre ?? '—'}
                                    </td>
                                    <td className="px-4 py-2 text-muted-foreground">
                                        {proceso.proveedor.nombre ?? '—'}
                                    </td>
                                    <td className="px-4 py-2">
                                        {proceso.monto ?? '—'}
                                    </td>
                                    <td className="px-4 py-2">
                                        <EstadoBadge
                                            estado={
                                                proceso.proceso.estado_actual
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

ProcesosIndex.layout = {
    breadcrumbs: [
        {
            title: 'Procesos de adquisición',
            href: procesos.index(),
        },
    ],
};

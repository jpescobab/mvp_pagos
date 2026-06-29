import { Head, Link, router, usePage } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import { Input } from '@/components/ui/input';
import proveedores from '@/routes/maestros/proveedores';
import type { Proveedor } from '@/types/maestros';
import type { Paginated } from '@/types/pago-proveedores';

type PageProps = {
    proveedores: Paginated<Proveedor>;
    q: string | null;
};

export default function ProveedoresIndex() {
    const { proveedores: pagina, q } = usePage<PageProps>().props;
    const [termino, setTermino] = useState(q ?? '');

    useEffect(() => {
        const timeout = setTimeout(() => {
            if (termino === (q ?? '')) {
                return;
            }

            router.get(
                proveedores.index().url,
                termino === '' ? {} : { q: termino },
                { preserveState: true, preserveScroll: true },
            );
        }, 300);

        return () => clearTimeout(timeout);
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [termino]);

    return (
        <>
            <Head title="Proveedores" />

            <div className="flex h-full flex-1 flex-col gap-4 p-4">
                <div className="flex items-center justify-between">
                    <h1 className="text-xl font-semibold tracking-tight">
                        Proveedores
                    </h1>
                    <Input
                        placeholder="Buscar por RUT o nombre…"
                        value={termino}
                        onChange={(e) => setTermino(e.target.value)}
                        className="w-72"
                    />
                </div>

                <div className="overflow-hidden rounded-xl border">
                    <table className="w-full text-sm">
                        <thead className="bg-muted/50 text-left text-muted-foreground">
                            <tr>
                                <th className="px-4 py-2 font-medium">RUT</th>
                                <th className="px-4 py-2 font-medium">
                                    Nombre
                                </th>
                                <th className="px-4 py-2 font-medium">
                                    Correo
                                </th>
                                <th className="px-4 py-2 font-medium">
                                    Dirección
                                </th>
                                <th className="px-4 py-2 font-medium">
                                    Contacto
                                </th>
                                <th className="px-4 py-2 font-medium">
                                    Activo
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
                                        Sin proveedores que coincidan.
                                    </td>
                                </tr>
                            )}
                            {pagina.data.map((proveedor) => (
                                <tr key={proveedor.id}>
                                    <td className="px-4 py-2 font-mono">
                                        {proveedor.rutproveedor}
                                    </td>
                                    <td className="px-4 py-2">
                                        {proveedor.nombre}
                                    </td>
                                    <td className="px-4 py-2 text-muted-foreground">
                                        {proveedor.correo ?? '—'}
                                    </td>
                                    <td className="px-4 py-2 text-muted-foreground">
                                        {proveedor.direccion ?? '—'}
                                    </td>
                                    <td className="px-4 py-2 text-muted-foreground">
                                        {proveedor.contacto ?? '—'}
                                    </td>
                                    <td className="px-4 py-2">
                                        {proveedor.activo ? 'Sí' : 'No'}
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

ProveedoresIndex.layout = {
    breadcrumbs: [
        {
            title: 'Proveedores',
            href: proveedores.index(),
        },
    ],
};

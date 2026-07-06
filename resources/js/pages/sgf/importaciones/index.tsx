import { Head, Link, router, usePage } from '@inertiajs/react';
import { Monto } from '@/components/ui/monto';
import { formatNumero } from '@/lib/format';
import importaciones from '@/routes/sgf/importaciones';
import type { Paginated } from '@/types/pago-proveedores';
import type { ImportacionSgf } from '@/types/sgf';

type PageProps = {
    importaciones: Paginated<ImportacionSgf>;
};

export default function ImportacionesSgfIndex() {
    const { importaciones: pagina } = usePage<PageProps>().props;

    return (
        <>
            <Head title="Importaciones SGF" />

            <div className="flex h-full flex-1 flex-col gap-4 p-4">
                <h1 className="text-xl font-semibold tracking-tight">
                    Importaciones SGF
                </h1>

                <div className="overflow-hidden rounded-xl border">
                    <table className="w-full text-sm">
                        <thead className="bg-muted/50 text-left text-muted-foreground">
                            <tr>
                                <th className="px-4 py-2 font-medium">
                                    Fuente
                                </th>
                                <th className="px-4 py-2 font-medium">
                                    Iniciado por
                                </th>
                                <th className="px-4 py-2 font-medium">
                                    Iniciado en
                                </th>
                                <th className="px-4 py-2 font-medium">
                                    Finalizado en
                                </th>
                                <th className="px-4 py-2 font-medium">
                                    Total filas
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
                                        Sin importaciones SGF registradas
                                        todavía.
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
                                    <td className="px-4 py-2">
                                        {importacion.fuente}
                                    </td>
                                    <td className="px-4 py-2 text-muted-foreground">
                                        {importacion.iniciado_por ?? 'Sistema'}
                                    </td>
                                    <td className="px-4 py-2 font-mono text-xs">
                                        {new Date(
                                            importacion.iniciado_en,
                                        ).toLocaleString()}
                                    </td>
                                    <td className="px-4 py-2 font-mono text-xs">
                                        {importacion.finalizado_en
                                            ? new Date(
                                                  importacion.finalizado_en,
                                              ).toLocaleString()
                                            : '—'}
                                    </td>
                                    <td className="px-4 py-2">
                                        <Monto
                                            valor={importacion.total_filas}
                                            variante="numero"
                                        />
                                    </td>
                                    <td className="px-4 py-2">
                                        {importacion.estado}
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>

                <div className="flex items-center justify-between text-sm text-muted-foreground">
                    <span>
                        Mostrando {formatNumero(pagina.meta.from ?? 0)}–
                        {formatNumero(pagina.meta.to ?? 0)}{' '}
                        de {formatNumero(pagina.meta.total)}
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

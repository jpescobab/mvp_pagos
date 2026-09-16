import { Head, Link, usePage } from '@inertiajs/react';
import { TipoCompraActionsMenu } from '@/components/maestros/tipo-compra-actions-menu';
import { TipoCompraStatusBadge } from '@/components/maestros/tipo-compra-status-badge';
import { Button } from '@/components/ui/button';
import tiposCompra from '@/routes/maestros/tipos-compra';
import type { TipoCompraMaestro } from '@/types/maestros';

type PageProps = {
    tiposCompra: TipoCompraMaestro[];
};

export default function TiposCompraIndex() {
    const { tiposCompra: tipos } = usePage<PageProps>().props;

    return (
        <>
            <Head title="Tipos de Compra" />

            <div className="flex h-full flex-1 flex-col gap-4 p-4">
                <div className="flex items-center justify-between">
                    <h1 className="text-xl font-semibold tracking-tight">
                        Tipos de Compra
                    </h1>
                    <Button asChild>
                        <Link href={tiposCompra.create().url}>
                            Nuevo tipo de compra
                        </Link>
                    </Button>
                </div>

                <div className="overflow-x-auto rounded-xl border">
                    <table className="w-full table-fixed text-xs">
                        <thead className="bg-muted/50 text-left text-[10px] tracking-wide text-muted-foreground uppercase">
                            <tr>
                                <th className="w-[40%] px-2.5 py-1 font-medium">
                                    Nombre
                                </th>
                                <th className="w-[35%] px-2.5 py-1 font-medium">
                                    Código
                                </th>
                                <th className="w-[15%] px-2.5 py-1 font-medium">
                                    Estado
                                </th>
                                <th className="w-[10%] px-2.5 py-1 font-medium"></th>
                            </tr>
                        </thead>
                        <tbody className="divide-y">
                            {tipos.length === 0 && (
                                <tr>
                                    <td
                                        colSpan={4}
                                        className="px-2.5 py-5 text-center text-muted-foreground"
                                    >
                                        Sin tipos de compra registrados.
                                    </td>
                                </tr>
                            )}
                            {tipos.map((tipo) => (
                                <tr key={tipo.id} className="hover:bg-muted/30">
                                    <td
                                        className="truncate px-2.5 py-1 font-medium"
                                        title={tipo.nombre}
                                    >
                                        {tipo.nombre}
                                    </td>
                                    <td className="truncate px-2.5 py-1 font-mono text-muted-foreground">
                                        {tipo.codigo}
                                    </td>
                                    <td className="px-2.5 py-1">
                                        <TipoCompraStatusBadge
                                            activo={tipo.activo}
                                        />
                                    </td>
                                    <td className="px-2.5 py-1 text-right">
                                        <TipoCompraActionsMenu
                                            tipoCompra={tipo}
                                        />
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            </div>
        </>
    );
}

TiposCompraIndex.layout = {
    breadcrumbs: [{ title: 'Tipos de Compra', href: tiposCompra.index() }],
};

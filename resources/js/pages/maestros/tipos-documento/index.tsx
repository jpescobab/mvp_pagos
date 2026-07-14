import { Head, Link, usePage } from '@inertiajs/react';
import { TipoDocumentoActionsMenu } from '@/components/maestros/tipo-documento-actions-menu';
import { TipoDocumentoStatusBadge } from '@/components/maestros/tipo-documento-status-badge';
import { Button } from '@/components/ui/button';
import tiposDocumento from '@/routes/maestros/tipos-documento';
import requisitosDocumentales from '@/routes/pago-proveedores/requisitos-documentales';
import type { TipoDocumentoMaestro } from '@/types/maestros';

type PageProps = {
    tiposDocumento: TipoDocumentoMaestro[];
};

export default function TiposDocumentoIndex() {
    const { tiposDocumento: tipos } = usePage<PageProps>().props;

    return (
        <>
            <Head title="Tipos de Documento" />

            <div className="flex h-full flex-1 flex-col gap-4 p-4">
                <div className="flex items-center justify-between">
                    <h1 className="text-xl font-semibold tracking-tight">
                        Tipos de Documento
                    </h1>
                    <div className="flex items-center gap-2">
                        <Button variant="outline" asChild>
                            <Link href={requisitosDocumentales.index().url}>
                                Requisitos documentales
                            </Link>
                        </Button>
                        <Button asChild>
                            <Link href={tiposDocumento.create().url}>
                                Nuevo tipo de documento
                            </Link>
                        </Button>
                    </div>
                </div>

                <div className="overflow-x-auto rounded-xl border">
                    <table className="w-full table-fixed text-xs">
                        <thead className="bg-muted/50 text-left text-[10px] tracking-wide text-muted-foreground uppercase">
                            <tr>
                                <th className="w-[30%] px-2.5 py-1 font-medium">
                                    Nombre
                                </th>
                                <th className="w-[20%] px-2.5 py-1 font-medium">
                                    Código
                                </th>
                                <th className="w-[35%] px-2.5 py-1 font-medium">
                                    Descripción
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
                                        colSpan={5}
                                        className="px-2.5 py-5 text-center text-muted-foreground"
                                    >
                                        Sin tipos de documento registrados.
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
                                    <td
                                        className="truncate px-2.5 py-1 text-muted-foreground"
                                        title={tipo.descripcion ?? undefined}
                                    >
                                        {tipo.descripcion ?? '—'}
                                    </td>
                                    <td className="px-2.5 py-1">
                                        <TipoDocumentoStatusBadge
                                            activo={tipo.activo}
                                        />
                                    </td>
                                    <td className="px-2.5 py-1 text-right">
                                        <TipoDocumentoActionsMenu
                                            tipoDocumento={tipo}
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

TiposDocumentoIndex.layout = {
    breadcrumbs: [
        {
            title: 'Tipos de Documento',
            href: tiposDocumento.index(),
        },
    ],
};

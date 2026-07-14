import { Head, Link, usePage } from '@inertiajs/react';
import { TipoProcesoPagoActionsMenu } from '@/components/maestros/tipo-proceso-pago-actions-menu';
import { TipoProcesoPagoStatusBadge } from '@/components/maestros/tipo-proceso-pago-status-badge';
import { Button } from '@/components/ui/button';
import tiposProcesoPago from '@/routes/maestros/tipos-proceso-pago';
import requisitosDocumentales from '@/routes/pago-proveedores/requisitos-documentales';
import type { TipoProcesoPagoMaestro } from '@/types/maestros';

type PageProps = {
    tiposProcesoPago: TipoProcesoPagoMaestro[];
};

export default function TiposProcesoPagoIndex() {
    const { tiposProcesoPago: tipos } = usePage<PageProps>().props;

    return (
        <>
            <Head title="Tipos de Proceso de Pago" />

            <div className="flex h-full flex-1 flex-col gap-4 p-4">
                <div className="flex items-center justify-between">
                    <h1 className="text-xl font-semibold tracking-tight">
                        Tipos de Proceso de Pago
                    </h1>
                    <div className="flex items-center gap-2">
                        <Button variant="outline" asChild>
                            <Link href={requisitosDocumentales.index().url}>
                                Requisitos documentales
                            </Link>
                        </Button>
                        <Button asChild>
                            <Link href={tiposProcesoPago.create().url}>
                                Nuevo tipo de proceso de pago
                            </Link>
                        </Button>
                    </div>
                </div>

                <div className="overflow-x-auto rounded-xl border">
                    <table className="w-full table-fixed text-xs">
                        <thead className="bg-muted/50 text-left text-[10px] tracking-wide text-muted-foreground uppercase">
                            <tr>
                                <th className="w-[35%] px-2.5 py-1 font-medium">
                                    Nombre
                                </th>
                                <th className="w-[35%] px-2.5 py-1 font-medium">
                                    Código
                                </th>
                                <th className="w-[15%] px-2.5 py-1 font-medium">
                                    Estado
                                </th>
                                <th className="w-[15%] px-2.5 py-1 font-medium"></th>
                            </tr>
                        </thead>
                        <tbody className="divide-y">
                            {tipos.length === 0 && (
                                <tr>
                                    <td
                                        colSpan={4}
                                        className="px-2.5 py-5 text-center text-muted-foreground"
                                    >
                                        Sin tipos de proceso de pago
                                        registrados.
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
                                        <TipoProcesoPagoStatusBadge
                                            activo={tipo.activo}
                                        />
                                    </td>
                                    <td className="px-2.5 py-1 text-right">
                                        <TipoProcesoPagoActionsMenu
                                            tipoProcesoPago={tipo}
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

TiposProcesoPagoIndex.layout = {
    breadcrumbs: [
        {
            title: 'Tipos de Proceso de Pago',
            href: tiposProcesoPago.index(),
        },
    ],
};

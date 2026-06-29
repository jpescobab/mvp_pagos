import { Head, router, usePage } from '@inertiajs/react';
import definiciones from '@/routes/workflow/definiciones';
import type { DefinicionWorkflow } from '@/types/workflow';

type PageProps = {
    definiciones: DefinicionWorkflow[];
};

export default function DefinicionesWorkflowIndex() {
    const { definiciones: lista } = usePage<PageProps>().props;

    return (
        <>
            <Head title="Definiciones de Workflow" />

            <div className="flex h-full flex-1 flex-col gap-4 p-4">
                <h1 className="text-xl font-semibold tracking-tight">
                    Definiciones de Workflow
                </h1>

                <div className="overflow-hidden rounded-xl border">
                    <table className="w-full text-sm">
                        <thead className="bg-muted/50 text-left text-muted-foreground">
                            <tr>
                                <th className="px-4 py-2 font-medium">
                                    Código
                                </th>
                                <th className="px-4 py-2 font-medium">
                                    Nombre
                                </th>
                                <th className="px-4 py-2 font-medium">
                                    Estado
                                </th>
                                <th className="px-4 py-2 font-medium">
                                    Estados
                                </th>
                                <th className="px-4 py-2 font-medium">
                                    Transiciones
                                </th>
                            </tr>
                        </thead>
                        <tbody className="divide-y">
                            {lista.length === 0 && (
                                <tr>
                                    <td
                                        colSpan={5}
                                        className="px-4 py-6 text-center text-muted-foreground"
                                    >
                                        Sin definiciones de workflow
                                        todavía.
                                    </td>
                                </tr>
                            )}
                            {lista.map((definicion) => (
                                <tr
                                    key={definicion.id}
                                    className="cursor-pointer hover:bg-muted/30"
                                    onClick={() =>
                                        router.visit(
                                            definiciones.show(definicion.id)
                                                .url,
                                        )
                                    }
                                >
                                    <td className="px-4 py-2 font-mono">
                                        {definicion.codigo}
                                    </td>
                                    <td className="px-4 py-2">
                                        {definicion.nombre}
                                    </td>
                                    <td className="px-4 py-2">
                                        {definicion.activo ? (
                                            <span className="text-green-600">
                                                Activo
                                            </span>
                                        ) : (
                                            <span className="text-muted-foreground">
                                                Inactivo
                                            </span>
                                        )}
                                    </td>
                                    <td className="px-4 py-2 text-muted-foreground">
                                        {definicion.estados_count}
                                    </td>
                                    <td className="px-4 py-2 text-muted-foreground">
                                        {definicion.transiciones_count}
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

DefinicionesWorkflowIndex.layout = {
    breadcrumbs: [
        {
            title: 'Definiciones de Workflow',
            href: definiciones.index(),
        },
    ],
};

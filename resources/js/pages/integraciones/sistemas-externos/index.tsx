import { Head, usePage } from '@inertiajs/react';
import sistemasExternos from '@/routes/integraciones/sistemas-externos';
import type { SistemaExterno } from '@/types/integraciones';

type PageProps = {
    sistemas: SistemaExterno[];
};

export default function SistemasExternosIndex() {
    const { sistemas } = usePage<PageProps>().props;

    return (
        <>
            <Head title="Sistemas Externos" />

            <div className="flex h-full flex-1 flex-col gap-4 p-4">
                <h1 className="text-xl font-semibold tracking-tight">
                    Sistemas Externos
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
                                    Tipo de integración
                                </th>
                                <th className="px-4 py-2 font-medium">
                                    Estado
                                </th>
                                <th className="px-4 py-2 font-medium">
                                    Trabajos de integración
                                </th>
                            </tr>
                        </thead>
                        <tbody className="divide-y">
                            {sistemas.length === 0 && (
                                <tr>
                                    <td
                                        colSpan={5}
                                        className="px-4 py-6 text-center text-muted-foreground"
                                    >
                                        Sin sistemas externos registrados
                                        todavía.
                                    </td>
                                </tr>
                            )}
                            {sistemas.map((sistema) => (
                                <tr key={sistema.id}>
                                    <td className="px-4 py-2 font-mono">
                                        {sistema.codigo}
                                    </td>
                                    <td className="px-4 py-2">
                                        {sistema.nombre}
                                    </td>
                                    <td className="px-4 py-2 text-muted-foreground">
                                        {sistema.tipo_integracion}
                                    </td>
                                    <td className="px-4 py-2">
                                        {sistema.activo ? (
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
                                        {sistema.trabajos_integracion_count}
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

SistemasExternosIndex.layout = {
    breadcrumbs: [
        {
            title: 'Sistemas Externos',
            href: sistemasExternos.index(),
        },
    ],
};

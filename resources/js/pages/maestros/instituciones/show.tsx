import { Head, Link, usePage } from '@inertiajs/react';
import { InstitucionStatusBadge } from '@/components/maestros/institucion-status-badge';
import { JurisdiccionStatusBadge } from '@/components/maestros/jurisdiccion-status-badge';
import { Button } from '@/components/ui/button';
import instituciones from '@/routes/maestros/instituciones';
import jurisdicciones from '@/routes/maestros/jurisdicciones';
import type { Institucion } from '@/types/maestros';

type PageProps = {
    institucion: Institucion;
};

export default function InstitucionesShow() {
    const { institucion } = usePage<PageProps>().props;
    const susJurisdicciones = institucion.jurisdicciones ?? [];

    return (
        <>
            <Head title={institucion.nombre} />

            <div className="flex h-full flex-1 flex-col gap-4 p-4">
                <div className="flex items-center justify-between">
                    <h1 className="text-xl font-semibold tracking-tight">
                        {institucion.nombre}
                    </h1>
                    <div className="flex items-center gap-2">
                        <Button asChild variant="outline">
                            <Link href={instituciones.edit(institucion.id).url}>
                                Editar
                            </Link>
                        </Button>
                    </div>
                </div>

                <dl className="grid grid-cols-2 gap-4 rounded-xl border p-4 text-sm">
                    <div>
                        <dt className="text-muted-foreground">Código</dt>
                        <dd className="font-mono">{institucion.codigo}</dd>
                    </div>
                    <div>
                        <dt className="text-muted-foreground">Estado</dt>
                        <dd>
                            <InstitucionStatusBadge
                                activo={institucion.activo}
                            />
                        </dd>
                    </div>
                </dl>

                <div className="flex flex-col gap-2">
                    <h2 className="text-sm font-semibold tracking-tight">
                        Jurisdicciones
                    </h2>

                    <div className="overflow-x-auto rounded-xl border">
                        <table className="w-full table-fixed text-xs">
                            <thead className="bg-muted/50 text-left text-[10px] tracking-wide text-muted-foreground uppercase">
                                <tr>
                                    <th className="w-[20%] px-2.5 py-1 font-medium">
                                        Código
                                    </th>
                                    <th className="w-[60%] px-2.5 py-1 font-medium">
                                        Nombre
                                    </th>
                                    <th className="w-[20%] px-2.5 py-1 font-medium">
                                        Estado
                                    </th>
                                </tr>
                            </thead>
                            <tbody className="divide-y">
                                {susJurisdicciones.length === 0 && (
                                    <tr>
                                        <td
                                            colSpan={3}
                                            className="px-2.5 py-5 text-center text-muted-foreground"
                                        >
                                            Esta institución no tiene
                                            jurisdicciones asociadas.
                                        </td>
                                    </tr>
                                )}
                                {susJurisdicciones.map((jurisdiccion) => (
                                    <tr
                                        key={jurisdiccion.id}
                                        className="hover:bg-muted/30"
                                    >
                                        <td className="px-2.5 py-1 font-mono text-muted-foreground">
                                            {jurisdiccion.codigo}
                                        </td>
                                        <td className="truncate px-2.5 py-1">
                                            <Link
                                                href={
                                                    jurisdicciones.show(
                                                        jurisdiccion.id,
                                                    ).url
                                                }
                                                className="font-medium underline-offset-4 hover:underline"
                                            >
                                                {jurisdiccion.nombre}
                                            </Link>
                                        </td>
                                        <td className="px-2.5 py-1">
                                            <JurisdiccionStatusBadge
                                                activo={jurisdiccion.activo}
                                            />
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </>
    );
}

InstitucionesShow.layout = {
    breadcrumbs: [
        { title: 'Instituciones', href: instituciones.index() },
        { title: 'Detalle', href: '#' },
    ],
};

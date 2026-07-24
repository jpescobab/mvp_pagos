import { Head, Link, usePage } from '@inertiajs/react';
import { CfinancieroStatusBadge } from '@/components/maestros/cfinanciero-status-badge';
import { JurisdiccionStatusBadge } from '@/components/maestros/jurisdiccion-status-badge';
import { Button } from '@/components/ui/button';
import cfinancieros from '@/routes/maestros/cfinancieros';
import instituciones from '@/routes/maestros/instituciones';
import jurisdicciones from '@/routes/maestros/jurisdicciones';
import type { Jurisdiccion } from '@/types/maestros';

type PageProps = {
    jurisdiccion: Jurisdiccion;
};

export default function JurisdiccionesShow() {
    const { jurisdiccion } = usePage<PageProps>().props;
    const susCfinancieros = jurisdiccion.cfinancieros ?? [];

    return (
        <>
            <Head title={jurisdiccion.nombre} />

            <div className="flex h-full flex-1 flex-col gap-4 p-4">
                <div className="flex items-center justify-between">
                    <h1 className="text-xl font-semibold tracking-tight">
                        {jurisdiccion.nombre}
                    </h1>
                    <div className="flex items-center gap-2">
                        <Button asChild variant="outline">
                            <Link
                                href={jurisdicciones.edit(jurisdiccion.id).url}
                            >
                                Editar
                            </Link>
                        </Button>
                    </div>
                </div>

                <dl className="grid grid-cols-2 gap-4 rounded-xl border p-4 text-sm">
                    <div>
                        <dt className="text-muted-foreground">Código</dt>
                        <dd className="font-mono">{jurisdiccion.codigo}</dd>
                    </div>
                    <div>
                        <dt className="text-muted-foreground">Estado</dt>
                        <dd>
                            <JurisdiccionStatusBadge
                                activo={jurisdiccion.activo}
                            />
                        </dd>
                    </div>
                    <div>
                        <dt className="text-muted-foreground">Institución</dt>
                        <dd>
                            <Link
                                href={
                                    instituciones.show(
                                        jurisdiccion.institucion.id,
                                    ).url
                                }
                                className="underline-offset-4 hover:underline"
                            >
                                {jurisdiccion.institucion.nombre}
                            </Link>
                        </dd>
                    </div>
                    <div>
                        <dt className="text-muted-foreground">Descripción</dt>
                        <dd>{jurisdiccion.descripcion ?? '—'}</dd>
                    </div>
                </dl>

                <div className="flex flex-col gap-2">
                    <h2 className="text-sm font-semibold tracking-tight">
                        Centros financieros
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
                                {susCfinancieros.length === 0 && (
                                    <tr>
                                        <td
                                            colSpan={3}
                                            className="px-2.5 py-5 text-center text-muted-foreground"
                                        >
                                            Esta jurisdicción no tiene centros
                                            financieros asociados.
                                        </td>
                                    </tr>
                                )}
                                {susCfinancieros.map((cfinanciero) => (
                                    <tr
                                        key={cfinanciero.id}
                                        className="hover:bg-muted/30"
                                    >
                                        <td className="px-2.5 py-1 font-mono text-muted-foreground">
                                            {cfinanciero.codigo}
                                        </td>
                                        <td className="truncate px-2.5 py-1">
                                            <Link
                                                href={
                                                    cfinancieros.show(
                                                        cfinanciero.id,
                                                    ).url
                                                }
                                                className="font-medium underline-offset-4 hover:underline"
                                            >
                                                {cfinanciero.nombre}
                                            </Link>
                                        </td>
                                        <td className="px-2.5 py-1">
                                            <CfinancieroStatusBadge
                                                activo={cfinanciero.activo}
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

JurisdiccionesShow.layout = {
    breadcrumbs: [
        { title: 'Jurisdicciones', href: jurisdicciones.index() },
        { title: 'Detalle', href: '#' },
    ],
};

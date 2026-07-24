import { Head, Link, usePage } from '@inertiajs/react';
import { DefinicionStatusBadge } from '@/components/informes-razonados/definicion-status-badge';
import { Button } from '@/components/ui/button';
import { formatFecha } from '@/lib/format';
import definiciones from '@/routes/informes-razonados/definiciones';
import ejecuciones from '@/routes/informes-razonados/ejecuciones';
import type { DefinicionInformeRazonado } from '@/types/informes-razonados';

type PageProps = {
    definicion: DefinicionInformeRazonado;
};

export default function DefinicionInformeRazonadoShow() {
    const { definicion, auth } = usePage<PageProps>().props;
    const susEjecuciones = definicion.ejecuciones ?? [];
    const puedeAdministrar = auth.permissions.includes('informes.administrar');

    return (
        <>
            <Head title={definicion.nombre} />

            <div className="flex h-full flex-1 flex-col gap-4 p-4">
                <div className="flex items-center justify-between">
                    <h1 className="text-xl font-semibold tracking-tight">
                        {definicion.nombre}
                    </h1>
                    {puedeAdministrar && (
                        <div className="flex items-center gap-2">
                            <Button asChild variant="outline">
                                <Link href={definiciones.edit(definicion.id).url}>
                                    Editar
                                </Link>
                            </Button>
                        </div>
                    )}
                </div>

                <dl className="grid grid-cols-2 gap-4 rounded-xl border p-4 text-sm">
                    <div>
                        <dt className="text-muted-foreground">Código</dt>
                        <dd className="font-mono">{definicion.codigo}</dd>
                    </div>
                    <div>
                        <dt className="text-muted-foreground">Estado</dt>
                        <dd>
                            <DefinicionStatusBadge activo={definicion.activo} />
                        </dd>
                    </div>
                    <div className="col-span-2">
                        <dt className="text-muted-foreground">Descripción</dt>
                        <dd>{definicion.descripcion ?? '—'}</dd>
                    </div>
                </dl>

                <div className="flex flex-col gap-2">
                    <h2 className="text-sm font-semibold tracking-tight">
                        Ejecuciones
                    </h2>

                    <div className="overflow-x-auto rounded-xl border">
                        <table className="w-full table-fixed text-xs">
                            <thead className="bg-muted/50 text-left text-[10px] tracking-wide text-muted-foreground uppercase">
                                <tr>
                                    <th className="w-[30%] px-2.5 py-1 font-medium">
                                        Corte
                                    </th>
                                    <th className="w-[25%] px-2.5 py-1 font-medium">
                                        Período
                                    </th>
                                    <th className="w-[25%] px-2.5 py-1 font-medium">
                                        Estado
                                    </th>
                                    <th className="w-[20%] px-2.5 py-1 font-medium"></th>
                                </tr>
                            </thead>
                            <tbody className="divide-y">
                                {susEjecuciones.length === 0 && (
                                    <tr>
                                        <td
                                            colSpan={4}
                                            className="px-2.5 py-5 text-center text-muted-foreground"
                                        >
                                            Esta definición no tiene ejecuciones
                                            generadas.
                                        </td>
                                    </tr>
                                )}
                                {susEjecuciones.map((ejecucion) => (
                                    <tr
                                        key={ejecucion.id}
                                        className="hover:bg-muted/30"
                                    >
                                        <td className="px-2.5 py-1 text-muted-foreground">
                                            {ejecucion.corte_fecha
                                                ? formatFecha(
                                                      ejecucion.corte_fecha,
                                                  )
                                                : '—'}
                                        </td>
                                        <td className="truncate px-2.5 py-1 text-muted-foreground">
                                            {ejecucion.periodo_codigo ?? '—'}
                                        </td>
                                        <td className="px-2.5 py-1 text-muted-foreground">
                                            {ejecucion.estado ?? '—'}
                                        </td>
                                        <td className="px-2.5 py-1 text-right">
                                            <Link
                                                href={
                                                    ejecuciones.show(
                                                        ejecucion.id,
                                                    ).url
                                                }
                                                className="underline underline-offset-4"
                                            >
                                                Ver
                                            </Link>
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

DefinicionInformeRazonadoShow.layout = {
    breadcrumbs: [
        {
            title: 'Definiciones de Informes Razonados',
            href: definiciones.index(),
        },
        { title: 'Detalle', href: '#' },
    ],
};

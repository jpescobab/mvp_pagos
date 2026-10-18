import { Head, Link, router, usePage } from '@inertiajs/react';
import { useState } from 'react';
import {
    EstadoPeriodoBadge,
    EstadoServicioBadge,
} from '@/components/servicios-contratados/estado-servicio-badge';
import { Button } from '@/components/ui/button';
import { Monto } from '@/components/ui/monto';
import { formatFecha } from '@/lib/format';
import contratos from '@/routes/contratos';
import casos from '@/routes/pago-proveedores/casos';
import servicios from '@/routes/servicios-contratados';
import type {
    PeriodoServicio,
    ServicioContratado,
} from '@/types/servicios-contratados';

type PageProps = {
    servicio: ServicioContratado;
    periodos: PeriodoServicio[];
    auth: { permissions: string[] };
};

export default function ServicioContratadoShow() {
    const { servicio, periodos, auth } = usePage<PageProps>().props;
    const [procesando, setProcesando] = useState(false);

    const terminado = servicio.estado === 'terminado';
    const puedeEditar =
        !terminado && auth.permissions.includes('servicios_contratados.editar');
    const puedeTerminar =
        !terminado &&
        auth.permissions.includes('servicios_contratados.terminar');

    function terminar() {
        setProcesando(true);

        router.post(
            servicios.terminar(servicio.id).url,
            {},
            { preserveScroll: true, onFinish: () => setProcesando(false) },
        );
    }

    return (
        <>
            <Head title={servicio.nombre} />

            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <div className="flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <h1 className="text-xl font-semibold tracking-tight">
                            {servicio.nombre}
                        </h1>
                        <p className="text-sm text-muted-foreground">
                            {servicio.categoria} ·{' '}
                            {servicio.proveedor.nombre ?? 'Sin proveedor'}
                        </p>
                    </div>
                    <div className="flex items-center gap-2">
                        {puedeEditar && (
                            <Button variant="outline" size="sm" asChild>
                                <Link href={servicios.edit(servicio.id).url}>
                                    Editar
                                </Link>
                            </Button>
                        )}
                        {puedeTerminar && (
                            <Button
                                variant="outline"
                                size="sm"
                                disabled={procesando}
                                onClick={terminar}
                            >
                                Terminar servicio
                            </Button>
                        )}
                        <EstadoServicioBadge estado={servicio.estado} />
                    </div>
                </div>

                {servicio.por_vencer && (
                    <div className="rounded-md border border-amber-500/40 bg-amber-500/10 px-3 py-2 text-sm text-amber-700 dark:text-amber-400">
                        Este servicio vence el {formatFecha(servicio.fecha_fin)}
                        . Revisa si corresponde renovar el contrato.
                    </div>
                )}

                <section className="grid grid-cols-2 gap-x-6 gap-y-3 rounded-xl border p-4 text-sm">
                    <div>
                        <span className="text-muted-foreground">
                            Contrato:{' '}
                        </span>
                        <Link
                            href={contratos.show(servicio.contrato.id).url}
                            className="underline"
                        >
                            {servicio.contrato.codigo}
                        </Link>
                    </div>
                    <div>
                        <span className="text-muted-foreground">
                            Monto mensual esperado:{' '}
                        </span>
                        <Monto valor={servicio.monto_mensual_esperado} />{' '}
                        {servicio.moneda}
                    </div>
                    <div>
                        <span className="text-muted-foreground">
                            Vigencia:{' '}
                        </span>
                        {formatFecha(servicio.fecha_inicio)} a{' '}
                        {formatFecha(servicio.fecha_fin)}
                    </div>
                    <div>
                        <span className="text-muted-foreground">
                            Centro de costo:{' '}
                        </span>
                        {servicio.ccosto
                            ? `${servicio.ccosto.codigo} · ${servicio.ccosto.nombre}`
                            : '—'}
                    </div>
                    <div>
                        <span className="text-muted-foreground">
                            Administrador:{' '}
                        </span>
                        {servicio.administrador?.nombre ?? '—'}
                    </div>
                    {servicio.observaciones && (
                        <div className="col-span-2">
                            <span className="text-muted-foreground">
                                Observaciones:{' '}
                            </span>
                            {servicio.observaciones}
                        </div>
                    )}
                </section>

                <section className="space-y-3 rounded-xl border p-4">
                    <h2 className="text-base font-medium">Períodos</h2>
                    <p className="text-xs text-muted-foreground">
                        Cruce informativo con los casos de pago del proveedor
                        según su período. No bloquea ni modifica ningún pago.
                    </p>

                    {periodos.length === 0 ? (
                        <p className="text-sm text-muted-foreground">
                            Aún no hay períodos transcurridos.
                        </p>
                    ) : (
                        <ul className="divide-y text-sm">
                            {periodos.map((periodo) => (
                                <li
                                    key={periodo.periodo}
                                    className="flex items-center justify-between gap-2 py-2"
                                >
                                    <span className="font-mono">
                                        {periodo.periodo}
                                    </span>
                                    <span className="flex flex-wrap gap-2 text-muted-foreground">
                                        {periodo.casos.map((caso) => (
                                            <Link
                                                key={caso.id}
                                                href={casos.show(caso.id).url}
                                                className="underline"
                                            >
                                                Caso {caso.sgf_id}
                                            </Link>
                                        ))}
                                    </span>
                                    <EstadoPeriodoBadge
                                        estado={periodo.estado}
                                    />
                                </li>
                            ))}
                        </ul>
                    )}
                </section>
            </div>
        </>
    );
}

ServicioContratadoShow.layout = {
    breadcrumbs: [
        { title: 'Servicios contratados', href: servicios.index() },
        { title: 'Detalle', href: '#' },
    ],
};

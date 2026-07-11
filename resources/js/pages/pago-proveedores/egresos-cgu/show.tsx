import { Head, usePage } from '@inertiajs/react';
import { Monto } from '@/components/ui/monto';
import { formatFecha } from '@/lib/format';
import egresosCgu from '@/routes/pago-proveedores/egresos-cgu';
import type { EgresoCgu } from '@/types/pago-proveedores';

type PageProps = {
    egreso: EgresoCgu;
};

export default function EgresoCguShow() {
    const { egreso } = usePage<PageProps>().props;

    return (
        <>
            <Head title={`Egreso ${egreso.numero_egreso}`} />

            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <div>
                    <h1 className="text-xl font-semibold tracking-tight">
                        Egreso {egreso.numero_egreso}
                    </h1>
                    <p className="text-sm text-muted-foreground">
                        {formatFecha(egreso.fecha)} · Monto
                        total <Monto valor={egreso.monto_total} />
                    </p>
                    {egreso.observaciones && (
                        <p className="mt-2 text-sm text-muted-foreground italic">
                            “{egreso.observaciones}”
                        </p>
                    )}
                </div>

                <section className="space-y-3 rounded-xl border p-4">
                    <h2 className="text-base font-medium">Casos cubiertos</h2>

                    {egreso.items.length === 0 ? (
                        <p className="text-sm text-muted-foreground">
                            Sin casos cubiertos.
                        </p>
                    ) : (
                        <ul className="divide-y text-sm">
                            {egreso.items.map((item, i) => (
                                <li
                                    key={i}
                                    className="flex items-center justify-between py-2"
                                >
                                    <span className="font-mono">
                                        {item.caso.sgf_id}
                                    </span>
                                    <Monto valor={item.monto} />
                                </li>
                            ))}
                        </ul>
                    )}
                </section>
            </div>
        </>
    );
}

EgresoCguShow.layout = {
    breadcrumbs: [
        { title: 'Egresos CGU', href: egresosCgu.index() },
        { title: 'Detalle', href: '#' },
    ],
};

import { Head, router, usePage } from '@inertiajs/react';
import { EstadoBadge } from '@/components/pago-proveedores/estado-badge';
import { Avatar, AvatarFallback } from '@/components/ui/avatar';
import { Badge } from '@/components/ui/badge';
import { Monto } from '@/components/ui/monto';
import { useInitials } from '@/hooks/use-initials';
import { formatFecha } from '@/lib/format';
import casos from '@/routes/pago-proveedores/casos';
import egresosCgu from '@/routes/pago-proveedores/egresos-cgu';
import type { EgresoCgu } from '@/types/pago-proveedores';

type PageProps = {
    egreso: EgresoCgu;
};

function MetaDato({ etiqueta, valor }: { etiqueta: string; valor: string }) {
    return (
        <div className="rounded-lg bg-muted/40 px-3 py-2">
            <div className="text-[10px] tracking-wide text-muted-foreground uppercase">
                {etiqueta}
            </div>
            <div className="truncate text-sm font-medium" title={valor}>
                {valor}
            </div>
        </div>
    );
}

export default function EgresoCguShow() {
    const { egreso } = usePage<PageProps>().props;
    const getInitials = useInitials();

    return (
        <>
            <Head title={`Egreso ${egreso.numero_egreso}`} />

            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <div>
                    <div className="flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <h1 className="text-xl font-semibold tracking-tight">
                                Egreso {egreso.numero_egreso}
                            </h1>
                            <p className="text-sm text-muted-foreground">
                                {formatFecha(egreso.fecha)} · Monto
                                total <Monto valor={egreso.monto_total} />
                            </p>
                        </div>
                        {egreso.generado_automaticamente && (
                            <Badge variant="secondary">
                                Generado automáticamente
                            </Badge>
                        )}
                    </div>
                    {egreso.observaciones && (
                        <p className="mt-2 text-sm text-muted-foreground italic">
                            “{egreso.observaciones}”
                        </p>
                    )}

                    <div className="mt-4 grid grid-cols-2 gap-3 sm:grid-cols-4">
                        <MetaDato
                            etiqueta="Período"
                            valor={egreso.periodo ?? '—'}
                        />
                        <MetaDato
                            etiqueta="Centro financiero"
                            valor={egreso.cfinanciero?.nombre ?? '—'}
                        />
                        <MetaDato
                            etiqueta="Casos cubiertos"
                            valor={String(egreso.cantidad_casos)}
                        />
                        <MetaDato
                            etiqueta="Registrado por"
                            valor={egreso.registrado_por ?? '—'}
                        />
                    </div>
                </div>

                <section className="space-y-3">
                    <h2 className="text-base font-medium">Casos cubiertos</h2>

                    {egreso.items.length === 0 ? (
                        <p className="text-sm text-muted-foreground">
                            Sin casos cubiertos.
                        </p>
                    ) : (
                        <div className="overflow-x-auto rounded-xl border">
                            <table className="w-full table-fixed text-xs">
                                <thead className="bg-muted/50 text-[10px] tracking-wide text-muted-foreground uppercase">
                                    <tr>
                                        <th className="px-2.5 py-2 text-left font-medium">
                                            Proveedor
                                        </th>
                                        <th className="w-[11%] px-2.5 py-2 text-left font-medium">
                                            SGF
                                        </th>
                                        <th className="w-[12%] px-2.5 py-2 text-left font-medium">
                                            N° DTE
                                        </th>
                                        <th className="hidden w-[11%] px-2.5 py-2 text-left font-medium lg:table-cell">
                                            Período
                                        </th>
                                        <th className="hidden w-[12%] px-2.5 py-2 text-left font-medium md:table-cell">
                                            Fecha SII
                                        </th>
                                        <th className="w-[9%] px-2.5 py-2 text-center font-medium">
                                            Estado
                                        </th>
                                        <th className="w-[15%] px-2.5 py-2 text-right font-medium">
                                            Monto
                                        </th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y">
                                    {egreso.items.map((item) => (
                                        <tr
                                            key={item.caso.id}
                                            className="cursor-pointer hover:bg-muted/30"
                                            onClick={() =>
                                                router.visit(
                                                    casos.show(item.caso.id).url,
                                                )
                                            }
                                        >
                                            <td className="px-2.5 py-1">
                                                <div className="flex items-center gap-2">
                                                    <Avatar className="size-6 shrink-0">
                                                        <AvatarFallback className="bg-accent text-[10px] font-semibold text-accent-foreground">
                                                            {getInitials(
                                                                item.proveedor
                                                                    ?.nombre ??
                                                                    item.caso
                                                                        .sgf_id,
                                                            )}
                                                        </AvatarFallback>
                                                    </Avatar>
                                                    <div className="min-w-0">
                                                        <div
                                                            className="truncate font-medium"
                                                            title={
                                                                item.proveedor
                                                                    ?.nombre ??
                                                                item.caso.sgf_id
                                                            }
                                                        >
                                                            {item.proveedor
                                                                ?.nombre ??
                                                                item.caso.sgf_id}
                                                        </div>
                                                        <div className="truncate font-mono text-[10px] text-muted-foreground">
                                                            {item.proveedor
                                                                ?.rutproveedor ??
                                                                '—'}
                                                        </div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td className="truncate px-2.5 py-1 font-mono text-muted-foreground">
                                                {item.caso.sgf_id}
                                            </td>
                                            <td className="truncate px-2.5 py-1 font-mono text-muted-foreground">
                                                {item.numero ?? '—'}
                                            </td>
                                            <td className="hidden truncate px-2.5 py-1 text-muted-foreground lg:table-cell">
                                                {item.periodo ?? '—'}
                                            </td>
                                            <td className="hidden truncate px-2.5 py-1 text-muted-foreground md:table-cell">
                                                {formatFecha(item.fecha_sii)}
                                            </td>
                                            <td className="px-2.5 py-1">
                                                <div className="flex justify-center">
                                                    {item.estado_actual ? (
                                                        <EstadoBadge
                                                            estado={
                                                                item.estado_actual
                                                            }
                                                            compact
                                                        />
                                                    ) : (
                                                        <span className="text-muted-foreground">
                                                            —
                                                        </span>
                                                    )}
                                                </div>
                                            </td>
                                            <td className="px-2.5 py-1 text-right whitespace-nowrap">
                                                <Monto valor={item.monto} />
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
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

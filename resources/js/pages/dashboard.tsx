import { Head, Link } from '@inertiajs/react';
import {
    FileBarChart,
    Receipt,
    ShoppingCart,
    TrendingUp,
    Wallet,
} from 'lucide-react';
import type { LucideIcon } from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import { dashboard } from '@/routes';
import casos from '@/routes/pago-proveedores/casos';

type Kpis = {
    casos_pago_activos: number;
    egresos_cgu_mes: number;
    adquisiciones_activas: number;
    informes_en_curso: number;
};

type Indicador = {
    tipo: string;
    valor: string;
    fecha_valor: string | null;
    periodo: string | null;
};

type CasoReciente = {
    id: number;
    sgf_id: string;
    proveedor: string | null;
    monto: string | null;
    estado: string | null;
    cerrado: boolean;
};

type PageProps = {
    kpis: Kpis;
    indicadores: Indicador[];
    casosRecientes: CasoReciente[];
};

const ETIQUETAS_INDICADOR: Record<string, string> = {
    UF: 'U.F',
    UTM: 'U.T.M',
    UTA: 'U.T.A',
    IPC: 'I.P.C',
    USD: 'Dólar',
};

function formatearValor(indicador: Indicador): string {
    const valor = Number(indicador.valor);

    if (Number.isNaN(valor)) {
        return indicador.valor;
    }

    if (indicador.tipo === 'IPC') {
        return `${new Intl.NumberFormat('es-CL', { maximumFractionDigits: 1 }).format(valor)}%`;
    }

    const decimales =
        indicador.tipo === 'UF' || indicador.tipo === 'USD' ? 2 : 0;

    return `$ ${new Intl.NumberFormat('es-CL', {
        minimumFractionDigits: decimales,
        maximumFractionDigits: decimales,
    }).format(valor)}`;
}

function formatearMonto(monto: string | null): string {
    if (monto === null) {
        return '—';
    }

    const valor = Number(monto);

    if (Number.isNaN(valor)) {
        return monto;
    }

    return `$ ${new Intl.NumberFormat('es-CL', { maximumFractionDigits: 0 }).format(valor)}`;
}

function KpiCard({
    titulo,
    valor,
    pie,
    icono: Icono,
}: {
    titulo: string;
    valor: number;
    pie: string;
    icono: LucideIcon;
}) {
    return (
        <article className="flex flex-col gap-2.5 rounded-2xl border bg-card p-4 shadow-sm">
            <div className="flex items-center justify-between text-xs font-medium text-muted-foreground">
                <span>{titulo}</span>
                <span className="grid size-7 place-items-center rounded-lg bg-accent text-accent-foreground">
                    <Icono className="size-3.5" />
                </span>
            </div>
            <div className="text-2xl font-bold tracking-tight tabular-nums">
                {new Intl.NumberFormat('es-CL').format(valor)}
            </div>
            <div className="text-[11px] text-muted-foreground">{pie}</div>
        </article>
    );
}

export default function Dashboard({
    kpis,
    indicadores,
    casosRecientes,
}: PageProps) {
    return (
        <>
            <Head title="Panel general" />

            <div className="flex h-full flex-1 flex-col gap-5 p-4">
                <h1 className="text-[22px] font-bold tracking-tight">
                    Panel general
                </h1>

                <section className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                    <KpiCard
                        titulo="Casos de pago activos"
                        valor={kpis.casos_pago_activos}
                        pie="Casos con tramitación abierta"
                        icono={Wallet}
                    />
                    <KpiCard
                        titulo="Egresos CGU del mes"
                        valor={kpis.egresos_cgu_mes}
                        pie="Registrados en el mes en curso"
                        icono={Receipt}
                    />
                    <KpiCard
                        titulo="Adquisiciones activas"
                        valor={kpis.adquisiciones_activas}
                        pie="Procesos de adquisición abiertos"
                        icono={ShoppingCart}
                    />
                    <KpiCard
                        titulo="Informes en curso"
                        valor={kpis.informes_en_curso}
                        pie="Informes razonados sin cerrar"
                        icono={FileBarChart}
                    />
                </section>

                {indicadores.length > 0 && (
                    <section className="grid grid-cols-2 gap-3 md:grid-cols-5">
                        {indicadores.map((indicador) => (
                            <div
                                key={indicador.tipo}
                                className="flex items-center gap-2.5 rounded-2xl border bg-card px-3.5 py-2.5 shadow-sm"
                            >
                                <TrendingUp className="size-4 shrink-0 text-primary" />
                                <div className="min-w-0">
                                    <div className="text-[10px] tracking-widest text-muted-foreground uppercase">
                                        {ETIQUETAS_INDICADOR[indicador.tipo] ??
                                            indicador.tipo}
                                    </div>
                                    <div className="truncate font-mono text-sm font-semibold">
                                        {formatearValor(indicador)}
                                    </div>
                                </div>
                            </div>
                        ))}
                    </section>
                )}

                <section className="rounded-2xl border bg-card p-4 shadow-sm">
                    <h3 className="mb-3 text-sm font-semibold">
                        Casos de pago recientes
                    </h3>

                    {casosRecientes.length === 0 && (
                        <p className="py-8 text-center text-sm text-muted-foreground">
                            Aún no hay casos de pago registrados.
                        </p>
                    )}

                    {casosRecientes.length > 0 && (
                        <table className="w-full border-collapse text-[13px]">
                            <thead>
                                <tr className="border-b text-left">
                                    <th className="px-2.5 py-2 text-[11px] font-medium tracking-wider text-muted-foreground uppercase">
                                        SGF ID
                                    </th>
                                    <th className="px-2.5 py-2 text-[11px] font-medium tracking-wider text-muted-foreground uppercase">
                                        Proveedor
                                    </th>
                                    <th className="px-2.5 py-2 text-right text-[11px] font-medium tracking-wider text-muted-foreground uppercase">
                                        Monto
                                    </th>
                                    <th className="px-2.5 py-2 text-[11px] font-medium tracking-wider text-muted-foreground uppercase">
                                        Estado
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                {casosRecientes.map((caso) => (
                                    <tr
                                        key={caso.id}
                                        className="border-b last:border-b-0 hover:bg-muted/50"
                                    >
                                        <td className="px-2.5 py-3">
                                            <Link
                                                href={casos.show(caso.id)}
                                                className="font-mono text-xs font-medium text-primary hover:underline"
                                                prefetch
                                            >
                                                {caso.sgf_id}
                                            </Link>
                                        </td>
                                        <td className="px-2.5 py-3 font-medium">
                                            {caso.proveedor ?? '—'}
                                        </td>
                                        <td className="px-2.5 py-3 text-right font-semibold tabular-nums">
                                            {formatearMonto(caso.monto)}
                                        </td>
                                        <td className="px-2.5 py-3">
                                            {caso.estado !== null ? (
                                                <Badge
                                                    variant={
                                                        caso.cerrado
                                                            ? 'outline'
                                                            : 'secondary'
                                                    }
                                                >
                                                    {caso.estado}
                                                </Badge>
                                            ) : (
                                                '—'
                                            )}
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    )}
                </section>
            </div>
        </>
    );
}

Dashboard.layout = {
    breadcrumbs: [
        {
            title: 'Panel general',
            href: dashboard(),
        },
    ],
};

import { usePage } from '@inertiajs/react';
import { ThemeToggle } from '@/components/theme-toggle';
import type { AuthLayoutProps } from '@/types';

export type IndicadorLogin = {
    tipo: string;
    valor: string;
    fecha_valor: string | null;
    periodo: string | null;
};

const ETIQUETAS: Record<string, string> = {
    UF: 'U.F',
    UTM: 'U.T.M',
    UTA: 'U.T.A',
    IPC: 'I.P.C',
    USD: 'Dólar',
};

function formatearIndicador(indicador: IndicadorLogin): string {
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

export default function AuthSimpleLayout({
    children,
    title,
    description,
}: AuthLayoutProps) {
    const { indicadores } = usePage<{
        indicadores?: IndicadorLogin[];
    }>().props;

    return (
        <div className="relative min-h-svh overflow-hidden bg-background">
            {/* Escena de fondo */}
            <div
                aria-hidden
                className="pointer-events-none absolute inset-0"
                style={{
                    background:
                        'radial-gradient(1200px 700px at 80% 10%, var(--accent), transparent 60%), radial-gradient(900px 600px at 10% 90%, var(--accent), transparent 60%)',
                }}
            />
            <div
                aria-hidden
                className="pointer-events-none absolute inset-0"
                style={{
                    backgroundImage:
                        'linear-gradient(var(--border) 1px, transparent 1px), linear-gradient(90deg, var(--border) 1px, transparent 1px)',
                    backgroundSize: '56px 56px',
                    maskImage:
                        'radial-gradient(ellipse at center, black 30%, transparent 75%)',
                }}
            />

            {/* Topbar */}
            <header className="fixed inset-x-0 top-0 z-10 flex items-center justify-between px-7 py-5">
                <span className="inline-flex items-center rounded-xl border bg-card px-3 py-1.5 shadow-sm">
                    <img
                        src="/images/logo-capj-light.png"
                        alt="Poder Judicial — República de Chile"
                        className="h-10 w-auto dark:hidden"
                    />
                    <img
                        src="/images/logo-capj-dark.png"
                        alt="Poder Judicial — República de Chile"
                        className="hidden h-10 w-auto dark:block"
                    />
                </span>
                <ThemeToggle />
            </header>

            {/* Chips de indicadores económicos */}
            {indicadores !== undefined && indicadores.length > 0 && (
                <div className="fixed inset-x-0 top-24 z-[4] mx-auto hidden max-w-5xl justify-between gap-3.5 px-12 md:flex">
                    {indicadores.map((indicador) => (
                        <div
                            key={indicador.tipo}
                            className="flex flex-1 items-center gap-2.5 rounded-2xl border bg-card/80 px-3.5 py-2.5 shadow-sm backdrop-blur-md"
                        >
                            <span className="size-2 shrink-0 rounded-full bg-primary shadow-[0_0_0_4px_var(--accent)]" />
                            <div className="min-w-0">
                                <div className="text-[11px] tracking-widest text-muted-foreground uppercase">
                                    {ETIQUETAS[indicador.tipo] ??
                                        indicador.tipo}
                                </div>
                                <div className="font-mono text-sm font-semibold">
                                    {formatearIndicador(indicador)}
                                </div>
                            </div>
                        </div>
                    ))}
                </div>
            )}

            {/* Tarjeta central */}
            <main className="relative z-[2] grid min-h-svh place-items-center px-6 pt-44 pb-24">
                <div className="w-full max-w-md rounded-[22px] border bg-card/80 p-9 pb-7 shadow-xl backdrop-blur-xl">
                    <div className="flex flex-col gap-1.5">
                        <span className="inline-flex w-fit items-center gap-2 rounded-full bg-accent px-2.5 py-1 text-[11px] font-semibold tracking-wider text-accent-foreground uppercase">
                            <span className="size-1.5 animate-pulse rounded-full bg-primary" />
                            CAPJ +
                        </span>
                        <h1 className="mt-2 text-[20px] leading-tight font-bold tracking-tight">
                            {title}
                        </h1>
                        <p className="mb-4 text-sm text-muted-foreground">
                            {description}
                        </p>
                    </div>

                    {children}

                    <div className="mt-5 flex items-center justify-between border-t border-dashed pt-4 font-mono text-[11px] text-muted-foreground">
                        <span>Conexión cifrada · TLS 1.3</span>
                        <span>CAPJ +</span>
                    </div>
                </div>
            </main>

            {/* Footer */}
            <footer className="pointer-events-none fixed inset-x-0 bottom-5 z-[3] text-center text-xs text-muted-foreground">
                © 2026 Poder Judicial · República de Chile
            </footer>
        </div>
    );
}

import { ThemeToggle } from '@/components/theme-toggle';
import type { AuthLayoutProps } from '@/types';

export default function AuthSimpleLayout({
    children,
    title,
    description,
}: AuthLayoutProps) {
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
            <header className="fixed inset-x-0 top-0 z-10 flex items-center justify-end px-7 py-5">
                <ThemeToggle />
            </header>

            {/* Tarjeta central */}
            <main className="relative z-[2] grid min-h-svh place-items-center px-6 pt-16 pb-16 md:pt-24 md:pb-24">
                <div className="relative w-full max-w-md overflow-hidden rounded-[22px] border bg-card/80 p-9 pb-7 shadow-xl backdrop-blur-xl">
                    {/* Logo como fondo */}
                    <img
                        src="/images/logo-capj-light.png"
                        alt=""
                        aria-hidden
                        className="pointer-events-none absolute inset-0 m-auto h-64 w-64 object-contain opacity-[0.06] dark:hidden"
                    />
                    <img
                        src="/images/logo-capj-dark.png"
                        alt=""
                        aria-hidden
                        className="pointer-events-none absolute inset-0 m-auto hidden h-64 w-64 object-contain opacity-[0.08] dark:block"
                    />

                    <div className="relative z-10 flex flex-col gap-1.5">
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

                    <div className="relative z-10">{children}</div>

                    <div className="relative z-10 mt-5 flex items-center justify-between border-t border-dashed pt-4 font-mono text-[11px] text-muted-foreground">
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

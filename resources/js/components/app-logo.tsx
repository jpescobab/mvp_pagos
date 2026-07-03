export default function AppLogo({ subtitle }: { subtitle?: string }) {
    return (
        <>
            <div className="flex aspect-square size-9 shrink-0 items-center justify-center overflow-hidden rounded-lg border bg-white dark:bg-card">
                <img
                    src="/images/logo-capj-light.png"
                    alt="Poder Judicial"
                    className="size-full object-contain p-0.5 dark:hidden"
                />
                <img
                    src="/images/logo-capj-dark.png"
                    alt="Poder Judicial"
                    className="hidden size-full object-contain p-0.5 dark:block"
                />
            </div>
            <div className="ml-1 grid flex-1 text-left text-sm">
                <span className="mb-0.5 truncate leading-tight font-semibold">
                    CAPJ +
                </span>
                {subtitle && (
                    <span className="truncate text-xs leading-tight text-sidebar-foreground/70">
                        {subtitle}
                    </span>
                )}
            </div>
        </>
    );
}

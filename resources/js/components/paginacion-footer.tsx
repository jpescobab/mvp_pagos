import { Link } from '@inertiajs/react';
import { formatNumero } from '@/lib/format';
import type { Paginated } from '@/types/pago-proveedores';

type PaginacionFooterProps = {
    meta: Paginated<unknown>['meta'];
    links: Paginated<unknown>['links'];
};

function EnlacePaginacion({
    href,
    children,
}: {
    href: string | null;
    children: React.ReactNode;
}) {
    return (
        <Link
            href={href ?? '#'}
            className={href ? 'underline' : 'pointer-events-none opacity-50'}
        >
            {children}
        </Link>
    );
}

export function PaginacionFooter({ meta, links }: PaginacionFooterProps) {
    return (
        <div className="flex items-center justify-between text-sm text-muted-foreground">
            <span>
                Mostrando {formatNumero(meta.from ?? 0)}–
                {formatNumero(meta.to ?? 0)} de {formatNumero(meta.total)}
            </span>
            <div className="flex gap-2">
                <EnlacePaginacion href={links.first}>Primera</EnlacePaginacion>
                <EnlacePaginacion href={links.prev}>Anterior</EnlacePaginacion>
                <EnlacePaginacion href={links.next}>Siguiente</EnlacePaginacion>
                <EnlacePaginacion href={links.last}>Última</EnlacePaginacion>
            </div>
        </div>
    );
}

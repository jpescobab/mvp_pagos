import { router } from '@inertiajs/react';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import type { Paginated } from '@/types/pago-proveedores';

const TAMANOS_PAGINA = [15, 25, 50, 100];

type PaginationProps = {
    pagina: Paginated<unknown>;
    perPage: number;
    onNavigate: (url: string) => void;
    onPerPageChange: (perPage: number) => void;
};

export function Pagination({
    pagina,
    perPage,
    onNavigate,
    onPerPageChange,
}: PaginationProps) {
    return (
        <div className="flex flex-col items-center justify-between gap-3 text-sm text-muted-foreground sm:flex-row">
            <span>
                Mostrando {pagina.meta.from ?? 0}–{pagina.meta.to ?? 0} de{' '}
                {pagina.meta.total}
            </span>

            <div className="flex items-center gap-4">
                <div className="flex items-center gap-2">
                    <span>Por página</span>
                    <Select
                        value={String(perPage)}
                        onValueChange={(value) =>
                            onPerPageChange(Number(value))
                        }
                    >
                        <SelectTrigger size="sm" className="w-20">
                            <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                            {TAMANOS_PAGINA.map((tamano) => (
                                <SelectItem
                                    key={tamano}
                                    value={String(tamano)}
                                >
                                    {tamano}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                </div>

                <div className="flex gap-2">
                    <button
                        type="button"
                        disabled={!pagina.links.prev}
                        onClick={() =>
                            pagina.links.prev && onNavigate(pagina.links.prev)
                        }
                        className="underline disabled:pointer-events-none disabled:opacity-50"
                    >
                        Anterior
                    </button>
                    <button
                        type="button"
                        disabled={!pagina.links.next}
                        onClick={() =>
                            pagina.links.next && onNavigate(pagina.links.next)
                        }
                        className="underline disabled:pointer-events-none disabled:opacity-50"
                    >
                        Siguiente
                    </button>
                </div>
            </div>
        </div>
    );
}

export function navegarPaginacion(url: string) {
    router.get(
        url,
        {},
        { preserveState: true, preserveScroll: true },
    );
}

import { usePage } from '@inertiajs/react';
import { TrendingUp } from 'lucide-react';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import {
    ETIQUETAS_INDICADOR,
    formatearValorIndicador,
} from '@/lib/indicadores';

export function TopbarIndicadores() {
    const { indicadoresTopbar } = usePage().props;

    if (indicadoresTopbar.length === 0) {
        return null;
    }

    return (
        <DropdownMenu>
            <DropdownMenuTrigger asChild>
                <Button
                    variant="outline"
                    size="icon"
                    aria-label="Indicadores económicos"
                >
                    <TrendingUp className="size-4" />
                </Button>
            </DropdownMenuTrigger>
            <DropdownMenuContent align="end" className="w-56">
                <DropdownMenuLabel>Indicadores económicos</DropdownMenuLabel>
                <DropdownMenuSeparator />
                <div className="flex flex-col gap-2 px-2 py-1.5">
                    {indicadoresTopbar.map((indicador) => (
                        <div
                            key={indicador.codigo}
                            className="flex items-center justify-between text-sm"
                        >
                            <span className="text-muted-foreground">
                                {ETIQUETAS_INDICADOR[indicador.codigo] ??
                                    indicador.codigo}
                            </span>
                            <span className="font-mono tabular-nums">
                                {formatearValorIndicador(indicador)}
                            </span>
                        </div>
                    ))}
                </div>
            </DropdownMenuContent>
        </DropdownMenu>
    );
}

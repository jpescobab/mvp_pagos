import { esNegativo, formatMonto, formatNumero } from '@/lib/format';
import { cn } from '@/lib/utils';

type ValorNumerico = number | string | null | undefined;

type MontoProps = {
    valor: ValorNumerico;
    variante?: 'monto' | 'numero';
    opciones?: Intl.NumberFormatOptions;
    className?: string;
};

export function Monto({
    valor,
    variante = 'monto',
    opciones,
    className,
}: MontoProps) {
    const texto =
        variante === 'monto'
            ? formatMonto(valor, opciones)
            : formatNumero(valor, opciones);

    return (
        <span
            className={cn(
                'font-mono tabular-nums',
                esNegativo(valor) && 'text-destructive',
                className,
            )}
        >
            {texto}
        </span>
    );
}

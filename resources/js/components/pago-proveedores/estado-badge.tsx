import { AlertTriangle, CheckCircle2, Clock, Inbox, XCircle } from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import {
    Tooltip,
    TooltipContent,
    TooltipTrigger,
} from '@/components/ui/tooltip';
import type { EstadoWorkflow } from '@/types/pago-proveedores';

const CODIGOS_RECHAZO = ['rechazada', 'anulada'];
const CODIGOS_ADVERTENCIA = ['observada'];

type Variante = 'rechazo' | 'advertencia' | 'final' | 'inicial' | 'default';

function variantePara(estado: EstadoWorkflow): Variante {
    if (CODIGOS_RECHAZO.some((codigo) => estado.codigo.includes(codigo))) {
        return 'rechazo';
    }

    if (CODIGOS_ADVERTENCIA.some((codigo) => estado.codigo.includes(codigo))) {
        return 'advertencia';
    }

    if (estado.es_final) {
        return 'final';
    }

    if (estado.es_inicial) {
        return 'inicial';
    }

    return 'default';
}

const ICONO_POR_VARIANTE: Record<Variante, typeof CheckCircle2> = {
    rechazo: XCircle,
    advertencia: AlertTriangle,
    final: CheckCircle2,
    inicial: Inbox,
    default: Clock,
};

const COLOR_POR_VARIANTE: Record<Variante, string> = {
    rechazo: 'text-destructive',
    advertencia: 'text-amber-700 dark:text-amber-400',
    final: 'text-green-700 dark:text-green-400',
    inicial: 'text-primary',
    default: 'text-muted-foreground',
};

type EstadoBadgeProps = {
    estado: EstadoWorkflow;
    /** Renderiza solo un ícono con tooltip en vez del badge con texto, para listados densos con poco espacio horizontal. */
    compact?: boolean;
};

export function EstadoBadge({ estado, compact = false }: EstadoBadgeProps) {
    const variante = variantePara(estado);

    if (compact) {
        const Icono = ICONO_POR_VARIANTE[variante];

        return (
            <Tooltip>
                <TooltipTrigger asChild>
                    <span className="inline-flex cursor-default items-center">
                        <Icono
                            className={`size-4 ${COLOR_POR_VARIANTE[variante]}`}
                        />
                        <span className="sr-only">{estado.nombre}</span>
                    </span>
                </TooltipTrigger>
                <TooltipContent>{estado.nombre}</TooltipContent>
            </Tooltip>
        );
    }

    if (variante === 'rechazo') {
        return <Badge variant="destructive">{estado.nombre}</Badge>;
    }

    if (variante === 'advertencia') {
        return (
            <Badge
                variant="outline"
                className="border-transparent bg-amber-500/15 text-amber-700 dark:bg-amber-400/10 dark:text-amber-400"
            >
                {estado.nombre}
            </Badge>
        );
    }

    if (variante === 'final') {
        return (
            <Badge
                variant="outline"
                className="border-transparent bg-green-600/15 text-green-700 dark:bg-green-400/10 dark:text-green-400"
            >
                {estado.nombre}
            </Badge>
        );
    }

    if (variante === 'inicial') {
        return <Badge variant="outline">{estado.nombre}</Badge>;
    }

    return <Badge variant="secondary">{estado.nombre}</Badge>;
}

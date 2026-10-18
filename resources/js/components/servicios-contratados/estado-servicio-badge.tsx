import { Badge } from '@/components/ui/badge';
import type {
    EstadoPeriodo,
    EstadoServicio,
} from '@/types/servicios-contratados';

type Apariencia = { texto: string; clase: string };

const ADVERTENCIA = 'bg-amber-500/15 text-amber-700 dark:text-amber-400';
const NEUTRO = 'bg-muted text-muted-foreground';

const ESTADO_SERVICIO: Record<EstadoServicio, Apariencia> = {
    vigente: { texto: 'Vigente', clase: 'text-success bg-success-soft' },
    por_vencer: { texto: 'Por vencer', clase: ADVERTENCIA },
    vencido: { texto: 'Vencido', clase: 'text-danger bg-danger-soft' },
    terminado: { texto: 'Terminado', clase: NEUTRO },
};

const ESTADO_PERIODO: Record<EstadoPeriodo, Apariencia> = {
    pagado: { texto: 'Pagado', clase: 'text-success bg-success-soft' },
    en_tramite: { texto: 'En trámite', clase: ADVERTENCIA },
    sin_caso: { texto: 'Sin caso', clase: NEUTRO },
    ambiguo: { texto: 'Ambiguo', clase: 'text-danger bg-danger-soft' },
};

export function EstadoServicioBadge({ estado }: { estado: EstadoServicio }) {
    const { texto, clase } = ESTADO_SERVICIO[estado];

    return <Badge className={`border-transparent ${clase}`}>{texto}</Badge>;
}

export function EstadoPeriodoBadge({ estado }: { estado: EstadoPeriodo }) {
    const { texto, clase } = ESTADO_PERIODO[estado];

    return <Badge className={`border-transparent ${clase}`}>{texto}</Badge>;
}

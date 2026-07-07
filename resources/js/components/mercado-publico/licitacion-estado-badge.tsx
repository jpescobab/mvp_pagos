import { Badge } from '@/components/ui/badge';

const ESTADOS_DANGER = ['desierta', 'revocada', 'suspendida', 'cancelada'];
const ESTADOS_SUCCESS = ['adjudicada'];

export function LicitacionEstadoBadge({ estado }: { estado: string | null }) {
    if (estado === null) {
        return <Badge variant="outline">Sin estado</Badge>;
    }

    const estadoNormalizado = estado.toLowerCase();

    if (ESTADOS_DANGER.some((codigo) => estadoNormalizado.includes(codigo))) {
        return (
            <Badge
                variant="outline"
                className="border-transparent bg-danger-soft text-destructive"
            >
                {estado}
            </Badge>
        );
    }

    if (ESTADOS_SUCCESS.some((codigo) => estadoNormalizado.includes(codigo))) {
        return (
            <Badge
                variant="outline"
                className="border-transparent bg-success-soft text-success"
            >
                {estado}
            </Badge>
        );
    }

    return <Badge variant="secondary">{estado}</Badge>;
}

import { Badge } from '@/components/ui/badge';

export function ImportacionEstadoBadge({ estado }: { estado: string }) {
    if (estado === 'completado') {
        return (
            <Badge
                variant="outline"
                className="border-transparent bg-success-soft text-success"
            >
                {estado}
            </Badge>
        );
    }

    if (estado === 'error') {
        return (
            <Badge
                variant="outline"
                className="border-transparent bg-danger-soft text-destructive"
            >
                {estado}
            </Badge>
        );
    }

    if (estado === 'huerfano') {
        return (
            <Badge
                variant="outline"
                className="border-transparent bg-warning-soft text-warning"
            >
                {estado}
            </Badge>
        );
    }

    return <Badge variant="secondary">{estado}</Badge>;
}

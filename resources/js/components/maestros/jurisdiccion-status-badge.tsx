import { Badge } from '@/components/ui/badge';

export function JurisdiccionStatusBadge({ activo }: { activo: boolean }) {
    if (activo) {
        return (
            <Badge
                variant="outline"
                className="border-transparent bg-success-soft text-success"
            >
                Activa
            </Badge>
        );
    }

    return (
        <Badge
            variant="outline"
            className="border-transparent bg-danger-soft text-destructive"
        >
            Inactiva
        </Badge>
    );
}

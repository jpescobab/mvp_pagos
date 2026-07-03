import { Badge } from '@/components/ui/badge';

export function ProveedorStatusBadge({ activo }: { activo: boolean }) {
    if (activo) {
        return (
            <Badge
                variant="outline"
                className="border-transparent bg-success-soft text-success"
            >
                Activo
            </Badge>
        );
    }

    return (
        <Badge
            variant="outline"
            className="border-transparent bg-danger-soft text-destructive"
        >
            Inactivo
        </Badge>
    );
}

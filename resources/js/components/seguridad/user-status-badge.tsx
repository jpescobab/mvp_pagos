import { Badge } from '@/components/ui/badge';

export function UserStatusBadge({ active }: { active: boolean }) {
    if (active) {
        return (
            <Badge
                variant="outline"
                className="border-transparent bg-green-600/15 text-green-700 dark:bg-green-400/10 dark:text-green-400"
            >
                Activo
            </Badge>
        );
    }

    return <Badge variant="secondary">Inactivo</Badge>;
}

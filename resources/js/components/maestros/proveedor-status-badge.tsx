import { Badge } from '@/components/ui/badge';
import type { EstadoProveedor } from '@/types/maestros';

const ESTILOS: Record<EstadoProveedor, { etiqueta: string; clase: string }> = {
    borrador: {
        etiqueta: 'Borrador',
        clase: 'border-transparent bg-warning-soft text-warning',
    },
    activo: {
        etiqueta: 'Activo',
        clase: 'border-transparent bg-success-soft text-success',
    },
    inactivo: {
        etiqueta: 'Inactivo',
        clase: 'border-transparent bg-danger-soft text-destructive',
    },
};

export function ProveedorStatusBadge({ estado }: { estado: EstadoProveedor }) {
    const { etiqueta, clase } = ESTILOS[estado];

    return (
        <Badge variant="outline" className={clase}>
            {etiqueta}
        </Badge>
    );
}

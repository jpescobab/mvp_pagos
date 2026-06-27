import { Badge } from '@/components/ui/badge';
import type { EstadoWorkflow } from '@/types/pago-proveedores';

const CODIGOS_RECHAZO = ['rechazada', 'anulada'];
const CODIGOS_ADVERTENCIA = ['observada'];

export function EstadoBadge({ estado }: { estado: EstadoWorkflow }) {
    if (CODIGOS_RECHAZO.some((codigo) => estado.codigo.includes(codigo))) {
        return <Badge variant="destructive">{estado.nombre}</Badge>;
    }

    if (CODIGOS_ADVERTENCIA.some((codigo) => estado.codigo.includes(codigo))) {
        return (
            <Badge
                variant="outline"
                className="border-transparent bg-amber-500/15 text-amber-700 dark:bg-amber-400/10 dark:text-amber-400"
            >
                {estado.nombre}
            </Badge>
        );
    }

    if (estado.es_final) {
        return (
            <Badge
                variant="outline"
                className="border-transparent bg-green-600/15 text-green-700 dark:bg-green-400/10 dark:text-green-400"
            >
                {estado.nombre}
            </Badge>
        );
    }

    if (estado.es_inicial) {
        return <Badge variant="outline">{estado.nombre}</Badge>;
    }

    return <Badge variant="secondary">{estado.nombre}</Badge>;
}

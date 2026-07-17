import { CheckCircle2 } from 'lucide-react';
import {
    Tooltip,
    TooltipContent,
    TooltipTrigger,
} from '@/components/ui/tooltip';

/**
 * Indicador puramente informativo: el caso cumple, en su instancia de
 * revisión activa, el mismo criterio que ya habilita la aprobación manual en
 * Revisión de Pagos (checklist obligatorio aprobado y totales verificados).
 * No implica ningún cambio de estado del `Proceso`.
 */
export function ListoParaRevisarBadge() {
    return (
        <Tooltip>
            <TooltipTrigger asChild>
                <span className="inline-flex cursor-default items-center">
                    <CheckCircle2 className="size-4 text-success" />
                    <span className="sr-only">Listo para revisar</span>
                </span>
            </TooltipTrigger>
            <TooltipContent>
                Listo para revisar: checklist obligatorio aprobado y totales
                verificados
            </TooltipContent>
        </Tooltip>
    );
}

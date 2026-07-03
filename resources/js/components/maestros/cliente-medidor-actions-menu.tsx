import { MoreHorizontal } from 'lucide-react';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import {
    Tooltip,
    TooltipContent,
    TooltipTrigger,
} from '@/components/ui/tooltip';

export function ClienteMedidorActionsMenu() {
    return (
        <DropdownMenu>
            <DropdownMenuTrigger asChild>
                <Button variant="ghost" size="icon" className="size-6">
                    <MoreHorizontal className="size-3.5" />
                    <span className="sr-only">Acciones</span>
                </Button>
            </DropdownMenuTrigger>
            <DropdownMenuContent align="end">
                <Tooltip>
                    <TooltipTrigger asChild>
                        <div>
                            <DropdownMenuItem disabled>
                                Ver detalle
                            </DropdownMenuItem>
                        </div>
                    </TooltipTrigger>
                    <TooltipContent>Disponible próximamente</TooltipContent>
                </Tooltip>
            </DropdownMenuContent>
        </DropdownMenu>
    );
}

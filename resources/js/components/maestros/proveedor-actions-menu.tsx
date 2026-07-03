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

export function ProveedorActionsMenu() {
    return (
        <DropdownMenu>
            <DropdownMenuTrigger asChild>
                <Button variant="ghost" size="icon">
                    <MoreHorizontal className="size-4" />
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

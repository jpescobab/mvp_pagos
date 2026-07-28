import { Link, router } from '@inertiajs/react';
import { MoreHorizontal } from 'lucide-react';
import { useState } from 'react';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import instituciones from '@/routes/maestros/instituciones';
import type { Institucion } from '@/types/maestros';

export function InstitucionActionsMenu({
    institucion,
}: {
    institucion: Institucion;
}) {
    const [confirmandoEliminar, setConfirmandoEliminar] = useState(false);
    const [procesando, setProcesando] = useState(false);

    function eliminar() {
        setProcesando(true);

        router.delete(instituciones.destroy(institucion.id).url, {
            preserveScroll: true,
            onFinish: () => {
                setProcesando(false);
                setConfirmandoEliminar(false);
            },
        });
    }

    return (
        <>
            <DropdownMenu>
                <DropdownMenuTrigger asChild>
                    <Button variant="ghost" size="icon" className="size-6">
                        <MoreHorizontal className="size-3.5" />
                        <span className="sr-only">Acciones</span>
                    </Button>
                </DropdownMenuTrigger>
                <DropdownMenuContent align="end">
                    <DropdownMenuItem asChild>
                        <Link href={instituciones.show(institucion.id).url}>
                            Ver detalle
                        </Link>
                    </DropdownMenuItem>
                    <DropdownMenuItem asChild>
                        <Link href={instituciones.edit(institucion.id).url}>
                            Editar
                        </Link>
                    </DropdownMenuItem>
                    <DropdownMenuItem
                        variant="destructive"
                        onSelect={() => setConfirmandoEliminar(true)}
                    >
                        Eliminar
                    </DropdownMenuItem>
                </DropdownMenuContent>
            </DropdownMenu>

            <Dialog
                open={confirmandoEliminar}
                onOpenChange={(open) => !open && setConfirmandoEliminar(false)}
            >
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Eliminar institución</DialogTitle>
                        <DialogDescription>
                            ¿Confirmas eliminar "{institucion.nombre}"? Esta
                            acción no se puede deshacer. Si tiene jurisdicciones
                            asociadas, la eliminación será rechazada.
                        </DialogDescription>
                    </DialogHeader>
                    <DialogFooter>
                        <Button
                            variant="outline"
                            onClick={() => setConfirmandoEliminar(false)}
                            disabled={procesando}
                        >
                            Cancelar
                        </Button>
                        <Button
                            variant="destructive"
                            onClick={eliminar}
                            disabled={procesando}
                        >
                            Eliminar
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </>
    );
}

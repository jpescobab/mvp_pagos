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
import definiciones from '@/routes/informes-razonados/definiciones';
import type { DefinicionInformeRazonado } from '@/types/informes-razonados';

export function DefinicionActionsMenu({
    definicion,
    puedeAdministrar,
}: {
    definicion: DefinicionInformeRazonado;
    puedeAdministrar: boolean;
}) {
    const [confirmandoEliminar, setConfirmandoEliminar] = useState(false);
    const [procesando, setProcesando] = useState(false);

    function eliminar() {
        setProcesando(true);

        router.delete(definiciones.destroy(definicion.id).url, {
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
                        <Link href={definiciones.show(definicion.id).url}>
                            Ver detalle
                        </Link>
                    </DropdownMenuItem>
                    {puedeAdministrar && (
                        <>
                            <DropdownMenuItem asChild>
                                <Link href={definiciones.edit(definicion.id).url}>
                                    Editar
                                </Link>
                            </DropdownMenuItem>
                            <DropdownMenuItem
                                variant="destructive"
                                onSelect={() => setConfirmandoEliminar(true)}
                            >
                                Eliminar
                            </DropdownMenuItem>
                        </>
                    )}
                </DropdownMenuContent>
            </DropdownMenu>

            <Dialog
                open={confirmandoEliminar}
                onOpenChange={(open) => !open && setConfirmandoEliminar(false)}
            >
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Eliminar definición</DialogTitle>
                        <DialogDescription>
                            ¿Confirmas eliminar "{definicion.nombre}"? Esta
                            acción no se puede deshacer. Si tiene ejecuciones
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

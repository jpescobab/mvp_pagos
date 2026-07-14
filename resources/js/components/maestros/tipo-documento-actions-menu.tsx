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
import tiposDocumento from '@/routes/maestros/tipos-documento';
import type { TipoDocumentoMaestro } from '@/types/maestros';

export function TipoDocumentoActionsMenu({
    tipoDocumento,
}: {
    tipoDocumento: TipoDocumentoMaestro;
}) {
    const [confirmandoEliminar, setConfirmandoEliminar] = useState(false);
    const [procesando, setProcesando] = useState(false);

    function eliminar() {
        setProcesando(true);

        router.delete(tiposDocumento.destroy(tipoDocumento.id).url, {
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
                        <Link href={tiposDocumento.show(tipoDocumento.id).url}>
                            Ver detalle
                        </Link>
                    </DropdownMenuItem>
                    <DropdownMenuItem asChild>
                        <Link href={tiposDocumento.edit(tipoDocumento.id).url}>
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
                        <DialogTitle>Eliminar tipo de documento</DialogTitle>
                        <DialogDescription>
                            ¿Confirmas eliminar "{tipoDocumento.nombre}"? Esta
                            acción no se puede deshacer. Si tiene documentos o
                            requisitos documentales asociados, la eliminación
                            será rechazada.
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

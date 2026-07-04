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
import proveedores from '@/routes/maestros/proveedores';
import type { Proveedor } from '@/types/maestros';

export function ProveedorActionsMenu({ proveedor }: { proveedor: Proveedor }) {
    const [confirmandoEliminar, setConfirmandoEliminar] = useState(false);
    const [procesando, setProcesando] = useState(false);

    function eliminar() {
        setProcesando(true);

        router.delete(proveedores.destroy(proveedor.id).url, {
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
                        <Link href={proveedores.show(proveedor.id).url}>
                            Ver detalle
                        </Link>
                    </DropdownMenuItem>
                    <DropdownMenuItem asChild>
                        <Link href={proveedores.edit(proveedor.id).url}>
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
                        <DialogTitle>Eliminar proveedor</DialogTitle>
                        <DialogDescription>
                            ¿Confirmas eliminar a "{proveedor.nombre}"? Esta
                            acción no se puede deshacer. Si tiene clientes
                            medidores, casos de pago, facturas o procesos de
                            adquisición asociados, la eliminación será
                            rechazada.
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

import { router } from '@inertiajs/react';
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
import {
    Tooltip,
    TooltipContent,
    TooltipTrigger,
} from '@/components/ui/tooltip';
import usuarios from '@/routes/usuarios';
import type { PermisosUsuarios, UsuarioListado } from '@/types/seguridad';

type Confirmacion = 'activar' | 'desactivar' | 'reset-password' | null;

type UserActionsMenuProps = {
    usuario: UsuarioListado;
    permissions: PermisosUsuarios;
};

export function UserActionsMenu({ usuario, permissions }: UserActionsMenuProps) {
    const [confirmacion, setConfirmacion] = useState<Confirmacion>(null);
    const [procesando, setProcesando] = useState(false);

    function confirmar() {
        setProcesando(true);

        const opciones = {
            preserveState: true,
            preserveScroll: true,
            onFinish: () => {
                setProcesando(false);
                setConfirmacion(null);
            },
        };

        if (confirmacion === 'activar') {
            router.patch(usuarios.activar(usuario.id).url, {}, opciones);
        } else if (confirmacion === 'desactivar') {
            router.patch(usuarios.desactivar(usuario.id).url, {}, opciones);
        } else if (confirmacion === 'reset-password') {
            router.post(usuarios.resetPassword(usuario.id).url, {}, opciones);
        }
    }

    const diferidas: Array<{
        label: string;
        permitido: boolean;
    }> = [{ label: 'Ver detalle', permitido: permissions.can_view_user }];

    return (
        <>
            <DropdownMenu>
                <DropdownMenuTrigger asChild>
                    <Button variant="ghost" size="icon">
                        <MoreHorizontal className="size-4" />
                        <span className="sr-only">Acciones</span>
                    </Button>
                </DropdownMenuTrigger>
                <DropdownMenuContent align="end">
                    {permissions.can_edit_user && (
                        <DropdownMenuItem
                            onSelect={() =>
                                router.get(usuarios.edit(usuario.id).url)
                            }
                        >
                            Editar usuario
                        </DropdownMenuItem>
                    )}
                    {!usuario.active && permissions.can_activate_user && (
                        <DropdownMenuItem
                            onSelect={() => setConfirmacion('activar')}
                        >
                            Activar usuario
                        </DropdownMenuItem>
                    )}
                    {usuario.active && permissions.can_deactivate_user && (
                        <DropdownMenuItem
                            onSelect={() => setConfirmacion('desactivar')}
                            variant="destructive"
                        >
                            Desactivar usuario
                        </DropdownMenuItem>
                    )}
                    {permissions.can_reset_password && (
                        <DropdownMenuItem
                            onSelect={() => setConfirmacion('reset-password')}
                        >
                            Resetear contraseña
                        </DropdownMenuItem>
                    )}
                    {diferidas
                        .filter((accion) => accion.permitido)
                        .map((accion) => (
                            <Tooltip key={accion.label}>
                                <TooltipTrigger asChild>
                                    <div>
                                        <DropdownMenuItem disabled>
                                            {accion.label}
                                        </DropdownMenuItem>
                                    </div>
                                </TooltipTrigger>
                                <TooltipContent>
                                    Disponible próximamente
                                </TooltipContent>
                            </Tooltip>
                        ))}
                </DropdownMenuContent>
            </DropdownMenu>

            <Dialog
                open={confirmacion !== null}
                onOpenChange={(open) => !open && setConfirmacion(null)}
            >
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>
                            {confirmacion === 'activar' &&
                                'Activar usuario'}
                            {confirmacion === 'desactivar' &&
                                'Desactivar usuario'}
                            {confirmacion === 'reset-password' &&
                                'Resetear contraseña'}
                        </DialogTitle>
                        <DialogDescription>
                            {confirmacion === 'activar' &&
                                `${usuario.name} podrá volver a iniciar sesión.`}
                            {confirmacion === 'desactivar' &&
                                `${usuario.name} no podrá iniciar sesión, pero conserva su historial.`}
                            {confirmacion === 'reset-password' &&
                                `Se generará una contraseña temporal para ${usuario.name}. Deberá cambiarla en su próximo ingreso.`}
                        </DialogDescription>
                    </DialogHeader>
                    <DialogFooter>
                        <Button
                            variant="outline"
                            onClick={() => setConfirmacion(null)}
                            disabled={procesando}
                        >
                            Cancelar
                        </Button>
                        <Button onClick={confirmar} disabled={procesando}>
                            Confirmar
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </>
    );
}

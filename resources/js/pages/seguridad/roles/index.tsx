import { Head, Link, router, usePage } from '@inertiajs/react';
import { useEffect, useRef, useState } from 'react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import {
    Tooltip,
    TooltipContent,
    TooltipTrigger,
} from '@/components/ui/tooltip';
import roles from '@/routes/roles';
import type { FiltrosRoles, RolListado } from '@/types/seguridad';

type PageProps = {
    roles: RolListado[];
    filters: FiltrosRoles;
};

export default function RolesIndex() {
    const page = usePage<PageProps>();
    const { roles: listaRoles, filters } = page.props;
    const { flash } = page;

    const [search, setSearch] = useState(filters.search ?? '');
    const [rolAEliminar, setRolAEliminar] = useState<RolListado | null>(null);
    const [procesando, setProcesando] = useState(false);
    const esPrimeraCarga = useRef(true);

    useEffect(() => {
        if (esPrimeraCarga.current) {
            esPrimeraCarga.current = false;

            return;
        }

        const timeout = setTimeout(() => {
            if (search === (filters.search ?? '')) {
                return;
            }

            router.get(
                roles.index().url,
                { search: search === '' ? null : search },
                { preserveState: true, preserveScroll: true },
            );
        }, 300);

        return () => clearTimeout(timeout);
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [search]);

    function eliminar() {
        if (rolAEliminar === null) {
            return;
        }

        setProcesando(true);

        router.delete(roles.destroy(rolAEliminar.id).url, {
            preserveScroll: true,
            onFinish: () => {
                setProcesando(false);
                setRolAEliminar(null);
            },
        });
    }

    function motivoBloqueo(rol: RolListado): string | null {
        if (rol.is_core) {
            return 'Los roles core del sistema no se pueden eliminar.';
        }

        if (rol.users_count > 0) {
            return 'Este rol tiene usuarios asignados. Reasígnalos antes de eliminarlo.';
        }

        return null;
    }

    return (
        <>
            <Head title="Roles y permisos" />

            <div className="flex h-full flex-1 flex-col gap-4 p-4">
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-xl font-semibold tracking-tight">
                            Roles y permisos
                        </h1>
                        <p className="text-sm text-muted-foreground">
                            Administración de roles y sus permisos asignados
                        </p>
                    </div>
                    <Button asChild>
                        <Link href={roles.create()}>Crear rol</Link>
                    </Button>
                </div>

                {flash.error && (
                    <div className="rounded-md border border-destructive/50 bg-destructive/10 px-4 py-2 text-sm text-destructive-foreground">
                        {flash.error}
                    </div>
                )}

                <Input
                    value={search}
                    onChange={(e) => setSearch(e.target.value)}
                    placeholder="Buscar rol por nombre…"
                    className="max-w-xs"
                />

                {listaRoles.length === 0 && (
                    <div className="rounded-xl border px-4 py-10 text-center text-muted-foreground">
                        No se encontraron roles.
                    </div>
                )}

                {listaRoles.length > 0 && (
                    <div className="overflow-hidden rounded-xl border">
                        <table className="w-full text-sm">
                            <thead className="bg-muted/50 text-left text-muted-foreground">
                                <tr>
                                    <th className="px-4 py-2 font-medium">
                                        Nombre
                                    </th>
                                    <th className="px-4 py-2 font-medium">
                                        Usuarios
                                    </th>
                                    <th className="px-4 py-2 font-medium">
                                        Permisos
                                    </th>
                                    <th className="px-4 py-2 font-medium"></th>
                                </tr>
                            </thead>
                            <tbody className="divide-y">
                                {listaRoles.map((rol) => {
                                    const bloqueo = motivoBloqueo(rol);

                                    return (
                                        <tr
                                            key={rol.id}
                                            className="hover:bg-muted/30"
                                        >
                                            <td className="px-4 py-2 font-medium">
                                                {rol.name}
                                                {rol.is_core && (
                                                    <Badge
                                                        variant="secondary"
                                                        className="ml-2"
                                                    >
                                                        Core
                                                    </Badge>
                                                )}
                                            </td>
                                            <td className="px-4 py-2 text-muted-foreground">
                                                {rol.users_count}
                                            </td>
                                            <td className="px-4 py-2 text-muted-foreground">
                                                {rol.permissions_count}
                                            </td>
                                            <td className="px-4 py-2 text-right">
                                                <div className="flex justify-end gap-2">
                                                    <Button
                                                        variant="outline"
                                                        size="sm"
                                                        asChild
                                                    >
                                                        <Link
                                                            href={roles.edit(
                                                                rol.id,
                                                            )}
                                                        >
                                                            Editar
                                                        </Link>
                                                    </Button>
                                                    {bloqueo === null ? (
                                                        <Button
                                                            variant="outline"
                                                            size="sm"
                                                            onClick={() =>
                                                                setRolAEliminar(
                                                                    rol,
                                                                )
                                                            }
                                                        >
                                                            Eliminar
                                                        </Button>
                                                    ) : (
                                                        <Tooltip>
                                                            <TooltipTrigger
                                                                asChild
                                                            >
                                                                <span>
                                                                    <Button
                                                                        variant="outline"
                                                                        size="sm"
                                                                        disabled
                                                                    >
                                                                        Eliminar
                                                                    </Button>
                                                                </span>
                                                            </TooltipTrigger>
                                                            <TooltipContent>
                                                                {bloqueo}
                                                            </TooltipContent>
                                                        </Tooltip>
                                                    )}
                                                </div>
                                            </td>
                                        </tr>
                                    );
                                })}
                            </tbody>
                        </table>
                    </div>
                )}
            </div>

            <Dialog
                open={rolAEliminar !== null}
                onOpenChange={(open) => !open && setRolAEliminar(null)}
            >
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Eliminar rol</DialogTitle>
                        <DialogDescription>
                            {rolAEliminar &&
                                `¿Confirmas eliminar el rol "${rolAEliminar.name}"? Esta acción no se puede deshacer.`}
                        </DialogDescription>
                    </DialogHeader>
                    <DialogFooter>
                        <Button
                            variant="outline"
                            onClick={() => setRolAEliminar(null)}
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

RolesIndex.layout = {
    breadcrumbs: [
        {
            title: 'Roles y permisos',
            href: roles.index(),
        },
    ],
};

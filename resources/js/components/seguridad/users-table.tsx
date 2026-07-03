import { UserActionsMenu } from '@/components/seguridad/user-actions-menu';
import { UserStatusBadge } from '@/components/seguridad/user-status-badge';
import { Avatar, AvatarFallback } from '@/components/ui/avatar';
import { Badge } from '@/components/ui/badge';
import { useInitials } from '@/hooks/use-initials';
import type { PermisosUsuarios, UsuarioListado } from '@/types/seguridad';

function formatearFecha(fecha: string | null) {
    if (fecha === null) {
        return '—';
    }

    return new Date(fecha).toLocaleString('es-CL', {
        dateStyle: 'short',
        timeStyle: 'short',
    });
}

type UsersTableProps = {
    users: UsuarioListado[];
    permissions: PermisosUsuarios;
};

export function UsersTable({ users, permissions }: UsersTableProps) {
    const getInitials = useInitials();

    return (
        <>
            <div className="hidden overflow-x-auto rounded-xl border md:block">
                <table className="w-full table-fixed text-xs">
                    <thead className="bg-muted/50 text-left text-[10px] tracking-wide text-muted-foreground uppercase">
                        <tr>
                            <th className="w-[14%] px-2.5 py-1 font-medium">
                                Nombre
                            </th>
                            <th className="w-[13%] px-2.5 py-1 font-medium">
                                Email
                            </th>
                            <th className="w-[7%] px-2.5 py-1 font-medium">
                                RUT
                            </th>
                            <th className="w-[8%] px-2.5 py-1 font-medium">
                                Cargo
                            </th>
                            <th className="hidden w-[7%] px-2.5 py-1 font-medium lg:table-cell">
                                Unidad
                            </th>
                            <th className="hidden w-[7%] px-2.5 py-1 font-medium lg:table-cell">
                                Jurisdicción
                            </th>
                            <th className="hidden w-[8%] px-2.5 py-1 font-medium lg:table-cell">
                                Centro financiero
                            </th>
                            <th className="hidden w-[8%] px-2.5 py-1 font-medium lg:table-cell">
                                Centro de costo
                            </th>
                            <th className="w-[9%] px-2.5 py-1 font-medium">
                                Roles
                            </th>
                            <th className="w-[6%] px-2.5 py-1 font-medium">
                                Estado
                            </th>
                            <th className="w-[8%] px-2.5 py-1 font-medium">
                                Último acceso
                            </th>
                            <th className="w-[5%] px-2.5 py-1 font-medium"></th>
                        </tr>
                    </thead>
                    <tbody className="divide-y">
                        {users.map((usuario) => (
                            <tr key={usuario.id} className="hover:bg-muted/30">
                                <td className="px-2.5 py-1">
                                    <div className="flex items-center gap-2">
                                        <Avatar className="size-6 shrink-0">
                                            <AvatarFallback className="bg-accent text-[10px] font-semibold text-accent-foreground">
                                                {getInitials(usuario.name)}
                                            </AvatarFallback>
                                        </Avatar>
                                        <div
                                            className="min-w-0 truncate font-medium"
                                            title={usuario.name}
                                        >
                                            {usuario.name}
                                        </div>
                                    </div>
                                </td>
                                <td
                                    className="truncate px-2.5 py-1 text-muted-foreground"
                                    title={usuario.email}
                                >
                                    {usuario.email}
                                </td>
                                <td className="truncate px-2.5 py-1 font-mono">
                                    {usuario.rut ?? '—'}
                                </td>
                                <td
                                    className="truncate px-2.5 py-1"
                                    title={usuario.cargo ?? undefined}
                                >
                                    {usuario.cargo ?? '—'}
                                </td>
                                <td
                                    className="hidden truncate px-2.5 py-1 lg:table-cell"
                                    title={usuario.unidad ?? undefined}
                                >
                                    {usuario.unidad ?? '—'}
                                </td>
                                <td
                                    className="hidden truncate px-2.5 py-1 lg:table-cell"
                                    title={usuario.jurisdiccion?.nombre}
                                >
                                    {usuario.jurisdiccion?.nombre ?? '—'}
                                </td>
                                <td
                                    className="hidden truncate px-2.5 py-1 lg:table-cell"
                                    title={usuario.centro_financiero?.nombre}
                                >
                                    {usuario.centro_financiero?.nombre ?? '—'}
                                </td>
                                <td
                                    className="hidden truncate px-2.5 py-1 lg:table-cell"
                                    title={usuario.centro_costo?.nombre}
                                >
                                    {usuario.centro_costo?.nombre ?? '—'}
                                </td>
                                <td className="px-2.5 py-1">
                                    <div className="flex flex-wrap gap-1">
                                        {usuario.roles.length === 0 && '—'}
                                        {usuario.roles.map((rol) => (
                                            <Badge
                                                key={rol}
                                                variant="secondary"
                                                className="text-[10px]"
                                            >
                                                {rol}
                                            </Badge>
                                        ))}
                                    </div>
                                </td>
                                <td className="px-2.5 py-1">
                                    <UserStatusBadge active={usuario.active} />
                                </td>
                                <td className="truncate px-2.5 py-1 text-muted-foreground">
                                    {formatearFecha(usuario.last_login_at)}
                                </td>
                                <td className="px-2.5 py-1 text-right">
                                    <UserActionsMenu
                                        usuario={usuario}
                                        permissions={permissions}
                                    />
                                </td>
                            </tr>
                        ))}
                    </tbody>
                </table>
            </div>

            <div className="flex flex-col gap-3 md:hidden">
                {users.map((usuario) => (
                    <div key={usuario.id} className="rounded-xl border p-4">
                        <div className="flex items-start justify-between gap-2">
                            <div>
                                <p className="font-medium">{usuario.name}</p>
                                <p className="text-sm text-muted-foreground">
                                    {usuario.email}
                                </p>
                            </div>
                            <UserActionsMenu
                                usuario={usuario}
                                permissions={permissions}
                            />
                        </div>
                        <div className="mt-3 flex flex-wrap items-center gap-2">
                            <UserStatusBadge active={usuario.active} />
                            {usuario.roles.map((rol) => (
                                <Badge key={rol} variant="secondary">
                                    {rol}
                                </Badge>
                            ))}
                        </div>
                    </div>
                ))}
            </div>
        </>
    );
}

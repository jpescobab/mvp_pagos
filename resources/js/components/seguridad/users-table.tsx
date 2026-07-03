import { UserActionsMenu } from '@/components/seguridad/user-actions-menu';
import { UserStatusBadge } from '@/components/seguridad/user-status-badge';
import { Badge } from '@/components/ui/badge';
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
    return (
        <>
            <div className="hidden overflow-hidden rounded-xl border md:block">
                <table className="w-full text-sm">
                    <thead className="bg-muted/50 text-left text-muted-foreground">
                        <tr>
                            <th className="px-4 py-2 font-medium">Nombre</th>
                            <th className="px-4 py-2 font-medium">Email</th>
                            <th className="px-4 py-2 font-medium">RUT</th>
                            <th className="px-4 py-2 font-medium">Cargo</th>
                            <th className="hidden px-4 py-2 font-medium lg:table-cell">
                                Unidad
                            </th>
                            <th className="hidden px-4 py-2 font-medium lg:table-cell">
                                Jurisdicción
                            </th>
                            <th className="hidden px-4 py-2 font-medium lg:table-cell">
                                Centro financiero
                            </th>
                            <th className="hidden px-4 py-2 font-medium lg:table-cell">
                                Centro de costo
                            </th>
                            <th className="px-4 py-2 font-medium">Roles</th>
                            <th className="px-4 py-2 font-medium">Estado</th>
                            <th className="px-4 py-2 font-medium">
                                Último acceso
                            </th>
                            <th className="px-4 py-2 font-medium"></th>
                        </tr>
                    </thead>
                    <tbody className="divide-y">
                        {users.map((usuario) => (
                            <tr key={usuario.id} className="hover:bg-muted/30">
                                <td className="px-4 py-2 font-medium">
                                    {usuario.name}
                                </td>
                                <td className="px-4 py-2 text-muted-foreground">
                                    {usuario.email}
                                </td>
                                <td className="px-4 py-2 font-mono text-xs">
                                    {usuario.rut ?? '—'}
                                </td>
                                <td className="px-4 py-2">
                                    {usuario.cargo ?? '—'}
                                </td>
                                <td className="hidden px-4 py-2 lg:table-cell">
                                    {usuario.unidad ?? '—'}
                                </td>
                                <td className="hidden px-4 py-2 lg:table-cell">
                                    {usuario.jurisdiccion?.nombre ?? '—'}
                                </td>
                                <td className="hidden px-4 py-2 lg:table-cell">
                                    {usuario.centro_financiero?.nombre ?? '—'}
                                </td>
                                <td className="hidden px-4 py-2 lg:table-cell">
                                    {usuario.centro_costo?.nombre ?? '—'}
                                </td>
                                <td className="px-4 py-2">
                                    <div className="flex flex-wrap gap-1">
                                        {usuario.roles.length === 0 && '—'}
                                        {usuario.roles.map((rol) => (
                                            <Badge
                                                key={rol}
                                                variant="secondary"
                                            >
                                                {rol}
                                            </Badge>
                                        ))}
                                    </div>
                                </td>
                                <td className="px-4 py-2">
                                    <UserStatusBadge
                                        active={usuario.active}
                                    />
                                </td>
                                <td className="px-4 py-2 text-muted-foreground">
                                    {formatearFecha(usuario.last_login_at)}
                                </td>
                                <td className="px-4 py-2 text-right">
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
                    <div
                        key={usuario.id}
                        className="rounded-xl border p-4"
                    >
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

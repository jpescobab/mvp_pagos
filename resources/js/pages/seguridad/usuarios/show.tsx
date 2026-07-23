import { Head, Link, router, usePage } from '@inertiajs/react';
import { useState } from 'react';
import { UserStatusBadge } from '@/components/seguridad/user-status-badge';
import { Avatar, AvatarFallback } from '@/components/ui/avatar';
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
import { useInitials } from '@/hooks/use-initials';
import { formatFechaHora } from '@/lib/format';
import usuarios from '@/routes/usuarios';
import type {
    ActividadUsuario,
    PermisosDetalleUsuario,
    PermisosEfectivos,
    UsuarioListado,
} from '@/types/seguridad';

type PageProps = {
    usuario: UsuarioListado;
    permisos_efectivos: PermisosEfectivos;
    actividad: ActividadUsuario;
    permissions: PermisosDetalleUsuario;
};

type Confirmacion = 'activar' | 'desactivar' | 'reset-password' | null;

function MetaDato({
    etiqueta,
    valor,
    mono = false,
}: {
    etiqueta: string;
    valor: string | null;
    mono?: boolean;
}) {
    const texto = valor ?? '—';

    return (
        <div className="rounded-lg bg-muted/40 px-3 py-2">
            <div className="text-[10px] tracking-wide text-muted-foreground uppercase">
                {etiqueta}
            </div>
            <div
                className={`truncate text-sm font-medium ${mono ? 'font-mono' : ''}`}
                title={texto}
            >
                {texto}
            </div>
        </div>
    );
}

export default function UsuarioShow() {
    const page = usePage<PageProps>();
    const {
        usuario,
        permisos_efectivos: permisosEfectivos,
        actividad,
        permissions,
    } = page.props;
    const { flash } = page;
    const getInitials = useInitials();

    const [confirmacion, setConfirmacion] = useState<Confirmacion>(null);
    const [procesando, setProcesando] = useState(false);
    const mostrarPassword = flash.passwordTemporal !== undefined;

    function confirmar() {
        setProcesando(true);

        const opciones = {
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

    return (
        <>
            <Head title={usuario.name} />

            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <div>
                    <div className="flex flex-wrap items-start justify-between gap-3">
                        <div className="flex items-center gap-3">
                            <Avatar className="size-10 shrink-0">
                                <AvatarFallback className="bg-accent text-sm font-semibold text-accent-foreground">
                                    {getInitials(usuario.name)}
                                </AvatarFallback>
                            </Avatar>
                            <div>
                                <div className="flex flex-wrap items-center gap-2">
                                    <h1 className="text-xl font-semibold tracking-tight">
                                        {usuario.name}
                                    </h1>
                                    <UserStatusBadge active={usuario.active} />
                                </div>
                                <p className="text-sm text-muted-foreground">
                                    {usuario.email}
                                </p>
                            </div>
                        </div>

                        <div className="flex flex-wrap items-center gap-2">
                            {permissions.can_edit_user && (
                                <Button asChild variant="outline">
                                    <Link href={usuarios.edit(usuario.id).url}>
                                        Editar
                                    </Link>
                                </Button>
                            )}
                            {!usuario.active && permissions.can_activate_user && (
                                <Button
                                    variant="outline"
                                    onClick={() => setConfirmacion('activar')}
                                >
                                    Activar
                                </Button>
                            )}
                            {usuario.active &&
                                permissions.can_deactivate_user && (
                                    <Button
                                        variant="outline"
                                        onClick={() =>
                                            setConfirmacion('desactivar')
                                        }
                                    >
                                        Desactivar
                                    </Button>
                                )}
                            {permissions.can_reset_password && (
                                <Button
                                    variant="outline"
                                    onClick={() =>
                                        setConfirmacion('reset-password')
                                    }
                                >
                                    Resetear contraseña
                                </Button>
                            )}
                        </div>
                    </div>

                    <div className="mt-4 grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-5">
                        <MetaDato etiqueta="RUT" valor={usuario.rut} mono />
                        <MetaDato etiqueta="Cargo" valor={usuario.cargo} />
                        <MetaDato etiqueta="Unidad" valor={usuario.unidad} />
                        <MetaDato
                            etiqueta="Último acceso"
                            valor={formatFechaHora(usuario.last_login_at)}
                        />
                        <MetaDato
                            etiqueta="Fecha de creación"
                            valor={formatFechaHora(usuario.created_at)}
                        />
                    </div>
                </div>

                <section className="space-y-3">
                    <h2 className="text-base font-medium">
                        Ámbito institucional
                    </h2>
                    <div className="grid grid-cols-1 gap-3 sm:grid-cols-3">
                        <MetaDato
                            etiqueta="Jurisdicción"
                            valor={usuario.jurisdiccion?.nombre ?? null}
                        />
                        <MetaDato
                            etiqueta="Centro financiero"
                            valor={usuario.centro_financiero?.nombre ?? null}
                        />
                        <MetaDato
                            etiqueta="Centro de costo"
                            valor={usuario.centro_costo?.nombre ?? null}
                        />
                    </div>
                </section>

                <section className="space-y-3">
                    <h2 className="text-base font-medium">
                        Roles y permisos efectivos
                    </h2>

                    {usuario.roles.length === 0 ? (
                        <p className="text-sm text-muted-foreground">
                            El usuario no tiene roles asignados.
                        </p>
                    ) : (
                        <>
                            <div className="flex flex-wrap gap-1">
                                {usuario.roles.map((rol) => (
                                    <Badge key={rol} variant="secondary">
                                        {rol}
                                    </Badge>
                                ))}
                            </div>

                            {permisosEfectivos.acceso_total ? (
                                <p className="rounded-lg border border-warning/40 bg-warning-soft px-3 py-2 text-sm">
                                    Acceso total al sistema. El rol{' '}
                                    <span className="font-mono">superadmin</span>{' '}
                                    pasa cualquier autorización sin depender de
                                    permisos asignados.
                                </p>
                            ) : permisosEfectivos.permisos.length === 0 ? (
                                <p className="text-sm text-muted-foreground">
                                    Sus roles no otorgan ningún permiso.
                                </p>
                            ) : (
                                <div className="flex flex-wrap gap-1">
                                    {permisosEfectivos.permisos.map(
                                        (permiso) => (
                                            <Badge
                                                key={permiso}
                                                variant="outline"
                                                className="font-mono text-[10px]"
                                            >
                                                {permiso}
                                            </Badge>
                                        ),
                                    )}
                                </div>
                            )}
                        </>
                    )}
                </section>

                <section className="space-y-3">
                    <h2 className="text-base font-medium">
                        Actividad reciente — acciones de negocio
                    </h2>

                    {actividad.negocio.length === 0 ? (
                        <p className="text-sm text-muted-foreground">
                            Sin acciones de negocio registradas.
                        </p>
                    ) : (
                        <div className="overflow-x-auto rounded-xl border">
                            <table className="w-full table-fixed text-xs">
                                <thead className="bg-muted/50 text-left text-[10px] tracking-wide text-muted-foreground uppercase">
                                    <tr>
                                        <th className="w-[30%] px-2.5 py-2 font-medium">
                                            Acción
                                        </th>
                                        <th className="w-[40%] px-2.5 py-2 font-medium">
                                            Entidad afectada
                                        </th>
                                        <th className="w-[30%] px-2.5 py-2 font-medium">
                                            Fecha
                                        </th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y">
                                    {actividad.negocio.map((registro) => (
                                        <tr
                                            key={registro.id}
                                            className="hover:bg-muted/30"
                                        >
                                            <td
                                                className="truncate px-2.5 py-1 font-medium"
                                                title={registro.action}
                                            >
                                                {registro.action}
                                            </td>
                                            <td
                                                className="truncate px-2.5 py-1 text-muted-foreground"
                                                title={
                                                    registro.auditable_type ??
                                                    undefined
                                                }
                                            >
                                                {registro.auditable_type ===
                                                null
                                                    ? '—'
                                                    : `${registro.auditable_type.split('\\').pop()} #${registro.auditable_id ?? '—'}`}
                                            </td>
                                            <td className="truncate px-2.5 py-1 text-muted-foreground">
                                                {formatFechaHora(
                                                    registro.created_at,
                                                )}
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    )}
                </section>

                <section className="space-y-3">
                    <h2 className="text-base font-medium">
                        Actividad reciente — eventos de seguridad
                    </h2>

                    {actividad.seguridad.length === 0 ? (
                        <p className="text-sm text-muted-foreground">
                            Sin eventos de seguridad registrados.
                        </p>
                    ) : (
                        <div className="overflow-x-auto rounded-xl border">
                            <table className="w-full table-fixed text-xs">
                                <thead className="bg-muted/50 text-left text-[10px] tracking-wide text-muted-foreground uppercase">
                                    <tr>
                                        <th className="w-[20%] px-2.5 py-2 font-medium">
                                            Evento
                                        </th>
                                        <th className="w-[32%] px-2.5 py-2 font-medium">
                                            Descripción
                                        </th>
                                        <th className="w-[13%] px-2.5 py-2 font-medium">
                                            IP
                                        </th>
                                        <th className="hidden w-[18%] px-2.5 py-2 font-medium lg:table-cell">
                                            Origen
                                        </th>
                                        <th className="w-[17%] px-2.5 py-2 font-medium">
                                            Fecha
                                        </th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y">
                                    {actividad.seguridad.map((registro) => (
                                        <tr
                                            key={registro.id}
                                            className="hover:bg-muted/30"
                                        >
                                            <td
                                                className="truncate px-2.5 py-1 font-medium"
                                                title={registro.event}
                                            >
                                                {registro.event}
                                            </td>
                                            <td
                                                className="truncate px-2.5 py-1 text-muted-foreground"
                                                title={
                                                    registro.description ??
                                                    undefined
                                                }
                                            >
                                                {registro.description ?? '—'}
                                            </td>
                                            <td className="truncate px-2.5 py-1 font-mono">
                                                {registro.ip_address ?? '—'}
                                            </td>
                                            <td
                                                className="hidden truncate px-2.5 py-1 text-muted-foreground lg:table-cell"
                                                title={
                                                    registro.user_agent ??
                                                    undefined
                                                }
                                            >
                                                {registro.user_agent ?? '—'}
                                            </td>
                                            <td className="truncate px-2.5 py-1 text-muted-foreground">
                                                {formatFechaHora(
                                                    registro.created_at,
                                                )}
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    )}
                </section>
            </div>

            <Dialog
                open={confirmacion !== null}
                onOpenChange={(open) => !open && setConfirmacion(null)}
            >
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>
                            {confirmacion === 'activar' && 'Activar usuario'}
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

            <Dialog
                open={mostrarPassword}
                onOpenChange={(open) => {
                    if (!open) {
                        router.flash(() => ({}));
                    }
                }}
            >
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Contraseña temporal generada</DialogTitle>
                        <DialogDescription>
                            {flash.usuarioNombre &&
                                `Para ${flash.usuarioNombre}. `}
                            Cópiala ahora: no volverá a mostrarse. El usuario
                            deberá cambiarla en su próximo ingreso.
                        </DialogDescription>
                    </DialogHeader>
                    <p className="rounded-md border bg-muted px-4 py-3 text-center font-mono text-lg tracking-wide">
                        {flash.passwordTemporal}
                    </p>
                </DialogContent>
            </Dialog>
        </>
    );
}

UsuarioShow.layout = {
    breadcrumbs: [
        { title: 'Usuarios', href: usuarios.index() },
        { title: 'Detalle', href: '#' },
    ],
};

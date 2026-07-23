import { router, usePage } from '@inertiajs/react';
import { Bell } from 'lucide-react';
import { useState } from 'react';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { formatFechaHora } from '@/lib/format';
import notificaciones from '@/routes/notificaciones';
import type { NotificacionWorkflow } from '@/types/workflow';

export function NotificacionesCampana() {
    const noLeidas = usePage().props.notificaciones_no_leidas;
    const [lista, setLista] = useState<NotificacionWorkflow[] | null>(null);
    const [cargando, setCargando] = useState(false);

    function alAbrir(abierto: boolean) {
        if (!abierto) {
            return;
        }

        setCargando(true);

        fetch(notificaciones.index().url, {
            headers: { Accept: 'application/json' },
        })
            .then((res) => res.json())
            // El backend usa JsonResource::withoutWrapping(): la respuesta es un
            // array plano de notificaciones, sin envoltorio `data`.
            .then((json: NotificacionWorkflow[]) => {
                setLista(json);
            })
            .finally(() => setCargando(false));

        // Abrir el panel da por vistas las notificaciones: marca todas como
        // leídas y refresca el conteo del share sin recargar la página entera.
        if (noLeidas > 0) {
            router.post(
                notificaciones.marcarLeidas().url,
                {},
                {
                    preserveScroll: true,
                    preserveState: true,
                    only: ['notificaciones_no_leidas'],
                },
            );
        }
    }

    return (
        <DropdownMenu onOpenChange={alAbrir}>
            <DropdownMenuTrigger asChild>
                <Button
                    variant="ghost"
                    size="icon"
                    className="relative h-9 w-9 cursor-pointer"
                >
                    <Bell className="!size-5 opacity-80" />
                    {noLeidas > 0 && (
                        <span className="absolute top-1 right-1 flex min-w-4 items-center justify-center rounded-full bg-destructive px-1 text-[10px] leading-4 font-semibold text-white">
                            {noLeidas > 99 ? '99+' : noLeidas}
                        </span>
                    )}
                    <span className="sr-only">Notificaciones</span>
                </Button>
            </DropdownMenuTrigger>
            <DropdownMenuContent align="end" className="w-80">
                <DropdownMenuLabel>Notificaciones</DropdownMenuLabel>
                <DropdownMenuSeparator />

                {cargando && lista === null && (
                    <p className="px-2 py-6 text-center text-sm text-muted-foreground">
                        Cargando…
                    </p>
                )}

                {lista !== null && lista.length === 0 && (
                    <p className="px-2 py-6 text-center text-sm text-muted-foreground">
                        No tienes notificaciones.
                    </p>
                )}

                {lista !== null && lista.length > 0 && (
                    <div className="max-h-96 overflow-y-auto">
                        {lista.map((notif) => (
                            <NotificacionItem
                                key={notif.id}
                                notificacion={notif}
                            />
                        ))}
                    </div>
                )}
            </DropdownMenuContent>
        </DropdownMenu>
    );
}

function NotificacionItem({
    notificacion,
}: {
    notificacion: NotificacionWorkflow;
}) {
    const contenido = (
        <>
            <p className="text-sm font-medium">
                {notificacion.descripcion ?? 'Actualización de proceso'}
            </p>
            {notificacion.estado_nuevo && (
                <p className="text-xs text-muted-foreground">
                    Pasó a{' '}
                    <span className="font-medium">
                        {notificacion.estado_nuevo}
                    </span>
                </p>
            )}
            <p className="text-[10px] text-muted-foreground">
                {formatFechaHora(notificacion.created_at)}
            </p>
        </>
    );

    const clase = `block rounded-md px-2 py-2 ${notificacion.leida ? '' : 'bg-accent/40'} hover:bg-accent`;

    if (notificacion.url) {
        return (
            <a href={notificacion.url} className={clase}>
                {contenido}
            </a>
        );
    }

    return <div className={clase}>{contenido}</div>;
}

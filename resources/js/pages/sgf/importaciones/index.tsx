import { Head, Link, router, usePage } from '@inertiajs/react';
import { Eye, MoreHorizontal, Trash2 } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
import { ImportacionEstadoBadge } from '@/components/sgf/importacion-estado-badge';
import { Avatar, AvatarFallback } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Input } from '@/components/ui/input';
import { Monto } from '@/components/ui/monto';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectSeparator,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { useInitials } from '@/hooks/use-initials';
import { formatFechaHora } from '@/lib/format';
import { formatNumero } from '@/lib/format';
import casos from '@/routes/sgf/casos';
import importaciones from '@/routes/sgf/importaciones';
import type { Paginated } from '@/types/pago-proveedores';
import type { ImportacionSgf } from '@/types/sgf';

const FILTRO_COMPLETADAS = 'completado';
const FILTRO_NO_COMPLETADAS = 'no_completadas';
const FILTRO_TODOS = 'todos';

type PageProps = {
    importaciones: Paginated<ImportacionSgf>;
    q: string | null;
    filtroEstado: string | null;
};

export default function ImportacionesSgfIndex() {
    const {
        importaciones: pagina,
        q,
        filtroEstado,
        auth,
    } = usePage<PageProps>().props;
    const [termino, setTermino] = useState(q ?? '');
    const getInitials = useInitials();
    const puedeImportar = auth.permissions.includes(
        'pago_proveedores.importar_casos_sgf',
    );
    const puedeEliminar = auth.permissions.includes(
        'integraciones_sgf.eliminar_importacion',
    );

    function eliminar(importacion: ImportacionSgf) {
        if (
            !window.confirm(
                `¿Eliminar la importación #${importacion.id} (${importacion.tipo})? Esta acción no se puede deshacer.`,
            )
        ) {
            return;
        }

        router.delete(importaciones.destroy(importacion.id).url, {
            preserveScroll: true,
        });
    }

    // El debounce de búsqueda solo debe reiniciarse cuando cambia `termino`
    // (si dependiera también de `filtroEstado`, cada cambio del <Select>
    // -que ya navega al instante en cambiarFiltroEstado()- reprogramaría
    // este timeout y dispararía una segunda navegación redundante 300ms
    // después). Pero el callback igual necesita el `filtroEstado` más
    // reciente al momento de disparar, no el que existía cuando el usuario
    // empezó a escribir: si el usuario cambia el filtro de estado mientras
    // una búsqueda sigue en el debounce, sin este ref el timeout ya
    // agendado dispara con el `filtroEstado` viejo (capturado por closure)
    // y revierte en silencio la elección recién hecha en el <Select>.
    const filtroEstadoRef = useRef(filtroEstado);
    useEffect(() => {
        filtroEstadoRef.current = filtroEstado;
    }, [filtroEstado]);

    useEffect(() => {
        const timeout = setTimeout(() => {
            if (termino === (q ?? '')) {
                return;
            }

            router.get(
                importaciones.index().url,
                {
                    ...(termino !== '' ? { q: termino } : {}),
                    ...(filtroEstadoRef.current
                        ? { estado: filtroEstadoRef.current }
                        : {}),
                },
                { preserveState: true, preserveScroll: true },
            );
        }, 300);

        return () => clearTimeout(timeout);
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [termino]);

    function cambiarFiltroEstado(valor: string) {
        router.get(
            importaciones.index().url,
            {
                ...(termino !== '' ? { q: termino } : {}),
                // "Completadas" es el filtro por defecto del backend: se navega
                // sin el parámetro `estado` para mantener la URL limpia.
                ...(valor !== FILTRO_COMPLETADAS ? { estado: valor } : {}),
            },
            { preserveState: true, preserveScroll: true },
        );
    }

    return (
        <>
            <Head title="Importaciones SGF" />

            <div className="flex h-full flex-1 flex-col gap-4 p-4">
                <div className="flex items-center justify-between">
                    <h1 className="text-xl font-semibold tracking-tight">
                        Importaciones SGF
                    </h1>
                    <div className="flex items-center gap-2">
                        <Input
                            placeholder="Buscar por tipo o usuario…"
                            value={termino}
                            onChange={(e) => setTermino(e.target.value)}
                            className="w-64"
                        />
                        <Select
                            value={filtroEstado ?? FILTRO_COMPLETADAS}
                            onValueChange={cambiarFiltroEstado}
                        >
                            <SelectTrigger className="w-48">
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value={FILTRO_COMPLETADAS}>
                                    Completadas
                                </SelectItem>
                                <SelectItem value={FILTRO_NO_COMPLETADAS}>
                                    No completadas
                                </SelectItem>
                                <SelectItem value={FILTRO_TODOS}>
                                    Todos los estados
                                </SelectItem>
                                <SelectSeparator />
                                <SelectItem value="en_progreso">
                                    En progreso
                                </SelectItem>
                                <SelectItem value="error">Error</SelectItem>
                                <SelectItem value="huerfano">
                                    Huérfano
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        {puedeImportar && (
                            <>
                                <Button
                                    variant="outline"
                                    onClick={() =>
                                        router.post(
                                            casos.importarPendientes().url,
                                        )
                                    }
                                >
                                    Importar pendientes de SGF
                                </Button>
                                <Button
                                    variant="outline"
                                    onClick={() =>
                                        router.post(
                                            casos.importarGrupoPagoOperaciones()
                                                .url,
                                        )
                                    }
                                >
                                    Importar grupo Pago Operaciones
                                </Button>
                            </>
                        )}
                    </div>
                </div>

                <div className="overflow-x-auto rounded-xl border">
                    <table className="w-full table-fixed text-xs">
                        <thead className="bg-muted/50 text-left text-[10px] tracking-wide text-muted-foreground uppercase">
                            <tr>
                                <th className="w-[18%] px-2.5 py-1 font-medium">
                                    Tipo
                                </th>
                                <th className="w-[14%] px-2.5 py-1 font-medium">
                                    Iniciado por
                                </th>
                                <th className="w-[22%] px-2.5 py-1 font-medium">
                                    Etapa del proceso
                                </th>
                                <th className="hidden w-[12%] px-2.5 py-1 font-medium md:table-cell">
                                    Iniciado en
                                </th>
                                <th className="hidden w-[12%] px-2.5 py-1 font-medium lg:table-cell">
                                    Finalizado en
                                </th>
                                <th className="w-[8%] px-2.5 py-1 text-right font-medium">
                                    Total elementos
                                </th>
                                <th className="w-[10%] px-2.5 py-1 font-medium">
                                    Estado
                                </th>
                                <th className="w-[4%] px-2.5 py-1 font-medium"></th>
                            </tr>
                        </thead>
                        <tbody className="divide-y">
                            {pagina.data.length === 0 && (
                                <tr>
                                    <td
                                        colSpan={8}
                                        className="px-2.5 py-5 text-center text-muted-foreground"
                                    >
                                        Sin importaciones SGF registradas que
                                        coincidan.
                                    </td>
                                </tr>
                            )}
                            {pagina.data.map((importacion) => (
                                <tr
                                    key={importacion.id}
                                    className="cursor-pointer hover:bg-muted/30"
                                    onClick={() =>
                                        router.visit(
                                            importaciones.show(importacion.id)
                                                .url,
                                        )
                                    }
                                >
                                    <td
                                        className="truncate px-2.5 py-1 font-medium"
                                        title={importacion.tipo}
                                    >
                                        {importacion.tipo}
                                    </td>
                                    <td className="px-2.5 py-1">
                                        <div className="flex items-center gap-2">
                                            <Avatar className="size-6 shrink-0">
                                                <AvatarFallback className="bg-accent text-[10px] font-semibold text-accent-foreground">
                                                    {getInitials(
                                                        importacion.iniciado_por ??
                                                            'Sistema',
                                                    )}
                                                </AvatarFallback>
                                            </Avatar>
                                            <span
                                                className="truncate text-muted-foreground"
                                                title={
                                                    importacion.iniciado_por ??
                                                    'Sistema'
                                                }
                                            >
                                                {importacion.iniciado_por ??
                                                    'Sistema'}
                                            </span>
                                        </div>
                                    </td>
                                    <td className="px-2.5 py-1">
                                        {importacion.desglose_estados.length ===
                                        0 ? (
                                            <span className="text-muted-foreground">
                                                —
                                            </span>
                                        ) : (
                                            <div className="flex flex-wrap gap-1">
                                                {importacion.desglose_estados.map(
                                                    (etapa) => (
                                                        <span
                                                            key={
                                                                etapa.estado_codigo
                                                            }
                                                            className="inline-flex max-w-full items-center gap-1 rounded-md bg-muted px-1.5 py-0.5 text-[10px] font-medium"
                                                            title={`${etapa.cantidad} en ${etapa.estado_nombre}`}
                                                        >
                                                            <span className="font-semibold">
                                                                {etapa.cantidad}
                                                            </span>
                                                            <span className="truncate">
                                                                {
                                                                    etapa.estado_nombre
                                                                }
                                                            </span>
                                                        </span>
                                                    ),
                                                )}
                                            </div>
                                        )}
                                    </td>
                                    <td
                                        className="hidden truncate px-2.5 py-1 font-mono text-muted-foreground md:table-cell"
                                        title={formatFechaHora(
                                            importacion.iniciado_en,
                                        )}
                                    >
                                        {formatFechaHora(
                                            importacion.iniciado_en,
                                        )}
                                    </td>
                                    <td
                                        className="hidden truncate px-2.5 py-1 font-mono text-muted-foreground lg:table-cell"
                                        title={formatFechaHora(
                                            importacion.finalizado_en,
                                        )}
                                    >
                                        {formatFechaHora(
                                            importacion.finalizado_en,
                                        )}
                                    </td>
                                    <td className="px-2.5 py-1 text-right">
                                        <Monto
                                            valor={importacion.total_elementos}
                                            variante="numero"
                                        />
                                    </td>
                                    <td className="px-2.5 py-1">
                                        <ImportacionEstadoBadge
                                            estado={importacion.estado}
                                        />
                                    </td>
                                    <td
                                        className="px-2.5 py-1 text-right"
                                        onClick={(e) => e.stopPropagation()}
                                    >
                                        <DropdownMenu>
                                            <DropdownMenuTrigger asChild>
                                                <Button
                                                    variant="ghost"
                                                    size="icon"
                                                    className="size-6"
                                                >
                                                    <MoreHorizontal className="size-3.5" />
                                                    <span className="sr-only">
                                                        Acciones
                                                    </span>
                                                </Button>
                                            </DropdownMenuTrigger>
                                            <DropdownMenuContent align="end">
                                                <DropdownMenuItem asChild>
                                                    <Link
                                                        href={importaciones.show.url(
                                                            importacion.id,
                                                        )}
                                                    >
                                                        <Eye className="size-3.5" />
                                                        Ver detalle
                                                    </Link>
                                                </DropdownMenuItem>
                                                {puedeEliminar && (
                                                    <DropdownMenuItem
                                                        disabled={
                                                            !importacion.eliminable
                                                        }
                                                        onSelect={() =>
                                                            eliminar(importacion)
                                                        }
                                                        className="text-destructive focus:text-destructive"
                                                        title={
                                                            importacion.eliminable
                                                                ? undefined
                                                                : 'Tiene casos o snapshots asociados; borrarla eliminaría trazabilidad.'
                                                        }
                                                    >
                                                        <Trash2 className="size-3.5" />
                                                        Eliminar
                                                    </DropdownMenuItem>
                                                )}
                                            </DropdownMenuContent>
                                        </DropdownMenu>
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>

                <div className="flex items-center justify-between text-sm text-muted-foreground">
                    <span>
                        Mostrando {formatNumero(pagina.meta.from ?? 0)}–
                        {formatNumero(pagina.meta.to ?? 0)} de{' '}
                        {formatNumero(pagina.meta.total)}
                    </span>
                    <div className="flex gap-2">
                        <Link
                            href={pagina.links.prev ?? '#'}
                            className={
                                pagina.links.prev
                                    ? 'underline'
                                    : 'pointer-events-none opacity-50'
                            }
                        >
                            Anterior
                        </Link>
                        <Link
                            href={pagina.links.next ?? '#'}
                            className={
                                pagina.links.next
                                    ? 'underline'
                                    : 'pointer-events-none opacity-50'
                            }
                        >
                            Siguiente
                        </Link>
                    </div>
                </div>
            </div>
        </>
    );
}

ImportacionesSgfIndex.layout = {
    breadcrumbs: [
        {
            title: 'Importaciones SGF',
            href: importaciones.index(),
        },
    ],
};

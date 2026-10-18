import { Head, Link, router, usePage } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import { PaginacionFooter } from '@/components/paginacion-footer';
import { EstadoServicioBadge } from '@/components/servicios-contratados/estado-servicio-badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Monto } from '@/components/ui/monto';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { formatFecha } from '@/lib/format';
import servicios from '@/routes/servicios-contratados';
import type { Paginated } from '@/types/pago-proveedores';
import type { ServicioContratado } from '@/types/servicios-contratados';

type PageProps = {
    servicios: Paginated<ServicioContratado>;
    q: string | null;
    estado: string | null;
    auth: { permissions: string[] };
};

const FILTRO_TODOS = 'todos';

export default function ServicioContratadoIndex() {
    const { servicios: pagina, q, estado, auth } = usePage<PageProps>().props;
    const [termino, setTermino] = useState(q ?? '');

    function buscar(parametros: { q?: string; estado?: string }) {
        const filtros = Object.fromEntries(
            Object.entries(parametros).filter(([, valor]) => valor),
        );

        router.get(servicios.index().url, filtros, {
            preserveState: true,
            preserveScroll: true,
        });
    }

    useEffect(() => {
        const timeout = setTimeout(() => {
            if (termino === (q ?? '')) {
                return;
            }

            buscar({ q: termino, estado: estado ?? undefined });
        }, 300);

        return () => clearTimeout(timeout);
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [termino]);

    return (
        <>
            <Head title="Servicios contratados" />

            <div className="flex h-full flex-1 flex-col gap-4 p-4">
                <div className="flex flex-wrap items-center justify-between gap-2">
                    <h1 className="text-xl font-semibold tracking-tight">
                        Servicios contratados
                    </h1>
                    <div className="flex items-center gap-2">
                        <Input
                            placeholder="Buscar por servicio, contrato o proveedor…"
                            value={termino}
                            onChange={(e) => setTermino(e.target.value)}
                            className="w-72"
                        />
                        <Select
                            value={estado ?? FILTRO_TODOS}
                            onValueChange={(valor) =>
                                buscar({
                                    q: termino,
                                    estado:
                                        valor === FILTRO_TODOS
                                            ? undefined
                                            : valor,
                                })
                            }
                        >
                            <SelectTrigger className="w-40">
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value={FILTRO_TODOS}>
                                    Todos los estados
                                </SelectItem>
                                <SelectItem value="vigente">Vigente</SelectItem>
                                <SelectItem value="por_vencer">
                                    Por vencer
                                </SelectItem>
                                <SelectItem value="vencido">Vencido</SelectItem>
                                <SelectItem value="terminado">
                                    Terminado
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        {auth.permissions.includes(
                            'servicios_contratados.crear',
                        ) && (
                            <Button asChild>
                                <Link href={servicios.create().url}>
                                    Nuevo servicio
                                </Link>
                            </Button>
                        )}
                    </div>
                </div>

                <div className="overflow-x-auto rounded-xl border">
                    <table className="w-full table-fixed text-xs">
                        <thead className="bg-muted/50 text-left text-[10px] tracking-wide text-muted-foreground uppercase">
                            <tr>
                                <th className="w-[24%] px-2.5 py-1 font-medium">
                                    Servicio
                                </th>
                                <th className="w-[10%] px-2.5 py-1 font-medium">
                                    Categoría
                                </th>
                                <th className="w-[22%] px-2.5 py-1 font-medium">
                                    Proveedor
                                </th>
                                <th className="w-[12%] px-2.5 py-1 font-medium">
                                    Contrato
                                </th>
                                <th className="w-[12%] px-2.5 py-1 text-right font-medium">
                                    Monto mensual
                                </th>
                                <th className="w-[10%] px-2.5 py-1 font-medium">
                                    Vence
                                </th>
                                <th className="w-[10%] px-2.5 py-1 font-medium">
                                    Estado
                                </th>
                            </tr>
                        </thead>
                        <tbody className="divide-y">
                            {pagina.data.length === 0 && (
                                <tr>
                                    <td
                                        colSpan={7}
                                        className="px-2.5 py-5 text-center text-muted-foreground"
                                    >
                                        Sin servicios que coincidan.
                                    </td>
                                </tr>
                            )}
                            {pagina.data.map((servicio) => (
                                <tr
                                    key={servicio.id}
                                    className="hover:bg-muted/30"
                                >
                                    <td
                                        className="truncate px-2.5 py-1"
                                        title={servicio.nombre}
                                    >
                                        <Link
                                            href={
                                                servicios.show(servicio.id).url
                                            }
                                            className="font-medium underline"
                                        >
                                            {servicio.nombre}
                                        </Link>
                                    </td>
                                    <td className="px-2.5 py-1 text-muted-foreground">
                                        {servicio.categoria}
                                    </td>
                                    <td
                                        className="truncate px-2.5 py-1 text-muted-foreground"
                                        title={servicio.proveedor.nombre ?? ''}
                                    >
                                        {servicio.proveedor.nombre ?? '—'}
                                    </td>
                                    <td className="px-2.5 py-1 font-mono text-muted-foreground">
                                        {servicio.contrato.codigo}
                                    </td>
                                    <td className="px-2.5 py-1 text-right">
                                        <Monto
                                            valor={
                                                servicio.monto_mensual_esperado
                                            }
                                        />
                                    </td>
                                    <td className="px-2.5 py-1 text-muted-foreground">
                                        {formatFecha(servicio.fecha_fin)}
                                    </td>
                                    <td className="px-2.5 py-1">
                                        <EstadoServicioBadge
                                            estado={servicio.estado}
                                        />
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>

                <PaginacionFooter meta={pagina.meta} links={pagina.links} />
            </div>
        </>
    );
}

ServicioContratadoIndex.layout = {
    breadcrumbs: [{ title: 'Servicios contratados', href: servicios.index() }],
};

import { Head, router, usePage } from '@inertiajs/react';
import { useState } from 'react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { formatFechaHora, formatNumero } from '@/lib/format';
import importaciones from '@/routes/presupuesto/importaciones';
import { index as lineasIndex } from '@/routes/presupuesto/lineas';
import type { Paginated } from '@/types/pago-proveedores';
import type { ImportacionPresupuesto } from '@/types/presupuesto';

type PageProps = {
    importaciones: Paginated<ImportacionPresupuesto>;
};

function EstadoBadge({ estado }: { estado: string }) {
    if (estado === 'completado') {
        return (
            <Badge
                variant="outline"
                className="border-transparent bg-success-soft text-success"
            >
                Completado
            </Badge>
        );
    }

    if (estado === 'error' || estado === 'huerfano') {
        return (
            <Badge
                variant="outline"
                className="border-transparent bg-danger-soft text-destructive"
            >
                {estado === 'error' ? 'Error' : 'Huérfano'}
            </Badge>
        );
    }

    return (
        <Badge variant="outline" className="text-muted-foreground">
            {estado}
        </Badge>
    );
}

export default function ImportacionesPresupuestoIndex() {
    const { importaciones: pagina } = usePage<PageProps>().props;
    const anioActual = new Date().getFullYear();
    const [archivo, setArchivo] = useState<File | null>(null);
    const [anio, setAnio] = useState(String(anioActual));
    const [enviando, setEnviando] = useState(false);
    const [error, setError] = useState<string | null>(null);

    function subirExcel() {
        if (archivo === null) {
            return;
        }

        setEnviando(true);
        setError(null);

        const formData = new FormData();
        formData.append('archivo', archivo);
        formData.append('anio', anio);

        router.post(importaciones.store().url, formData, {
            preserveScroll: true,
            onError: (errores) => {
                setError(
                    errores.archivo ??
                        errores.anio ??
                        'No se pudo importar el archivo.',
                );
            },
            onFinish: () => setEnviando(false),
            onSuccess: () => setArchivo(null),
        });
    }

    return (
        <>
            <Head title="Importaciones de Presupuesto" />

            <div className="flex h-full flex-1 flex-col gap-4 p-4">
                <div className="flex items-center justify-between">
                    <h1 className="text-xl font-semibold tracking-tight">
                        Importaciones de Presupuesto
                    </h1>
                    <Button variant="outline" asChild>
                        <a href={lineasIndex().url}>
                            Ver líneas de presupuesto
                        </a>
                    </Button>
                </div>

                <div className="flex items-end gap-2 rounded-xl border p-3">
                    <div className="flex flex-col gap-1">
                        <label className="text-xs text-muted-foreground">
                            Excel de CGU
                        </label>
                        <input
                            type="file"
                            accept=".xlsx,.xls"
                            className="w-72 text-xs"
                            onChange={(e) =>
                                setArchivo(e.target.files?.[0] ?? null)
                            }
                        />
                    </div>
                    <div className="flex flex-col gap-1">
                        <label className="text-xs text-muted-foreground">
                            Año
                        </label>
                        <Input
                            type="number"
                            value={anio}
                            onChange={(e) => setAnio(e.target.value)}
                            className="w-24"
                        />
                    </div>
                    <Button
                        onClick={subirExcel}
                        disabled={archivo === null || enviando}
                    >
                        {enviando ? 'Importando…' : 'Importar'}
                    </Button>
                    {error && (
                        <span className="text-xs text-destructive">
                            {error}
                        </span>
                    )}
                </div>

                <div className="overflow-x-auto rounded-xl border">
                    <table className="w-full table-fixed text-xs">
                        <thead className="bg-muted/50 text-left text-[10px] tracking-wide text-muted-foreground uppercase">
                            <tr>
                                <th className="w-[15%] px-2.5 py-1 font-medium">
                                    Versión
                                </th>
                                <th className="w-[10%] px-2.5 py-1 font-medium">
                                    Año
                                </th>
                                <th className="w-[15%] px-2.5 py-1 font-medium">
                                    Estado
                                </th>
                                <th className="w-[30%] px-2.5 py-1 font-medium">
                                    Totales
                                </th>
                                <th className="w-[15%] px-2.5 py-1 font-medium">
                                    Responsable
                                </th>
                                <th className="w-[15%] px-2.5 py-1 font-medium">
                                    Fecha
                                </th>
                            </tr>
                        </thead>
                        <tbody className="divide-y">
                            {pagina.data.length === 0 && (
                                <tr>
                                    <td
                                        colSpan={6}
                                        className="px-2.5 py-5 text-center text-muted-foreground"
                                    >
                                        Sin importaciones registradas.
                                    </td>
                                </tr>
                            )}
                            {pagina.data.map((importacion) => (
                                <tr
                                    key={importacion.id}
                                    className="hover:bg-muted/30"
                                >
                                    <td className="px-2.5 py-1 font-mono">
                                        {importacion.nro_version}
                                    </td>
                                    <td className="px-2.5 py-1">
                                        {importacion.anio}
                                    </td>
                                    <td className="px-2.5 py-1">
                                        <EstadoBadge
                                            estado={importacion.estado}
                                        />
                                    </td>
                                    <td className="px-2.5 py-1 text-muted-foreground">
                                        {formatNumero(
                                            importacion.total_recibidos,
                                        )}{' '}
                                        recibidas ·{' '}
                                        {formatNumero(
                                            importacion.total_creados,
                                        )}{' '}
                                        creadas ·{' '}
                                        {formatNumero(
                                            importacion.total_actualizados,
                                        )}{' '}
                                        actualizadas ·{' '}
                                        {formatNumero(
                                            importacion.total_omitidos,
                                        )}{' '}
                                        omitidas
                                    </td>
                                    <td
                                        className="truncate px-2.5 py-1 text-muted-foreground"
                                        title={importacion.creado_por ?? '—'}
                                    >
                                        {importacion.creado_por ?? '—'}
                                    </td>
                                    <td className="px-2.5 py-1 text-muted-foreground">
                                        {formatFechaHora(
                                            importacion.finalizado_en,
                                        )}
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            </div>
        </>
    );
}

ImportacionesPresupuestoIndex.layout = {
    breadcrumbs: [
        {
            title: 'Importaciones de Presupuesto',
            href: importaciones.index(),
        },
    ],
};

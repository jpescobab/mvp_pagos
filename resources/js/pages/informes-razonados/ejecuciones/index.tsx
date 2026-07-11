import { Head, router, usePage } from '@inertiajs/react';
import { useState } from 'react';
import { Button } from '@/components/ui/button';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { formatFecha } from '@/lib/format';
import ejecuciones from '@/routes/informes-razonados/ejecuciones';
import type {
    CorteSeleccionable,
    DefinicionSeleccionable,
    EjecucionInformeRazonado,
} from '@/types/informes-razonados';

type PageProps = {
    ejecuciones: EjecucionInformeRazonado[];
    definiciones: DefinicionSeleccionable[];
    cortesPublicados: CorteSeleccionable[];
};

export default function EjecucionesInformeRazonadoIndex() {
    const {
        ejecuciones: lista,
        definiciones,
        cortesPublicados,
    } = usePage<PageProps>().props;

    const [definicionId, setDefinicionId] = useState('');
    const [corteId, setCorteId] = useState('');
    const [procesando, setProcesando] = useState(false);
    const [error, setError] = useState<string | null>(null);

    function iniciarEjecucion() {
        setProcesando(true);
        setError(null);

        router.post(
            ejecuciones.store().url,
            {
                definicion_informe_razonado_id: definicionId,
                corte_reportabilidad_id: corteId,
            },
            {
                preserveScroll: true,
                onSuccess: () => {
                    setDefinicionId('');
                    setCorteId('');
                },
                onError: (errors) =>
                    setError(
                        Object.values(errors as Record<string, string>)[0] ??
                            null,
                    ),
                onFinish: () => setProcesando(false),
            },
        );
    }

    return (
        <>
            <Head title="Ejecuciones de Informes Razonados" />

            <div className="flex h-full flex-1 flex-col gap-4 p-4">
                <h1 className="text-xl font-semibold tracking-tight">
                    Ejecuciones de Informes Razonados
                </h1>

                <section className="space-y-3 rounded-xl border p-4">
                    <h2 className="text-base font-medium">Iniciar ejecución</h2>

                    {error && (
                        <p className="text-sm text-destructive">{error}</p>
                    )}

                    {cortesPublicados.length === 0 && (
                        <p className="text-sm text-muted-foreground">
                            No hay cortes de reportabilidad publicados todavía.
                        </p>
                    )}

                    <div className="flex flex-wrap items-end gap-2">
                        <Select
                            value={definicionId}
                            onValueChange={setDefinicionId}
                        >
                            <SelectTrigger className="w-64">
                                <SelectValue placeholder="Definición de informe" />
                            </SelectTrigger>
                            <SelectContent>
                                {definiciones.map((definicion) => (
                                    <SelectItem
                                        key={definicion.id}
                                        value={String(definicion.id)}
                                    >
                                        {definicion.nombre}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>

                        <Select value={corteId} onValueChange={setCorteId}>
                            <SelectTrigger className="w-64">
                                <SelectValue placeholder="Corte publicado" />
                            </SelectTrigger>
                            <SelectContent>
                                {cortesPublicados.map((corte) => (
                                    <SelectItem
                                        key={corte.id}
                                        value={String(corte.id)}
                                    >
                                        {formatFecha(corte.fecha_corte)}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>

                        <Button
                            disabled={
                                procesando ||
                                definicionId === '' ||
                                corteId === ''
                            }
                            onClick={iniciarEjecucion}
                        >
                            Iniciar ejecución
                        </Button>
                    </div>
                </section>

                <div className="overflow-hidden rounded-xl border">
                    <table className="w-full text-sm">
                        <thead className="bg-muted/50 text-left text-muted-foreground">
                            <tr>
                                <th className="px-4 py-2 font-medium">
                                    Definición
                                </th>
                                <th className="px-4 py-2 font-medium">Corte</th>
                                <th className="px-4 py-2 font-medium">
                                    Estado
                                </th>
                                <th className="px-4 py-2 font-medium">
                                    Generado
                                </th>
                            </tr>
                        </thead>
                        <tbody className="divide-y">
                            {lista.length === 0 && (
                                <tr>
                                    <td
                                        colSpan={4}
                                        className="px-4 py-6 text-center text-muted-foreground"
                                    >
                                        Sin ejecuciones todavía.
                                    </td>
                                </tr>
                            )}
                            {lista.map((ejecucion) => (
                                <tr
                                    key={ejecucion.id}
                                    className="cursor-pointer hover:bg-muted/30"
                                    onClick={() =>
                                        router.visit(
                                            ejecuciones.show(ejecucion.id).url,
                                        )
                                    }
                                >
                                    <td className="px-4 py-2">
                                        {ejecucion.definicion.nombre}
                                    </td>
                                    <td className="px-4 py-2 text-muted-foreground">
                                        {ejecucion.corte.periodo_codigo}
                                    </td>
                                    <td className="px-4 py-2">
                                        {ejecucion.proceso?.estado_actual
                                            .nombre ?? '—'}
                                    </td>
                                    <td className="px-4 py-2 text-muted-foreground">
                                        {formatFecha(ejecucion.generado_en)}
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

EjecucionesInformeRazonadoIndex.layout = {
    breadcrumbs: [
        {
            title: 'Ejecuciones de Informes Razonados',
            href: ejecuciones.index(),
        },
    ],
};

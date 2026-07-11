import { Head, Link, router, usePage } from '@inertiajs/react';
import { useState } from 'react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { formatFecha } from '@/lib/format';
import cortes from '@/routes/reportabilidad/cortes';
import periodos from '@/routes/reportabilidad/periodos';
import type { PeriodoReportabilidad } from '@/types/reportabilidad';

type PageProps = {
    periodos: PeriodoReportabilidad[];
};

export default function PeriodosReportabilidadIndex() {
    const { periodos: lista } = usePage<PageProps>().props;

    const [codigo, setCodigo] = useState('');
    const [fechaInicio, setFechaInicio] = useState('');
    const [fechaFin, setFechaFin] = useState('');
    const [procesando, setProcesando] = useState(false);
    const [error, setError] = useState<string | null>(null);

    function abrirPeriodo() {
        setProcesando(true);
        setError(null);

        router.post(
            periodos.store().url,
            { codigo, fecha_inicio: fechaInicio, fecha_fin: fechaFin },
            {
                preserveScroll: true,
                onSuccess: () => {
                    setCodigo('');
                    setFechaInicio('');
                    setFechaFin('');
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

    function crearCorte(periodo: PeriodoReportabilidad) {
        router.post(
            periodos.cortes.store(periodo.id).url,
            {},
            { preserveScroll: true },
        );
    }

    return (
        <>
            <Head title="Períodos de Reportabilidad" />

            <div className="flex h-full flex-1 flex-col gap-4 p-4">
                <h1 className="text-xl font-semibold tracking-tight">
                    Períodos de Reportabilidad
                </h1>

                <section className="space-y-3 rounded-xl border p-4">
                    <h2 className="text-base font-medium">Abrir período</h2>

                    {error && (
                        <p className="text-sm text-destructive">{error}</p>
                    )}

                    <div className="flex flex-wrap items-end gap-2">
                        <div className="space-y-1">
                            <Label htmlFor="codigo-periodo">Código</Label>
                            <Input
                                id="codigo-periodo"
                                value={codigo}
                                onChange={(e) => setCodigo(e.target.value)}
                                placeholder="2026-06"
                            />
                        </div>
                        <div className="space-y-1">
                            <Label htmlFor="fecha-inicio-periodo">
                                Fecha inicio
                            </Label>
                            <Input
                                id="fecha-inicio-periodo"
                                type="date"
                                value={fechaInicio}
                                onChange={(e) => setFechaInicio(e.target.value)}
                            />
                        </div>
                        <div className="space-y-1">
                            <Label htmlFor="fecha-fin-periodo">Fecha fin</Label>
                            <Input
                                id="fecha-fin-periodo"
                                type="date"
                                value={fechaFin}
                                onChange={(e) => setFechaFin(e.target.value)}
                            />
                        </div>
                        <Button
                            disabled={
                                procesando ||
                                codigo === '' ||
                                fechaInicio === '' ||
                                fechaFin === ''
                            }
                            onClick={abrirPeriodo}
                        >
                            Abrir período
                        </Button>
                    </div>
                </section>

                {lista.length === 0 && (
                    <p className="text-sm text-muted-foreground">
                        Sin períodos de reportabilidad todavía.
                    </p>
                )}

                {lista.map((periodo) => (
                    <section
                        key={periodo.id}
                        className="space-y-3 rounded-xl border p-4"
                    >
                        <div className="flex items-center justify-between">
                            <h2 className="font-mono text-base font-medium">
                                {periodo.codigo}
                            </h2>
                            <span className="text-sm text-muted-foreground">
                                {formatFecha(periodo.fecha_inicio)}{' '}
                                –{' '}
                                {formatFecha(periodo.fecha_fin)}
                            </span>
                        </div>

                        {periodo.cortes.length === 0 ? (
                            <p className="text-sm text-muted-foreground">
                                Sin cortes en este período todavía.
                            </p>
                        ) : (
                            <ul className="divide-y text-sm">
                                {periodo.cortes.map((corte) => (
                                    <li
                                        key={corte.id}
                                        className="flex items-center justify-between py-2"
                                    >
                                        <Link
                                            href={cortes.show(corte.id)}
                                            className="hover:underline"
                                        >
                                            {formatFecha(corte.fecha_corte)}
                                        </Link>
                                        <span
                                            className={
                                                corte.estado === 'publicado'
                                                    ? 'text-green-600'
                                                    : 'text-muted-foreground'
                                            }
                                        >
                                            {corte.estado}
                                        </span>
                                    </li>
                                ))}
                            </ul>
                        )}

                        <Button
                            variant="outline"
                            onClick={() => crearCorte(periodo)}
                        >
                            Crear corte
                        </Button>
                    </section>
                ))}
            </div>
        </>
    );
}

PeriodosReportabilidadIndex.layout = {
    breadcrumbs: [
        {
            title: 'Períodos de Reportabilidad',
            href: periodos.index(),
        },
    ],
};

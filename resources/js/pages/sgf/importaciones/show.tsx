import { Head, Link, usePage, usePoll } from '@inertiajs/react';
import { useEffect } from 'react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { formatFechaHora, formatMonto, formatNumero } from '@/lib/format';
import { show as mostrarCaso } from '@/routes/pago-proveedores/casos';
import egresosCgu from '@/routes/pago-proveedores/egresos-cgu';
import importaciones from '@/routes/sgf/importaciones';
import type { ImportacionSgf } from '@/types/sgf';

type PageProps = {
    importacion: ImportacionSgf;
};

export default function ImportacionSgfShow() {
    const { importacion } = usePage<PageProps>().props;
    const enProgreso = importacion.estado === 'en_progreso';
    const resumen = importacion.resumen;

    const { start, stop } = usePoll(2000, undefined, { autoStart: false });

    useEffect(() => {
        if (enProgreso) {
            start();
        } else {
            stop();
        }
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [enProgreso]);

    return (
        <>
            <Head title={`Importación SGF — ${importacion.tipo}`} />

            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <div>
                    <h1 className="text-xl font-semibold tracking-tight">
                        Importación SGF
                    </h1>
                    <p className="text-sm text-muted-foreground">
                        Tipo: {importacion.tipo} · Iniciado por:{' '}
                        {importacion.iniciado_por ?? 'Sistema'} · Estado:{' '}
                        {importacion.estado}
                    </p>
                    <p className="text-sm text-muted-foreground">
                        Iniciado en: {formatFechaHora(importacion.iniciado_en)}
                        {' · '}
                        Finalizado en:{' '}
                        {formatFechaHora(importacion.finalizado_en)}
                    </p>
                    {importacion.error && (
                        <p className="text-sm text-destructive">
                            Error: {importacion.error}
                        </p>
                    )}
                </div>

                {resumen && (
                    <section className="grid grid-cols-1 gap-3 sm:grid-cols-4">
                        <div className="rounded-xl border p-4">
                            <p className="text-xs text-muted-foreground">
                                Monto total importado
                            </p>
                            <p className="text-lg font-semibold tracking-tight">
                                {formatMonto(resumen.monto_total)}
                            </p>
                        </div>
                        <div className="rounded-xl border p-4">
                            <p className="text-xs text-muted-foreground">
                                Proveedores identificados
                            </p>
                            <p className="text-lg font-semibold tracking-tight">
                                {formatNumero(resumen.proveedores_identificados)}
                            </p>
                        </div>
                        <div className="rounded-xl border p-4">
                            <p className="text-xs text-muted-foreground">
                                Proveedores sin identificar
                            </p>
                            <p
                                className={`text-lg font-semibold tracking-tight ${resumen.proveedores_no_identificados > 0 ? 'text-destructive' : ''}`}
                            >
                                {formatNumero(
                                    resumen.proveedores_no_identificados,
                                )}
                            </p>
                        </div>
                        <div className="rounded-xl border p-4">
                            <p className="text-xs text-muted-foreground">
                                Casos listos para Egreso
                            </p>
                            <p className="text-lg font-semibold tracking-tight">
                                {formatNumero(resumen.casos_listos)} /{' '}
                                {formatNumero(
                                    resumen.casos_listos +
                                        resumen.casos_pendientes,
                                )}
                            </p>
                        </div>
                    </section>
                )}

                {resumen && (
                    <div className="flex justify-end">
                        <Button
                            variant="outline"
                            disabled={resumen.casos_listos === 0}
                            asChild={resumen.casos_listos > 0}
                        >
                            {resumen.casos_listos > 0 ? (
                                <Link
                                    href={
                                        egresosCgu.create({
                                            query: {
                                                trabajo_integracion_id:
                                                    importacion.id,
                                            },
                                        }).url
                                    }
                                >
                                    Continuar a Asignar Egreso
                                </Link>
                            ) : (
                                'Continuar a Asignar Egreso'
                            )}
                        </Button>
                    </div>
                )}

                <section className="space-y-3 rounded-xl border p-4">
                    <h2 className="text-base font-medium">
                        Snapshots producidos (
                        {formatNumero(importacion.total_elementos)})
                    </h2>

                    {(importacion.snapshots ?? []).length === 0 ? (
                        <p className="text-sm text-muted-foreground">
                            {importacion.estado === 'en_progreso'
                                ? 'Importación en curso…'
                                : 'Sin snapshots producidos todavía.'}
                        </p>
                    ) : (
                        <ul className="divide-y text-sm">
                            {(importacion.snapshots ?? []).map((snapshot) => (
                                <li key={snapshot.id} className="py-3">
                                    <div className="flex flex-wrap items-baseline justify-between gap-x-4 gap-y-1">
                                        <div className="flex flex-wrap items-baseline gap-x-2">
                                            <span className="font-medium">
                                                {snapshot.proveedor ?? '—'}
                                            </span>
                                            <span className="font-mono text-xs text-muted-foreground">
                                                {snapshot.referencia_externa}
                                            </span>
                                            {snapshot.estado_sgf && (
                                                <Badge variant="secondary">
                                                    {snapshot.estado_sgf}
                                                </Badge>
                                            )}
                                            {snapshot.caso_id && (
                                                <Badge
                                                    variant="outline"
                                                    className={
                                                        snapshot.listo_para_egreso
                                                            ? 'border-transparent bg-success-soft text-success'
                                                            : 'border-transparent bg-warning-soft text-warning'
                                                    }
                                                >
                                                    {snapshot.listo_para_egreso
                                                        ? 'Listo'
                                                        : 'Pendiente'}
                                                </Badge>
                                            )}
                                        </div>
                                        <span className="font-semibold tracking-tight">
                                            {formatMonto(snapshot.monto)}
                                        </span>
                                    </div>
                                    <div className="mt-1 flex flex-wrap items-center gap-x-4 gap-y-1 text-xs text-muted-foreground">
                                        <span>
                                            Folio: {snapshot.folio_egreso ?? '—'}
                                        </span>
                                        <span>
                                            Número: {snapshot.numero ?? '—'}
                                        </span>
                                        <span>
                                            Período: {snapshot.periodo ?? '—'}
                                        </span>
                                        <span>
                                            {formatFechaHora(
                                                snapshot.capturado_en,
                                            )}
                                        </span>
                                        <span className="font-mono">
                                            {snapshot.hash.slice(0, 12)}…
                                        </span>
                                        {snapshot.caso_id && (
                                            <Link
                                                href={
                                                    mostrarCaso(
                                                        snapshot.caso_id,
                                                    ).url
                                                }
                                                className="text-primary hover:underline"
                                            >
                                                Ver caso
                                                {snapshot.caso_estado
                                                    ? ` (${snapshot.caso_estado})`
                                                    : ''}
                                            </Link>
                                        )}
                                    </div>
                                    {snapshot.observacion && (
                                        <p className="mt-1 text-xs text-muted-foreground italic">
                                            {snapshot.observacion}
                                        </p>
                                    )}
                                </li>
                            ))}
                        </ul>
                    )}
                </section>
            </div>
        </>
    );
}

ImportacionSgfShow.layout = {
    breadcrumbs: [
        { title: 'Importaciones SGF', href: importaciones.index() },
        { title: 'Detalle', href: '#' },
    ],
};

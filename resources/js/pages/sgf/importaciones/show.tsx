import { Head, usePage, usePoll } from '@inertiajs/react';
import { useEffect } from 'react';
import { formatFechaHora, formatNumero } from '@/lib/format';
import importaciones from '@/routes/sgf/importaciones';
import type { ImportacionSgf } from '@/types/sgf';

type PageProps = {
    importacion: ImportacionSgf;
};

export default function ImportacionSgfShow() {
    const { importacion } = usePage<PageProps>().props;
    const enProgreso = importacion.estado === 'en_progreso';

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
                                <li
                                    key={snapshot.id}
                                    className="flex items-center justify-between py-2"
                                >
                                    <span className="font-mono">
                                        {snapshot.referencia_externa}
                                    </span>
                                    <span className="text-muted-foreground">
                                        {formatFechaHora(
                                            snapshot.capturado_en,
                                        )}{' '}
                                        ·{' '}
                                        <span className="font-mono text-xs">
                                            {snapshot.hash.slice(0, 12)}…
                                        </span>
                                    </span>
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

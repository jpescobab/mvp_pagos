import { Head, usePage } from '@inertiajs/react';
import { formatNumero } from '@/lib/format';
import importaciones from '@/routes/sgf/importaciones';
import type { ImportacionSgf } from '@/types/sgf';

type PageProps = {
    importacion: ImportacionSgf;
};

export default function ImportacionSgfShow() {
    const { importacion } = usePage<PageProps>().props;

    return (
        <>
            <Head title={`Importación SGF — ${importacion.fuente}`} />

            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <div>
                    <h1 className="text-xl font-semibold tracking-tight">
                        Importación SGF
                    </h1>
                    <p className="text-sm text-muted-foreground">
                        Fuente: {importacion.fuente} · Iniciado por:{' '}
                        {importacion.iniciado_por ?? 'Sistema'} · Estado:{' '}
                        {importacion.estado}
                    </p>
                    <p className="text-sm text-muted-foreground">
                        Iniciado en:{' '}
                        {new Date(importacion.iniciado_en).toLocaleString()}
                        {' · '}
                        Finalizado en:{' '}
                        {importacion.finalizado_en
                            ? new Date(
                                  importacion.finalizado_en,
                              ).toLocaleString()
                            : '—'}
                    </p>
                </div>

                <section className="space-y-3 rounded-xl border p-4">
                    <h2 className="text-base font-medium">
                        Snapshots producidos (
                        {formatNumero(importacion.total_filas)})
                    </h2>

                    {(importacion.snapshots ?? []).length === 0 ? (
                        <p className="text-sm text-muted-foreground">
                            Sin snapshots producidos todavía.
                        </p>
                    ) : (
                        <ul className="divide-y text-sm">
                            {(importacion.snapshots ?? []).map((snapshot) => (
                                <li
                                    key={snapshot.id}
                                    className="flex items-center justify-between py-2"
                                >
                                    <span className="font-mono">
                                        {snapshot.sgf_id}
                                    </span>
                                    <span className="text-muted-foreground">
                                        {new Date(
                                            snapshot.capturado_en,
                                        ).toLocaleString()}{' '}
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

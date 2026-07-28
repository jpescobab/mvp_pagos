import { Head, router, usePage } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { formatFecha } from '@/lib/format';
import cortes from '@/routes/reportabilidad/cortes';
import type { CorteReportabilidad } from '@/types/reportabilidad';

type PageProps = {
    corte: CorteReportabilidad;
};

export default function CorteReportabilidadShow() {
    const { corte, auth } = usePage<PageProps>().props;

    const esBorrador = corte.estado === 'borrador';
    const puedeGenerar = auth.permissions.includes(
        'reportabilidad.generar_corte',
    );
    const items = corte.items ?? [];

    function generar() {
        router.post(cortes.generar(corte.id).url, {}, { preserveScroll: true });
    }

    function publicar() {
        router.post(
            cortes.publicar(corte.id).url,
            {},
            { preserveScroll: true },
        );
    }

    return (
        <>
            <Head title={`Corte ${corte.periodo?.codigo ?? corte.id}`} />

            <div className="flex h-full flex-1 flex-col gap-4 p-4">
                <div className="flex items-center justify-between">
                    <h1 className="text-xl font-semibold tracking-tight">
                        Corte del período {corte.periodo?.codigo}
                    </h1>
                    <span
                        className={
                            corte.estado === 'publicado'
                                ? 'text-green-600'
                                : 'text-muted-foreground'
                        }
                    >
                        {corte.estado}
                    </span>
                </div>

                <dl className="grid grid-cols-3 gap-4 rounded-xl border p-4 text-sm">
                    <div>
                        <dt className="text-muted-foreground">
                            Fecha de corte
                        </dt>
                        <dd>{formatFecha(corte.fecha_corte)}</dd>
                    </div>
                    <div>
                        <dt className="text-muted-foreground">Items</dt>
                        <dd>{corte.items_count}</dd>
                    </div>
                    <div>
                        <dt className="text-muted-foreground">Snapshots</dt>
                        <dd>{corte.snapshots_count}</dd>
                    </div>
                    <div>
                        <dt className="text-muted-foreground">
                            Ejecuciones de informe
                        </dt>
                        <dd>{corte.ejecuciones_informe_razonado_count}</dd>
                    </div>
                    {corte.publicado_por && (
                        <div>
                            <dt className="text-muted-foreground">
                                Publicado por
                            </dt>
                            <dd>
                                {corte.publicado_por}
                                {corte.publicado_en &&
                                    ` el ${formatFecha(corte.publicado_en)}`}
                            </dd>
                        </div>
                    )}
                </dl>

                <section className="space-y-2 rounded-xl border p-4">
                    <div className="flex items-center justify-between">
                        <h2 className="text-base font-medium">
                            Contenido del corte
                        </h2>
                        {esBorrador && puedeGenerar && (
                            <Button
                                variant="outline"
                                size="sm"
                                onClick={generar}
                            >
                                {items.length > 0
                                    ? 'Regenerar contenido'
                                    : 'Generar contenido'}
                            </Button>
                        )}
                    </div>
                    {items.length === 0 ? (
                        <p className="text-sm text-muted-foreground">
                            Sin contenido todavía. Genera el corte para incluir
                            las entidades reportables del período.
                        </p>
                    ) : (
                        <ul className="divide-y text-sm">
                            {items.map((item) => (
                                <li
                                    key={item.id}
                                    className="flex items-center justify-between py-2"
                                >
                                    <span>{item.etiqueta}</span>
                                    <span className="text-muted-foreground">
                                        {item.entidad_tipo ?? '—'}
                                        {item.entidad_id
                                            ? ` #${item.entidad_id}`
                                            : ''}
                                    </span>
                                </li>
                            ))}
                        </ul>
                    )}
                </section>

                {esBorrador && (
                    <Button onClick={publicar}>Publicar corte</Button>
                )}
            </div>
        </>
    );
}

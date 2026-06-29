import { Head, router, usePage } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import cortes from '@/routes/reportabilidad/cortes';
import type { CorteReportabilidad } from '@/types/reportabilidad';

type PageProps = {
    corte: CorteReportabilidad;
};

export default function CorteReportabilidadShow() {
    const { corte } = usePage<PageProps>().props;

    function publicar() {
        router.post(cortes.publicar(corte.id).url, {}, { preserveScroll: true });
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
                        <dd>
                            {new Date(corte.fecha_corte).toLocaleDateString()}
                        </dd>
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
                                    ` el ${new Date(corte.publicado_en).toLocaleDateString()}`}
                            </dd>
                        </div>
                    )}
                </dl>

                {corte.estado === 'borrador' && (
                    <Button onClick={publicar}>Publicar corte</Button>
                )}
            </div>
        </>
    );
}

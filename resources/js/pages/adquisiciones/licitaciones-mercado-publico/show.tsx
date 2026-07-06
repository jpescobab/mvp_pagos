import { Head, router } from '@inertiajs/react';
import { useState } from 'react';
import {
    AccionesEncabezadoFichaMercadoPublico,
    CronogramaTimeline,
    FichaConsultaMercadoPublico,
} from '@/components/mercado-publico/ficha-consulta';
import type { SeccionFichaConsulta } from '@/components/mercado-publico/ficha-consulta';
import { LicitacionEstadoBadge } from '@/components/mercado-publico/licitacion-estado-badge';
import { Button } from '@/components/ui/button';
import { Monto } from '@/components/ui/monto';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import licitacionesMp from '@/routes/adquisiciones/licitaciones_mp';
import type { LicitacionMercadoPublico } from '@/types/adquisiciones';

const URL_BASE_DETALLE_LICITACION_MERCADO_PUBLICO =
    'https://www.mercadopublico.cl/Procurement/Modules/RFB/DetailsAcquisition.aspx?idlicitacion=';

function urlDetalleLicitacionMercadoPublico(codigo: string): string {
    return `${URL_BASE_DETALLE_LICITACION_MERCADO_PUBLICO}${encodeURIComponent(codigo)}`;
}

type ProcesoAdquisicionSeleccionable = {
    id: number;
    codigo: string;
};

type PageProps = {
    licitacion: LicitacionMercadoPublico;
    procesosAdquisicion: ProcesoAdquisicionSeleccionable[];
};

function construirSecciones(
    licitacion: LicitacionMercadoPublico,
): SeccionFichaConsulta[] {
    return [
        {
            key: 'cronograma',
            titulo: 'Cronograma',
            contenido: <CronogramaTimeline eventos={licitacion.cronograma} />,
        },
        {
            key: 'organismo-comprador',
            titulo: 'Datos del organismo comprador',
            contenido: (
                <dl className="grid grid-cols-1 gap-3 text-sm sm:grid-cols-3">
                    <div>
                        <dt className="text-muted-foreground">Organismo</dt>
                        <dd>{licitacion.organismo_comprador?.nombre ?? '—'}</dd>
                    </div>
                    <div>
                        <dt className="text-muted-foreground">Unidad</dt>
                        <dd>{licitacion.organismo_comprador?.unidad ?? '—'}</dd>
                    </div>
                    <div>
                        <dt className="text-muted-foreground">RUT</dt>
                        <dd>{licitacion.organismo_comprador?.rut ?? '—'}</dd>
                    </div>
                </dl>
            ),
        },
        {
            key: 'condiciones',
            titulo: 'Condiciones',
            contenido: (
                <dl className="grid grid-cols-1 gap-3 text-sm sm:grid-cols-2">
                    <div>
                        <dt className="text-muted-foreground">Moneda</dt>
                        <dd>{licitacion.moneda ?? '—'}</dd>
                    </div>
                    <div>
                        <dt className="text-muted-foreground">
                            Monto estimado
                        </dt>
                        <dd>
                            <Monto valor={licitacion.monto_estimado} />
                        </dd>
                    </div>
                </dl>
            ),
        },
        {
            key: 'adjudicacion',
            titulo: 'Adjudicación',
            contenido: licitacion.adjudicacion ? (
                <dl className="grid grid-cols-1 gap-3 text-sm sm:grid-cols-4">
                    <div>
                        <dt className="text-muted-foreground">Fecha</dt>
                        <dd>{licitacion.adjudicacion.fecha ?? '—'}</dd>
                    </div>
                    <div>
                        <dt className="text-muted-foreground">N.º de acta</dt>
                        <dd>{licitacion.adjudicacion.numero ?? '—'}</dd>
                    </div>
                    <div>
                        <dt className="text-muted-foreground">
                            N.º de oferentes
                        </dt>
                        <dd>
                            {licitacion.adjudicacion.numero_oferentes ?? '—'}
                        </dd>
                    </div>
                    <div>
                        <dt className="text-muted-foreground">Acta</dt>
                        <dd>
                            {licitacion.adjudicacion.url_acta ? (
                                <a
                                    href={licitacion.adjudicacion.url_acta}
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    className="underline"
                                >
                                    Ver acta
                                </a>
                            ) : (
                                '—'
                            )}
                        </dd>
                    </div>
                </dl>
            ) : (
                <p className="text-sm text-muted-foreground">
                    Esta licitación aún no ha sido adjudicada.
                </p>
            ),
        },
        {
            key: 'items',
            titulo: 'Ítems',
            contenido:
                (licitacion.items ?? []).length === 0 ? (
                    <p className="text-sm text-muted-foreground">
                        Sin ítems informados.
                    </p>
                ) : (
                    <table className="w-full text-sm">
                        <thead>
                            <tr className="border-b text-left text-muted-foreground">
                                <th className="py-2">Código</th>
                                <th className="py-2">Descripción</th>
                                <th className="py-2 text-right">Cantidad</th>
                                <th className="py-2">Proveedor adjudicado</th>
                                <th className="py-2 text-right">
                                    Monto unitario
                                </th>
                            </tr>
                        </thead>
                        <tbody className="divide-y">
                            {(licitacion.items ?? []).map((item) => (
                                <tr key={item.id}>
                                    <td className="py-2 font-mono">
                                        {item.codigo_producto ?? '—'}
                                    </td>
                                    <td className="py-2">
                                        {item.nombre_producto ??
                                            item.descripcion}
                                    </td>
                                    <td className="py-2 text-right">
                                        <Monto
                                            valor={item.cantidad}
                                            variante="numero"
                                        />
                                    </td>
                                    <td className="py-2">
                                        {item.adjudicacion
                                            ? `${item.adjudicacion.nombre_proveedor ?? '—'} (${item.adjudicacion.rut_proveedor ?? '—'})`
                                            : '—'}
                                    </td>
                                    <td className="py-2 text-right">
                                        {item.adjudicacion?.monto_unitario !=
                                        null ? (
                                            <Monto
                                                valor={
                                                    item.adjudicacion
                                                        .monto_unitario
                                                }
                                            />
                                        ) : (
                                            '—'
                                        )}
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                ),
        },
    ];
}

export default function LicitacionMercadoPublicoShow({
    licitacion,
    procesosAdquisicion,
}: PageProps) {
    const [procesoSeleccionado, setProcesoSeleccionado] = useState<string>(
        licitacion.proceso_adquisicion
            ? String(licitacion.proceso_adquisicion.id)
            : '',
    );
    const [procesando, setProcesando] = useState(false);

    function vincular() {
        if (procesoSeleccionado === '') {
            return;
        }

        setProcesando(true);
        router.post(
            licitacionesMp.vinculo.store.url(licitacion.id),
            { proceso_adquisicion_id: Number(procesoSeleccionado) },
            { preserveScroll: true, onFinish: () => setProcesando(false) },
        );
    }

    function desvincular() {
        setProcesando(true);
        router.delete(licitacionesMp.vinculo.destroy.url(licitacion.id), {
            preserveScroll: true,
            onFinish: () => setProcesando(false),
        });
    }

    return (
        <>
            <Head title={`Licitación ${licitacion.codigo}`} />

            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <FichaConsultaMercadoPublico
                    encabezado={{
                        titulo: `Licitación ${licitacion.codigo}`,
                        subtitulo: licitacion.nombre,
                        acciones: (
                            <>
                                <LicitacionEstadoBadge
                                    estado={licitacion.estado_mercado_publico}
                                />
                                <AccionesEncabezadoFichaMercadoPublico
                                    payloadCrudo={licitacion.payload_crudo}
                                    urlDetalle={urlDetalleLicitacionMercadoPublico(
                                        licitacion.codigo,
                                    )}
                                    urlPdf={null}
                                />
                            </>
                        ),
                    }}
                    secciones={construirSecciones(licitacion)}
                />

                <section className="space-y-3 rounded-xl border p-4">
                    <h2 className="text-base font-medium">
                        Proceso de adquisición vinculado
                    </h2>

                    {licitacion.proceso_adquisicion ? (
                        <div className="flex items-center justify-between">
                            <p className="text-sm">
                                {licitacion.proceso_adquisicion.codigo}
                            </p>
                            <Button
                                variant="outline"
                                disabled={procesando}
                                onClick={desvincular}
                            >
                                Desvincular
                            </Button>
                        </div>
                    ) : (
                        <div className="flex flex-wrap items-end gap-2">
                            <Select
                                value={procesoSeleccionado}
                                onValueChange={setProcesoSeleccionado}
                            >
                                <SelectTrigger className="w-64">
                                    <SelectValue placeholder="Selecciona un proceso" />
                                </SelectTrigger>
                                <SelectContent>
                                    {procesosAdquisicion.map((proceso) => (
                                        <SelectItem
                                            key={proceso.id}
                                            value={String(proceso.id)}
                                        >
                                            {proceso.codigo}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            <Button
                                disabled={
                                    procesando || procesoSeleccionado === ''
                                }
                                onClick={vincular}
                            >
                                Vincular
                            </Button>
                        </div>
                    )}
                </section>
            </div>
        </>
    );
}

LicitacionMercadoPublicoShow.layout = {
    breadcrumbs: [
        {
            title: 'Licitaciones (Mercado Público)',
            href: licitacionesMp.index(),
        },
        { title: 'Detalle', href: '#' },
    ],
};

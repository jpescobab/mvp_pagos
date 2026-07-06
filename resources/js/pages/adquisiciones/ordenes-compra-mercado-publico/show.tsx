import { Head, router } from '@inertiajs/react';
import { useState } from 'react';
import {
    CronogramaTimeline,
    FichaConsultaMercadoPublico,
} from '@/components/mercado-publico/ficha-consulta';
import type { SeccionFichaConsulta } from '@/components/mercado-publico/ficha-consulta';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Monto } from '@/components/ui/monto';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import ordenesCompraMp from '@/routes/adquisiciones/ordenes_compra_mp';
import type { OrdenCompraMercadoPublico } from '@/types/adquisiciones';

type ProcesoAdquisicionSeleccionable = {
    id: number;
    codigo: string;
};

type PageProps = {
    orden: OrdenCompraMercadoPublico;
    procesosAdquisicion: ProcesoAdquisicionSeleccionable[];
};

function construirSecciones(
    orden: OrdenCompraMercadoPublico,
): SeccionFichaConsulta[] {
    return [
        {
            key: 'cronograma',
            titulo: 'Cronograma',
            contenido: <CronogramaTimeline eventos={orden.cronograma} />,
        },
        {
            key: 'organismo-comprador',
            titulo: 'Datos del organismo comprador',
            contenido: (
                <dl className="grid grid-cols-1 gap-3 text-sm sm:grid-cols-3">
                    <div>
                        <dt className="text-muted-foreground">Organismo</dt>
                        <dd>{orden.organismo_comprador?.nombre ?? '—'}</dd>
                    </div>
                    <div>
                        <dt className="text-muted-foreground">Unidad</dt>
                        <dd>{orden.organismo_comprador?.unidad ?? '—'}</dd>
                    </div>
                    <div>
                        <dt className="text-muted-foreground">RUT</dt>
                        <dd>{orden.organismo_comprador?.rut ?? '—'}</dd>
                    </div>
                </dl>
            ),
        },
        {
            key: 'condiciones',
            titulo: 'Condiciones del contrato',
            contenido: (
                <dl className="grid grid-cols-1 gap-3 text-sm sm:grid-cols-4">
                    <div>
                        <dt className="text-muted-foreground">Moneda</dt>
                        <dd>{orden.moneda ?? '—'}</dd>
                    </div>
                    <div>
                        <dt className="text-muted-foreground">Forma de pago</dt>
                        <dd>{orden.forma_pago ?? '—'}</dd>
                    </div>
                    <div>
                        <dt className="text-muted-foreground">
                            Plazo de entrega
                        </dt>
                        <dd>
                            {orden.plazo_entrega_dias !== null
                                ? `${orden.plazo_entrega_dias} días`
                                : '—'}
                        </dd>
                    </div>
                    <div>
                        <dt className="text-muted-foreground">Monto neto</dt>
                        <dd>
                            <Monto valor={orden.monto_neto} />
                        </dd>
                    </div>
                </dl>
            ),
        },
        {
            key: 'adjudicacion',
            titulo: 'Adjudicación',
            contenido: (
                <dl className="grid grid-cols-1 gap-3 text-sm sm:grid-cols-3">
                    <div>
                        <dt className="text-muted-foreground">Proveedor</dt>
                        <dd>{orden.proveedor?.nombre ?? '—'}</dd>
                    </div>
                    <div>
                        <dt className="text-muted-foreground">RUT</dt>
                        <dd>{orden.proveedor?.rutproveedor ?? '—'}</dd>
                    </div>
                    <div>
                        <dt className="text-muted-foreground">Monto total</dt>
                        <dd>
                            <Monto valor={orden.monto_total} />
                        </dd>
                    </div>
                </dl>
            ),
        },
        {
            key: 'items',
            titulo: 'Ítems',
            contenido:
                (orden.items ?? []).length === 0 ? (
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
                                <th className="py-2 text-right">
                                    Precio unitario
                                </th>
                                <th className="py-2 text-right">Total</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y">
                            {(orden.items ?? []).map((item) => (
                                <tr key={item.id}>
                                    <td className="py-2 font-mono">
                                        {item.codigo_producto ?? '—'}
                                    </td>
                                    <td className="py-2">{item.descripcion}</td>
                                    <td className="py-2 text-right">
                                        <Monto
                                            valor={item.cantidad}
                                            variante="numero"
                                        />
                                    </td>
                                    <td className="py-2 text-right">
                                        <Monto valor={item.precio_unitario} />
                                    </td>
                                    <td className="py-2 text-right">
                                        <Monto valor={item.monto_total} />
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                ),
        },
    ];
}

export default function OrdenCompraMercadoPublicoShow({
    orden,
    procesosAdquisicion,
}: PageProps) {
    const [procesoSeleccionado, setProcesoSeleccionado] = useState<string>(
        orden.proceso_adquisicion ? String(orden.proceso_adquisicion.id) : '',
    );
    const [procesando, setProcesando] = useState(false);

    function vincular() {
        if (procesoSeleccionado === '') {
            return;
        }

        setProcesando(true);
        router.post(
            ordenesCompraMp.vinculo.store.url(orden.id),
            { proceso_adquisicion_id: Number(procesoSeleccionado) },
            { preserveScroll: true, onFinish: () => setProcesando(false) },
        );
    }

    function desvincular() {
        setProcesando(true);
        router.delete(ordenesCompraMp.vinculo.destroy.url(orden.id), {
            preserveScroll: true,
            onFinish: () => setProcesando(false),
        });
    }

    return (
        <>
            <Head title={`OC ${orden.codigo}`} />

            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <FichaConsultaMercadoPublico
                    encabezado={{
                        titulo: `OC ${orden.codigo}`,
                        acciones: (
                            <Badge variant="outline">
                                {orden.estado_mercado_publico ?? 'Sin estado'}
                            </Badge>
                        ),
                    }}
                    secciones={construirSecciones(orden)}
                />

                <section className="space-y-3 rounded-xl border p-4">
                    <h2 className="text-base font-medium">
                        Proceso de adquisición vinculado
                    </h2>

                    {orden.proceso_adquisicion ? (
                        <div className="flex items-center justify-between">
                            <p className="text-sm">
                                {orden.proceso_adquisicion.codigo}
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

OrdenCompraMercadoPublicoShow.layout = {
    breadcrumbs: [
        {
            title: 'Órdenes de compra (Mercado Público)',
            href: ordenesCompraMp.index(),
        },
        { title: 'Detalle', href: '#' },
    ],
};

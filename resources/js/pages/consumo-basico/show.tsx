import { Head, Link, router, usePage } from '@inertiajs/react';
import { useState } from 'react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Monto } from '@/components/ui/monto';
import consumoBasico from '@/routes/consumo-basico';
import casos from '@/routes/pago-proveedores/casos';
import type { ConsumoBasico } from '@/types/consumo-basico';

type PageProps = {
    consumoBasico: ConsumoBasico;
};

export default function ConsumoBasicoShow() {
    const { consumoBasico: detalle } = usePage<PageProps>().props;

    const [subiendo, setSubiendo] = useState(false);
    const [errorArchivo, setErrorArchivo] = useState<string | null>(null);

    function subirDocumento(archivo: File) {
        setSubiendo(true);
        setErrorArchivo(null);

        const formData = new FormData();
        formData.append('archivo', archivo);

        router.post(consumoBasico.documento.store(detalle.id).url, formData, {
            preserveScroll: true,
            onError: (errores) =>
                setErrorArchivo(
                    (errores as Record<string, string>).archivo ?? null,
                ),
            onFinish: () => setSubiendo(false),
        });
    }

    function eliminarDocumento(vinculoId: number) {
        router.delete(
            consumoBasico.documento.destroy({
                consumoBasico: detalle.id,
                vinculo: vinculoId,
            }).url,
            { preserveScroll: true },
        );
    }

    return (
        <>
            <Head title={`Consumo — ${detalle.numero_documento}`} />

            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <div className="flex items-center justify-between">
                    <h1 className="text-xl font-semibold tracking-tight">
                        Detalle de consumo — {detalle.numero_documento}
                    </h1>
                    <div className="flex items-center gap-2">
                        <Button asChild variant="outline">
                            <Link href={consumoBasico.edit(detalle.id).url}>
                                Editar
                            </Link>
                        </Button>
                        <Button asChild variant="outline">
                            <Link
                                href={
                                    casos.show(detalle.caso_pago_proveedor_id)
                                        .url
                                }
                            >
                                Volver al caso
                            </Link>
                        </Button>
                    </div>
                </div>

                <dl className="grid grid-cols-2 gap-4 rounded-xl border p-4 text-sm">
                    <div>
                        <dt className="text-muted-foreground">
                            Cliente medidor
                        </dt>
                        <dd>
                            {detalle.cliente_medidor?.numero_cliente} ·{' '}
                            {detalle.cliente_medidor?.tipo_suministro}
                        </dd>
                    </div>
                    <div>
                        <dt className="text-muted-foreground">
                            N.º de medidor
                        </dt>
                        <dd>{detalle.numero_medidor}</dd>
                    </div>
                    <div>
                        <dt className="text-muted-foreground">
                            Período de lectura
                        </dt>
                        <dd>
                            {detalle.fecha_inicio_lectura} —{' '}
                            {detalle.fecha_fin_lectura}
                        </dd>
                    </div>
                    <div>
                        <dt className="text-muted-foreground">Consumo</dt>
                        <dd>
                            <Monto valor={detalle.consumo} variante="numero" />
                            {detalle.lectura_estimada && (
                                <Badge variant="secondary" className="ml-2">
                                    Estimada
                                </Badge>
                            )}
                        </dd>
                    </div>
                    <div>
                        <dt className="text-muted-foreground">Tarifa</dt>
                        <dd>{detalle.tarifa ?? '—'}</dd>
                    </div>
                    <div>
                        <dt className="text-muted-foreground">
                            Fecha de emisión
                        </dt>
                        <dd>{detalle.fecha_emision}</dd>
                    </div>
                    <div>
                        <dt className="text-muted-foreground">
                            Fecha de vencimiento
                        </dt>
                        <dd>{detalle.fecha_vencimiento}</dd>
                    </div>
                    <div>
                        <dt className="text-muted-foreground">Monto neto</dt>
                        <dd>
                            <Monto valor={detalle.monto_neto} />
                        </dd>
                    </div>
                    <div>
                        <dt className="text-muted-foreground">IVA</dt>
                        <dd>
                            <Monto valor={detalle.iva} />
                        </dd>
                    </div>
                    <div>
                        <dt className="text-muted-foreground">Monto exento</dt>
                        <dd>
                            <Monto valor={detalle.monto_exento} />
                        </dd>
                    </div>
                    <div>
                        <dt className="text-muted-foreground">
                            Saldo anterior
                        </dt>
                        <dd>
                            <Monto valor={detalle.saldo_anterior} />
                        </dd>
                    </div>
                    <div>
                        <dt className="text-muted-foreground">Monto total</dt>
                        <dd className="font-semibold">
                            <Monto valor={detalle.monto_total} />
                        </dd>
                    </div>
                </dl>

                <div className="grid max-w-xl gap-3 rounded-xl border p-4">
                    <h2 className="text-sm font-semibold tracking-tight">
                        PDF de la boleta/factura
                    </h2>

                    {detalle.documento ? (
                        <div className="flex items-center justify-between text-sm">
                            <a
                                href={
                                    consumoBasico.documento.descargar({
                                        consumoBasico: detalle.id,
                                        documento:
                                            detalle.documento.documento_id,
                                    }).url
                                }
                                className="underline"
                            >
                                {detalle.documento.nombre_archivo ??
                                    'Descargar PDF'}
                            </a>
                            <Button
                                variant="outline"
                                size="sm"
                                onClick={() =>
                                    eliminarDocumento(
                                        (
                                            detalle.documento as NonNullable<
                                                typeof detalle.documento
                                            >
                                        ).vinculo_id,
                                    )
                                }
                            >
                                Quitar
                            </Button>
                        </div>
                    ) : (
                        <>
                            <input
                                type="file"
                                accept=".pdf"
                                disabled={subiendo}
                                onChange={(e) => {
                                    const archivo = e.target.files?.[0];

                                    if (archivo) {
                                        subirDocumento(archivo);
                                    }

                                    e.target.value = '';
                                }}
                            />
                            {errorArchivo && (
                                <p className="text-sm text-destructive">
                                    {errorArchivo}
                                </p>
                            )}
                        </>
                    )}
                </div>
            </div>
        </>
    );
}

ConsumoBasicoShow.layout = {
    breadcrumbs: [
        { title: 'Casos de pago', href: casos.index() },
        { title: 'Detalle de consumo', href: '#' },
    ],
};

import { Head, router } from '@inertiajs/react';
import { useState } from 'react';
import {
    AccionesEncabezadoFichaMercadoPublico,
    CronogramaTimeline,
    FichaConsultaMercadoPublico,
} from '@/components/mercado-publico/ficha-consulta';
import type { SeccionFichaConsulta } from '@/components/mercado-publico/ficha-consulta';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Monto } from '@/components/ui/monto';
import { restarMontos } from '@/lib/format';
import ordenesCompraMp, {
    pdf as pdfOrdenCompraMp,
} from '@/routes/adquisiciones/ordenes_compra_mp';
import type {
    DiferenciaCampoOrdenCompraMercadoPublico,
    OrdenCompraMercadoPublico,
    PayloadNormalizadoOrdenCompraMercadoPublico,
} from '@/types/adquisiciones';

const URL_BASE_DETALLE_OC_MERCADO_PUBLICO =
    'https://www.mercadopublico.cl/PurchaseOrder/Modules/PO/DetailsPurchaseOrder.aspx?codigoOC=';

function urlDetalleOcMercadoPublico(codigo: string): string {
    return `${URL_BASE_DETALLE_OC_MERCADO_PUBLICO}${encodeURIComponent(codigo)}`;
}

type OcParaFicha = {
    estadoMercadoPublico: string | null;
    moneda: string | null;
    formaPago: string | null;
    plazoEntregaDias: number | null;
    montoNeto: string | number | null;
    montoTotal: string | number | null;
    fechaEmision: string | null;
    organismoComprador: {
        nombre: string | null;
        unidad: string | null;
        rut: string | null;
    } | null;
    cronograma: { estado: string | null; fecha: string | null }[];
    proveedor: { nombre: string | null; rut: string | null } | null;
    items: {
        codigoProducto: string | null;
        descripcion: string;
        cantidad: string | number;
        precioUnitario: string | number;
        montoTotal: string | number;
    }[];
};

function ordenLocalAFicha(orden: OrdenCompraMercadoPublico): OcParaFicha {
    return {
        estadoMercadoPublico: orden.estado_mercado_publico,
        moneda: orden.moneda,
        formaPago: orden.forma_pago,
        plazoEntregaDias: orden.plazo_entrega_dias,
        montoNeto: orden.monto_neto,
        montoTotal: orden.monto_total,
        fechaEmision: orden.fecha_emision,
        organismoComprador: orden.organismo_comprador,
        cronograma: orden.cronograma,
        proveedor: orden.proveedor
            ? {
                  nombre: orden.proveedor.nombre,
                  rut: orden.proveedor.rutproveedor,
              }
            : null,
        items: (orden.items ?? []).map((item) => ({
            codigoProducto: item.codigo_producto,
            descripcion: item.descripcion,
            cantidad: item.cantidad,
            precioUnitario: item.precio_unitario,
            montoTotal: item.monto_total,
        })),
    };
}

function payloadAFicha(
    payload: PayloadNormalizadoOrdenCompraMercadoPublico,
): OcParaFicha {
    return {
        estadoMercadoPublico: payload.estado,
        moneda: payload.moneda,
        formaPago: payload.forma_pago,
        plazoEntregaDias: payload.plazo_entrega_dias,
        montoNeto: payload.monto_neto,
        montoTotal: payload.monto_total,
        fechaEmision: payload.fecha_emision,
        organismoComprador: payload.organismo_comprador,
        cronograma: payload.cronograma,
        proveedor: {
            nombre: payload.proveedor.nombre,
            rut: payload.proveedor.rut,
        },
        items: payload.items.map((item) => ({
            codigoProducto: item.codigo_producto,
            descripcion: item.descripcion,
            cantidad: item.cantidad,
            precioUnitario: item.precio_unitario,
            montoTotal: item.monto_total,
        })),
    };
}

function construirSecciones(oc: OcParaFicha): SeccionFichaConsulta[] {
    return [
        {
            key: 'cronograma',
            titulo: 'Cronograma',
            contenido: <CronogramaTimeline eventos={oc.cronograma} />,
        },
        {
            key: 'desglose-financiero',
            titulo: 'Desglose financiero',
            contenido: (
                <dl className="grid grid-cols-1 gap-3 text-sm sm:grid-cols-3">
                    <div>
                        <dt className="text-muted-foreground">Monto neto</dt>
                        <dd>
                            <Monto valor={oc.montoNeto} />
                        </dd>
                    </div>
                    <div>
                        <dt className="text-muted-foreground">Impuesto</dt>
                        <dd>
                            <Monto
                                valor={restarMontos(
                                    oc.montoTotal,
                                    oc.montoNeto,
                                )}
                            />
                        </dd>
                    </div>
                    <div>
                        <dt className="text-muted-foreground">Monto total</dt>
                        <dd>
                            <Monto valor={oc.montoTotal} />
                        </dd>
                    </div>
                </dl>
            ),
        },
        {
            key: 'organismo-comprador',
            titulo: 'Datos del organismo comprador',
            contenido: (
                <dl className="grid grid-cols-1 gap-3 text-sm sm:grid-cols-3">
                    <div>
                        <dt className="text-muted-foreground">Organismo</dt>
                        <dd>{oc.organismoComprador?.nombre ?? '—'}</dd>
                    </div>
                    <div>
                        <dt className="text-muted-foreground">Unidad</dt>
                        <dd>{oc.organismoComprador?.unidad ?? '—'}</dd>
                    </div>
                    <div>
                        <dt className="text-muted-foreground">RUT</dt>
                        <dd>{oc.organismoComprador?.rut ?? '—'}</dd>
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
                        <dd>{oc.moneda ?? '—'}</dd>
                    </div>
                    <div>
                        <dt className="text-muted-foreground">Forma de pago</dt>
                        <dd>{oc.formaPago ?? '—'}</dd>
                    </div>
                    <div>
                        <dt className="text-muted-foreground">
                            Plazo de entrega
                        </dt>
                        <dd>
                            {oc.plazoEntregaDias !== null
                                ? `${oc.plazoEntregaDias} días`
                                : '—'}
                        </dd>
                    </div>
                    <div>
                        <dt className="text-muted-foreground">Monto neto</dt>
                        <dd>
                            <Monto valor={oc.montoNeto} />
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
                        <dd>{oc.proveedor?.nombre ?? '—'}</dd>
                    </div>
                    <div>
                        <dt className="text-muted-foreground">RUT</dt>
                        <dd>{oc.proveedor?.rut ?? '—'}</dd>
                    </div>
                    <div>
                        <dt className="text-muted-foreground">Monto total</dt>
                        <dd>
                            <Monto valor={oc.montoTotal} />
                        </dd>
                    </div>
                </dl>
            ),
        },
        {
            key: 'items',
            titulo: 'Ítems',
            contenido:
                oc.items.length === 0 ? (
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
                            {oc.items.map((item, i) => (
                                <tr key={i}>
                                    <td className="py-2 font-mono">
                                        {item.codigoProducto ?? '—'}
                                    </td>
                                    <td className="py-2">{item.descripcion}</td>
                                    <td className="py-2 text-right">
                                        <Monto
                                            valor={item.cantidad}
                                            variante="numero"
                                        />
                                    </td>
                                    <td className="py-2 text-right">
                                        <Monto valor={item.precioUnitario} />
                                    </td>
                                    <td className="py-2 text-right">
                                        <Monto valor={item.montoTotal} />
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                ),
        },
    ];
}

const NOMBRES_CAMPOS_DIFERENCIA: Record<string, string> = {
    estado_mercado_publico: 'Estado',
    moneda: 'Moneda',
    forma_pago: 'Forma de pago',
    plazo_entrega_dias: 'Plazo de entrega',
    monto_neto: 'Monto neto',
    monto_total: 'Monto total',
    fecha_emision: 'Fecha de emisión',
    organismo_comprador: 'Organismo comprador',
    cronograma: 'Cronograma',
};

type PageProps = {
    codigo: string | null;
    ordenLocal?: OrdenCompraMercadoPublico;
    vistaPrevia?: {
        payload_normalizado: PayloadNormalizadoOrdenCompraMercadoPublico;
        payload_crudo?: unknown;
        proveedor_existente: {
            id: number;
            nombre: string;
            rutproveedor: string | null;
        } | null;
    };
    noEncontrada?: boolean;
    comparacion?: {
        encontrada: boolean;
        diferencias: Record<string, DiferenciaCampoOrdenCompraMercadoPublico>;
    };
};

export default function BuscarOrdenCompraMercadoPublico({
    codigo,
    ordenLocal,
    vistaPrevia,
    noEncontrada,
    comparacion,
}: PageProps) {
    const [codigoIngresado, setCodigoIngresado] = useState(codigo ?? '');
    const [procesando, setProcesando] = useState(false);
    const [comparacionDescartada, setComparacionDescartada] = useState(false);

    function consultar() {
        if (codigoIngresado.trim() === '') {
            return;
        }

        router.get(
            ordenesCompraMp.index.url({
                query: { codigo: codigoIngresado.trim() },
            }),
            {},
            { preserveState: true },
        );
    }

    function verificar() {
        if (!ordenLocal) {
            return;
        }

        setProcesando(true);
        setComparacionDescartada(false);
        router.post(
            ordenesCompraMp.verificar.url(ordenLocal.id),
            {},
            { preserveState: true, onFinish: () => setProcesando(false) },
        );
    }

    function aplicarActualizacion() {
        if (!ordenLocal) {
            return;
        }

        setProcesando(true);
        router.post(
            ordenesCompraMp.actualizar.url(ordenLocal.id),
            {},
            { onFinish: () => setProcesando(false) },
        );
    }

    function guardar() {
        setProcesando(true);
        router.post(
            ordenesCompraMp.guardar.url(),
            {
                codigo: vistaPrevia?.payload_normalizado.codigo,
            },
            { onFinish: () => setProcesando(false) },
        );
    }

    const comparacionVisible =
        comparacion !== undefined && !comparacionDescartada;
    const diferenciasEntries = comparacion
        ? Object.entries(comparacion.diferencias)
        : [];

    return (
        <>
            <Head title="Buscar Orden de Compra" />

            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <div className="flex flex-row items-end gap-2 rounded-xl border p-4">
                    <div className="flex-1 space-y-1">
                        <label
                            htmlFor="codigo-oc"
                            className="text-sm text-muted-foreground"
                        >
                            Código de Orden de Compra
                        </label>
                        <Input
                            id="codigo-oc"
                            value={codigoIngresado}
                            onChange={(e) => setCodigoIngresado(e.target.value)}
                            onKeyDown={(e) => {
                                if (e.key === 'Enter') {
                                    consultar();
                                }
                            }}
                            placeholder="Ej: 2182-99-AG26"
                        />
                    </div>
                    <Button onClick={consultar}>Consultar</Button>
                </div>

                {ordenLocal && (
                    <>
                        <FichaConsultaMercadoPublico
                            encabezado={{
                                titulo: `OC ${ordenLocal.codigo}`,
                                subtitulo: ordenLocal.proceso_adquisicion && (
                                    <span>
                                        Vinculada a{' '}
                                        {ordenLocal.proceso_adquisicion.codigo}
                                    </span>
                                ),
                                montoDestacado: (
                                    <>
                                        <p className="text-xs text-muted-foreground">
                                            Monto total
                                        </p>
                                        <p className="text-lg font-semibold">
                                            <Monto
                                                valor={ordenLocal.monto_total}
                                            />
                                        </p>
                                    </>
                                ),
                                acciones: (
                                    <>
                                        <Badge variant="outline">
                                            {ordenLocal.estado_mercado_publico ??
                                                'Sin estado'}
                                        </Badge>
                                        <Button
                                            variant="outline"
                                            disabled={procesando}
                                            onClick={verificar}
                                        >
                                            Verificar contra Mercado Público
                                        </Button>
                                        <AccionesEncabezadoFichaMercadoPublico
                                            payloadCrudo={
                                                ordenLocal.payload_crudo
                                            }
                                            urlDetalle={urlDetalleOcMercadoPublico(
                                                ordenLocal.codigo,
                                            )}
                                            urlPdf={pdfOrdenCompraMp.url({
                                                query: {
                                                    codigo: ordenLocal.codigo,
                                                },
                                            })}
                                        />
                                    </>
                                ),
                            }}
                            secciones={construirSecciones(
                                ordenLocalAFicha(ordenLocal),
                            )}
                        />

                        {comparacionVisible && (
                            <section className="space-y-3 rounded-xl border p-4">
                                <h2 className="text-base font-medium">
                                    Comparación con Mercado Público
                                </h2>

                                {!comparacion?.encontrada ? (
                                    <p className="text-sm text-muted-foreground">
                                        La OC ya no está disponible en Mercado
                                        Público.
                                    </p>
                                ) : diferenciasEntries.length === 0 ? (
                                    <p className="text-sm text-muted-foreground">
                                        El registro local está actualizado, no
                                        se encontraron diferencias.
                                    </p>
                                ) : (
                                    <>
                                        <table className="w-full text-sm">
                                            <thead>
                                                <tr className="border-b text-left text-muted-foreground">
                                                    <th className="py-2">
                                                        Campo
                                                    </th>
                                                    <th className="py-2">
                                                        Local
                                                    </th>
                                                    <th className="py-2">
                                                        Mercado Público
                                                    </th>
                                                </tr>
                                            </thead>
                                            <tbody className="divide-y">
                                                {diferenciasEntries.map(
                                                    ([campo, diferencia]) => (
                                                        <tr key={campo}>
                                                            <td className="py-2">
                                                                {NOMBRES_CAMPOS_DIFERENCIA[
                                                                    campo
                                                                ] ?? campo}
                                                            </td>
                                                            <td className="py-2 text-muted-foreground">
                                                                {JSON.stringify(
                                                                    diferencia.local,
                                                                )}
                                                            </td>
                                                            <td className="py-2">
                                                                {JSON.stringify(
                                                                    diferencia.api,
                                                                )}
                                                            </td>
                                                        </tr>
                                                    ),
                                                )}
                                            </tbody>
                                        </table>
                                        <div className="flex gap-2">
                                            <Button
                                                disabled={procesando}
                                                onClick={aplicarActualizacion}
                                            >
                                                Actualizar
                                            </Button>
                                            <Button
                                                variant="outline"
                                                disabled={procesando}
                                                onClick={() =>
                                                    setComparacionDescartada(
                                                        true,
                                                    )
                                                }
                                            >
                                                Mantener
                                            </Button>
                                        </div>
                                    </>
                                )}
                            </section>
                        )}
                    </>
                )}

                {vistaPrevia && (
                    <FichaConsultaMercadoPublico
                        encabezado={{
                            titulo: `OC ${vistaPrevia.payload_normalizado.codigo}`,
                            subtitulo: 'Vista previa · aún no guardada',
                            montoDestacado: (
                                <>
                                    <p className="text-xs text-muted-foreground">
                                        Monto total
                                    </p>
                                    <p className="text-lg font-semibold">
                                        <Monto
                                            valor={
                                                vistaPrevia.payload_normalizado
                                                    .monto_total
                                            }
                                        />
                                    </p>
                                </>
                            ),
                            acciones: (
                                <>
                                    <Badge variant="outline">
                                        {vistaPrevia.payload_normalizado
                                            .estado ?? 'Sin estado'}
                                    </Badge>
                                    <AccionesEncabezadoFichaMercadoPublico
                                        payloadCrudo={vistaPrevia.payload_crudo}
                                        urlDetalle={urlDetalleOcMercadoPublico(
                                            vistaPrevia.payload_normalizado
                                                .codigo,
                                        )}
                                        urlPdf={pdfOrdenCompraMp.url({
                                            query: {
                                                codigo: vistaPrevia
                                                    .payload_normalizado.codigo,
                                            },
                                        })}
                                    />
                                </>
                            ),
                        }}
                        secciones={[
                            ...construirSecciones(
                                payloadAFicha(vistaPrevia.payload_normalizado),
                            ),
                            {
                                key: 'proveedor-emisor',
                                titulo: 'Proveedor emisor',
                                contenido: (
                                    <div className="flex items-center justify-between">
                                        {vistaPrevia.proveedor_existente ? (
                                            <p className="text-sm">
                                                {
                                                    vistaPrevia
                                                        .proveedor_existente
                                                        .nombre
                                                }{' '}
                                                <span className="text-muted-foreground">
                                                    (
                                                    {
                                                        vistaPrevia
                                                            .proveedor_existente
                                                            .rutproveedor
                                                    }
                                                    )
                                                </span>
                                            </p>
                                        ) : (
                                            <p className="text-sm text-muted-foreground">
                                                Se creará un proveedor nuevo con
                                                estos datos al guardar:{' '}
                                                {
                                                    vistaPrevia
                                                        .payload_normalizado
                                                        .proveedor.nombre
                                                }{' '}
                                                (
                                                {
                                                    vistaPrevia
                                                        .payload_normalizado
                                                        .proveedor.rut
                                                }
                                                )
                                            </p>
                                        )}
                                        <Button
                                            disabled={procesando}
                                            onClick={() => guardar()}
                                        >
                                            Guardar OC
                                        </Button>
                                    </div>
                                ),
                            },
                        ]}
                    />
                )}

                {noEncontrada && (
                    <div className="rounded-xl border p-4 text-sm text-muted-foreground">
                        La OC «{codigo}» no fue encontrada ni localmente ni en
                        Mercado Público.
                    </div>
                )}

                {!ordenLocal && !vistaPrevia && !noEncontrada && (
                    <div className="rounded-xl border p-4 text-sm text-muted-foreground">
                        Ingresa un código de Orden de Compra para comenzar.
                    </div>
                )}
            </div>
        </>
    );
}

BuscarOrdenCompraMercadoPublico.layout = {
    breadcrumbs: [
        {
            title: 'Órdenes de compra (Mercado Público)',
            href: ordenesCompraMp.index(),
        },
    ],
};

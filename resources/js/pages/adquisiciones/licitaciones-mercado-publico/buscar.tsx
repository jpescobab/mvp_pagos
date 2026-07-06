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
import { Input } from '@/components/ui/input';
import { Monto } from '@/components/ui/monto';
import licitacionesMp from '@/routes/adquisiciones/licitaciones_mp';
import type {
    DiferenciaCampoLicitacionMercadoPublico,
    LicitacionMercadoPublico,
    PayloadNormalizadoLicitacionMercadoPublico,
} from '@/types/adquisiciones';

const URL_BASE_DETALLE_LICITACION_MERCADO_PUBLICO =
    'https://www.mercadopublico.cl/Procurement/Modules/RFB/DetailsAcquisition.aspx?idlicitacion=';

function urlDetalleLicitacionMercadoPublico(codigo: string): string {
    return `${URL_BASE_DETALLE_LICITACION_MERCADO_PUBLICO}${encodeURIComponent(codigo)}`;
}

type LicitacionParaFicha = {
    nombre: string | null;
    estadoMercadoPublico: string | null;
    moneda: string | null;
    montoEstimado: string | number | null;
    organismoComprador: {
        nombre: string | null;
        unidad: string | null;
        rut: string | null;
    } | null;
    cronograma: { estado: string | null; fecha: string | null }[];
    adjudicacion: PayloadNormalizadoLicitacionMercadoPublico['adjudicacion'];
    items: {
        codigoProducto: string | null;
        nombreProducto: string | null;
        descripcion: string;
        cantidad: string | number;
        adjudicacion: PayloadNormalizadoLicitacionMercadoPublico['items'][number]['adjudicacion'];
    }[];
};

function licitacionLocalAFicha(
    licitacion: LicitacionMercadoPublico,
): LicitacionParaFicha {
    return {
        nombre: licitacion.nombre,
        estadoMercadoPublico: licitacion.estado_mercado_publico,
        moneda: licitacion.moneda,
        montoEstimado: licitacion.monto_estimado,
        organismoComprador: licitacion.organismo_comprador,
        cronograma: licitacion.cronograma,
        adjudicacion: licitacion.adjudicacion,
        items: (licitacion.items ?? []).map((item) => ({
            codigoProducto: item.codigo_producto,
            nombreProducto: item.nombre_producto,
            descripcion: item.descripcion,
            cantidad: item.cantidad,
            adjudicacion: item.adjudicacion,
        })),
    };
}

function payloadAFicha(
    payload: PayloadNormalizadoLicitacionMercadoPublico,
): LicitacionParaFicha {
    return {
        nombre: payload.nombre,
        estadoMercadoPublico: payload.estado,
        moneda: payload.moneda,
        montoEstimado: payload.monto_estimado,
        organismoComprador: payload.organismo_comprador,
        cronograma: payload.cronograma,
        adjudicacion: payload.adjudicacion,
        items: payload.items.map((item) => ({
            codigoProducto: item.codigo_producto,
            nombreProducto: item.nombre_producto,
            descripcion: item.descripcion,
            cantidad: item.cantidad,
            adjudicacion: item.adjudicacion,
        })),
    };
}

function construirSecciones(
    licitacion: LicitacionParaFicha,
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
                        <dd>{licitacion.organismoComprador?.nombre ?? '—'}</dd>
                    </div>
                    <div>
                        <dt className="text-muted-foreground">Unidad</dt>
                        <dd>{licitacion.organismoComprador?.unidad ?? '—'}</dd>
                    </div>
                    <div>
                        <dt className="text-muted-foreground">RUT</dt>
                        <dd>{licitacion.organismoComprador?.rut ?? '—'}</dd>
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
                            <Monto valor={licitacion.montoEstimado} />
                        </dd>
                    </div>
                </dl>
            ),
        },
        {
            key: 'adjudicacion',
            titulo: 'Adjudicación',
            contenido: licitacion.adjudicacion ? (
                <dl className="grid grid-cols-1 gap-3 text-sm sm:grid-cols-3">
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
                licitacion.items.length === 0 ? (
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
                            </tr>
                        </thead>
                        <tbody className="divide-y">
                            {licitacion.items.map((item, i) => (
                                <tr key={i}>
                                    <td className="py-2 font-mono">
                                        {item.codigoProducto ?? '—'}
                                    </td>
                                    <td className="py-2">
                                        {item.nombreProducto ??
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
                                </tr>
                            ))}
                        </tbody>
                    </table>
                ),
        },
    ];
}

const NOMBRES_CAMPOS_DIFERENCIA: Record<string, string> = {
    nombre: 'Nombre',
    estado_mercado_publico: 'Estado',
    codigo_estado_mercado_publico: 'Código de estado',
    moneda: 'Moneda',
    monto_estimado: 'Monto estimado',
    organismo_comprador: 'Organismo comprador',
    cronograma: 'Cronograma',
    adjudicacion: 'Adjudicación',
};

type PageProps = {
    codigo: string | null;
    licitacionLocal?: LicitacionMercadoPublico;
    vistaPrevia?: {
        payload_normalizado: PayloadNormalizadoLicitacionMercadoPublico;
        payload_crudo?: unknown;
    };
    noEncontrada?: boolean;
    comparacion?: {
        encontrada: boolean;
        diferencias: Record<string, DiferenciaCampoLicitacionMercadoPublico>;
    };
};

export default function BuscarLicitacionMercadoPublico({
    codigo,
    licitacionLocal,
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
            licitacionesMp.index.url({
                query: { codigo: codigoIngresado.trim() },
            }),
            {},
            { preserveState: true },
        );
    }

    function verificar() {
        if (!licitacionLocal) {
            return;
        }

        setProcesando(true);
        setComparacionDescartada(false);
        router.post(
            licitacionesMp.verificar.url(licitacionLocal.id),
            {},
            { preserveState: true, onFinish: () => setProcesando(false) },
        );
    }

    function aplicarActualizacion() {
        if (!licitacionLocal) {
            return;
        }

        setProcesando(true);
        router.post(
            licitacionesMp.actualizar.url(licitacionLocal.id),
            {},
            { onFinish: () => setProcesando(false) },
        );
    }

    function guardar() {
        setProcesando(true);
        router.post(
            licitacionesMp.guardar.url(),
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
            <Head title="Buscar Licitación" />

            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <div className="flex flex-row items-end gap-2 rounded-xl border p-4">
                    <div className="flex-1 space-y-1">
                        <label
                            htmlFor="codigo-licitacion"
                            className="text-sm text-muted-foreground"
                        >
                            Código de Licitación
                        </label>
                        <Input
                            id="codigo-licitacion"
                            value={codigoIngresado}
                            onChange={(e) => setCodigoIngresado(e.target.value)}
                            onKeyDown={(e) => {
                                if (e.key === 'Enter') {
                                    consultar();
                                }
                            }}
                            placeholder="Ej: 1004-34-LE26"
                        />
                    </div>
                    <Button onClick={consultar}>Consultar</Button>
                </div>

                {licitacionLocal && (
                    <>
                        <FichaConsultaMercadoPublico
                            encabezado={{
                                titulo: `Licitación ${licitacionLocal.codigo}`,
                                subtitulo:
                                    licitacionLocal.proceso_adquisicion ? (
                                        <span>
                                            Vinculada a{' '}
                                            {
                                                licitacionLocal
                                                    .proceso_adquisicion.codigo
                                            }
                                        </span>
                                    ) : (
                                        licitacionLocal.nombre
                                    ),
                                acciones: (
                                    <>
                                        <LicitacionEstadoBadge
                                            estado={
                                                licitacionLocal.estado_mercado_publico
                                            }
                                        />
                                        <Button
                                            variant="outline"
                                            disabled={procesando}
                                            onClick={verificar}
                                        >
                                            Verificar contra Mercado Público
                                        </Button>
                                        <AccionesEncabezadoFichaMercadoPublico
                                            payloadCrudo={
                                                licitacionLocal.payload_crudo
                                            }
                                            urlDetalle={urlDetalleLicitacionMercadoPublico(
                                                licitacionLocal.codigo,
                                            )}
                                            urlPdf={null}
                                        />
                                    </>
                                ),
                            }}
                            secciones={construirSecciones(
                                licitacionLocalAFicha(licitacionLocal),
                            )}
                        />

                        {comparacionVisible && (
                            <section className="space-y-3 rounded-xl border p-4">
                                <h2 className="text-base font-medium">
                                    Comparación con Mercado Público
                                </h2>

                                {!comparacion?.encontrada ? (
                                    <p className="text-sm text-muted-foreground">
                                        La licitación ya no está disponible en
                                        Mercado Público.
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
                            titulo: `Licitación ${vistaPrevia.payload_normalizado.codigo}`,
                            subtitulo:
                                vistaPrevia.payload_normalizado.nombre ??
                                'Vista previa · aún no guardada',
                            acciones: (
                                <>
                                    <LicitacionEstadoBadge
                                        estado={
                                            vistaPrevia.payload_normalizado
                                                .estado
                                        }
                                    />
                                    <AccionesEncabezadoFichaMercadoPublico
                                        payloadCrudo={vistaPrevia.payload_crudo}
                                        urlDetalle={urlDetalleLicitacionMercadoPublico(
                                            vistaPrevia.payload_normalizado
                                                .codigo,
                                        )}
                                        urlPdf={null}
                                    />
                                    <Button
                                        disabled={procesando}
                                        onClick={() => guardar()}
                                    >
                                        Guardar Licitación
                                    </Button>
                                </>
                            ),
                        }}
                        secciones={construirSecciones(
                            payloadAFicha(vistaPrevia.payload_normalizado),
                        )}
                    />
                )}

                {noEncontrada && (
                    <div className="rounded-xl border p-4 text-sm text-muted-foreground">
                        La licitación «{codigo}» no fue encontrada ni localmente
                        ni en Mercado Público.
                    </div>
                )}

                {!licitacionLocal && !vistaPrevia && !noEncontrada && (
                    <div className="rounded-xl border p-4 text-sm text-muted-foreground">
                        Ingresa un código de Licitación para comenzar.
                    </div>
                )}
            </div>
        </>
    );
}

BuscarLicitacionMercadoPublico.layout = {
    breadcrumbs: [
        {
            title: 'Licitaciones (Mercado Público)',
            href: licitacionesMp.index(),
        },
    ],
};

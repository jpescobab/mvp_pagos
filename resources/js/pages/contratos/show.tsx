import { Head, Link, router, usePage } from '@inertiajs/react';
import { useState } from 'react';
import { EstadoBadge } from '@/components/pago-proveedores/estado-badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Monto } from '@/components/ui/monto';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { formatFechaHora } from '@/lib/format';
import ordenesCompraMp from '@/routes/adquisiciones/ordenes_compra_mp';
import contratos from '@/routes/contratos';
import documentos from '@/routes/procesos/documentos';
import type {
    CasoPagoProveedorSeleccionable,
    Contrato,
    ContratoSeleccionable,
} from '@/types/contratos';
import type { TipoDocumentoSeleccionable } from '@/types/pago-proveedores';

type PageProps = {
    contrato: Contrato;
    tiposDocumento: TipoDocumentoSeleccionable[];
    procesosAdquisicion: ContratoSeleccionable[];
    licitacionesMercadoPublico: ContratoSeleccionable[];
    casosPagoProveedor: CasoPagoProveedorSeleccionable[];
};

export default function ContratoShow() {
    const {
        contrato,
        tiposDocumento,
        procesosAdquisicion,
        licitacionesMercadoPublico,
        casosPagoProveedor,
        auth,
    } = usePage<PageProps>().props;

    const proceso = contrato.proceso;
    const estadoActual = proceso?.estado_actual.codigo;
    const puedeEditar =
        estadoActual === 'borrador' &&
        auth.permissions.includes('contratos.editar');
    const puedeVincular = auth.permissions.includes('contratos.editar');
    const puedeVincularPago = auth.permissions.includes(
        'contratos.vincular_pago',
    );

    const [procesando, setProcesando] = useState(false);
    const [errorTransicion, setErrorTransicion] = useState<string | null>(null);

    function ejecutarTransicion(codigo: string) {
        setProcesando(true);
        setErrorTransicion(null);

        router.post(
            contratos.transiciones.store(contrato.id).url,
            { codigo },
            {
                preserveScroll: true,
                onError: (errors) =>
                    setErrorTransicion(
                        (errors as Record<string, string>).transicion ?? null,
                    ),
                onFinish: () => setProcesando(false),
            },
        );
    }

    // Ítems de convenio de precio
    const [nuevoItem, setNuevoItem] = useState({
        descripcion: '',
        unidad_medida: '',
        precio_unitario: '',
        moneda: '',
        vigente_desde: '',
        vigente_hasta: '',
    });
    const [errorItem, setErrorItem] = useState<string | null>(null);

    function agregarItem() {
        setErrorItem(null);

        router.post(
            contratos.items_convenio_precio.store(contrato.id).url,
            {
                descripcion: nuevoItem.descripcion,
                unidad_medida: nuevoItem.unidad_medida || null,
                precio_unitario: nuevoItem.precio_unitario,
                moneda: nuevoItem.moneda || null,
                vigente_desde: nuevoItem.vigente_desde || null,
                vigente_hasta: nuevoItem.vigente_hasta || null,
            },
            {
                preserveScroll: true,
                onSuccess: () =>
                    setNuevoItem({
                        descripcion: '',
                        unidad_medida: '',
                        precio_unitario: '',
                        moneda: '',
                        vigente_desde: '',
                        vigente_hasta: '',
                    }),
                onError: (errors) =>
                    setErrorItem(
                        (errors as Record<string, string>).descripcion ??
                            (errors as Record<string, string>)
                                .precio_unitario ??
                            null,
                    ),
            },
        );
    }

    function eliminarItem(itemId: number) {
        router.delete(
            contratos.items_convenio_precio.destroy([contrato.id, itemId]).url,
            { preserveScroll: true },
        );
    }

    // Calendario de cuotas
    const [errorCalendario, setErrorCalendario] = useState<string | null>(null);
    const [cuotaEnEdicion, setCuotaEnEdicion] = useState<number | null>(null);
    const [edicionCuota, setEdicionCuota] = useState({
        fecha_vencimiento: '',
        monto: '',
    });
    const [casoSeleccionadoPorCuota, setCasoSeleccionadoPorCuota] = useState<
        Record<number, string>
    >({});

    function generarCalendario() {
        setErrorCalendario(null);

        router.post(
            contratos.cuotas.generar(contrato.id).url,
            {},
            {
                preserveScroll: true,
                onError: (errors) =>
                    setErrorCalendario(
                        (errors as Record<string, string>).monto_total ??
                            (errors as Record<string, string>)
                                .periodicidad_pago ??
                            null,
                    ),
            },
        );
    }

    function iniciarEdicionCuota(cuota: {
        id: number;
        fecha_vencimiento: string;
        monto: string;
    }) {
        setCuotaEnEdicion(cuota.id);
        setEdicionCuota({
            fecha_vencimiento: cuota.fecha_vencimiento,
            monto: cuota.monto,
        });
    }

    function guardarCuota() {
        if (cuotaEnEdicion === null) {
            return;
        }

        router.put(
            contratos.cuotas.update([contrato.id, cuotaEnEdicion]).url,
            edicionCuota,
            {
                preserveScroll: true,
                onSuccess: () => setCuotaEnEdicion(null),
            },
        );
    }

    function vincularPago(cuotaId: number) {
        const casoId = casoSeleccionadoPorCuota[cuotaId];

        if (!casoId) {
            return;
        }

        router.post(
            contratos.cuotas.vincular_pago([contrato.id, cuotaId]).url,
            { caso_pago_proveedor_id: Number(casoId) },
            { preserveScroll: true },
        );
    }

    function desvincularPago(cuotaId: number) {
        router.delete(
            contratos.cuotas.desvincular_pago([contrato.id, cuotaId]).url,
            { preserveScroll: true },
        );
    }

    // Vínculos con Adquisiciones/Mercado Público
    const [procesoAdquisicionSeleccionado, setProcesoAdquisicionSeleccionado] =
        useState<string>(
            contrato.proceso_adquisicion
                ? String(contrato.proceso_adquisicion.id)
                : '',
        );
    const [procesandoVinculoPA, setProcesandoVinculoPA] = useState(false);

    function vincularProcesoAdquisicion() {
        if (procesoAdquisicionSeleccionado === '') {
            return;
        }

        setProcesandoVinculoPA(true);
        router.post(
            contratos.vinculo_proceso_adquisicion.store(contrato.id).url,
            {
                proceso_adquisicion_id: Number(procesoAdquisicionSeleccionado),
            },
            {
                preserveScroll: true,
                onFinish: () => setProcesandoVinculoPA(false),
            },
        );
    }

    function desvincularProcesoAdquisicion() {
        setProcesandoVinculoPA(true);
        router.delete(
            contratos.vinculo_proceso_adquisicion.destroy(contrato.id).url,
            {
                preserveScroll: true,
                onFinish: () => setProcesandoVinculoPA(false),
            },
        );
    }

    const [licitacionSeleccionada, setLicitacionSeleccionada] =
        useState<string>(
            contrato.licitacion_mercado_publico
                ? String(contrato.licitacion_mercado_publico.id)
                : '',
        );
    const [procesandoVinculoLM, setProcesandoVinculoLM] = useState(false);

    function vincularLicitacion() {
        if (licitacionSeleccionada === '') {
            return;
        }

        setProcesandoVinculoLM(true);
        router.post(
            contratos.vinculo_licitacion_mp.store(contrato.id).url,
            { licitacion_mercado_publico_id: Number(licitacionSeleccionada) },
            {
                preserveScroll: true,
                onFinish: () => setProcesandoVinculoLM(false),
            },
        );
    }

    function desvincularLicitacion() {
        setProcesandoVinculoLM(true);
        router.delete(
            contratos.vinculo_licitacion_mp.destroy(contrato.id).url,
            {
                preserveScroll: true,
                onFinish: () => setProcesandoVinculoLM(false),
            },
        );
    }

    // Documentos
    const [tipoDocumentoId, setTipoDocumentoId] = useState('');
    const [archivo, setArchivo] = useState<File | null>(null);
    const [subiendoDocumento, setSubiendoDocumento] = useState(false);
    const [errorDocumento, setErrorDocumento] = useState<string | null>(null);

    function subirDocumento() {
        if (archivo === null || tipoDocumentoId === '' || !proceso) {
            return;
        }

        setSubiendoDocumento(true);
        setErrorDocumento(null);

        const formData = new FormData();
        formData.append('archivo', archivo);
        formData.append('tipo_documento_id', tipoDocumentoId);

        router.post(documentos.store({ proceso: proceso.id }).url, formData, {
            preserveScroll: true,
            onSuccess: () => {
                setArchivo(null);
                setTipoDocumentoId('');
            },
            onError: (errors) =>
                setErrorDocumento(
                    (errors as Record<string, string>).archivo ??
                        (errors as Record<string, string>).tipo_documento_id ??
                        null,
                ),
            onFinish: () => setSubiendoDocumento(false),
        });
    }

    function desvincularDocumento(vinculoId: number) {
        if (!proceso) {
            return;
        }

        router.delete(
            documentos.destroy({ proceso: proceso.id, vinculo: vinculoId }).url,
            { preserveScroll: true },
        );
    }

    const historial = [...(proceso?.historial_transiciones ?? [])].reverse();

    return (
        <>
            <Head title={contrato.codigo} />

            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <div className="flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <h1 className="font-mono text-xl font-semibold tracking-tight">
                            {contrato.codigo}
                        </h1>
                        <p className="text-sm text-muted-foreground">
                            ID institucional {contrato.id_institucional} ·{' '}
                            {contrato.proveedor.nombre ?? '—'}
                        </p>
                    </div>
                    <div className="flex items-center gap-2">
                        {puedeEditar && (
                            <Button
                                variant="outline"
                                size="sm"
                                onClick={() =>
                                    router.get(contratos.edit(contrato.id).url)
                                }
                            >
                                Editar
                            </Button>
                        )}
                        {proceso && (
                            <EstadoBadge estado={proceso.estado_actual} />
                        )}
                    </div>
                </div>

                <section className="grid grid-cols-2 gap-x-6 gap-y-3 rounded-xl border p-4 text-sm">
                    <div>
                        <span className="text-muted-foreground">
                            Modalidad de compra:{' '}
                        </span>
                        {contrato.modalidad_compra}
                    </div>
                    <div>
                        <span className="text-muted-foreground">
                            Tipo de contrato:{' '}
                        </span>
                        {contrato.tipo_contrato}
                    </div>
                    <div>
                        <span className="text-muted-foreground">
                            ID proceso Mercado Público:{' '}
                        </span>
                        {contrato.id_proceso_mp ?? '—'}
                    </div>
                    <div>
                        <span className="text-muted-foreground">
                            Vigencia:{' '}
                        </span>
                        {contrato.fecha_inicio_vigencia} →{' '}
                        {contrato.fecha_fin_vigencia}
                    </div>
                    <div className="col-span-2">
                        <span className="text-muted-foreground">
                            Referencia:{' '}
                        </span>
                        {contrato.referencia}
                    </div>
                    {(contrato.materia || contrato.submateria) && (
                        <div className="col-span-2">
                            <span className="text-muted-foreground">
                                Materia:{' '}
                            </span>
                            {contrato.materia ?? '—'}
                            {contrato.submateria && ` / ${contrato.submateria}`}
                        </div>
                    )}
                    {contrato.tiene_calendario_pago && (
                        <>
                            <div>
                                <span className="text-muted-foreground">
                                    Periodicidad de pago:{' '}
                                </span>
                                {contrato.periodicidad_pago ?? '—'}
                            </div>
                            <div>
                                <span className="text-muted-foreground">
                                    Monto total:{' '}
                                </span>
                                <Monto valor={contrato.monto_total} />
                            </div>
                        </>
                    )}
                </section>

                <section className="space-y-3 rounded-xl border p-4">
                    <h2 className="text-base font-medium">
                        Transiciones disponibles
                    </h2>

                    {errorTransicion && (
                        <p className="text-sm text-destructive">
                            {errorTransicion}
                        </p>
                    )}

                    {!proceso ||
                    proceso.transiciones_disponibles.length === 0 ? (
                        <p className="text-sm text-muted-foreground">
                            No hay transiciones disponibles desde el estado
                            actual.
                        </p>
                    ) : (
                        <div className="flex flex-wrap gap-2">
                            {proceso.transiciones_disponibles.map(
                                (transicion) => (
                                    <Button
                                        key={transicion.codigo}
                                        variant="outline"
                                        disabled={procesando}
                                        onClick={() =>
                                            ejecutarTransicion(
                                                transicion.codigo,
                                            )
                                        }
                                    >
                                        {transicion.nombre}
                                    </Button>
                                ),
                            )}
                        </div>
                    )}
                </section>

                {contrato.tiene_convenio_precio && (
                    <section className="space-y-3 rounded-xl border p-4">
                        <h2 className="text-base font-medium">
                            Ítems de convenio de precio
                        </h2>

                        {(contrato.items_convenio_precio ?? []).length === 0 ? (
                            <p className="text-sm text-muted-foreground">
                                Sin ítems registrados todavía.
                            </p>
                        ) : (
                            <table className="w-full text-sm">
                                <thead>
                                    <tr className="border-b text-left text-muted-foreground">
                                        <th className="py-2">Descripción</th>
                                        <th className="py-2">Unidad</th>
                                        <th className="py-2 text-right">
                                            Precio unitario
                                        </th>
                                        <th className="py-2">Vigencia</th>
                                        <th className="py-2" />
                                    </tr>
                                </thead>
                                <tbody className="divide-y">
                                    {(contrato.items_convenio_precio ?? []).map(
                                        (item) => (
                                            <tr key={item.id}>
                                                <td className="py-2">
                                                    {item.descripcion}
                                                </td>
                                                <td className="py-2 text-muted-foreground">
                                                    {item.unidad_medida ?? '—'}
                                                </td>
                                                <td className="py-2 text-right">
                                                    <Monto
                                                        valor={
                                                            item.precio_unitario
                                                        }
                                                    />
                                                </td>
                                                <td className="py-2 text-muted-foreground">
                                                    {item.vigente_desde ?? '—'}
                                                    {' → '}
                                                    {item.vigente_hasta ?? '—'}
                                                </td>
                                                <td className="py-2 text-right">
                                                    {puedeEditar && (
                                                        <Button
                                                            variant="outline"
                                                            size="sm"
                                                            onClick={() =>
                                                                eliminarItem(
                                                                    item.id,
                                                                )
                                                            }
                                                        >
                                                            Eliminar
                                                        </Button>
                                                    )}
                                                </td>
                                            </tr>
                                        ),
                                    )}
                                </tbody>
                            </table>
                        )}

                        {puedeEditar && (
                            <div className="flex flex-wrap items-end gap-2 border-t pt-3">
                                {errorItem && (
                                    <p className="w-full text-sm text-destructive">
                                        {errorItem}
                                    </p>
                                )}
                                <div className="grid gap-1">
                                    <Label htmlFor="item-descripcion">
                                        Descripción
                                    </Label>
                                    <Input
                                        id="item-descripcion"
                                        className="w-48"
                                        value={nuevoItem.descripcion}
                                        onChange={(e) =>
                                            setNuevoItem({
                                                ...nuevoItem,
                                                descripcion: e.target.value,
                                            })
                                        }
                                    />
                                </div>
                                <div className="grid gap-1">
                                    <Label htmlFor="item-unidad">Unidad</Label>
                                    <Input
                                        id="item-unidad"
                                        className="w-28"
                                        value={nuevoItem.unidad_medida}
                                        onChange={(e) =>
                                            setNuevoItem({
                                                ...nuevoItem,
                                                unidad_medida: e.target.value,
                                            })
                                        }
                                    />
                                </div>
                                <div className="grid gap-1">
                                    <Label htmlFor="item-precio">
                                        Precio unitario
                                    </Label>
                                    <Input
                                        id="item-precio"
                                        type="number"
                                        step="0.01"
                                        className="w-32"
                                        value={nuevoItem.precio_unitario}
                                        onChange={(e) =>
                                            setNuevoItem({
                                                ...nuevoItem,
                                                precio_unitario: e.target.value,
                                            })
                                        }
                                    />
                                </div>
                                <div className="grid gap-1">
                                    <Label htmlFor="item-desde">Desde</Label>
                                    <Input
                                        id="item-desde"
                                        type="date"
                                        className="w-36"
                                        value={nuevoItem.vigente_desde}
                                        onChange={(e) =>
                                            setNuevoItem({
                                                ...nuevoItem,
                                                vigente_desde: e.target.value,
                                            })
                                        }
                                    />
                                </div>
                                <div className="grid gap-1">
                                    <Label htmlFor="item-hasta">Hasta</Label>
                                    <Input
                                        id="item-hasta"
                                        type="date"
                                        className="w-36"
                                        value={nuevoItem.vigente_hasta}
                                        onChange={(e) =>
                                            setNuevoItem({
                                                ...nuevoItem,
                                                vigente_hasta: e.target.value,
                                            })
                                        }
                                    />
                                </div>
                                <Button
                                    disabled={
                                        nuevoItem.descripcion === '' ||
                                        nuevoItem.precio_unitario === ''
                                    }
                                    onClick={agregarItem}
                                >
                                    Agregar
                                </Button>
                            </div>
                        )}
                    </section>
                )}

                {contrato.tiene_calendario_pago && (
                    <section className="space-y-3 rounded-xl border p-4">
                        <h2 className="text-base font-medium">
                            Calendario de pago
                        </h2>

                        {errorCalendario && (
                            <p className="text-sm text-destructive">
                                {errorCalendario}
                            </p>
                        )}

                        {(contrato.cuotas ?? []).length === 0 ? (
                            <div className="flex items-center justify-between">
                                <p className="text-sm text-muted-foreground">
                                    Sin cuotas generadas todavía.
                                </p>
                                {puedeEditar && (
                                    <Button onClick={generarCalendario}>
                                        Generar calendario
                                    </Button>
                                )}
                            </div>
                        ) : (
                            <table className="w-full text-sm">
                                <thead>
                                    <tr className="border-b text-left text-muted-foreground">
                                        <th className="py-2">N°</th>
                                        <th className="py-2">Vencimiento</th>
                                        <th className="py-2 text-right">
                                            Monto
                                        </th>
                                        <th className="py-2">Estado</th>
                                        <th className="py-2">Caso de pago</th>
                                        <th className="py-2" />
                                    </tr>
                                </thead>
                                <tbody className="divide-y">
                                    {(contrato.cuotas ?? []).map((cuota) => (
                                        <tr key={cuota.id}>
                                            <td className="py-2">
                                                {cuota.numero_cuota}
                                            </td>
                                            <td className="py-2">
                                                {cuotaEnEdicion === cuota.id ? (
                                                    <Input
                                                        type="date"
                                                        className="w-36"
                                                        value={
                                                            edicionCuota.fecha_vencimiento
                                                        }
                                                        onChange={(e) =>
                                                            setEdicionCuota({
                                                                ...edicionCuota,
                                                                fecha_vencimiento:
                                                                    e.target
                                                                        .value,
                                                            })
                                                        }
                                                    />
                                                ) : (
                                                    cuota.fecha_vencimiento
                                                )}
                                            </td>
                                            <td className="py-2 text-right">
                                                {cuotaEnEdicion === cuota.id ? (
                                                    <Input
                                                        type="number"
                                                        step="0.01"
                                                        className="w-28"
                                                        value={
                                                            edicionCuota.monto
                                                        }
                                                        onChange={(e) =>
                                                            setEdicionCuota({
                                                                ...edicionCuota,
                                                                monto: e.target
                                                                    .value,
                                                            })
                                                        }
                                                    />
                                                ) : (
                                                    <Monto
                                                        valor={cuota.monto}
                                                    />
                                                )}
                                            </td>
                                            <td className="py-2">
                                                {cuota.estado === 'pagada'
                                                    ? 'Pagada'
                                                    : cuota.esta_vencida
                                                      ? 'Vencida'
                                                      : 'Pendiente'}
                                            </td>
                                            <td className="py-2">
                                                {cuota.caso_pago_proveedor ? (
                                                    <span className="font-mono">
                                                        {
                                                            cuota
                                                                .caso_pago_proveedor
                                                                .sgf_id
                                                        }
                                                    </span>
                                                ) : (
                                                    '—'
                                                )}
                                            </td>
                                            <td className="space-x-1 py-2 text-right">
                                                {puedeEditar &&
                                                    estadoActual ===
                                                        'borrador' &&
                                                    (cuotaEnEdicion ===
                                                    cuota.id ? (
                                                        <Button
                                                            size="sm"
                                                            onClick={
                                                                guardarCuota
                                                            }
                                                        >
                                                            Guardar
                                                        </Button>
                                                    ) : (
                                                        <Button
                                                            variant="outline"
                                                            size="sm"
                                                            onClick={() =>
                                                                iniciarEdicionCuota(
                                                                    cuota,
                                                                )
                                                            }
                                                        >
                                                            Editar
                                                        </Button>
                                                    ))}
                                                {puedeVincularPago &&
                                                    (cuota.caso_pago_proveedor ? (
                                                        <Button
                                                            variant="outline"
                                                            size="sm"
                                                            onClick={() =>
                                                                desvincularPago(
                                                                    cuota.id,
                                                                )
                                                            }
                                                        >
                                                            Desvincular pago
                                                        </Button>
                                                    ) : (
                                                        <span className="inline-flex items-center gap-1">
                                                            <Select
                                                                value={
                                                                    casoSeleccionadoPorCuota[
                                                                        cuota.id
                                                                    ] ?? ''
                                                                }
                                                                onValueChange={(
                                                                    v,
                                                                ) =>
                                                                    setCasoSeleccionadoPorCuota(
                                                                        {
                                                                            ...casoSeleccionadoPorCuota,
                                                                            [cuota.id]:
                                                                                v,
                                                                        },
                                                                    )
                                                                }
                                                            >
                                                                <SelectTrigger className="h-8 w-40">
                                                                    <SelectValue placeholder="Caso de pago" />
                                                                </SelectTrigger>
                                                                <SelectContent>
                                                                    {casosPagoProveedor.map(
                                                                        (
                                                                            caso,
                                                                        ) => (
                                                                            <SelectItem
                                                                                key={
                                                                                    caso.id
                                                                                }
                                                                                value={String(
                                                                                    caso.id,
                                                                                )}
                                                                            >
                                                                                {
                                                                                    caso.sgf_id
                                                                                }
                                                                            </SelectItem>
                                                                        ),
                                                                    )}
                                                                </SelectContent>
                                                            </Select>
                                                            <Button
                                                                size="sm"
                                                                disabled={
                                                                    !casoSeleccionadoPorCuota[
                                                                        cuota.id
                                                                    ]
                                                                }
                                                                onClick={() =>
                                                                    vincularPago(
                                                                        cuota.id,
                                                                    )
                                                                }
                                                            >
                                                                Vincular
                                                            </Button>
                                                        </span>
                                                    ))}
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        )}
                    </section>
                )}

                <section className="space-y-3 rounded-xl border p-4">
                    <h2 className="text-base font-medium">
                        Proceso de adquisición vinculado
                    </h2>

                    {contrato.proceso_adquisicion ? (
                        <div className="flex items-center justify-between">
                            <p className="text-sm">
                                {contrato.proceso_adquisicion.codigo}
                            </p>
                            {puedeVincular && (
                                <Button
                                    variant="outline"
                                    disabled={procesandoVinculoPA}
                                    onClick={desvincularProcesoAdquisicion}
                                >
                                    Desvincular
                                </Button>
                            )}
                        </div>
                    ) : (
                        puedeVincular && (
                            <div className="flex flex-wrap items-end gap-2">
                                <Select
                                    value={procesoAdquisicionSeleccionado}
                                    onValueChange={
                                        setProcesoAdquisicionSeleccionado
                                    }
                                >
                                    <SelectTrigger className="w-64">
                                        <SelectValue placeholder="Selecciona un proceso" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {procesosAdquisicion.map((proc) => (
                                            <SelectItem
                                                key={proc.id}
                                                value={String(proc.id)}
                                            >
                                                {proc.codigo}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                <Button
                                    disabled={
                                        procesandoVinculoPA ||
                                        procesoAdquisicionSeleccionado === ''
                                    }
                                    onClick={vincularProcesoAdquisicion}
                                >
                                    Vincular
                                </Button>
                            </div>
                        )
                    )}
                </section>

                <section className="space-y-3 rounded-xl border p-4">
                    <h2 className="text-base font-medium">
                        Licitación (Mercado Público) vinculada
                    </h2>

                    {contrato.licitacion_mercado_publico ? (
                        <div className="flex items-center justify-between">
                            <p className="text-sm">
                                {contrato.licitacion_mercado_publico.codigo}
                            </p>
                            {puedeVincular && (
                                <Button
                                    variant="outline"
                                    disabled={procesandoVinculoLM}
                                    onClick={desvincularLicitacion}
                                >
                                    Desvincular
                                </Button>
                            )}
                        </div>
                    ) : (
                        puedeVincular && (
                            <div className="flex flex-wrap items-end gap-2">
                                <Select
                                    value={licitacionSeleccionada}
                                    onValueChange={setLicitacionSeleccionada}
                                >
                                    <SelectTrigger className="w-64">
                                        <SelectValue placeholder="Selecciona una licitación" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {licitacionesMercadoPublico.map(
                                            (lic) => (
                                                <SelectItem
                                                    key={lic.id}
                                                    value={String(lic.id)}
                                                >
                                                    {lic.codigo}
                                                </SelectItem>
                                            ),
                                        )}
                                    </SelectContent>
                                </Select>
                                <Button
                                    disabled={
                                        procesandoVinculoLM ||
                                        licitacionSeleccionada === ''
                                    }
                                    onClick={vincularLicitacion}
                                >
                                    Vincular
                                </Button>
                            </div>
                        )
                    )}
                </section>

                <section className="space-y-3 rounded-xl border p-4">
                    <h2 className="text-base font-medium">
                        Órdenes de compra (Mercado Público)
                    </h2>

                    {(contrato.ordenes_compra_mercado_publico ?? []).length ===
                    0 ? (
                        <p className="text-sm text-muted-foreground">
                            Sin órdenes de compra vinculadas todavía.
                        </p>
                    ) : (
                        <ul className="divide-y text-sm">
                            {(
                                contrato.ordenes_compra_mercado_publico ?? []
                            ).map((orden) => (
                                <li
                                    key={orden.id}
                                    className="flex items-center justify-between py-2"
                                >
                                    <span className="font-mono">
                                        {orden.codigo}
                                    </span>
                                    <Link
                                        href={ordenesCompraMp.show.url(
                                            orden.id,
                                        )}
                                        className="text-primary underline"
                                    >
                                        Ver
                                    </Link>
                                </li>
                            ))}
                        </ul>
                    )}
                </section>

                <section className="space-y-3 rounded-xl border p-4">
                    <h2 className="text-base font-medium">
                        Checklist documental
                    </h2>

                    {!proceso?.checklist ? (
                        <p className="text-sm text-muted-foreground">
                            Sin checklist generado aún.
                        </p>
                    ) : (
                        <ul className="divide-y text-sm">
                            {proceso.checklist.items.map((item, i) => (
                                <li
                                    key={i}
                                    className="flex items-center justify-between py-2"
                                >
                                    <span>
                                        {item.tipo_documento ??
                                            'Documento sin tipo'}{' '}
                                        <span className="text-muted-foreground">
                                            ({item.tipo_requisito})
                                        </span>
                                    </span>
                                    <span className="flex items-center gap-2 text-muted-foreground">
                                        {item.estado_cumplimiento}
                                        {item.documento_id !== null && (
                                            <a
                                                href={
                                                    documentos.descargar({
                                                        proceso: proceso.id,
                                                        documento:
                                                            item.documento_id,
                                                    }).url
                                                }
                                                className="underline"
                                            >
                                                Ver documento
                                            </a>
                                        )}
                                    </span>
                                </li>
                            ))}
                        </ul>
                    )}
                </section>

                <section className="space-y-3 rounded-xl border p-4">
                    <h2 className="text-base font-medium">Documentos</h2>

                    {errorDocumento && (
                        <p className="text-sm text-destructive">
                            {errorDocumento}
                        </p>
                    )}

                    {(proceso?.documentos ?? []).length === 0 ? (
                        <p className="text-sm text-muted-foreground">
                            Sin documentos vinculados todavía.
                        </p>
                    ) : (
                        <ul className="divide-y text-sm">
                            {(proceso?.documentos ?? []).map((doc) => (
                                <li
                                    key={doc.vinculo_id}
                                    className="flex items-center justify-between py-2"
                                >
                                    <span>
                                        {doc.tipo_documento ??
                                            'Documento sin tipo'}{' '}
                                        <span className="text-muted-foreground">
                                            ({doc.nombre_archivo}) ·{' '}
                                            {doc.estado_vigente}
                                        </span>
                                    </span>
                                    <div className="flex gap-2">
                                        <a
                                            href={
                                                documentos.descargar({
                                                    proceso: proceso!.id,
                                                    documento: doc.documento_id,
                                                }).url
                                            }
                                            className="text-sm underline"
                                        >
                                            Descargar
                                        </a>
                                        {puedeEditar && (
                                            <Button
                                                variant="outline"
                                                size="sm"
                                                onClick={() =>
                                                    desvincularDocumento(
                                                        doc.vinculo_id,
                                                    )
                                                }
                                            >
                                                Desvincular
                                            </Button>
                                        )}
                                    </div>
                                </li>
                            ))}
                        </ul>
                    )}

                    {puedeEditar && (
                        <div className="flex flex-wrap items-end gap-2">
                            <div className="space-y-1">
                                <Label htmlFor="tipo-documento">
                                    Tipo de documento
                                </Label>
                                <Select
                                    value={tipoDocumentoId}
                                    onValueChange={setTipoDocumentoId}
                                >
                                    <SelectTrigger id="tipo-documento">
                                        <SelectValue placeholder="Selecciona un tipo" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {tiposDocumento.map((tipo) => (
                                            <SelectItem
                                                key={tipo.id}
                                                value={String(tipo.id)}
                                            >
                                                {tipo.nombre}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </div>
                            <div className="space-y-1">
                                <Label htmlFor="archivo">Archivo</Label>
                                <input
                                    id="archivo"
                                    type="file"
                                    accept=".pdf,.jpg,.jpeg,.png"
                                    onChange={(e) =>
                                        setArchivo(e.target.files?.[0] ?? null)
                                    }
                                    className="text-sm"
                                />
                            </div>
                            <Button
                                disabled={
                                    subiendoDocumento ||
                                    archivo === null ||
                                    tipoDocumentoId === ''
                                }
                                onClick={subirDocumento}
                            >
                                Subir
                            </Button>
                        </div>
                    )}
                </section>

                <section className="space-y-3 rounded-xl border p-4">
                    <h2 className="text-base font-medium">
                        Historial de transiciones
                    </h2>

                    {historial.length === 0 ? (
                        <p className="text-sm text-muted-foreground">
                            Sin transiciones registradas todavía.
                        </p>
                    ) : (
                        <ul className="divide-y text-sm">
                            {historial.map((item, i) => (
                                <li key={i} className="space-y-1 py-3">
                                    <div className="flex items-center justify-between">
                                        <span className="font-medium">
                                            {item.transicion.nombre}
                                        </span>
                                        <span className="text-muted-foreground">
                                            {formatFechaHora(item.created_at)}
                                        </span>
                                    </div>
                                    <p className="text-muted-foreground">
                                        {item.estado_origen.codigo} →{' '}
                                        {item.estado_destino.codigo} ·{' '}
                                        {item.user.name ?? 'Sistema'}
                                    </p>
                                    {item.comentario && (
                                        <p className="italic">
                                            “{item.comentario}”
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

ContratoShow.layout = {
    breadcrumbs: [
        { title: 'Contratos', href: contratos.index() },
        { title: 'Detalle', href: '#' },
    ],
};

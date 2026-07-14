import { Head, Link, router, usePage } from '@inertiajs/react';
import { useMemo, useState } from 'react';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import tiposDocumento from '@/routes/maestros/tipos-documento';
import tiposProcesoPago from '@/routes/maestros/tipos-proceso-pago';
import requisitosDocumentales from '@/routes/pago-proveedores/requisitos-documentales';
import type {
    RequisitoDocumentalMatrizItem,
    TipoDocumentoSeleccionableMatriz,
    TipoProcesoPago,
} from '@/types/pago-proveedores';

type PageProps = {
    tiposDocumento: TipoDocumentoSeleccionableMatriz[];
    tiposProcesoPago: TipoProcesoPago[];
    requisitos: RequisitoDocumentalMatrizItem[];
};

const SIN_TIPO_PROCESO = 'sin-tipo-proceso';
const NO_APLICA = 'no-aplica';

function claveCelda(
    tipoDocumentoId: number,
    tipoProcesoPagoId: number | null,
): string {
    return `${tipoDocumentoId}:${tipoProcesoPagoId ?? 'null'}`;
}

export default function RequisitosDocumentalesIndex() {
    const {
        tiposDocumento: tiposDoc,
        tiposProcesoPago: tiposProceso,
        requisitos,
    } = usePage<PageProps>().props;

    const [actualizando, setActualizando] = useState<string | null>(null);

    const mapaRequisitos = useMemo(() => {
        const mapa = new Map<string, RequisitoDocumentalMatrizItem>();

        for (const requisito of requisitos) {
            mapa.set(
                claveCelda(
                    requisito.tipo_documento_id,
                    requisito.tipo_proceso_pago_id,
                ),
                requisito,
            );
        }

        return mapa;
    }, [requisitos]);

    function actualizarCelda(
        tipoDocumentoId: number,
        tipoProcesoPagoId: number | null,
        valor: string,
    ) {
        const clave = claveCelda(tipoDocumentoId, tipoProcesoPagoId);
        setActualizando(clave);

        router.put(
            requisitosDocumentales.update(tipoDocumentoId).url,
            {
                tipo_proceso_pago_id: tipoProcesoPagoId,
                tipo_requisito: valor === NO_APLICA ? null : valor,
            },
            {
                preserveScroll: true,
                preserveState: true,
                onFinish: () => setActualizando(null),
            },
        );
    }

    const columnas: { id: number | null; nombre: string }[] = [
        { id: null, nombre: 'Todos los tipos' },
        ...tiposProceso.map((tipo) => ({ id: tipo.id, nombre: tipo.nombre })),
    ];

    return (
        <>
            <Head title="Requisitos Documentales" />

            <div className="flex h-full flex-1 flex-col gap-4 p-4">
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-xl font-semibold tracking-tight">
                            Requisitos Documentales
                        </h1>
                        <p className="text-sm text-muted-foreground">
                            Define qué documentos son obligatorios u opcionales
                            según el tipo de proceso de pago.
                        </p>
                    </div>
                    <div className="flex items-center gap-2">
                        <Link
                            href={tiposProcesoPago.index().url}
                            className="text-sm underline"
                        >
                            Tipos de proceso de pago
                        </Link>
                        <Link
                            href={tiposDocumento.index().url}
                            className="text-sm underline"
                        >
                            Tipos de documento
                        </Link>
                    </div>
                </div>

                <div className="overflow-x-auto rounded-xl border">
                    <table className="w-full border-collapse text-xs">
                        <thead className="bg-muted/50 text-left text-[10px] tracking-wide text-muted-foreground uppercase">
                            <tr>
                                <th className="sticky left-0 z-10 min-w-40 bg-muted/50 px-2.5 py-2 font-medium">
                                    Documento
                                </th>
                                {columnas.map((columna) => (
                                    <th
                                        key={columna.id ?? SIN_TIPO_PROCESO}
                                        className="min-w-40 px-2.5 py-2 font-medium"
                                    >
                                        {columna.nombre}
                                    </th>
                                ))}
                            </tr>
                        </thead>
                        <tbody className="divide-y">
                            {tiposDoc.length === 0 && (
                                <tr>
                                    <td
                                        colSpan={columnas.length + 1}
                                        className="px-2.5 py-5 text-center text-muted-foreground"
                                    >
                                        Sin tipos de documento activos.
                                    </td>
                                </tr>
                            )}
                            {tiposDoc.map((tipoDocumento) => (
                                <tr key={tipoDocumento.id}>
                                    <td
                                        className="sticky left-0 z-10 truncate bg-background px-2.5 py-1.5 font-medium"
                                        title={tipoDocumento.nombre}
                                    >
                                        {tipoDocumento.nombre}
                                    </td>
                                    {columnas.map((columna) => {
                                        const clave = claveCelda(
                                            tipoDocumento.id,
                                            columna.id,
                                        );
                                        const requisito =
                                            mapaRequisitos.get(clave);
                                        const valor =
                                            requisito?.tipo_requisito ??
                                            NO_APLICA;

                                        return (
                                            <td
                                                key={clave}
                                                className="px-2.5 py-1.5"
                                            >
                                                <Select
                                                    value={valor}
                                                    disabled={
                                                        actualizando === clave
                                                    }
                                                    onValueChange={(
                                                        nuevoValor,
                                                    ) =>
                                                        actualizarCelda(
                                                            tipoDocumento.id,
                                                            columna.id,
                                                            nuevoValor,
                                                        )
                                                    }
                                                >
                                                    <SelectTrigger className="h-8 w-full text-xs">
                                                        <SelectValue />
                                                    </SelectTrigger>
                                                    <SelectContent>
                                                        <SelectItem
                                                            value={NO_APLICA}
                                                        >
                                                            No aplica
                                                        </SelectItem>
                                                        <SelectItem value="opcional">
                                                            Opcional
                                                        </SelectItem>
                                                        <SelectItem value="obligatorio">
                                                            Obligatorio
                                                        </SelectItem>
                                                    </SelectContent>
                                                </Select>
                                            </td>
                                        );
                                    })}
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            </div>
        </>
    );
}

RequisitosDocumentalesIndex.layout = {
    breadcrumbs: [
        {
            title: 'Requisitos Documentales',
            href: requisitosDocumentales.index(),
        },
    ],
};

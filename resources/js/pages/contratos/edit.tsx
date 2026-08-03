import { Head, router, usePage } from '@inertiajs/react';
import { useState } from 'react';
import { ContratoForm } from '@/components/contratos/contrato-form';
import type { ContratoFormValues } from '@/components/contratos/contrato-form';
import contratos from '@/routes/contratos';
import type { Contrato } from '@/types/contratos';

type PageProps = {
    contrato: Contrato;
};

export default function ContratoEdit() {
    const { contrato } = usePage<PageProps>().props;

    const [valores, setValores] = useState<ContratoFormValues>({
        id_institucional: String(contrato.id_institucional),
        modalidad_compra: contrato.modalidad_compra,
        id_proceso_mp: contrato.id_proceso_mp ?? '',
        tipo_contrato: contrato.tipo_contrato,
        referencia: contrato.referencia,
        fecha_inicio_vigencia: contrato.fecha_inicio_vigencia,
        fecha_fin_vigencia: contrato.fecha_fin_vigencia,
        materia: contrato.materia ?? '',
        submateria: contrato.submateria ?? '',
        tiene_convenio_precio: contrato.tiene_convenio_precio,
        tiene_calendario_pago: contrato.tiene_calendario_pago,
        periodicidad_pago: contrato.periodicidad_pago ?? '',
        monto_total: contrato.monto_total ?? '',
        proveedor_rutproveedor: contrato.proveedor.rutproveedor ?? '',
        proveedor_nombre: contrato.proveedor.nombre ?? '',
    });
    const [errors, setErrors] = useState<Record<string, string>>({});
    const [procesando, setProcesando] = useState(false);

    function enviar() {
        setProcesando(true);
        setErrors({});

        router.put(
            contratos.update(contrato.id).url,
            {
                id_institucional: valores.id_institucional
                    ? Number(valores.id_institucional)
                    : null,
                modalidad_compra: valores.modalidad_compra,
                id_proceso_mp: valores.id_proceso_mp || null,
                tipo_contrato: valores.tipo_contrato,
                referencia: valores.referencia,
                fecha_inicio_vigencia: valores.fecha_inicio_vigencia,
                fecha_fin_vigencia: valores.fecha_fin_vigencia,
                materia: valores.materia || null,
                submateria: valores.submateria || null,
                tiene_convenio_precio: valores.tiene_convenio_precio,
                tiene_calendario_pago: valores.tiene_calendario_pago,
                periodicidad_pago: valores.tiene_calendario_pago
                    ? valores.periodicidad_pago || null
                    : null,
                monto_total: valores.monto_total || null,
                proveedor_id: contrato.proveedor.id,
            },
            {
                onError: (errores) =>
                    setErrors(errores as Record<string, string>),
                onFinish: () => setProcesando(false),
            },
        );
    }

    return (
        <>
            <Head title={`Editar ${contrato.codigo}`} />

            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <h1 className="text-xl font-semibold tracking-tight">
                    Editar {contrato.codigo}
                </h1>

                <ContratoForm
                    valores={valores}
                    onChange={setValores}
                    errors={errors}
                    procesando={procesando}
                    onSubmit={enviar}
                    textoBoton="Guardar cambios"
                    hrefCancelar={contratos.show(contrato.id).url}
                    modoEdicion
                />
            </div>
        </>
    );
}

ContratoEdit.layout = {
    breadcrumbs: [
        { title: 'Contratos', href: contratos.index() },
        { title: 'Editar', href: '#' },
    ],
};

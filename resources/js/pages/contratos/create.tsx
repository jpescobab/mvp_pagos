import { Head, router } from '@inertiajs/react';
import { useState } from 'react';
import {
    CONTRATO_FORM_VALORES_INICIALES,
    ContratoForm,
} from '@/components/contratos/contrato-form';
import contratos from '@/routes/contratos';

export default function ContratoCreate() {
    const [valores, setValores] = useState(CONTRATO_FORM_VALORES_INICIALES);
    const [errors, setErrors] = useState<Record<string, string>>({});
    const [procesando, setProcesando] = useState(false);

    function enviar() {
        setProcesando(true);
        setErrors({});

        router.post(
            contratos.store().url,
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
                proveedor: {
                    rutproveedor: valores.proveedor_rutproveedor,
                    nombre: valores.proveedor_nombre || null,
                },
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
            <Head title="Nuevo contrato" />

            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <h1 className="text-xl font-semibold tracking-tight">
                    Nuevo contrato
                </h1>

                <ContratoForm
                    valores={valores}
                    onChange={setValores}
                    errors={errors}
                    procesando={procesando}
                    onSubmit={enviar}
                    textoBoton="Crear borrador"
                    hrefCancelar={contratos.index().url}
                />
            </div>
        </>
    );
}

ContratoCreate.layout = {
    breadcrumbs: [
        { title: 'Contratos', href: contratos.index() },
        { title: 'Nuevo', href: contratos.create() },
    ],
};

import { Head, router, usePage } from '@inertiajs/react';
import { useState } from 'react';
import {
    CDP_FORM_VALORES_INICIALES,
    CdpForm,
    SIN_ADQUISICION,
    SIN_MEDIO_SOLICITUD,
} from '@/components/presupuesto/cdp-form';
import cdps from '@/routes/presupuesto/cdps';
import type {
    LineaPresupuestoSeleccionable,
    ProcesoAdquisicionSeleccionable,
} from '@/types/presupuesto';

type PageProps = {
    lineasPresupuesto: LineaPresupuestoSeleccionable[];
    procesosAdquisicion: ProcesoAdquisicionSeleccionable[];
};

export default function CdpCreate() {
    const { lineasPresupuesto, procesosAdquisicion } =
        usePage<PageProps>().props;

    const [valores, setValores] = useState(CDP_FORM_VALORES_INICIALES);
    const [errors, setErrors] = useState<Record<string, string>>({});
    const [procesando, setProcesando] = useState(false);

    function enviar() {
        setProcesando(true);
        setErrors({});

        router.post(
            cdps.store().url,
            {
                presupuesto_id: valores.presupuesto_id
                    ? Number(valores.presupuesto_id)
                    : null,
                fecha_solicitud: valores.fecha_solicitud || null,
                tipo_gasto: valores.tipo_gasto,
                codigo_iniciativa: valores.codigo_iniciativa || null,
                nombre: valores.nombre,
                nombre_iniciativa: valores.nombre_iniciativa || null,
                caracter_gasto: valores.caracter_gasto,
                medio_solicitud:
                    valores.medio_solicitud === SIN_MEDIO_SOLICITUD
                        ? null
                        : valores.medio_solicitud,
                requerimiento_numero: valores.requerimiento_numero || null,
                moneda_compra: valores.moneda_compra,
                total_moneda_compra: valores.total_moneda_compra || null,
                fecha_paridad: valores.fecha_paridad || null,
                anio_validez: valores.anio_validez
                    ? Number(valores.anio_validez)
                    : null,
                proceso_adquisicion_id:
                    valores.proceso_adquisicion_id === SIN_ADQUISICION
                        ? null
                        : Number(valores.proceso_adquisicion_id),
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
            <Head title="Nuevo CDP" />

            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <h1 className="text-xl font-semibold tracking-tight">
                    Nuevo CDP
                </h1>

                <CdpForm
                    valores={valores}
                    onChange={setValores}
                    errors={errors}
                    lineasPresupuesto={lineasPresupuesto}
                    procesosAdquisicion={procesosAdquisicion}
                    procesando={procesando}
                    onSubmit={enviar}
                    textoBoton="Crear borrador"
                    hrefCancelar={cdps.index().url}
                />
            </div>
        </>
    );
}

CdpCreate.layout = {
    breadcrumbs: [
        { title: 'CDP', href: cdps.index() },
        { title: 'Nuevo', href: cdps.create() },
    ],
};

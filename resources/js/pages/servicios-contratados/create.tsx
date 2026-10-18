import { Head, router, usePage } from '@inertiajs/react';
import { useState } from 'react';
import { FormularioServicio } from '@/components/servicios-contratados/formulario-servicio';
import servicios from '@/routes/servicios-contratados';
import type { OpcionesFormularioServicio } from '@/types/servicios-contratados';

export default function ServicioContratadoCrear() {
    const opciones = usePage<OpcionesFormularioServicio>().props;
    const [errors, setErrors] = useState<Record<string, string>>({});
    const [procesando, setProcesando] = useState(false);

    function enviar(datos: Record<string, unknown>) {
        setProcesando(true);
        setErrors({});

        router.post(servicios.store().url, datos as never, {
            onError: (e) => setErrors(e as Record<string, string>),
            onFinish: () => setProcesando(false),
        });
    }

    return (
        <>
            <Head title="Nuevo servicio contratado" />

            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <h1 className="text-xl font-semibold tracking-tight">
                    Nuevo servicio contratado
                </h1>
                <FormularioServicio
                    opciones={opciones}
                    errors={errors}
                    procesando={procesando}
                    onEnviar={enviar}
                />
            </div>
        </>
    );
}

ServicioContratadoCrear.layout = {
    breadcrumbs: [
        { title: 'Servicios contratados', href: servicios.index() },
        { title: 'Nuevo', href: '#' },
    ],
};

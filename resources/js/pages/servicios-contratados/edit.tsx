import { Head, router, usePage } from '@inertiajs/react';
import { useState } from 'react';
import { FormularioServicio } from '@/components/servicios-contratados/formulario-servicio';
import servicios from '@/routes/servicios-contratados';
import type {
    OpcionesFormularioServicio,
    ServicioContratado,
} from '@/types/servicios-contratados';

type PageProps = OpcionesFormularioServicio & { servicio: ServicioContratado };

export default function ServicioContratadoEditar() {
    const { servicio, ...opciones } = usePage<PageProps>().props;
    const [errors, setErrors] = useState<Record<string, string>>({});
    const [procesando, setProcesando] = useState(false);

    function enviar(datos: Record<string, unknown>) {
        setProcesando(true);
        setErrors({});

        router.put(servicios.update(servicio.id).url, datos as never, {
            onError: (e) => setErrors(e as Record<string, string>),
            onFinish: () => setProcesando(false),
        });
    }

    return (
        <>
            <Head title={`Editar ${servicio.nombre}`} />

            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <h1 className="text-xl font-semibold tracking-tight">
                    Editar servicio contratado
                </h1>
                <FormularioServicio
                    opciones={opciones}
                    servicio={servicio}
                    errors={errors}
                    procesando={procesando}
                    onEnviar={enviar}
                />
            </div>
        </>
    );
}

ServicioContratadoEditar.layout = {
    breadcrumbs: [
        { title: 'Servicios contratados', href: servicios.index() },
        { title: 'Editar', href: '#' },
    ],
};

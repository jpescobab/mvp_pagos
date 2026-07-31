import { Head, usePage } from '@inertiajs/react';
import { SolicitudCompraFormulario } from '@/components/adquisiciones/solicitud-compra-formulario';
import procesos from '@/routes/adquisiciones/procesos';
import type {
    CcostoSeleccionable,
    FuncionarioSeleccionable,
    ProveedorSeleccionable,
} from '@/types/adquisiciones';

type PageProps = {
    ccostos: CcostoSeleccionable[];
    funcionarios: FuncionarioSeleccionable[];
    proveedores: ProveedorSeleccionable[];
};

export default function ProcesosCrear() {
    const { ccostos, funcionarios, proveedores } = usePage<PageProps>().props;

    return (
        <>
            <Head title="Nueva solicitud de compra" />
            <SolicitudCompraFormulario
                modo="crear"
                ccostos={ccostos}
                funcionarios={funcionarios}
                proveedores={proveedores}
                accionUrl={procesos.store().url}
                metodoHttp="post"
                volverUrl={procesos.index().url}
            />
        </>
    );
}

ProcesosCrear.layout = {
    breadcrumbs: [
        { title: 'Procesos de adquisición', href: procesos.index() },
        { title: 'Nueva', href: procesos.create() },
    ],
};

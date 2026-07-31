import { Head, usePage } from '@inertiajs/react';
import { SolicitudCompraFormulario } from '@/components/adquisiciones/solicitud-compra-formulario';
import type { SolicitudCompraValoresIniciales } from '@/components/adquisiciones/solicitud-compra-formulario';
import procesos from '@/routes/adquisiciones/procesos';
import type {
    CcostoSeleccionable,
    FuncionarioSeleccionable,
    ProveedorSeleccionable,
} from '@/types/adquisiciones';

type ProcesoEditable = SolicitudCompraValoresIniciales & {
    id: number;
    codigo: string;
};

type PageProps = {
    proceso: ProcesoEditable;
    ccostos: CcostoSeleccionable[];
    funcionarios: FuncionarioSeleccionable[];
    proveedores: ProveedorSeleccionable[];
};

export default function ProcesosEditar() {
    const { proceso, ccostos, funcionarios, proveedores } =
        usePage<PageProps>().props;

    return (
        <>
            <Head title={`Editar solicitud ${proceso.codigo}`} />
            <SolicitudCompraFormulario
                modo="editar"
                ccostos={ccostos}
                funcionarios={funcionarios}
                proveedores={proveedores}
                accionUrl={procesos.update(proceso.id).url}
                metodoHttp="put"
                volverUrl={procesos.show(proceso.id).url}
                valoresIniciales={proceso}
            />
        </>
    );
}

ProcesosEditar.layout = {
    breadcrumbs: [
        { title: 'Procesos de adquisición', href: procesos.index() },
        { title: 'Editar', href: '#' },
    ],
};

import { Head, usePage } from '@inertiajs/react';
import { ProveedorFormulario } from '@/components/maestros/proveedor-formulario';
import proveedores from '@/routes/maestros/proveedores';
import type { CatalogosProveedor, Proveedor } from '@/types/maestros';

type PageProps = {
    proveedor: Proveedor;
    catalogos: CatalogosProveedor;
    tieneDocumentoRespaldo: boolean;
};

export default function ProveedoresEditar() {
    const { proveedor, catalogos, tieneDocumentoRespaldo } =
        usePage<PageProps>().props;

    return (
        <>
            <Head title={`Editar ${proveedor.nombre}`} />
            <ProveedorFormulario
                modo="editar"
                catalogos={catalogos}
                accionUrl={proveedores.update(proveedor.id).url}
                metodoHttp="patch"
                volverUrl={proveedores.show(proveedor.id).url}
                valoresIniciales={proveedor}
                tieneDocumentoRespaldo={tieneDocumentoRespaldo}
            />
        </>
    );
}

ProveedoresEditar.layout = {
    breadcrumbs: [
        { title: 'Proveedores', href: proveedores.index() },
        { title: 'Editar', href: '#' },
    ],
};

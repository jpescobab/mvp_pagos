import { Head } from '@inertiajs/react';
import { ProveedorFormulario } from '@/components/maestros/proveedor-formulario';
import proveedores from '@/routes/maestros/proveedores';
import type { CatalogosProveedor } from '@/types/maestros';

type PageProps = {
    catalogos: CatalogosProveedor;
};

export default function ProveedoresCrear({ catalogos }: PageProps) {
    return (
        <>
            <Head title="Nuevo proveedor" />
            <ProveedorFormulario
                modo="crear"
                catalogos={catalogos}
                accionUrl={proveedores.store().url}
                metodoHttp="post"
                volverUrl={proveedores.index().url}
            />
        </>
    );
}

ProveedoresCrear.layout = {
    breadcrumbs: [
        { title: 'Proveedores', href: proveedores.index() },
        { title: 'Nuevo', href: proveedores.create() },
    ],
};

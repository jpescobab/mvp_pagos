import { Head } from '@inertiajs/react';
import { ProveedorFormulario } from '@/components/maestros/proveedor-formulario';
import proveedores from '@/routes/maestros/proveedores';
import type { CatalogosProveedor, Proveedor } from '@/types/maestros';

type PageProps = {
    catalogos: CatalogosProveedor;
    valoresIniciales?: Partial<Proveedor>;
};

export default function ProveedoresCrear({
    catalogos,
    valoresIniciales,
}: PageProps) {
    return (
        <>
            <Head title="Nuevo proveedor" />
            <ProveedorFormulario
                modo="crear"
                catalogos={catalogos}
                accionUrl={proveedores.store().url}
                metodoHttp="post"
                volverUrl={proveedores.index().url}
                valoresIniciales={valoresIniciales}
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
